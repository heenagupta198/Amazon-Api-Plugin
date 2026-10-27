<?php
/**
 * Plugin Name: MMI Mobile Header (News)
 * Description: Mobile header on mymobileindia.com news site: menu (left), logo (center), search (right). CSS only — no JS, no front-end queries.
 * Version: 1.2.0
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

/**
 * One static CSS block in head (homepage + all templates). Avoids extra HTTP and WP Rocket dropping an external file.
 */
function mmi_news_mobile_header_css(): void {
	if ( is_admin() ) {
		return;
	}
	?>
<style id="mmi-news-mobile-header">
@media (max-width:767px){
.td-header-mobile-wrap .vc_row.tdc-element-style,
.td-header-mobile-sticky-wrap .vc_row.tdc-element-style{
display:flex!important;flex-flow:row nowrap!important;align-items:center!important;justify-content:space-between!important;width:100%!important;margin:0!important}
.td-header-mobile-wrap .vc_row.tdc-element-style>.vc_column,
.td-header-mobile-sticky-wrap .vc_row.tdc-element-style>.vc_column,
.td-header-mobile-wrap .vc_row.tdc-element-style .wpb_wrapper,
.td-header-mobile-sticky-wrap .vc_row.tdc-element-style .wpb_wrapper{
display:contents!important}
.td-header-mobile-wrap .tdb_mobile_menu,
.td-header-mobile-sticky-wrap .tdb_mobile_menu{order:1!important;flex:0 0 auto!important}
.td-header-mobile-wrap .tdb_header_logo,
.td-header-mobile-sticky-wrap .tdb_header_logo{order:2!important;flex:1 1 auto!important;min-width:0!important;text-align:center!important}
.td-header-mobile-wrap .tdb_mobile_search,
.td-header-mobile-sticky-wrap .tdb_mobile_search{order:3!important;flex:0 0 auto!important}
.td-header-mobile-wrap .tdb_header_logo .tdb-logo-a,
.td-header-mobile-sticky-wrap .tdb_header_logo .tdb-logo-a{justify-content:center!important;margin-left:auto!important;margin-right:auto!important}
.td-header-mobile-wrap .tdb_mobile_menu svg,.td-header-mobile-wrap .tdb_mobile_menu svg *,
.td-header-mobile-wrap .tdb_mobile_search svg,.td-header-mobile-wrap .tdb_mobile_search svg *,
.td-header-mobile-sticky-wrap .tdb_mobile_menu svg,.td-header-mobile-sticky-wrap .tdb_mobile_menu svg *,
.td-header-mobile-sticky-wrap .tdb_mobile_search svg,.td-header-mobile-sticky-wrap .tdb_mobile_search svg *{fill:#e31e24!important}
}
</style>
	<?php
}
add_action( 'wp_head', 'mmi_news_mobile_header_css', 99 );
