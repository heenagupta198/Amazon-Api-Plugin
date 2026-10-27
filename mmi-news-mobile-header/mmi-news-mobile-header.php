<?php
/**
 * Plugin Name: MMI Mobile Header Layout
 * Description: English news mobile header order (menu | logo | search) and equal hamburger lines on Devices.
 * Version: 1.1.0
 * Author: MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-news-mobile-header
 *
 * @package MMI_News_Mobile_Header
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_NEWS_MOBILE_HEADER_VERSION', '1.1.0' );
define( 'MMI_NEWS_MOBILE_HEADER_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_NEWS_MOBILE_HEADER_URL', plugin_dir_url( __FILE__ ) );

/**
 * @return bool
 */
function mmi_mobile_header_is_devices_site(): bool {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return 0 === strpos( $uri, '/devices' );
}

/**
 * Critical layout rules inlined so WP Rocket / deferred CSS cannot skip reorder on small screens.
 */
function mmi_mobile_header_print_critical_css(): void {
	if ( is_admin() || mmi_mobile_header_is_devices_site() ) {
		return;
	}
	?>
<style id="mmi-mobile-header-critical">
@media (max-width:767px){
.td-header-mobile-wrap .tdc-row>.vc_row,.td-header-mobile-sticky-wrap .tdc-row>.vc_row{display:flex!important;flex-flow:row nowrap!important;align-items:center!important;justify-content:space-between!important;width:100%!important}
.td-header-mobile-wrap .tdc-row>.vc_row>.vc_column:nth-child(1),.td-header-mobile-sticky-wrap .tdc-row>.vc_row>.vc_column:nth-child(1){order:2!important;flex:1 1 auto!important;width:auto!important;text-align:center!important}
.td-header-mobile-wrap .tdc-row>.vc_row>.vc_column:nth-child(2),.td-header-mobile-sticky-wrap .tdc-row>.vc_row>.vc_column:nth-child(2){order:3!important;flex:0 0 auto!important;width:auto!important}
.td-header-mobile-wrap .tdc-row>.vc_row>.vc_column:nth-child(3),.td-header-mobile-sticky-wrap .tdc-row>.vc_row>.vc_column:nth-child(3){order:1!important;flex:0 0 auto!important;width:auto!important}
}
</style>
	<?php
}
add_action( 'wp_head', 'mmi_mobile_header_print_critical_css', 2 );

/**
 * Enqueue site-specific mobile header CSS.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_admin() ) {
			return;
		}

		if ( mmi_mobile_header_is_devices_site() ) {
			wp_enqueue_style(
				'mmi-devices-hamburger',
				MMI_NEWS_MOBILE_HEADER_URL . 'assets/css/devices-hamburger.css',
				array(),
				MMI_NEWS_MOBILE_HEADER_VERSION
			);
			return;
		}

		wp_enqueue_style(
			'mmi-news-mobile-header',
			MMI_NEWS_MOBILE_HEADER_URL . 'assets/css/mobile-header.css',
			array(),
			MMI_NEWS_MOBILE_HEADER_VERSION
		);
	},
	100
);
