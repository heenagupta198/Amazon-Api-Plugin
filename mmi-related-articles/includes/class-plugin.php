<?php
/**
 * Plugin bootstrap.
 *
 * @package MMI_Related_Articles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
class MMI_RA_Plugin {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		MMI_RA_Meta_Box::init();
		MMI_RA_Ajax_Handler::init();
		MMI_RA_Frontend::init();
	}
}
