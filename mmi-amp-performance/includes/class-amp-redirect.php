<?php
/**
 * One-hop 301s for non-article AMP URLs.
 *
 * Archive, home, search, and paged AMP URLs were redirecting badly:
 * 302 to a slash-less URL (second hop), ?amp=1 on a category to the
 * homepage, and /page/N/amp/ to a 404. Article AMP documents are left
 * untouched.
 *
 * @package MMI_AMP_Performance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect legacy non-singular AMP URLs to the canonical front-end URL.
 */
final class MMI_AMP_Redirect {

	/**
	 * Register the redirect before canonical and AMP plugin redirects.
	 */
	public static function register(): void {
		// `wp` runs as soon as the main query is ready, before AMP plugins that
		// redirect on the default `wp` priority. template_redirect covers
		// redirect_canonical (priority 10) if nothing exited earlier.
		add_action( 'wp', array( __CLASS__, 'maybe_redirect' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), -1 );
	}

	/**
	 * Send a single permanent redirect when this request is a non-article AMP URL.
	 */
	public static function maybe_redirect(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! is_string( $request_uri ) || ! self::is_amp_url( $request_uri ) ) {
			return;
		}

		if ( ! self::should_redirect_current_query( $request_uri ) ) {
			return;
		}

		$target = self::target_from_request_uri( $request_uri, home_url( '/' ) );
		if ( ! is_string( $target ) || '' === $target ) {
			return;
		}

		$status = (int) apply_filters( 'mmi_amp_perf_redirect_status', 301 );
		if ( $status < 300 || $status > 399 ) {
			$status = 301;
		}

		wp_safe_redirect( $target, $status );
		if ( ! headers_sent() ) {
			header( 'Cache-Control: public, max-age=86400' );
		}
		exit;
	}

	/**
	 * Article AMP stays as-is. Home, archives, search, and multi-segment AMP 404s redirect.
	 *
	 * @param string $request_uri Raw request URI.
	 */
	public static function should_redirect_current_query( string $request_uri ): bool {
		return self::decide(
			array(
				'enabled'   => (bool) apply_filters( 'mmi_amp_perf_redirect_non_singular', true ),
				'feed'      => is_feed(),
				'singular'  => is_singular(),
				'embed'     => is_embed(),
				'trackback' => is_trackback(),
				'front'     => is_front_page(),
				'home'      => is_home(),
				'archive'   => is_archive(),
				'search'    => is_search(),
				'is_404'    => is_404(),
			),
			self::non_amp_segment_count( $request_uri )
		);
	}

	/**
	 * Redirect policy isolated from WordPress conditionals so it can be tested.
	 *
	 * Article AMP (singular) is never redirected. Home, archives, and search are.
	 * A 404 redirects only when the non-AMP path has two or more segments, which
	 * is the paged AMP case (/section/page/2/amp/) and not a single article slug.
	 *
	 * @param array<string,bool> $flags    Query flags.
	 * @param int                $segments Non-AMP path segment count.
	 */
	public static function decide( array $flags, int $segments ): bool {
		if ( empty( $flags['enabled'] ) ) {
			return false;
		}
		if ( ! empty( $flags['feed'] ) || ! empty( $flags['singular'] ) || ! empty( $flags['embed'] ) || ! empty( $flags['trackback'] ) ) {
			return false;
		}
		if ( ! empty( $flags['front'] ) || ! empty( $flags['home'] ) || ! empty( $flags['archive'] ) || ! empty( $flags['search'] ) ) {
			return true;
		}
		return ! empty( $flags['is_404'] ) && $segments >= 2;
	}

	/**
	 * Whether the URI is an /amp/ endpoint or an amp query URL.
	 *
	 * @param string $request_uri Raw request URI.
	 */
	public static function is_amp_url( string $request_uri ): bool {
		$parts = wp_parse_url( $request_uri );
		if ( ! is_array( $parts ) ) {
			return false;
		}

		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
		if ( preg_match( '#/amp/?$#i', $path ) ) {
			return true;
		}

		if ( empty( $parts['query'] ) ) {
			return false;
		}

		$args = array();
		parse_str( (string) $parts['query'], $args );
		return array_key_exists( 'amp', $args );
	}

	/**
	 * Build the non-AMP URL, always with a trailing slash.
	 *
	 * @param string $request_uri Raw request URI (path + query).
	 * @param string $home        Site home URL, with or without a trailing slash.
	 * @return string|null Null when the URI is not an AMP URL.
	 */
	public static function target_from_request_uri( string $request_uri, string $home ): ?string {
		$parts = wp_parse_url( $request_uri );
		if ( ! is_array( $parts ) ) {
			return null;
		}

		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
		$had  = false;

		if ( preg_match( '#/amp/?$#i', $path ) ) {
			$path = (string) preg_replace( '#/amp/?$#i', '/', $path );
			$had  = true;
		}

		$args = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $args );
			if ( array_key_exists( 'amp', $args ) ) {
				unset( $args['amp'] );
				$had = true;
			}
		}

		if ( ! $had ) {
			return null;
		}

		$path = '/' . trim( $path, '/' );
		if ( '/' !== $path ) {
			$path .= '/';
		}

		$target = rtrim( $home, '/' ) . $path;
		if ( $args ) {
			$target .= '?' . http_build_query( $args );
		}

		return $target;
	}

	/**
	 * Path segments after the AMP marker is removed.
	 *
	 * @param string $request_uri Raw request URI.
	 */
	public static function non_amp_segment_count( string $request_uri ): int {
		$target = self::target_from_request_uri( $request_uri, 'https://example.com/' );
		if ( ! is_string( $target ) ) {
			return 0;
		}
		$path = (string) wp_parse_url( $target, PHP_URL_PATH );
		return count( array_filter( explode( '/', $path ) ) );
	}
}
