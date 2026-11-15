<?php
/**
 * Plugin Name: MMI TV Attributes
 * Description: Creates a separate WooCommerce attribute set for TVs (does not change existing mobile attributes).
 * Version:     1.0.0
 * Author:      MMI
 * Text Domain: mmi-tv-attributes
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_TV_VERSION', '1.0.0' );
define( 'MMI_TV_FILE', __FILE__ );
define( 'MMI_TV_PATH', plugin_dir_path( __FILE__ ) );

require_once MMI_TV_PATH . 'includes/class-schema.php';
require_once MMI_TV_PATH . 'includes/class-installer.php';
require_once MMI_TV_PATH . 'includes/class-admin.php';
require_once MMI_TV_PATH . 'includes/class-frontend.php';

/**
 * Bootstrap after WooCommerce is ready.
 */
function mmi_tv_attributes_init(): void {
	if ( get_option( 'mmi_tv_attributes_pending_install' ) ) {
		MMI_TV_Installer::install();
		delete_option( 'mmi_tv_attributes_pending_install' );
	}
	MMI_TV_Admin::init();
	MMI_TV_Frontend::init();
}
add_action( 'woocommerce_loaded', 'mmi_tv_attributes_init' );

/**
 * @return void
 */
function mmi_tv_attributes_missing_notice(): void {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'MMI TV Attributes requires WooCommerce to be installed and active.', 'mmi-tv-attributes' );
	echo '</p></div>';
}

/**
 * @return void
 */
function mmi_tv_attributes_check_dependencies(): void {
	if ( class_exists( 'WooCommerce' ) ) {
		return;
	}
	add_action( 'admin_notices', 'mmi_tv_attributes_missing_notice' );
}
add_action( 'plugins_loaded', 'mmi_tv_attributes_check_dependencies', 20 );

/**
 * @return void
 */
function mmi_tv_attributes_declare_hpos(): void {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MMI_TV_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'mmi_tv_attributes_declare_hpos' );

/**
 * @return void
 */
function mmi_tv_attributes_activate(): void {
	add_option( 'mmi_tv_attributes_pending_install', '1' );
	if ( function_exists( 'wc_create_attribute' ) ) {
		MMI_TV_Installer::install();
		delete_option( 'mmi_tv_attributes_pending_install' );
	}
}
register_activation_hook( __FILE__, 'mmi_tv_attributes_activate' );
