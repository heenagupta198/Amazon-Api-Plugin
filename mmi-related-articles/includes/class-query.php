<?php
/**
 * Lightweight post search with date-priority ordering.
 *
 * @package MMI_Related_Articles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Search helper — avoids heavy queries (no_found_rows, small limits).
 */
class MMI_RA_Query {

	const MAX_RESULTS = 20;

	/**
	 * @var string|null
	 */
	private static $title_filter_term = null;

	/**
	 * Search published posts; recent window first, then older matches.
	 * Title-only match (avoids Oppo search matching Vivo in post body).
	 *
	 * @param string $search     Search term.
	 * @param int    $exclude_id Post ID to exclude (current post).
	 * @param string $priority   7, 15, or all.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( $search, $exclude_id, $priority = '7' ) {
		$search = trim( (string) $search );
		if ( '' === $search ) {
			return array();
		}

		$exclude_id = absint( $exclude_id );
		$limit      = self::MAX_RESULTS;

		if ( 'all' === $priority ) {
			return self::map_posts(
				self::run_query(
					array(
						'mmi_ra_title_search' => $search,
						'posts_per_page'      => $limit,
						'post__not_in'        => $exclude_id ? array( $exclude_id ) : array(),
					)
				)
			);
		}

		$days  = ( '15' === $priority ) ? 15 : 7;
		$after = $days . ' days ago';

		$recent = self::run_query(
			array(
				'mmi_ra_title_search' => $search,
				'posts_per_page'      => $limit,
				'post__not_in'        => $exclude_id ? array( $exclude_id ) : array(),
				'date_query'          => array(
					array(
						'after' => $after,
					),
				),
			)
		);

		$results = self::tag_bucket( self::map_posts( $recent ), 'recent' );
		if ( count( $results ) >= $limit ) {
			return array_slice( $results, 0, $limit );
		}

		$found_ids = array_column( $results, 'id' );
		if ( $exclude_id ) {
			$found_ids[] = $exclude_id;
		}

		$remaining = $limit - count( $results );
		$older     = self::run_query(
			array(
				'mmi_ra_title_search' => $search,
				'posts_per_page'      => $remaining,
				'post__not_in'        => array_map( 'absint', $found_ids ),
				'date_query'          => array(
					array(
						'before' => $after,
					),
				),
			)
		);

		return array_merge( $results, self::tag_bucket( self::map_posts( $older ), 'older' ) );
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @param string                         $bucket recent|older.
	 * @return array<int, array<string, mixed>>
	 */
	private static function tag_bucket( array $rows, $bucket ) {
		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['bucket'] = $bucket;
		}
		return $rows;
	}

	/**
	 * Suggest related posts from title keywords (admin suggestions only).
	 *
	 * @param int    $post_id  Source post.
	 * @param string $priority Date priority.
	 * @return array<int, array<string, mixed>>
	 */
	public static function auto_suggest( $post_id, $priority = '7' ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );
		if ( ! $post || 'post' !== $post->post_type ) {
			return array();
		}

		$terms  = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
		$search = self::extract_search_phrase( $post->post_title, $terms );

		if ( '' === $search ) {
			return array();
		}

		return self::search( $search, $post_id, $priority );
	}

	/**
	 * Fetch posts by saved IDs preserving order.
	 *
	 * @param int[] $ids Post IDs.
	 * @return WP_Post[]
	 */
	public static function get_posts_by_ids( array $ids ) {
		$ids = array_values(
			array_filter(
				array_map( 'absint', $ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		);

		if ( empty( $ids ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'post__in'               => $ids,
				'posts_per_page'         => count( $ids ),
				'orderby'                => 'post__in',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
				'ignore_sticky_posts'    => true,
			)
		);

		return $query->posts;
	}

	/**
	 * @param array<string, mixed> $extra Extra WP_Query args.
	 * @return WP_Post[]
	 */
	private static function run_query( array $extra ) {
		$title_search = '';
		if ( isset( $extra['mmi_ra_title_search'] ) ) {
			$title_search = trim( (string) $extra['mmi_ra_title_search'] );
			unset( $extra['mmi_ra_title_search'] );
		}

		$args = array_merge(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'ignore_sticky_posts'    => true,
			),
			$extra
		);

		if ( '' !== $title_search ) {
			self::$title_filter_term = $title_search;
			add_filter( 'posts_where', array( __CLASS__, 'filter_where_title_only' ), 10, 2 );
		}

		$query = new WP_Query( $args );

		if ( '' !== $title_search ) {
			remove_filter( 'posts_where', array( __CLASS__, 'filter_where_title_only' ), 10 );
			self::$title_filter_term = null;
		}

		return $query->posts;
	}

	/**
	 * Restrict search to post_title; every word must appear in the title.
	 *
	 * @param string    $where WHERE clause.
	 * @param WP_Query  $query Query.
	 * @return string
	 */
	public static function filter_where_title_only( $where, $query ) {
		if ( null === self::$title_filter_term || ! $query instanceof WP_Query ) {
			return $where;
		}

		global $wpdb;

		$words = preg_split( '/\s+/u', self::$title_filter_term, -1, PREG_SPLIT_NO_EMPTY );
		if ( empty( $words ) ) {
			return $where;
		}

		foreach ( $words as $word ) {
			if ( mb_strlen( $word ) < 2 ) {
				continue;
			}
			$like   = '%' . $wpdb->esc_like( $word ) . '%';
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $like );
		}

		return $where;
	}

	/**
	 * @param WP_Post[] $posts Posts.
	 * @return array<int, array<string, mixed>>
	 */
	private static function map_posts( array $posts ) {
		$out = array();
		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$out[] = array(
				'id'       => (int) $post->ID,
				'title'    => html_entity_decode( get_the_title( $post ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'date'     => get_the_date( '', $post ),
				'thumb'    => self::get_thumb_url( $post->ID ),
				'category' => self::get_primary_category_name( $post->ID ),
			);
		}
		return $out;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_primary_category_name( $post_id ) {
		$cats = get_the_category( $post_id );
		if ( empty( $cats ) || ! is_array( $cats ) ) {
			return '';
		}
		return (string) $cats[0]->name;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_thumb_url( $post_id ) {
		$url = get_the_post_thumbnail_url( $post_id, 'medium' );
		if ( ! $url ) {
			$url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
		}
		if ( $url ) {
			return $url;
		}
		return includes_url( 'images/media/default.png' );
	}

	/**
	 * Pick a short brand-like phrase from title (e.g. "OnePlus" from OnePlus 16...).
	 *
	 * @param string        $title Post title.
	 * @param array<string> $categories Category names.
	 * @return string
	 */
	private static function extract_search_phrase( $title, array $categories ) {
		$title = trim( wp_strip_all_tags( $title ) );
		if ( preg_match( '/^([A-Za-z][A-Za-z0-9+\-\.]{1,24})/u', $title, $m ) ) {
			return $m[1];
		}
		if ( ! empty( $categories[0] ) ) {
			return $categories[0];
		}
		$words = preg_split( '/\s+/', $title );
		return isset( $words[0] ) ? $words[0] : '';
	}
}
