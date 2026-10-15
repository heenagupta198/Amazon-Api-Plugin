<?php
/**
 * Plugin Name: MMI Content Slider
 * Description: Link news articles to device products and show a Latest News carousel on product spec pages (multisite).
 * Version:     1.2.0
 * Author:      MyMobileIndia
 * Text Domain: mmi-content-slider
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Network: true
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_CSL_VERSION', '1.2.0' );
define( 'MMI_CSL_FILE', __FILE__ );
define( 'MMI_CSL_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_CSL_URL', plugin_dir_url( __FILE__ ) );
define( 'MMI_CSL_META_KEY', '_mmi_linked_product_ids' );

require_once MMI_CSL_PATH . 'includes/class-mmi-csl.php';

/**
 * Bootstrap plugin.
 */
function mmi_csl_init(): void {
	MMI_CSL::instance();
}
add_action( 'plugins_loaded', 'mmi_csl_init' );

/**
 * Store detected blog IDs on activation (multisite).
 */
function mmi_csl_activate( $network_wide ): void {
	if ( ! is_multisite() ) {
		return;
	}

	if ( $network_wide ) {
		$devices = MMI_CSL_Sites::get_devices_blog_id();
		$news    = MMI_CSL_Sites::get_news_blog_id();
		update_network_option( null, MMI_CSL_Sites::OPTION_DEVICES_BLOG_ID, $devices );
		update_network_option( null, MMI_CSL_Sites::OPTION_NEWS_BLOG_ID, $news );
	}
}
register_activation_hook( __FILE__, 'mmi_csl_activate' );
