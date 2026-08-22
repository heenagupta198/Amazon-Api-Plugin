<?php
/**
 * Plugin Name: MMI Amazon Price Sync
 * Plugin URI:  https://github.com/yogesh/comapre-Plugin
 * Description: Sync WooCommerce product prices from Amazon India via RapidAPI using ASIN.
 * Version:     1.3.9
 * Author:      Yogesh
 * Text Domain: mmi-amazon-price-sync
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_APS_VERSION', '1.3.9' );
define( 'MMI_APS_FILE', __FILE__ );
define( 'MMI_APS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_APS_URL', plugin_dir_url( __FILE__ ) );

require_once MMI_APS_PATH . 'includes/class-api-client.php';
require_once MMI_APS_PATH . 'includes/class-settings.php';
require_once MMI_APS_PATH . 'includes/class-sync.php';
require_once MMI_APS_PATH . 'includes/class-cron.php';
require_once MMI_APS_PATH . 'includes/class-admin.php';
require_once MMI_APS_PATH . 'includes/class-product-meta.php';
require_once MMI_APS_PATH . 'includes/class-frontend.php';
require_once MMI_APS_PATH . 'includes/class-affiliate.php';
require_once MMI_APS_PATH . 'includes/class-plugin.php';

/**
 * Bootstrap plugin after WooCommerce is fully loaded.
 */
function mmi_aps_init() {
	MMI_APS_Plugin::instance();
}
add_action( 'woocommerce_loaded', 'mmi_aps_init' );

/**
 * Show notice when WooCommerce is missing.
 */
function mmi_aps_check_dependencies() {
	if ( class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_action( 'admin_notices', 'mmi_aps_woocommerce_missing_notice' );
}
add_action( 'plugins_loaded', 'mmi_aps_check_dependencies', 20 );

/**
 * Admin notice when WooCommerce is missing.
 */
function mmi_aps_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'MMI Amazon Price Sync requires WooCommerce to be installed and active.', 'mmi-amazon-price-sync' );
	echo '</p></div>';
}

/**
 * Declare HPOS compatibility.
 */
function mmi_aps_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MMI_APS_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'mmi_aps_declare_hpos_compatibility' );

/**
 * Activation: schedule cron.
 */
function mmi_aps_activate() {
	MMI_APS_Cron::activate();
}
register_activation_hook( __FILE__, 'mmi_aps_activate' );

/**
 * Deactivation: clear cron.
 */
function mmi_aps_deactivate() {
	MMI_APS_Cron::deactivate();
}
register_deactivation_hook( __FILE__, 'mmi_aps_deactivate' );
