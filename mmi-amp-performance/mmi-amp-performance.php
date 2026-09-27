<?php
/**
 * Plugin Name: MMI AMP Performance (Core Web Vitals)
 * Description: Improves LCP and image delivery on legacy AMP URLs only. Does not modify non-AMP front-end output.
 * Version: 1.0.1
 * Author: MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-amp-performance
 *
 * @package MMI_AMP_Performance
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_AMP_PERF_VERSION', '1.0.1' );
define( 'MMI_AMP_PERF_PATH', plugin_dir_path( __FILE__ ) );

require_once MMI_AMP_PERF_PATH . 'includes/class-amp-context.php';
require_once MMI_AMP_PERF_PATH . 'includes/class-lcp-preload.php';
require_once MMI_AMP_PERF_PATH . 'includes/class-amp-featured-image.php';
require_once MMI_AMP_PERF_PATH . 'includes/class-amp-cache-headers.php';

/**
 * Bootstrap after other plugins (AMP plugin must load first).
 */
add_action(
	'plugins_loaded',
	static function () {
		add_action( 'template_redirect', 'mmi_amp_perf_maybe_boot', 0 );
		// Official AMP plugin may be ready earlier on some setups.
		if ( MMI_AMP_Context::is_amp_request() ) {
			mmi_amp_perf_boot();
		}
	},
	20
);

/**
 * Late boot when AMP is only known after routing.
 */
function mmi_amp_perf_maybe_boot(): void {
	if ( MMI_AMP_Context::is_amp_request() ) {
		mmi_amp_perf_boot();
	}
}

/**
 * Register AMP-only optimizations.
 */
function mmi_amp_perf_boot(): void {
	static $booted = false;
	if ( $booted ) {
		return;
	}
	$booted = true;

	MMI_AMP_LCP_Preload::register();
	MMI_AMP_Featured_Image::register();
	MMI_AMP_Cache_Headers::register();
}
