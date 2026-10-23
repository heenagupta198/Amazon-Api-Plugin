<?php
/**
 * Plugin Name: MMI News Mobile Header Layout
 * Description: Reorders the TagDiv mobile header to match the Devices site: menu (left), logo (center), search (right).
 * Version: 1.0.0
 * Author: MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-news-mobile-header
 *
 * @package MMI_News_Mobile_Header
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_NEWS_MOBILE_HEADER_VERSION', '1.0.0' );
define( 'MMI_NEWS_MOBILE_HEADER_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_NEWS_MOBILE_HEADER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Enqueue mobile header layout CSS on the main news site only (not /devices/).
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_admin() ) {
			return;
		}

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( str_starts_with( $uri, '/devices' ) ) {
			return;
		}

		wp_enqueue_style(
			'mmi-news-mobile-header',
			MMI_NEWS_MOBILE_HEADER_URL . 'assets/css/mobile-header.css',
			array(),
			MMI_NEWS_MOBILE_HEADER_VERSION
		);
	},
	100
);
