<?php
/**
 * Plugin Name: MMI Feature News (Static Grid)
 * Description: Shows latest Feature category posts in a fixed grid (4 on desktop, 1 on mobile) with View All — no slider, loop, or auto-scroll.
 * Version:     1.2.0
 * Author:      MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-feature-news
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_FN_VERSION', '1.2.0' );
define( 'MMI_FN_FILE', __FILE__ );
define( 'MMI_FN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_FN_URL', plugin_dir_url( __FILE__ ) );

require_once MMI_FN_PATH . 'includes/class-settings.php';
require_once MMI_FN_PATH . 'includes/class-remote-posts.php';
require_once MMI_FN_PATH . 'includes/class-shortcode.php';
require_once MMI_FN_PATH . 'includes/class-shortcode-hero.php';
require_once MMI_FN_PATH . 'includes/class-tagdiv-static.php';

/**
 * Bootstrap plugin.
 */
function mmi_feature_news_init(): void {
	MMI_FN_Settings::register();
	MMI_FN_Shortcode::register();
	MMI_FN_Shortcode_Hero::register();
	MMI_FN_Tagdiv_Static::register();
}
add_action( 'plugins_loaded', 'mmi_feature_news_init' );

/**
 * One-time settings refresh for Tech News + layout defaults.
 */
function mmi_fn_maybe_upgrade_settings(): void {
	$ver = (string) get_option( 'mmi_fn_db_version', '0' );
	if ( version_compare( $ver, '1.2.0', '>=' ) ) {
		return;
	}

	$stored = get_option( MMI_FN_Settings::OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	if ( in_array( $stored['category_slug'] ?? '', array( 'feature', '' ), true ) ) {
		$stored['category_slug']      = 'tech-news';
		$stored['remote_category_id'] = 10;
		$stored['view_all_url']       = 'https://www.mymobileindia.com/tech-news/';
	}

	$title = (string) ( $stored['section_title'] ?? '' );
	if ( in_array( $title, array( 'Featured News', 'Feature News', 'Featured' ), true ) ) {
		$stored['section_title'] = 'Feature';
	}

	$stored['hero_title']         = '';
	$stored['hero_show_header']   = 0;
	$stored['hero_show_view_all'] = 0;

	update_option( MMI_FN_Settings::OPTION, array_merge( MMI_FN_Settings::defaults(), $stored ) );
	update_option( 'mmi_fn_db_version', '1.2.0' );
}
add_action( 'plugins_loaded', 'mmi_fn_maybe_upgrade_settings', 5 );
