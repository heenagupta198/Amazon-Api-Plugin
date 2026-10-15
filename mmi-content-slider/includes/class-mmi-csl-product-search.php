<?php
/**
 * Product search on devices subsite (title, slug, brand taxonomy).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Product_Search {

	const CACHE_TTL    = 300;
	const MAX_TERM_LEN = 80;
	const MAX_RESULTS  = 30;
	const RATE_LIMIT   = 60;
	const RATE_WINDOW  = 60;

	/**
	 * @return string[]
	 */
	public static function get_post_types(): array {
		$types = array( 'product', 'devices' );

		return array_values( array_unique( apply_filters( 'mmi_csl_product_post_types', $types ) ) );
	}

	/**
	 * @return string[]
	 */
	public static function get_brand_taxonomies(): array {
		$taxonomies = array( 'product_brand', 'pwb-brand', 'yith_product_brand' );

		return array_values( array_unique( apply_filters( 'mmi_csl_brand_taxonomies', $taxonomies ) ) );
	}

	/**
	 * @return array<int,array{id:int,label:string}>
	 */
	public static function search( string $term, int $limit = self::MAX_RESULTS ): array {
		$term  = trim( wp_strip_all_tags( $term ) );
		$term  = mb_substr( $term, 0, self::MAX_TERM_LEN );
		$limit = max( 1, min( self::MAX_RESULTS, $limit ) );

		if ( '' === $term ) {
			return array();
		}

		$cache_key = 'mmi_csl_q_' . get_current_blog_id() . '_v4_' . md5( strtolower( $term ) . '|' . $limit );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$types = self::get_post_types();
		if ( empty( $types ) ) {
			return array();
		}

		if ( preg_match( '/^\d+$/', $term ) ) {
			$items = self::query_by_id( (int) $term, $types );
			set_transient( $cache_key, $items, self::CACHE_TTL );
			return $items;
		}

		$scored = array();
		$tokens = self::tokenize( $term );

		// Brand-first: "OPPO" → only Oppo brand products from product_brand.
		$brand_items = self::query_products_for_brand_term( $term, $tokens, $types, $limit );
		if ( ! empty( $brand_items ) ) {
			self::merge_scored( $scored, $brand_items, 200 );
		}

		self::merge_scored( $scored, self::query_phrase_title( $term, $types, $limit * 2 ), 120 );
		self::merge_scored( $scored, self::query_title_tokens( $term, $types, $limit * 2, true ), 80 );
		self::merge_scored( $scored, self::query_slug( $term, $types, $limit * 2 ), 50 );

		if ( empty( $scored ) ) {
			self::merge_scored( $scored, self::query_title_tokens( $term, $types, $limit * 2, false ), 30 );
			self::merge_scored( $scored, self::query_loose_substring( $term, $types, $limit * 2 ), 20 );
		}

		$items = self::sort_and_format( $scored, $limit, $tokens );

		set_transient( $cache_key, $items, self::CACHE_TTL );

		return $items;
	}

	/**
	 * Top brands for quick-filter buttons in admin.
	 *
	 * @return array<int,array{slug:string,name:string,count:int}>
	 */
	public static function get_popular_brands( int $limit = 12 ): array {
		$limit = max( 1, min( 20, $limit ) );
		$cache = get_transient( 'mmi_csl_brands_' . get_current_blog_id() );
		if ( is_array( $cache ) ) {
			return array_slice( $cache, 0, $limit );
		}

		$brands = array();
		foreach ( self::get_brand_taxonomies() as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => true,
					'number'     => 30,
					'orderby'    => 'count',
					'order'      => 'DESC',
				)
			);

			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$brands[] = array(
					'slug'  => (string) $term->slug,
					'name'  => (string) $term->name,
					'count' => (int) $term->count,
				);
			}
			break;
		}

		set_transient( 'mmi_csl_brands_' . get_current_blog_id(), $brands, HOUR_IN_SECONDS );

		return array_slice( $brands, 0, $limit );
	}

	/**
	 * @param int[] $ids Product IDs.
	 * @return array<int,array{id:int,label:string}>
	 */
	public static function resolve_ids( array $ids ): array {
		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$items = array();
		foreach ( $ids as $id ) {
			$found = self::query_by_id( $id, self::get_post_types() );
			if ( ! empty( $found ) ) {
				$items[] = $found[0];
			}
		}

		return $items;
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,title:string,score:int}>
	 */
	private static function query_products_for_brand_term( string $term, array $tokens, array $types, int $limit ): array {
		$brand_term = self::resolve_brand_term( $term, $tokens );
		if ( ! $brand_term ) {
			return array();
		}

		$model_tokens = array_values(
			array_filter(
				$tokens,
				static function ( string $token ) use ( $brand_term ): bool {
					$slug = strtolower( $brand_term->slug );
					$name = strtolower( $brand_term->name );
					return $token !== $slug && $token !== $name && false === strpos( $name, $token );
				}
			)
		);

		return self::query_in_brand_term( (int) $brand_term->term_id, $brand_term->taxonomy, $types, $limit, $model_tokens );
	}

	/**
	 * @param string[] $tokens Search tokens.
	 */
	private static function resolve_brand_term( string $term, array $tokens ): ?WP_Term {
		foreach ( self::get_brand_taxonomies() as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}

			$first = ! empty( $tokens[0] ) ? $tokens[0] : strtolower( $term );
			$candidates = array_unique( array_filter( array( $first, strtolower( trim( $term ) ) ) ) );

			foreach ( $candidates as $candidate ) {
				$by_slug = get_term_by( 'slug', sanitize_title( $candidate ), $tax );
				if ( $by_slug instanceof WP_Term ) {
					return $by_slug;
				}

				$search = get_terms(
					array(
						'taxonomy'   => $tax,
						'hide_empty' => false,
						'name__like' => $candidate,
						'number'     => 5,
					)
				);

				if ( is_array( $search ) ) {
					foreach ( $search as $t ) {
						if ( strtolower( $t->name ) === strtolower( $candidate ) || strtolower( $t->slug ) === sanitize_title( $candidate ) ) {
							return $t;
						}
					}
					if ( count( $tokens ) === 1 && ! empty( $search[0] ) ) {
						return $search[0];
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param string[] $types Post types.
	 * @param string[] $model_tokens Extra title tokens within brand.
	 * @return array<int,array{id:int,title:string,score:int}>
	 */
	private static function query_in_brand_term( int $term_id, string $taxonomy, array $types, int $limit, array $model_tokens = array() ): array {
		global $wpdb;

		$pt_in = self::sql_in_placeholders( $types );
		$params = array_merge( array( $term_id, $taxonomy ), $types );

		$sql = "SELECT DISTINCT p.ID, p.post_title
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE tt.term_id = %d AND tt.taxonomy = %s
			AND p.post_status = 'publish' AND p.post_type IN ($pt_in)";

		foreach ( $model_tokens as $token ) {
			$sql     .= ' AND LOWER(p.post_title) LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $token ) . '%';
		}

		$sql     .= ' ORDER BY p.post_title ASC LIMIT %d';
		$params[] = $limit;

		return self::rows_to_scored( $wpdb->get_results( $wpdb->prepare( $sql, $params ) ), 90, $model_tokens );
	}

	/**
	 * @param array<int,array{id:int,title:string,score:int}> $into Target map keyed by ID.
	 * @param array<int,array{id:int,title:string,score:int}> $rows Rows to merge.
	 */
	private static function merge_scored( array &$into, array $rows, int $boost ): void {
		foreach ( $rows as $row ) {
			$id = (int) $row['id'];
			if ( $id <= 0 ) {
				continue;
			}
			$score = (int) $row['score'] + $boost;
			if ( ! isset( $into[ $id ] ) || $into[ $id ]['score'] < $score ) {
				$into[ $id ] = array(
					'id'    => $id,
					'title' => (string) $row['title'],
					'score' => $score,
				);
			}
		}
	}

	/**
	 * @param array<int,array{id:int,title:string,score:int}> $scored Scored rows.
	 * @param string[]                                        $tokens Optional filter tokens.
	 * @return array<int,array{id:int,label:string}>
	 */
	private static function sort_and_format( array $scored, int $limit, array $tokens = array() ): array {
		if ( empty( $scored ) ) {
			return array();
		}

		usort(
			$scored,
			static function ( array $a, array $b ): int {
				if ( $a['score'] === $b['score'] ) {
					return strcasecmp( $a['title'], $b['title'] );
				}
				return $b['score'] <=> $a['score'];
			}
		);

		$items = array();
		foreach ( array_slice( $scored, 0, $limit ) as $row ) {
			if ( ! empty( $tokens ) ) {
				$lower = strtolower( $row['title'] );
				$match = false;
				foreach ( $tokens as $token ) {
					if ( false !== strpos( $lower, $token ) ) {
						$match = true;
						break;
					}
				}
				// Single-word brand search: drop non-matching titles unless brand query already scoped.
			}
			$items[] = self::format_item( (int) $row['id'], (string) $row['title'] );
		}

		return $items;
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,label:string}>
	 */
	private static function query_by_id( int $id, array $types ): array {
		global $wpdb;

		if ( $id <= 0 ) {
			return array();
		}

		$in     = self::sql_in_placeholders( $types );
		$sql    = "SELECT ID, post_title FROM {$wpdb->posts}
			WHERE ID = %d AND post_status = 'publish' AND post_type IN ($in) LIMIT 1";
		$params = array_merge( array( $id ), $types );
		$row    = $wpdb->get_row( $wpdb->prepare( $sql, $params ) );

		if ( ! $row ) {
			return array();
		}

		return array( self::format_item( (int) $row->ID, (string) $row->post_title ) );
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,title:string,score:int}>
	 */
	private static function query_phrase_title( string $term, array $types, int $limit ): array {
		global $wpdb;

		$in      = self::sql_in_placeholders( $types );
		$phrase  = '%' . $wpdb->esc_like( strtolower( $term ) ) . '%';
		$params  = array_merge( $types, array( $phrase, $limit ) );
		$sql     = "SELECT ID, post_title FROM {$wpdb->posts}
			WHERE post_status = 'publish' AND post_type IN ($in)
			AND LOWER(post_title) LIKE %s
			ORDER BY post_title ASC LIMIT %d";

		return self::rows_to_scored( $wpdb->get_results( $wpdb->prepare( $sql, $params ) ), 100 );
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,title:string,score:int}>
	 */
	private static function query_loose_substring( string $term, array $types, int $limit ): array {
		global $wpdb;

		$compact = preg_replace( '/\s+/', '', strtolower( $term ) );
		if ( ! is_string( $compact ) || strlen( $compact ) < 3 ) {
			return array();
		}

		$in     = self::sql_in_placeholders( $types );
		$like   = '%' . $wpdb->esc_like( $compact ) . '%';
		$params = array_merge( $types, array( $like, $limit ) );
		$sql    = "SELECT ID, post_title FROM {$wpdb->posts}
			WHERE post_status = 'publish' AND post_type IN ($in)
			AND REPLACE(LOWER(post_title), ' ', '') LIKE %s
			ORDER BY post_title ASC LIMIT %d";

		return self::rows_to_scored( $wpdb->get_results( $wpdb->prepare( $sql, $params ) ), 15 );
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,title:string,score:int}>
	 */
	private static function query_slug( string $term, array $types, int $limit ): array {
		global $wpdb;

		$slug   = sanitize_title( $term );
		$in     = self::sql_in_placeholders( $types );
		$like   = '%' . $wpdb->esc_like( $slug ) . '%';
		$params = array_merge( $types, array( $like, $limit ) );
		$sql    = "SELECT ID, post_title FROM {$wpdb->posts}
			WHERE post_status = 'publish' AND post_type IN ($in)
			AND post_name LIKE %s
			ORDER BY post_title ASC LIMIT %d";

		return self::rows_to_scored( $wpdb->get_results( $wpdb->prepare( $sql, $params ) ), 40 );
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,title:string,score:int}>
	 */
	private static function query_title_tokens( string $term, array $types, int $limit, bool $require_all ): array {
		global $wpdb;

		$tokens = self::tokenize( $term );
		if ( empty( $tokens ) ) {
			return array();
		}

		$in = self::sql_in_placeholders( $types );

		if ( $require_all ) {
			$where  = array( "post_status = 'publish'", "post_type IN ($in)" );
			$params = $types;

			foreach ( $tokens as $token ) {
				$where[]  = 'LOWER(post_title) LIKE %s';
				$params[] = '%' . $wpdb->esc_like( $token ) . '%';
			}

			$params[] = $limit;
			$sql      = 'SELECT ID, post_title FROM ' . $wpdb->posts . ' WHERE ' . implode( ' AND ', $where ) .
				' ORDER BY post_title ASC LIMIT %d';

			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		} else {
			$like_parts = array();
			$params     = $types;
			foreach ( $tokens as $token ) {
				$like_parts[] = 'LOWER(post_title) LIKE %s';
				$params[]     = '%' . $wpdb->esc_like( $token ) . '%';
			}
			$params[] = $limit;
			$sql      = "SELECT ID, post_title FROM {$wpdb->posts}
				WHERE post_status = 'publish' AND post_type IN ($in)
				AND (" . implode( ' OR ', $like_parts ) . ")
				ORDER BY post_title ASC LIMIT %d";
			$rows     = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}

		return self::rows_to_scored( $rows, $require_all ? 60 : 25, $tokens );
	}

	/**
	 * @param object[]|null $rows   DB rows.
	 * @param string[]      $tokens Optional tokens for extra scoring.
	 * @return array<int,array{id:int,title:string,score:int}>
	 */
	private static function rows_to_scored( $rows, int $base_score, array $tokens = array() ): array {
		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$id    = (int) $row->ID;
			$title = (string) $row->post_title;
			$score = $base_score;

			if ( ! empty( $tokens ) ) {
				$lower = strtolower( $title );
				foreach ( $tokens as $token ) {
					if ( false !== strpos( $lower, $token ) ) {
						$score += 8;
					}
				}
			}

			$out[] = array(
				'id'    => $id,
				'title' => $title,
				'score' => $score,
			);
		}

		return $out;
	}

	/**
	 * @return string[]
	 */
	private static function tokenize( string $term ): array {
		$term = strtolower( $term );
		$term = preg_replace( '/[^\p{L}\p{N}\s\-\+]/u', ' ', $term );
		if ( ! is_string( $term ) ) {
			return array();
		}

		$parts = preg_split( '/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array();
		}

		$tokens = array();
		foreach ( $parts as $part ) {
			$part = trim( $part, '-' );
			if ( '' !== $part ) {
				$tokens[] = $part;
			}
		}

		return array_values( array_unique( $tokens ) );
	}

	private static function sql_in_placeholders( array $values ): string {
		return implode( ', ', array_fill( 0, count( $values ), '%s' ) );
	}

	/**
	 * @return array{id:int,label:string}
	 */
	private static function format_item( int $id, string $title ): array {
		$brand = self::get_product_brand_label( $id );

		return array(
			'id'    => $id,
			'label' => sprintf( '#%d — %s%s', $id, $title, '' !== $brand ? ' [' . $brand . ']' : '' ),
		);
	}

	private static function get_product_brand_label( int $product_id ): string {
		foreach ( self::get_brand_taxonomies() as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$terms = get_the_terms( $product_id, $tax );
			if ( is_array( $terms ) && ! empty( $terms[0]->name ) ) {
				return (string) $terms[0]->name;
			}
		}

		return '';
	}

	public static function check_rate_limit(): bool {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}

		$key   = 'mmi_csl_rl_' . $user_id;
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
