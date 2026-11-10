<?php
/**
 * Multisite blog resolution (main news site + /devices/ store).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Sites {

	const OPTION_DEVICES_BLOG_ID = 'mmi_csl_devices_blog_id';
	const OPTION_NEWS_BLOG_ID    = 'mmi_csl_news_blog_id';

	/**
	 * English news site (not the /devices/ WooCommerce install).
	 */
	public static function is_news_site(): bool {
		return ! self::is_devices_site();
	}

	/**
	 * Blog ID where news posts are published (usually the main site).
	 */
	public static function get_news_blog_id(): int {
		$stored = (int) get_network_option( null, self::OPTION_NEWS_BLOG_ID, 0 );
		if ( $stored > 0 ) {
			return $stored;
		}

		if ( is_multisite() && function_exists( 'get_main_site_id' ) ) {
			return (int) get_main_site_id();
		}

		return (int) get_current_blog_id();
	}

	/**
	 * Blog ID for the WooCommerce devices subsite (path /devices/).
	 */
	public static function get_devices_blog_id(): int {
		$stored = (int) get_network_option( null, self::OPTION_DEVICES_BLOG_ID, 0 );
		if ( $stored > 0 ) {
			return $stored;
		}

		if ( ! is_multisite() ) {
			return (int) get_current_blog_id();
		}

		$sites = get_sites(
			array(
				'number'   => 50,
				'path'     => '/devices/',
				'fields'   => 'ids',
				'archived' => 0,
				'spam'     => 0,
				'deleted'  => 0,
			)
		);

		if ( ! empty( $sites[0] ) ) {
			return (int) $sites[0];
		}

		foreach ( get_sites( array( 'number' => 100, 'fields' => 'ids' ) ) as $site_id ) {
			$path = (string) get_blog_option( (int) $site_id, 'siteurl', '' );
			if ( false !== strpos( $path, '/devices' ) ) {
				return (int) $site_id;
			}
		}

		return (int) get_current_blog_id();
	}

	/**
	 * Normalize stored linked IDs (JSON ints, JSON strings, or PHP-serialized arrays).
	 *
	 * @param mixed $raw Meta value.
	 * @return int[]
	 */
	public static function parse_id_list( $raw ): array {
		if ( is_array( $raw ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $raw ) ) ) );
		}

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return array();
		}

		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $decoded ) ) ) );
		}

		if ( function_exists( 'maybe_unserialize' ) ) {
			$unserialized = maybe_unserialize( $raw );
			if ( is_array( $unserialized ) ) {
				return array_values( array_unique( array_filter( array_map( 'intval', $unserialized ) ) ) );
			}
		}

		return array();
	}

	/**
	 * @return int[] Sanitized product IDs from post meta.
	 */
	public static function get_linked_product_ids( int $post_id ): array {
		return self::parse_id_list( get_post_meta( $post_id, MMI_CSL_META_KEY, true ) );
	}

	/**
	 * @param int[] $product_ids Device product IDs.
	 */
	public static function save_linked_product_ids( int $post_id, array $product_ids ): void {
		$product_ids = array_values( array_unique( array_filter( array_map( 'intval', $product_ids ) ) ) );
		if ( empty( $product_ids ) ) {
			delete_post_meta( $post_id, MMI_CSL_META_KEY );
			return;
		}

		update_post_meta( $post_id, MMI_CSL_META_KEY, wp_json_encode( $product_ids ) );
	}

	/**
	 * News articles linked to a devices product ID (local DB on news site).
	 *
	 * @return int[]
	 */
	public static function find_news_post_ids_for_product( int $product_id, int $limit = 12 ): array {
		$product_id = max( 0, $product_id );
		$limit      = max( 1, min( 30, $limit ) );

		if ( $product_id <= 0 ) {
			return array();
		}

		if ( self::is_devices_site() ) {
			return array();
		}

		$from_index = array();
		if ( is_multisite() ) {
			$devices_blog = self::get_devices_blog_id();
			$switched     = false;
			if ( get_current_blog_id() !== $devices_blog ) {
				switch_to_blog( $devices_blog );
				$switched = true;
			}
			$from_index = MMI_CSL_Sync::get_news_ids_for_product( $product_id );
			if ( $switched ) {
				restore_current_blog();
			}
		}

		$from_meta = self::query_news_ids_by_meta( $product_id, $limit );
		return array_values( array_unique( array_merge( $from_index, $from_meta ) ) );
	}

	/**
	 * Match JSON `[22404,22257]` (integers) as well as `["22404"]` (quoted strings).
	 * The previous LIKE `"22404"` never matched integer JSON, so REST always returned [].
	 *
	 * @return int[]
	 */
	private static function query_news_ids_by_meta( int $product_id, int $limit ): array {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( (string) $product_id ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
				MMI_CSL_META_KEY,
				$like
			)
		);

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return array();
		}

		$matched = array();
		foreach ( $rows as $row ) {
			$ids = self::parse_id_list( isset( $row->meta_value ) ? $row->meta_value : '' );
			if ( in_array( $product_id, $ids, true ) ) {
				$matched[] = (int) $row->post_id;
			}
		}

		$matched = array_values( array_unique( array_filter( $matched ) ) );
		if ( empty( $matched ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'post__in'               => $matched,
				'posts_per_page'         => $limit,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$ids = is_array( $query->posts ) ? array_map( 'intval', $query->posts ) : array();
		wp_reset_postdata();

		return $ids;
	}

	/**
	 * Whether the current request is on the devices subsite (URL or blog ID).
	 */
	public static function is_devices_site(): bool {
		$siteurl = (string) get_option( 'siteurl', '' );
		if ( false !== strpos( $siteurl, '/devices' ) ) {
			return true;
		}

		$home = (string) home_url( '/' );
		if ( false !== strpos( $home, '/devices' ) ) {
			return true;
		}

		return is_multisite() && (int) get_current_blog_id() === self::get_devices_blog_id();
	}
}
