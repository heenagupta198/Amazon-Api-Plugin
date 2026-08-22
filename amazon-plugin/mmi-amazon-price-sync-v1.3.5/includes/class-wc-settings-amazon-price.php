<?php
/**
 * WooCommerce settings tab: Amazon Price Sync.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Settings_Page' ) ) {
	return;
}

class MMI_APS_WC_Settings_Amazon_Price extends WC_Settings_Page {

	public function __construct() {
		$this->id    = 'mmi_amazon_price_sync';
		$this->label = __( 'Amazon Price Sync', 'mmi-amazon-price-sync' );

		parent::__construct();
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_settings(): array {
		$using_constant = defined( 'MMI_APS_RAPIDAPI_KEY' ) && MMI_APS_RAPIDAPI_KEY;

		$settings = array(
			array(
				'title' => __( 'RapidAPI Configuration', 'mmi-amazon-price-sync' ),
				'type'  => 'title',
				'desc'  => __( 'Connect to your RapidAPI Amazon India product endpoint. Each product only needs an ASIN — the plugin fetches the live price.', 'mmi-amazon-price-sync' ),
				'id'    => 'mmi_aps_api_options',
			),
		);

		if ( $using_constant ) {
			$settings[] = array(
				'title' => __( 'RapidAPI Key', 'mmi-amazon-price-sync' ),
				'type'  => 'mmi_aps_constant_notice',
				'id'    => 'mmi_aps_rapidapi_key_notice',
			);
		} else {
			$settings[] = array(
				'title'    => __( 'RapidAPI Key', 'mmi-amazon-price-sync' ),
				'desc'     => __( 'Your x-rapidapi-key from the RapidAPI dashboard.', 'mmi-amazon-price-sync' ),
				'id'       => MMI_APS_Settings::OPTION_API_KEY,
				'type'     => 'password',
				'default'  => '',
				'desc_tip' => true,
				'autoload' => false,
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'title'    => __( 'RapidAPI Host', 'mmi-amazon-price-sync' ),
					'desc'     => __( 'x-rapidapi-host header value.', 'mmi-amazon-price-sync' ),
					'id'       => MMI_APS_Settings::OPTION_API_HOST,
					'type'     => 'text',
					'default'  => MMI_APS_Settings::DEFAULT_HOST,
					'desc_tip' => true,
				),
				array(
					'title'    => __( 'API Endpoint Path', 'mmi-amazon-price-sync' ),
					'desc'     => __( 'Path appended to the host, e.g. /amazon/product-details', 'mmi-amazon-price-sync' ),
					'id'       => MMI_APS_Settings::OPTION_API_ENDPOINT,
					'type'     => 'text',
					'default'  => MMI_APS_Settings::DEFAULT_ENDPOINT,
					'desc_tip' => true,
				),
				array(
					'title'    => __( 'Amazon Country', 'mmi-amazon-price-sync' ),
					'desc'     => __( 'Marketplace country code sent to the API (IN for Amazon India).', 'mmi-amazon-price-sync' ),
					'id'       => MMI_APS_Settings::OPTION_COUNTRY,
					'type'     => 'text',
					'default'  => 'IN',
					'desc_tip' => true,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'mmi_aps_api_options',
				),
				array(
					'title' => __( 'How it works', 'mmi-amazon-price-sync' ),
					'type'  => 'title',
					'desc'  => '<ol style="margin-left:1.2em;">'
						. '<li>' . esc_html__( 'Add an Amazon ASIN on each WooCommerce product (Product Data → Amazon Price tab).', 'mmi-amazon-price-sync' ) . '</li>'
						. '<li>' . esc_html__( 'Click "Fetch Price" to pull the live Amazon India price via RapidAPI.', 'mmi-amazon-price-sync' ) . '</li>'
						. '<li>' . esc_html__( 'Amazon price, original price, and last-updated time are saved as product meta.', 'mmi-amazon-price-sync' ) . '</li>'
						. '<li>' . esc_html__( 'The storefront shows the Amazon price automatically — your manual WooCommerce price is not overwritten.', 'mmi-amazon-price-sync' ) . '</li>'
						. '</ol>'
						. '<p><code>define( \'MMI_APS_RAPIDAPI_KEY\', \'your-key-here\' );</code> ' . esc_html__( 'in wp-config.php overrides the key stored here.', 'mmi-amazon-price-sync' ) . '</p>',
					'id'    => 'mmi_aps_howto',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'mmi_aps_howto',
				),
			)
		);

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}
}
