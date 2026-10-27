<?php
/**
 * Plugin Name: MMI Mobile Header (News)
 * Description: Mobile header: menu (left), logo (center), search (right). Single-file plugin — no extra CSS URL (no 404).
 * Version: 1.3.0
 * Author: MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-news-mobile-header
 *
 * @package MMI_News_Mobile_Header
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_MOBILE_HEADER_VERSION', '1.3.0' );
define( 'MMI_MOBILE_HEADER_FILE', __FILE__ );
define( 'MMI_MOBILE_HEADER_STYLE_ID', 'mmi-mh-layout-v130' );

/**
 * @return string
 */
function mmi_mobile_header_get_css(): string {
	return '@media (max-width:767px){'
		. '.td-header-mobile-wrap .vc_row.tdc-element-style,'
		. '.td-header-mobile-sticky-wrap .vc_row.tdc-element-style{'
		. 'display:flex!important;flex-flow:row nowrap!important;align-items:center!important;'
		. 'justify-content:space-between!important;width:100%!important;margin:0!important}'
		. '.td-header-mobile-wrap .vc_row.tdc-element-style>.vc_column,'
		. '.td-header-mobile-sticky-wrap .vc_row.tdc-element-style>.vc_column{'
		. 'width:auto!important;max-width:none!important;min-width:0!important;flex-basis:auto!important;'
		. 'padding-left:0!important;padding-right:0!important}'
		. '.td-header-mobile-wrap .vc_row.tdc-element-style>.vc_column:nth-child(1),'
		. '.td-header-mobile-sticky-wrap .vc_row.tdc-element-style>.vc_column:nth-child(1){'
		. 'order:2!important;flex:1 1 auto!important;text-align:center!important}'
		. '.td-header-mobile-wrap .vc_row.tdc-element-style>.vc_column:nth-child(2),'
		. '.td-header-mobile-sticky-wrap .vc_row.tdc-element-style>.vc_column:nth-child(2){'
		. 'order:3!important;flex:0 0 auto!important}'
		. '.td-header-mobile-wrap .vc_row.tdc-element-style>.vc_column:nth-child(3),'
		. '.td-header-mobile-sticky-wrap .vc_row.tdc-element-style>.vc_column:nth-child(3){'
		. 'order:1!important;flex:0 0 auto!important}'
		. '.td-header-mobile-wrap .tdb_header_logo .tdb-logo-a,'
		. '.td-header-mobile-sticky-wrap .tdb_header_logo .tdb-logo-a{'
		. 'justify-content:center!important;margin-left:auto!important;margin-right:auto!important}'
		. '.td-header-mobile-wrap .tdb_mobile_menu svg,.td-header-mobile-wrap .tdb_mobile_menu svg *,'
		. '.td-header-mobile-wrap .tdb_mobile_search svg,.td-header-mobile-wrap .tdb_mobile_search svg *,'
		. '.td-header-mobile-sticky-wrap .tdb_mobile_menu svg,.td-header-mobile-sticky-wrap .tdb_mobile_menu svg *,'
		. '.td-header-mobile-sticky-wrap .tdb_mobile_search svg,.td-header-mobile-sticky-wrap .tdb_mobile_search svg *'
		. '{fill:#e31e24!important}}';
}

/**
 * @return string
 */
function mmi_mobile_header_style_tag(): string {
	return '<style id="' . esc_attr( MMI_MOBILE_HEADER_STYLE_ID ) . '">'
		. mmi_mobile_header_get_css()
		. '</style>';
}

/**
 * Front-end: register inline style (no external file).
 */
function mmi_mobile_header_enqueue(): void {
	if ( is_admin() ) {
		return;
	}
	wp_register_style( 'mmi-mobile-header-layout', false, array(), MMI_MOBILE_HEADER_VERSION );
	wp_enqueue_style( 'mmi-mobile-header-layout' );
	wp_add_inline_style( 'mmi-mobile-header-layout', mmi_mobile_header_get_css() );
}
add_action( 'wp_enqueue_scripts', 'mmi_mobile_header_enqueue', 9999 );

/**
 * Late head output (backup if enqueue is stripped).
 */
function mmi_mobile_header_print_style(): void {
	if ( is_admin() ) {
		return;
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS from plugin.
	echo mmi_mobile_header_style_tag();
}
add_action( 'wp_head', 'mmi_mobile_header_print_style', 99999 );

/**
 * Inject into WP Rocket cached HTML so guest homepage keeps rules.
 *
 * @param string $html
 * @return string
 */
function mmi_mobile_header_rocket_buffer( $html ) {
	if ( is_admin() || ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	if ( false !== strpos( $html, MMI_MOBILE_HEADER_STYLE_ID ) ) {
		return $html;
	}
	$tag = mmi_mobile_header_style_tag();
	if ( false !== strpos( $html, '</head>' ) ) {
		return str_replace( '</head>', $tag . '</head>', $html, 1 );
	}
	return $html;
}
add_filter( 'rocket_buffer', 'mmi_mobile_header_rocket_buffer', 999 );

/**
 * Clear caches on activate / update.
 */
function mmi_mobile_header_purge_caches(): void {
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
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
 * @param array<int,string> $patterns
 * @return array<int,string>
 */
function mmi_mobile_header_rocket_rucss_inline_exclude( $patterns ) {
	$patterns[] = MMI_MOBILE_HEADER_STYLE_ID;
	return $patterns;
}
add_filter( 'rocket_rucss_inline_content_exclusions', 'mmi_mobile_header_rocket_rucss_inline_exclude' );
