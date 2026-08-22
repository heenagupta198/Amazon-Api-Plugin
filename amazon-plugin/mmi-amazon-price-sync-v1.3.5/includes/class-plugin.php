<?php
/**
 * Main plugin bootstrap.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_APS_Plugin {

	/** @var self|null */
	private static $instance = null;

	public const META_ASIN            = '_mmi_amazon_asin';
	public const META_PRICE             = '_mmi_amazon_price';
	public const META_ORIGINAL_PRICE    = '_mmi_amazon_original_price';
	public const META_LAST_UPDATED      = '_mmi_amazon_last_updated';
	public const META_TITLE             = '_mmi_amazon_title';
	public const META_CURRENCY          = '_mmi_amazon_currency';
	public const META_DELIVERY          = '_mmi_amazon_delivery';

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		MMI_APS_Settings::init();
		MMI_APS_Cron::init();
		MMI_APS_Admin::init();
		MMI_APS_Product_Meta::init();
		MMI_APS_Frontend::init();
		MMI_APS_Affiliate::maybe_init();
	}
}
