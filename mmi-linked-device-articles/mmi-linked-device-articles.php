<?php
/**
 * Plugin Name: MMI Linked Device Articles
 * Description: Link English news articles to device products and show a Related Articles carousel on product pages. Does not replace MMI Content Slider.
 * Version:     1.0.0
 * Author:      MyMobileIndia
 * Text Domain: mmi-linked-device-articles
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Network: true
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_LDA_VERSION', '1.0.0' );
define( 'MMI_LDA_FILE', __FILE__ );
define( 'MMI_LDA_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_LDA_URL', plugin_dir_url( __FILE__ ) );
define( 'MMI_LDA_META_KEY', '_mmi_linked_product_ids' );

require_once MMI_LDA_PATH . 'includes/class-mmi-lda.php';

/**
 * Bootstrap plugin.
 */
function mmi_lda_init(): void {
	MMI_LDA::instance();
}
add_action( 'plugins_loaded', 'mmi_lda_init' );

/**
 * Store detected blog IDs on activation (multisite).
 */
function mmi_lda_activate( $network_wide ): void {
	if ( ! is_multisite() || ! $network_wide ) {
		return;
	}

	require_once MMI_LDA_PATH . 'includes/class-mmi-lda-sites.php';

	$devices = MMI_LDA_Sites::get_devices_blog_id();
	$news    = MMI_LDA_Sites::get_news_blog_id();
	update_network_option( null, MMI_LDA_Sites::OPTION_DEVICES_BLOG_ID, $devices );
	update_network_option( null, MMI_LDA_Sites::OPTION_NEWS_BLOG_ID, $news );
}
register_activation_hook( __FILE__, 'mmi_lda_activate' );
