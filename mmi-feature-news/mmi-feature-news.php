<?php
/**
 * Plugin Name: MMI Feature Section
 * Description: One shortcode: latest Feature posts from mymobileindia.com (4 desktop, 1 mobile, View All). For ReHub /devices/.
 * Version:     2.0.0
 * Author:      MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-feature-news
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_FN_VERSION', '2.0.0' );
define( 'MMI_FN_FILE', __FILE__ );
define( 'MMI_FN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_FN_URL', plugin_dir_url( __FILE__ ) );

require_once MMI_FN_PATH . 'includes/class-settings.php';
require_once MMI_FN_PATH . 'includes/class-remote-posts.php';
require_once MMI_FN_PATH . 'includes/class-shortcode.php';

/**
 * Bootstrap plugin.
 */
function mmi_feature_news_init(): void {
	MMI_FN_Settings::register();
	MMI_FN_Shortcode::register();
}
add_action( 'plugins_loaded', 'mmi_feature_news_init' );

/**
 * Settings reset for simplified Feature-only plugin.
 */
function mmi_fn_maybe_upgrade_settings(): void {
	$ver = (string) get_option( 'mmi_fn_db_version', '0' );
	if ( version_compare( $ver, '2.0.0', '>=' ) ) {
		return;
	}

	update_option( MMI_FN_Settings::OPTION, MMI_FN_Settings::defaults() );
	update_option( 'mmi_fn_db_version', '2.0.0' );
}
add_action( 'plugins_loaded', 'mmi_fn_maybe_upgrade_settings', 5 );
