<?php
/**
 * Plugin Name: MMI Related Articles
 * Plugin URI:  https://mymobile.in
 * Description: AJAX related-article picker with date priority (7/15 days), drag-and-drop order, and automatic frontend carousel for Newspaper/TagDiv themes.
 * Version:     1.0.1
 * Author:      My Mobile
 * Text Domain: mmi-related-articles
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_RA_VERSION', '1.0.1' );
define( 'MMI_RA_FILE', __FILE__ );
define( 'MMI_RA_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_RA_URL', plugin_dir_url( __FILE__ ) );
define( 'MMI_RA_META_KEY', '_mmi_related_post_ids' );

require_once MMI_RA_PATH . 'includes/class-query.php';
require_once MMI_RA_PATH . 'includes/class-ajax-handler.php';
require_once MMI_RA_PATH . 'includes/class-meta-box.php';
require_once MMI_RA_PATH . 'includes/class-frontend.php';
require_once MMI_RA_PATH . 'includes/class-plugin.php';

/**
 * Bootstrap plugin.
 */
function mmi_ra_init() {
	MMI_RA_Plugin::instance();
}
add_action( 'plugins_loaded', 'mmi_ra_init' );
