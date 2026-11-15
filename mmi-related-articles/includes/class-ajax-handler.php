<?php
/**
 * Admin AJAX endpoints.
 *
 * @package MMI_Related_Articles
 */

defined( 'ABSPATH' ) || exit;

/**
 * AJAX handler.
 */
class MMI_RA_Ajax_Handler {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_mmi_ra_search_posts', array( __CLASS__, 'search_posts' ) );
		add_action( 'wp_ajax_mmi_ra_auto_suggest', array( __CLASS__, 'auto_suggest' ) );
	}

	/**
	 * Verify capability and nonce.
	 *
	 * @return void
	 */
	private static function verify_request() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mmi-related-articles' ) ), 403 );
		}

		check_ajax_referer( 'mmi_ra_admin', 'nonce' );
	}

	/**
	 * Search posts.
	 */
	public static function search_posts() {
		self::verify_request();

		$search     = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$priority   = isset( $_POST['priority'] ) ? sanitize_key( wp_unslash( $_POST['priority'] ) ) : '7';
		$exclude_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! in_array( $priority, array( '7', '15', 'all' ), true ) ) {
			$priority = '7';
		}

		$results = MMI_RA_Query::search( $search, $exclude_id, $priority );

		wp_send_json_success(
			array(
				'results' => $results,
			)
		);
	}

	/**
	 * Auto-suggest related posts for current article.
	 */
	public static function auto_suggest() {
		self::verify_request();

		$post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$priority = isset( $_POST['priority'] ) ? sanitize_key( wp_unslash( $_POST['priority'] ) ) : '7';

		if ( ! in_array( $priority, array( '7', '15', 'all' ), true ) ) {
			$priority = '7';
		}

		$results = MMI_RA_Query::auto_suggest( $post_id, $priority );

		wp_send_json_success(
			array(
				'results' => $results,
			)
		);
	}
}
