<?php
/**
 * Plugin bootstrap.
 */

defined( 'ABSPATH' ) || exit;

require_once MMI_CSL_PATH . 'includes/class-mmi-csl-sites.php';
require_once MMI_CSL_PATH . 'includes/class-mmi-csl-admin.php';
require_once MMI_CSL_PATH . 'includes/class-mmi-csl-frontend.php';

final class MMI_CSL {

	/** @var self|null */
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		if ( is_admin() ) {
			MMI_CSL_Admin::init();
		}

		MMI_CSL_Frontend::init();
	}
}
