<?php
/**
 * Plugin Name: MMI Word Publisher
 * Description: Lets writers send Microsoft Word articles to WordPress as drafts (title, HTML body, links, bold subheadings). Pair with the MMI Word Add-in.
 * Version: 1.0.0
 * Author: MyMobileIndia
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: mmi-word-publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MMI_WORD_PUBLISHER_VERSION', '1.0.0' );
define( 'MMI_WORD_PUBLISHER_FILE', __FILE__ );
define( 'MMI_WORD_PUBLISHER_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMI_WORD_PUBLISHER_URL', plugin_dir_url( __FILE__ ) );

require_once MMI_WORD_PUBLISHER_DIR . 'includes/class-rest.php';
require_once MMI_WORD_PUBLISHER_DIR . 'includes/class-admin.php';
require_once MMI_WORD_PUBLISHER_DIR . 'includes/class-addin-serve.php';

add_action(
	'plugins_loaded',
	static function () {
		MMI_Word_Publisher_REST::init();
		MMI_Word_Publisher_Admin::init();
		MMI_Word_Publisher_Addin_Serve::init();
	}
);
