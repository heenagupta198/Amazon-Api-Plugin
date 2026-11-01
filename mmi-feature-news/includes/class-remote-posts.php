<?php
/**
 * Fetch Feature posts from the English news site (for ReHub /devices).
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalized post row for templates.
 */
final class MMI_FN_Post_Item {

	/** @var int */
	public $id;

	/** @var string */
	public $title;

	/** @var string */
	public $link;

	/** @var string */
	public $author_name;

	/** @var int */
	public $timestamp;

	/** @var string */
	public $image_url;

	/**
	 * @param array<string, mixed> $row Row data.
	 */
	public function __construct( array $row ) {
		$this->id          = (int) ( $row['id'] ?? 0 );
		$this->title       = (string) ( $row['title'] ?? '' );
		$this->link        = (string) ( $row['link'] ?? '' );
		$this->author_name = (string) ( $row['author_name'] ?? '' );
		$this->timestamp   = (int) ( $row['timestamp'] ?? 0 );
		$this->image_url   = (string) ( $row['image_url'] ?? '' );
	}
}

/**
 * Local WP_Query + remote REST fallback.
 */
final class MMI_FN_Remote_Posts {

	/**
	 * @param string $category_slug Category slug.
	 * @param int    $limit         Number of posts.
	 * @param int    $offset        Offset.
	 * @return MMI_FN_Post_Item[]
	 */
	public static function get_posts( string $category_slug, int $limit, int $offset = 0 ): array {
		$limit  = max( 1, min( 12, $limit ) );
		$offset = max( 0, $offset );

		$local = self::query_local( $category_slug, $limit, $offset );
		if ( ! empty( $local ) ) {
			return $local;
		}

		$settings = MMI_FN_Settings::get();
		if ( empty( $settings['use_remote_news'] ) ) {
			return array();
		}

		return self::query_remote( $category_slug, $limit, $offset, $settings );
	}

	/**
	 * @return MMI_FN_Post_Item[]
	 */
	private static function query_local( string $category_slug, int $limit, int $offset ): array {
		$cat  = get_category_by_slug( sanitize_title( $category_slug ) );
		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'offset'              => $offset,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( $cat ) {
			$args['cat'] = (int) $cat->term_id;
		} else {
			$args['category_name'] = sanitize_title( $category_slug );
		}

		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return array();
		}

		$items = array();
		while ( $query->have_posts() ) {
			$query->the_post();
			$thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
			if ( ! $thumb ) {
				$thumb = get_the_post_thumbnail_url( get_the_ID(), 'full' );
			}
			$items[] = new MMI_FN_Post_Item(
				array(
					'id'          => get_the_ID(),
					'title'       => get_the_title(),
					'link'        => get_permalink(),
					'author_name' => get_the_author(),
					'timestamp'   => (int) get_the_time( 'U' ),
					'image_url'   => (string) $thumb,
				)
			);
		}
		wp_reset_postdata();

		return $items;
	}

	/**
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return MMI_FN_Post_Item[]
	 */
	private static function query_remote( string $category_slug, int $limit, int $offset, array $settings ): array {
		$base = untrailingslashit( (string) ( $settings['news_site_url'] ?? 'https://www.mymobileindia.com' ) );
		if ( '' === $base ) {
			return array();
		}

		$cat_id = (int) ( $settings['remote_category_id'] ?? 0 );
		if ( $cat_id <= 0 ) {
			$cat_id = self::resolve_remote_category_id( $base, $category_slug );
		}

		$cache_key = 'mmi_fn_' . md5( $base . '|' . $cat_id . '|' . $limit . '|' . $offset );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return self::hydrate_items( $cached );
		}

		$url = add_query_arg(
			array(
				'categories' => $cat_id > 0 ? $cat_id : '',
				'per_page'   => $limit,
				'offset'     => $offset,
				'_embed'     => '1',
			),
			$base . '/wp-json/wp/v2/posts'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( 200 !== $code || '' === $body ) {
			return array();
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$rows = array();
		foreach ( $decoded as $post ) {
			if ( ! is_array( $post ) ) {
				continue;
			}
			$rows[] = self::map_rest_post( $post );
		}

		set_transient( $cache_key, $rows, (int) ( $settings['cache_minutes'] ?? 10 ) * MINUTE_IN_SECONDS );

		return self::hydrate_items( $rows );
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Cached rows.
	 * @return MMI_FN_Post_Item[]
	 */
	private static function hydrate_items( array $rows ): array {
		$items = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$items[] = new MMI_FN_Post_Item( $row );
			}
		}
		return $items;
	}

	/**
	 * @param array<string, mixed> $post REST post object.
	 * @return array<string, mixed>
	 */
	private static function map_rest_post( array $post ): array {
		$title = isset( $post['title']['rendered'] ) ? wp_strip_all_tags( (string) $post['title']['rendered'] ) : '';
		$link  = (string) ( $post['link'] ?? '' );
		$ts    = isset( $post['date_gmt'] ) ? strtotime( (string) $post['date_gmt'] . ' UTC' ) : 0;

		$author = 'Team My Mobile';
		if ( ! empty( $post['_embedded']['author'][0]['name'] ) ) {
			$author = (string) $post['_embedded']['author'][0]['name'];
		}

		$image = '';
		if ( ! empty( $post['_embedded']['wp:featuredmedia'][0]['source_url'] ) ) {
			$image = (string) $post['_embedded']['wp:featuredmedia'][0]['source_url'];
		}

		return array(
			'id'          => (int) ( $post['id'] ?? 0 ),
			'title'       => $title,
			'link'        => $link,
			'author_name' => $author,
			'timestamp'   => (int) $ts,
			'image_url'   => $image,
		);
	}

	private static function resolve_remote_category_id( string $base, string $slug ): int {
		$cache_key = 'mmi_fn_cat_' . md5( $base . '|' . $slug );
		$cached    = get_transient( $cache_key );
		if ( is_numeric( $cached ) ) {
			return (int) $cached;
		}

		$url      = $base . '/wp-json/wp/v2/categories?slug=' . rawurlencode( sanitize_title( $slug ) );
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			return 9;
		}
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		$id      = 9;
		if ( is_array( $decoded ) && ! empty( $decoded[0]['id'] ) ) {
			$id = (int) $decoded[0]['id'];
		}
		set_transient( $cache_key, $id, DAY_IN_SECONDS );
		return $id;
	}

	/**
	 * @param MMI_FN_Post_Item[] $items Posts.
	 */
	public static function human_time_ago( MMI_FN_Post_Item $item ): string {
		if ( $item->timestamp <= 0 ) {
			return '';
		}
		$diff = human_time_diff( $item->timestamp, (int) current_time( 'timestamp' ) );
		return sprintf( __( '%s ago', 'mmi-feature-news' ), $diff );
	}

	/**
	 * Empty-state hint for Elementor / admins when fetch fails.
	 */
	public static function empty_message( string $context ): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		$s = MMI_FN_Settings::get();
		return '<p class="mmi-fn-admin-notice" style="padding:12px;border:1px dashed #c62828;background:#ffebee;color:#111;">'
			. esc_html(
				sprintf(
					/* translators: 1: shortcode context */
					__( 'MMI Feature News (%1$s): no posts loaded. On /devices/ enable remote news and set source URL to https://www.mymobileindia.com in Settings → MMI Feature News.', 'mmi-feature-news' ),
					$context
				)
			)
			. '</p>';
	}
}
