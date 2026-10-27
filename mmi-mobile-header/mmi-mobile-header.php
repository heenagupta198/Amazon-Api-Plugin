<?php
/**
 * Plugin Name: MMI Mobile Header (News)
 * Description: Mobile header on mymobileindia.com news site: menu (left), logo (center), search (right). CSS only — no JS, no front-end queries.
 * Version: 1.2.1
 * Author: MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-news-mobile-header
 *
 * Install on the main English news WordPress only (not /devices/).
 *
 * @package MMI_News_Mobile_Header
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_MOBILE_HEADER_VERSION', '1.2.1' );
define( 'MMI_MOBILE_HEADER_FILE', __FILE__ );
define( 'MMI_MOBILE_HEADER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Clear page caches so logged-out / mobile visitors see new header (not only logged-in admin).
 */
function mmi_mobile_header_purge_caches(): void {
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
	}
	if ( function_exists( 'litespeed_purge_all' ) ) {
		do_action( 'litespeed_purge_all' );
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	if ( class_exists( 'WpeCommon' ) && method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
		WpeCommon::purge_varnish_cache();
	}
}
register_activation_hook( MMI_MOBILE_HEADER_FILE, 'mmi_mobile_header_purge_caches' );

/**
 * @param array<string,mixed> $upgrader_object
 * @param array<string,mixed> $options
 */
function mmi_mobile_header_purge_on_plugin_update( $upgrader_object, $options ): void {
	if ( empty( $options['action'] ) || 'update' !== $options['action'] ) {
		return;
	}
	if ( empty( $options['type'] ) || 'plugin' !== $options['type'] ) {
		return;
	}
	if ( empty( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
		return;
	}
	$basename = plugin_basename( MMI_MOBILE_HEADER_FILE );
	if ( in_array( $basename, $options['plugins'], true ) ) {
		mmi_mobile_header_purge_caches();
	}
}
add_action( 'upgrader_process_complete', 'mmi_mobile_header_purge_on_plugin_update', 10, 2 );

/**
 * Front-end CSS (logged-in users often bypass cache; file + inline keeps guest/mobile HTML correct after purge).
 */
function mmi_mobile_header_enqueue(): void {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'mmi-mobile-header',
		MMI_MOBILE_HEADER_URL . 'assets/mobile-header.css',
		array(),
		MMI_MOBILE_HEADER_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'mmi_mobile_header_enqueue', 9999 );

/**
 * WP Rocket: do not strip this stylesheet when Remove Unused CSS is on.
 *
 * @param array<int,string> $excluded
 * @return array<int,string>
 */
function mmi_mobile_header_rocket_exclude_css( $excluded ) {
	$excluded[] = '/mmi-mobile-header/assets/mobile-header.css';
	return $excluded;
}
add_filter( 'rocket_exclude_css', 'mmi_mobile_header_rocket_exclude_css' );

/**
 * WP Rocket: keep inline rules if used.
 *
 * @param array<int,string> $patterns
 * @return array<int,string>
 */
function mmi_mobile_header_rocket_rucss_inline_exclude( $patterns ) {
	$patterns[] = 'mmi-news-mobile-header';
	return $patterns;
}
add_filter( 'rocket_rucss_inline_content_exclusions', 'mmi_mobile_header_rocket_rucss_inline_exclude' );
