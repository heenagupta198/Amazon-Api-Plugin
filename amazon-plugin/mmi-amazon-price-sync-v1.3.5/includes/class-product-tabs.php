<?php
/**
 * WooCommerce product tabs — 91mobiles-style PRICES tab.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Product_Tabs {

	public static function init(): void {
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'register_prices_tab' ), 98 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'style_tab_labels' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public static function register_prices_tab( array $tabs ): array {
		if ( ! is_singular( array( 'product', 'devices' ) ) ) {
			return $tabs;
		}

		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product || ! self::product_has_amazon_listings( $product ) ) {
			return $tabs;
		}

		$tabs['mmi_prices'] = array(
			'title'    => __( 'PRICES', 'mmi-amazon-price-sync' ),
			'priority' => 12,
			'callback' => array( __CLASS__, 'render_prices_tab_panel' ),
		);

		return $tabs;
	}

	/**
	 * Uppercase default tab titles where it helps match 91mobiles (theme-dependent).
	 *
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public static function style_tab_labels( array $tabs ): array {
		$map = array(
			'description'  => __( 'SPECS', 'mmi-amazon-price-sync' ),
			'additional_information' => __( 'INFO', 'mmi-amazon-price-sync' ),
		);

		foreach ( $map as $key => $label ) {
			if ( isset( $tabs[ $key ]['title'] ) ) {
				$tabs[ $key ]['title'] = $label;
			}
		}

		return apply_filters( 'mmi_aps_product_tabs', $tabs );
	}

	public static function render_prices_tab_panel(): void {
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product ) {
			return;
		}

		echo MMI_APS_Prices_Table::render_product( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function enqueue_assets(): void {
		if ( ! is_singular( array( 'product', 'devices' ) ) ) {
			return;
		}

		wp_enqueue_style(
			'mmi-aps-product-tabs',
			MMI_APS_URL . 'assets/css/product-tabs.css',
			array(),
			MMI_APS_VERSION
		);

		wp_enqueue_script(
			'mmi-aps-product-tabs',
			MMI_APS_URL . 'assets/js/product-tabs.js',
			array( 'jquery' ),
			MMI_APS_VERSION,
			true
		);
	}

	/**
	 * Open PRICES tab — used by See Prices button.
	 */
	public static function get_prices_tab_anchor(): string {
		return '#tab-mmi_prices';
	}

	/**
	 * @param WC_Product $product Product.
	 */
	private static function product_has_amazon_listings( WC_Product $product ): bool {
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $vid ) {
				if ( '' !== MMI_APS_Product_Meta::get_asin( (int) $vid ) ) {
					return true;
				}
			}
			return false;
		}

		return '' !== MMI_APS_Product_Meta::get_asin( $product->get_id() );
	}
}