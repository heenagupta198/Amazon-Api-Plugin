<?php
/**
 * Multisite blog resolution (main news site + /devices/ store).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Sites {

	const OPTION_DEVICES_BLOG_ID = 'mmi_csl_devices_blog_id';
	const OPTION_NEWS_BLOG_ID    = 'mmi_csl_news_blog_id';

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
	 * @return int[] Sanitized product IDs from post meta.
	 */
	public static function get_linked_product_ids( int $post_id ): array {
		$raw = get_post_meta( $post_id, MMI_CSL_META_KEY, true );
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return array_values( array_unique( array_filter( array_map( 'intval', $decoded ) ) ) );
			}
		}

		if ( is_array( $raw ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $raw ) ) ) );
		}

		return array();
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
	 * Fetch published news posts linked to a devices subsite product.
	 *
	 * @return WP_Post[]
	 */
	public static function get_news_for_product( int $product_id, int $limit = 12 ): array {
		$news_blog  = self::get_news_blog_id();
		$product_id = max( 0, $product_id );
		$limit      = max( 1, min( 30, $limit ) );

		if ( $product_id <= 0 ) {
			return array();
		}

		$from_index = MMI_CSL_Sync::get_news_ids_for_product( $product_id );
		$from_meta  = self::find_news_ids_by_article_meta( $product_id, $news_blog, $limit );
		$news_post_ids = array_values( array_unique( array_merge( $from_index, $from_meta ) ) );

		if ( empty( $news_post_ids ) ) {
			return array();
		}

		$news_post_ids = array_slice( $news_post_ids, 0, $limit );

		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $news_blog ) {
			switch_to_blog( $news_blog );
			$switched = true;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'post__in'               => $news_post_ids,
				'orderby'                => 'post__in',
				'posts_per_page'         => count( $news_post_ids ),
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$posts = $query->posts;
		wp_reset_postdata();

		if ( $switched ) {
			restore_current_blog();
		}

		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Fallback when reverse index is missing (legacy links).
	 *
	 * @return int[]
	 */
	private static function find_news_ids_by_article_meta( int $product_id, int $news_blog, int $limit ): array {
		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $news_blog ) {
			switch_to_blog( $news_blog );
			$switched = true;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => MMI_CSL_META_KEY,
						'value'   => '"' . $product_id . '"',
						'compare' => 'LIKE',
					),
				),
			)
		);

		$ids = is_array( $query->posts ) ? array_map( 'intval', $query->posts ) : array();
		wp_reset_postdata();

		if ( $switched ) {
			restore_current_blog();
		}

		return $ids;
	}

	/**
	 * Whether the current request is on the devices subsite (URL or blog ID).
	 */
	public static function is_devices_site(): bool {
		if ( (int) get_current_blog_id() === self::get_devices_blog_id() ) {
			return true;
		}

		$home = (string) home_url( '/' );
		if ( false !== strpos( $home, '/devices' ) ) {
			return true;
		}

		$siteurl = (string) get_option( 'siteurl', '' );
		return false !== strpos( $siteurl, '/devices' );
	}
}
