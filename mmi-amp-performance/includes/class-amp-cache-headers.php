<?php
/**
 * Longer cache for AMP HTML (helps field TTFB when origin is slow).
 *
 * @package MMI_AMP_Performance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-Control for AMP responses only.
 */
final class MMI_AMP_Cache_Headers {

	/**
	 * Register hook.
	 */
	public static function register(): void {
		add_action( 'send_headers', array( __CLASS__, 'send_headers' ), 20 );
	}

	/**
	 * Suggest edge/browser cache for static AMP documents (logged-out).
	 */
	public static function send_headers(): void {
		if ( ! MMI_AMP_Context::is_amp_request() || is_user_logged_in() || is_admin() ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		$max_age = (int) apply_filters( 'mmi_amp_perf_cache_max_age', 3600 );
		if ( $max_age <= 0 ) {
			return;
		}

		header( 'Cache-Control: public, max-age=' . $max_age, true );
	}
}
