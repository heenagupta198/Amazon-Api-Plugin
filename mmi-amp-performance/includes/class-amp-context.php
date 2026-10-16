<?php
/**
 * Detect AMP requests without affecting normal pages.
 *
 * @package MMI_AMP_Performance
 */

defined( 'ABSPATH' ) || exit;

/**
 * AMP context helpers.
 */
final class MMI_AMP_Context {

	/**
	 * Whether the current request is an AMP document.
	 */
	public static function is_amp_request(): bool {
		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
			return true;
		}

		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			return true;
		}

		// AMP for WP and legacy /amp/ permalinks.
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$uri = wp_unslash( $_SERVER['REQUEST_URI'] );
			if ( preg_match( '#/(?:amp/?|\?amp=1)(?:\?|#|$)#i', $uri ) || preg_match( '#/amp/?$#i', $uri ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'mmi_amp_perf_is_amp_request', false );
	}

	/**
	 * Singular post view (article AMP pages).
	 */
	public static function is_amp_singular(): bool {
		return self::is_amp_request() && is_singular( array( 'post', 'page' ) );
	}
}
