<?php
/**
 * Multisite blog resolution (main news site + /devices/ store).
 */

defined( 'ABSPATH' ) || exit;

class MMI_LDA_Sites {

	const OPTION_DEVICES_BLOG_ID = 'mmi_lda_devices_blog_id';
	const OPTION_NEWS_BLOG_ID    = 'mmi_lda_news_blog_id';

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
		if ( $stored <= 0 ) {
			$stored = (int) get_network_option( null, 'mmi_csl_news_blog_id', 0 );
		}
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
		if ( $stored <= 0 ) {
			$stored = (int) get_network_option( null, 'mmi_csl_devices_blog_id', 0 );
		}
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

		$raw = trim( stripslashes( $raw ) );

		$decoded = json_decode( $raw, true );
		if ( is_string( $decoded ) ) {
			$decoded = json_decode( $decoded, true );
		}
		if ( is_array( $decoded ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $decoded ) ) ) );
		}

		if ( function_exists( 'maybe_unserialize' ) ) {
			$unserialized = maybe_unserialize( $raw );
			if ( is_array( $unserialized ) ) {
				return array_values( array_unique( array_filter( array_map( 'intval', $unserialized ) ) ) );
			}
		}

		if ( preg_match( '/^\d+(?:\s*,\s*\d+)*$/', $raw ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', preg_split( '/\s*,\s*/', $raw ) ) ) ) );
		}

		return array();
	}

	/**
	 * @return string[]
	 */
	public static function meta_keys(): array {
		return array_values(
			array_unique(
				array(
					MMI_LDA_META_KEY,
					MMI_LDA_LEGACY_META_KEY,
				)
			)
		);
	}

	/**
	 * @return int[] Sanitized product IDs from post meta.
	 */
	public static function get_linked_product_ids( int $post_id ): array {
		$ids = array();
		foreach ( self::meta_keys() as $key ) {
			$ids = array_merge( $ids, self::parse_id_list( get_post_meta( $post_id, $key, true ) ) );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * @param int[] $product_ids Device product IDs.
	 */
	public static function save_linked_product_ids( int $post_id, array $product_ids ): void {
		$product_ids = array_values( array_unique( array_filter( array_map( 'intval', $product_ids ) ) ) );
		$encoded     = wp_json_encode( $product_ids );

		if ( empty( $product_ids ) ) {
			foreach ( self::meta_keys() as $key ) {
				delete_post_meta( $post_id, $key );
			}
			return;
		}

		foreach ( self::meta_keys() as $key ) {
			update_post_meta( $post_id, $key, $encoded );
		}
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
			$from_index = MMI_LDA_Sync::get_news_ids_for_product( $product_id );
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
		$keys = self::meta_keys();
		$in   = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
		$sql  = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ($in) AND meta_value LIKE %s";
		$args = array_merge( $keys, array( $like ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );

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
				'post_type'              => array( 'post' ),
				'post_status'            => 'publish',
				'post__in'               => $matched,
				'posts_per_page'         => $limit,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'suppress_filters'       => true,
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
