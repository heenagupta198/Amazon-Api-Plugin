<?php
/**
 * Policy and URL tests for non-article AMP redirects.
 *
 * Usage: php tests/amp-redirect-test.php
 *
 * @package MMI_AMP_Performance
 */

define( 'ABSPATH', __DIR__ );

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Minimal stand-in for the WordPress helper.
	 *
	 * @param string   $url       URL.
	 * @param int|null $component parse_url component.
	 * @return mixed
	 */
	function wp_parse_url( $url, $component = -1 ) {
		if ( -1 === $component ) {
			$parsed = parse_url( $url );
			return false === $parsed ? false : $parsed;
		}
		return parse_url( $url, $component );
	}
}

require dirname( __DIR__ ) . '/includes/class-amp-redirect.php';

$home    = 'https://www.mymobileindia.com/';
$failed  = 0;
$checked = 0;

/**
 * Assert a target URL.
 *
 * @param string      $uri      Request URI.
 * @param string|null $expected Expected absolute URL, or null.
 */
function expect_target( string $uri, ?string $expected ): void {
	global $home, $failed, $checked;
	++$checked;
	$actual = MMI_AMP_Redirect::target_from_request_uri( $uri, $home );
	if ( $actual !== $expected ) {
		++$failed;
		fwrite( STDERR, "FAIL target {$uri}\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n" );
	}
}

/**
 * Assert a redirect decision.
 *
 * @param array<string,bool> $flags    Query flags.
 * @param int                $segments Segment count.
 * @param bool               $expected Expected decision.
 */
function expect_decide( array $flags, int $segments, bool $expected ): void {
	global $failed, $checked;
	++$checked;
	$actual = MMI_AMP_Redirect::decide( $flags, $segments );
	if ( $actual !== $expected ) {
		++$failed;
		fwrite( STDERR, 'FAIL decide ' . json_encode( $flags ) . " segments={$segments}\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n" );
	}
}

expect_target( '/tech-news/amp/', 'https://www.mymobileindia.com/tech-news/' );
expect_target( '/tech-news/amp', 'https://www.mymobileindia.com/tech-news/' );
expect_target( '/feature/amp', 'https://www.mymobileindia.com/feature/' );
expect_target( '/amp/', 'https://www.mymobileindia.com/' );
expect_target( '/amp', 'https://www.mymobileindia.com/' );
expect_target( '/how-to/page/2/amp/', 'https://www.mymobileindia.com/how-to/page/2/' );
expect_target( '/tech-news/page/2/amp/', 'https://www.mymobileindia.com/tech-news/page/2/' );
expect_target( '/tech-news/?amp=1', 'https://www.mymobileindia.com/tech-news/' );
expect_target( '/tech-news/?amp', 'https://www.mymobileindia.com/tech-news/' );
expect_target( '/?amp=1', 'https://www.mymobileindia.com/' );
expect_target( '/tech-news/page/2/?amp=1', 'https://www.mymobileindia.com/tech-news/page/2/' );
expect_target( '/tech-news/?amp=1&utm_source=news', 'https://www.mymobileindia.com/tech-news/?utm_source=news' );
expect_target( '/reviews/amp/?utm_source=news', 'https://www.mymobileindia.com/reviews/?utm_source=news' );
expect_target( '/create-gmail-account-how-to-create-a-new-google-account-on-mobile-phone/', null );
expect_target( '/tech-news/', null );
expect_target( '/oneplus-ace-7-pro/?amp=1', 'https://www.mymobileindia.com/oneplus-ace-7-pro/' );

$article = array(
	'enabled'  => true,
	'singular' => true,
);
expect_decide( $article, 1, false );

$category = array(
	'enabled' => true,
	'archive' => true,
);
expect_decide( $category, 1, true );

$home_flags = array(
	'enabled' => true,
	'front'   => true,
	'home'    => true,
);
expect_decide( $home_flags, 0, true );

$paged_404 = array(
	'enabled' => true,
	'is_404'  => true,
);
expect_decide( $paged_404, 3, true );
expect_decide( $paged_404, 1, false );

$disabled = array(
	'enabled' => false,
	'archive' => true,
);
expect_decide( $disabled, 1, false );

$feed = array(
	'enabled' => true,
	'archive' => true,
	'feed'    => true,
);
expect_decide( $feed, 1, false );

if ( 1 !== MMI_AMP_Redirect::non_amp_segment_count( '/missing/amp/' ) ) {
	++$failed;
	fwrite( STDERR, "FAIL segment count for single slug\n" );
}
++$checked;
if ( 3 !== MMI_AMP_Redirect::non_amp_segment_count( '/how-to/page/2/amp/' ) ) {
	++$failed;
	fwrite( STDERR, "FAIL segment count for paged amp\n" );
}
++$checked;

if ( ! MMI_AMP_Redirect::is_amp_url( '/tech-news/amp/' ) || ! MMI_AMP_Redirect::is_amp_url( '/tech-news/?amp=1' ) ) {
	++$failed;
	fwrite( STDERR, "FAIL is_amp_url positive\n" );
}
++$checked;
if ( MMI_AMP_Redirect::is_amp_url( '/tech-news/' ) ) {
	++$failed;
	fwrite( STDERR, "FAIL is_amp_url negative\n" );
}
++$checked;

if ( $failed > 0 ) {
	fwrite( STDERR, "{$failed} failed of {$checked}\n" );
	exit( 1 );
}

echo "OK {$checked} assertions\n";
