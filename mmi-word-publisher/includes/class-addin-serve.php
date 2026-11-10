<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures manifest is reachable and documents the public URL.
 */
class MMI_Word_Publisher_Addin_Serve {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'send_manifest_headers' ) );
	}

	public static function manifest_url(): string {
		return MMI_WORD_PUBLISHER_URL . 'add-in/manifest.xml';
	}

	public static function send_manifest_headers(): void {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$uri = wp_unslash( $_SERVER['REQUEST_URI'] );
		if ( false === strpos( $uri, 'mmi-word-publisher/add-in/manifest.xml' ) ) {
			return;
		}
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/xml; charset=utf-8' );
		}
	}
}
