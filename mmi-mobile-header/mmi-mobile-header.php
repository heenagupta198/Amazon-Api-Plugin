<?php
/**
 * Plugin Name: MMI Mobile Header (News)
 * Description: Mobile header menu | logo | search on all pages (home, categories, articles). WP Rocket safe.
 * Version: 1.4.0
 * Author: MMI
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-news-mobile-header
 *
 * @package MMI_News_Mobile_Header
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_MOBILE_HEADER_VERSION', '1.4.0' );
define( 'MMI_MOBILE_HEADER_FILE', __FILE__ );
define( 'MMI_MOBILE_HEADER_MARKER', 'mmi-mh-v140' );

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
	return '<style data-' . MMI_MOBILE_HEADER_MARKER . '="1">'
		. mmi_mobile_header_get_css()
		. '</style>';
}

/**
 * Print CSS in head (no id= — WP Rocket was emptying id="mmi-news-mobile-header" on home/category).
 */
function mmi_mobile_header_print_style(): void {
	if ( is_admin() ) {
		return;
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo mmi_mobile_header_style_tag();
}
add_action( 'wp_head', 'mmi_mobile_header_print_style', 99999 );

/**
 * Inject on cached HTML (homepage/category Use CSS bundle strips mobile @media rules).
 *
 * @param string $html
 * @return string
 */
function mmi_mobile_header_rocket_buffer( $html ) {
	if ( is_admin() || ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	if ( false !== strpos( $html, MMI_MOBILE_HEADER_MARKER ) ) {
		return $html;
	}
	$tag = mmi_mobile_header_style_tag();
	if ( false !== strpos( $html, '</head>' ) ) {
		return str_replace( '</head>', $tag . '</head>', $html, 1 );
	}
	return $html;
}
add_filter( 'rocket_buffer', 'mmi_mobile_header_rocket_buffer', 99999 );

/**
 * Keep mobile header rules when Remove Unused CSS runs (often desktop crawl drops @media max-width).
 *
 * @param array<int,string> $safelist
 * @return array<int,string>
 */
function mmi_mobile_header_rucss_safelist( $safelist ) {
	$keep = array(
		'.td-header-mobile-wrap',
		'.td-header-mobile-sticky-wrap',
		'.td-header-mobile-wrap .vc_row.tdc-element-style',
		'.td-header-mobile-wrap .vc_row.tdc-element-style > .vc_column:nth-child(1)',
		'.td-header-mobile-wrap .vc_row.tdc-element-style > .vc_column:nth-child(2)',
		'.td-header-mobile-wrap .vc_row.tdc-element-style > .vc_column:nth-child(3)',
		'.tdb_mobile_menu',
		'.tdb_mobile_search',
		'.tdb_header_logo',
	);
	return array_merge( $safelist, $keep );
}
add_filter( 'rocket_rucss_safelist', 'mmi_mobile_header_rucss_safelist' );

/**
 * @param array<int,string> $excluded
 * @return array<int,string>
 */
function mmi_mobile_header_rocket_used_css_excluded( $excluded ) {
	$excluded[] = MMI_MOBILE_HEADER_MARKER;
	return $excluded;
}
add_filter( 'rocket_rucss_inline_content_exclusions', 'mmi_mobile_header_rocket_used_css_excluded' );

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
	if ( function_exists( 'rocket_clean_used_css' ) ) {
		rocket_clean_used_css();
	}
}
register_activation_hook( MMI_MOBILE_HEADER_FILE, 'mmi_mobile_header_purge_caches' );

/**
 * @param mixed               $upgrader_object
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
