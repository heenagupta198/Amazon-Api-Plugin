<?php
/**
 * Plugin bootstrap.
 */

defined( 'ABSPATH' ) || exit;

require_once MMI_FS_PATH . 'includes/class-mmi-fs-index.php';
require_once MMI_FS_PATH . 'includes/class-mmi-fs-settings.php';
require_once MMI_FS_PATH . 'includes/class-mmi-fs-frontend.php';

final class MMI_FS {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		MMI_FS_Settings::init();
		MMI_FS_Frontend::init();
		MMI_FS_Index::init_hooks();
	}
}
