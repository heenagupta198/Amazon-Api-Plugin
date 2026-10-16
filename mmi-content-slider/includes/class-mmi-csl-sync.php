<?php
/**
 * Reverse index on device products → news post IDs (fast frontend, no cross-site LIKE).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Sync {

	const PRODUCT_NEWS_META = '_mmi_linked_news_post_ids';

	/**
	 * @param int   $news_post_id News post on main site.
	 * @param int[] $old_ids      Previously linked product IDs.
	 * @param int[] $new_ids      Newly saved product IDs.
	 */
	public static function sync_diff( int $news_post_id, array $old_ids, array $new_ids ): void {
		$news_post_id = max( 0, $news_post_id );
		if ( $news_post_id <= 0 ) {
			return;
		}

		$old_ids = array_values( array_unique( array_filter( array_map( 'intval', $old_ids ) ) ) );
		$new_ids = array_values( array_unique( array_filter( array_map( 'intval', $new_ids ) ) ) );

		$removed = array_diff( $old_ids, $new_ids );
		$added   = array_diff( $new_ids, $old_ids );

		if ( empty( $removed ) && empty( $added ) ) {
			return;
		}

		$devices_blog = MMI_CSL_Sites::get_devices_blog_id();
		$switched     = false;

		if ( is_multisite() && get_current_blog_id() !== $devices_blog ) {
			switch_to_blog( $devices_blog );
			$switched = true;
		}

		foreach ( $removed as $product_id ) {
			self::remove_news_from_product( (int) $product_id, $news_post_id );
		}

		foreach ( $added as $product_id ) {
			self::add_news_to_product( (int) $product_id, $news_post_id );
		}

		if ( $switched ) {
			restore_current_blog();
		}
	}

	/**
	 * @param int[] $product_ids All products that were linked before trash/delete.
	 */
	public static function detach_news_post( int $news_post_id, array $product_ids ): void {
		self::sync_diff( $news_post_id, $product_ids, array() );
	}

	/**
	 * @return int[] News post IDs stored on a device product.
	 */
	public static function get_news_ids_for_product( int $product_id ): array {
		$raw = get_post_meta( $product_id, self::PRODUCT_NEWS_META, true );
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

	private static function add_news_to_product( int $product_id, int $news_post_id ): void {
		if ( $product_id <= 0 || $news_post_id <= 0 ) {
			return;
		}

		$post = get_post( $product_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		$types = MMI_CSL_Product_Search::get_post_types();
		if ( ! in_array( $post->post_type, $types, true ) ) {
			return;
		}

		$ids = self::get_news_ids_for_product( $product_id );
		if ( ! in_array( $news_post_id, $ids, true ) ) {
			$ids[] = $news_post_id;
		}

		rsort( $ids, SORT_NUMERIC );

		update_post_meta( $product_id, self::PRODUCT_NEWS_META, wp_json_encode( array_values( $ids ) ) );
	}

	private static function remove_news_from_product( int $product_id, int $news_post_id ): void {
		if ( $product_id <= 0 ) {
			return;
		}

		$ids = self::get_news_ids_for_product( $product_id );
		$ids = array_values( array_diff( $ids, array( $news_post_id ) ) );

		if ( empty( $ids ) ) {
			delete_post_meta( $product_id, self::PRODUCT_NEWS_META );
			return;
		}

		update_post_meta( $product_id, self::PRODUCT_NEWS_META, wp_json_encode( $ids ) );
	}
}
