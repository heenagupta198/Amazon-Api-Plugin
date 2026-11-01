<?php
/**
 * Plugin Name: MMI Feature News (Static Grid)
 * Description: Shows latest Feature category posts in a fixed grid (4 on desktop, 1 on mobile) with View All — no slider, loop, or auto-scroll.
 * Version:     1.1.0
 * Author:      MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-feature-news
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_FN_VERSION', '1.1.0' );
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
