<?php
/**
 * Plugin Name: MMI Mobile List
 * Plugin URI:  https://mymobileindia.com/devices/
 * Description: 91mobiles-style spec cards for PAF pages, Best/Latest/Upcoming category archives, and brand pages on MyMobileIndia Devices.
 * Version:     1.3.0
 * Author:      My Mobile
 * Text Domain: mmi-mobile-list
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_MLL_VERSION', '1.3.0' );
define( 'MMI_MLL_FILE', __FILE__ );
define( 'MMI_MLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_MLL_URL', plugin_dir_url( __FILE__ ) );

require_once MMI_MLL_PATH . 'includes/class-settings.php';
require_once MMI_MLL_PATH . 'includes/class-specs.php';
require_once MMI_MLL_PATH . 'includes/class-icons.php';
require_once MMI_MLL_PATH . 'includes/class-renderer.php';
require_once MMI_MLL_PATH . 'includes/class-shortcode.php';
require_once MMI_MLL_PATH . 'includes/class-archive.php';
require_once MMI_MLL_PATH . 'includes/class-admin.php';
require_once MMI_MLL_PATH . 'includes/class-plugin.php';

/**
 * Bootstrap plugin.
 */
function mmi_mll_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-error"><p>';
				esc_html_e( 'MMI Mobile List requires WooCommerce.', 'mmi-mobile-list' );
				echo '</p></div>';
			}
		);
		return;
	}

	MMI_MLL_Plugin::instance();
}
add_action( 'plugins_loaded', 'mmi_mll_init' );

register_activation_hook(
	__FILE__,
	static function () {
		if ( ! get_option( MMI_MLL_Settings::OPTION_ARCHIVES, false ) ) {
			update_option(
				MMI_MLL_Settings::OPTION_ARCHIVES,
				array(
					'best-mobiles'     => true,
					'latest-mobiles'   => true,
					'upcoming-mobiles' => true,
				)
			);
		}
		update_option( MMI_MLL_Settings::OPTION_BRAND_ARCHIVES, true );
	}
);