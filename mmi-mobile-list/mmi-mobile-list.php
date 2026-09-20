<?php
/**
 * Plugin Name: MMI Mobile Price List
 * Description: Shortcode listings of WooCommerce mobiles by price band, category, and brand (91mobiles-style buckets).
 * Version:     1.2.1
 * Author:      Yogesh
 * Text Domain: mmi-mobile-list
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_ML_VERSION', '1.2.1' );
define( 'MMI_ML_FILE', __FILE__ );
define( 'MMI_ML_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_ML_URL', plugin_dir_url( __FILE__ ) );

require_once MMI_ML_PATH . 'includes/class-mmi-cache.php';
require_once MMI_ML_PATH . 'includes/class-mmi-specs.php';
require_once MMI_ML_PATH . 'includes/class-mmi-card.php';
require_once MMI_ML_PATH . 'includes/class-mmi-query.php';
require_once MMI_ML_PATH . 'includes/class-mmi-shortcode.php';
require_once MMI_ML_PATH . 'includes/class-mmi-admin.php';

/**
 * Bootstrap after WooCommerce loads.
 */
function mmi_ml_init() {
	MMI_ML_Shortcode::register();
	if ( is_admin() ) {
		MMI_ML_Admin::register();
	}
}
add_action( 'woocommerce_loaded', 'mmi_ml_init' );

/**
 * Dependency notice.
 */
function mmi_ml_check_dependencies() {
	if ( class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'MMI Mobile Price List requires WooCommerce.', 'mmi-mobile-list' );
			echo '</p></div>';
		}
	);
}
add_action( 'plugins_loaded', 'mmi_ml_check_dependencies', 20 );

/**
 * HPOS compatibility.
 */
function mmi_ml_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			MMI_ML_FILE,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'mmi_ml_declare_hpos_compatibility' );

/**
 * Default settings on activate.
 */
function mmi_ml_activate() {
	if ( false === get_option( 'mmi_ml_per_page', false ) ) {
		add_option( 'mmi_ml_per_page', 10 );
	}
	if ( false === get_option( 'mmi_ml_cache_ttl', false ) ) {
		add_option( 'mmi_ml_cache_ttl', 6 * HOUR_IN_SECONDS );
	}
}
register_activation_hook( __FILE__, 'mmi_ml_activate' );
