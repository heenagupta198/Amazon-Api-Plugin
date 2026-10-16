<?php
/**
 * Plugin bootstrap.
 */

defined( 'ABSPATH' ) || exit;

require_once MMI_CSL_PATH . 'includes/class-mmi-csl-settings.php';
require_once MMI_CSL_PATH . 'includes/class-mmi-csl-sites.php';
require_once MMI_CSL_PATH . 'includes/class-mmi-csl-product-search.php';
require_once MMI_CSL_PATH . 'includes/class-mmi-csl-remote.php';
require_once MMI_CSL_PATH . 'includes/class-mmi-csl-sync.php';
require_once MMI_CSL_PATH . 'includes/class-mmi-csl-rest.php';
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
		MMI_CSL_Settings::init();
		MMI_CSL_Rest::init();

		if ( is_admin() ) {
			MMI_CSL_Admin::init();
		}

		if ( MMI_CSL_Sites::is_devices_site() ) {
			MMI_CSL_Frontend::init();
		}
	}
}
