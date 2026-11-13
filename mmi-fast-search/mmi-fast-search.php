<?php
/**
 * Plugin Name: MMI Fast Frontend Search
 * Description: Ultra-light client-side search overlay. Replaces slow tagDiv header AJAX search with an instant local index (low server CPU/RAM).
 * Version:     1.0.0
 * Author:      MyMobileIndia
 * Text Domain: mmi-fast-search
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'MMI_FS_VERSION', '1.0.0' );
define( 'MMI_FS_FILE', __FILE__ );
define( 'MMI_FS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMI_FS_URL', plugin_dir_url( __FILE__ ) );

require_once MMI_FS_PATH . 'includes/class-mmi-fs.php';

/**
 * Bootstrap.
 */
function mmi_fs_init(): void {
	MMI_FS::instance();
}
add_action( 'plugins_loaded', 'mmi_fs_init' );

/**
 * Build search index on activation and schedule nightly rebuild.
 */
function mmi_fs_activate(): void {
	require_once MMI_FS_PATH . 'includes/class-mmi-fs-index.php';
	MMI_FS_Index::rebuild();

	if ( ! wp_next_scheduled( 'mmi_fs_rebuild_index' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mmi_fs_rebuild_index' );
	}
}
register_activation_hook( __FILE__, 'mmi_fs_activate' );

/**
 * Clear scheduled event on deactivation.
 */
function mmi_fs_deactivate(): void {
	wp_clear_scheduled_hook( 'mmi_fs_rebuild_index' );
}
register_deactivation_hook( __FILE__, 'mmi_fs_deactivate' );
