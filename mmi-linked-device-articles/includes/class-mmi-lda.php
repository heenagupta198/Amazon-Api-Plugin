<?php
/**
 * Plugin bootstrap.
 */

defined( 'ABSPATH' ) || exit;

require_once MMI_LDA_PATH . 'includes/class-mmi-lda-settings.php';
require_once MMI_LDA_PATH . 'includes/class-mmi-lda-sites.php';
require_once MMI_LDA_PATH . 'includes/class-mmi-lda-product-search.php';
require_once MMI_LDA_PATH . 'includes/class-mmi-lda-remote.php';
require_once MMI_LDA_PATH . 'includes/class-mmi-lda-sync.php';
require_once MMI_LDA_PATH . 'includes/class-mmi-lda-rest.php';
require_once MMI_LDA_PATH . 'includes/class-mmi-lda-admin.php';
require_once MMI_LDA_PATH . 'includes/class-mmi-lda-frontend.php';

final class MMI_LDA {

	/** @var self|null */
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		MMI_LDA_Settings::init();
		MMI_LDA_Rest::init();

		if ( is_admin() ) {
			MMI_LDA_Admin::init();
		}

		if ( MMI_LDA_Sites::is_devices_site() ) {
			MMI_LDA_Frontend::init();
		}
	}
}
