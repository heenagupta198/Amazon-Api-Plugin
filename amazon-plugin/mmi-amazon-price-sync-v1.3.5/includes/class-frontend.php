<?php
/**
 * Frontend price display — show Amazon price without overwriting WC price fields.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Frontend {

	public static function init(): void {
		add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_get_regular_price', array( __CLASS__, 'filter_regular_price' ), 20, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'filter_sale_price' ), 20, 2 );
		add_filter( 'woocommerce_product_is_on_sale', array( __CLASS__, 'filter_is_on_sale' ), 20, 2 );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_price_html_mobile' ), 60, 2 );
		add_filter( 'woocommerce_sale_flash', array( __CLASS__, 'filter_sale_flash_mobile' ), 20, 3 );

		// Variations inherit parent ASIN meta in a future version; simple products only for v1.
		add_filter( 'woocommerce_variation_prices_price', array( __CLASS__, 'filter_variation_price' ), 20, 3 );
		add_filter( 'woocommerce_variation_prices_regular_price', array( __CLASS__, 'filter_variation_regular_price' ), 20, 3 );
		add_filter( 'woocommerce_variation_prices_sale_price', array( __CLASS__, 'filter_variation_sale_price' ), 20, 3 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_mobile_styles' ) );
	}

	/**
	 * @param mixed           $price   Current price.
	 * @param WC_Product|null $product Product object.
	 * @return mixed
	 */
	public static function filter_price( $price, $product ) {
		$amazon_price = self::get_amazon_price_for_product( $product );
		return null !== $amazon_price ? (string) $amazon_price : $price;
	}

	/**
	 * @param mixed           $price   Current regular price.
	 * @param WC_Product|null $product Product object.
	 * @return mixed
	 */
	public static function filter_regular_price( $price, $product ) {
		$amazon_price          = self::get_amazon_price_for_product( $product );
		$amazon_original_price = self::get_amazon_original_for_product( $product );

		if ( null === $amazon_price ) {
			return $price;
		}

		if ( self::is_mobile_storefront() ) {
			return (string) $amazon_price;
		}

		if ( null !== $amazon_original_price && $amazon_original_price > $amazon_price ) {
			return (string) $amazon_original_price;
		}

		return (string) $amazon_price;
	}

	/**
	 * @param mixed           $price   Current sale price.
	 * @param WC_Product|null $product Product object.
	 * @return mixed
	 */
	public static function filter_sale_price( $price, $product ) {
		$amazon_price          = self::get_amazon_price_for_product( $product );
		$amazon_original_price = self::get_amazon_original_for_product( $product );

		if ( null === $amazon_price ) {
			return $price;
		}

		if ( self::is_mobile_storefront() ) {
			return '';
		}

		if ( null !== $amazon_original_price && $amazon_original_price > $amazon_price ) {
			return (string) $amazon_price;
		}

		return '';
	}

	/**
	 * @param bool            $on_sale Whether product is on sale.
	 * @param WC_Product|null $product Product object.
	 */
	public static function filter_is_on_sale( bool $on_sale, $product ): bool {
		$amazon_price          = self::get_amazon_price_for_product( $product );
		$amazon_original_price = self::get_amazon_original_for_product( $product );

		if ( null !== $amazon_price && self::is_mobile_storefront() ) {
			return false;
		}

		if ( null !== $amazon_price && null !== $amazon_original_price && $amazon_original_price > $amazon_price ) {
			return true;
		}

		return $on_sale;
	}

	/**
	 * On mobile, output a single buying price (no strikethrough MRP markup).
	 *
	 * @param string     $html    Price HTML.
	 * @param WC_Product $product Product object.
	 */
	public static function filter_price_html_mobile( string $html, $product ): string {
		if ( ! self::is_mobile_storefront() || ! $product instanceof WC_Product ) {
			return $html;
		}

		$amazon_price = self::get_amazon_price_for_product( $product );
		if ( null === $amazon_price ) {
			return $html;
		}

		return wc_price( $amazon_price );
	}

	/**
	 * Hide WooCommerce / theme sale badge (e.g. -44%) on mobile for Amazon-priced products.
	 *
	 * @param string          $html    Badge HTML.
	 * @param WP_Post         $post    Post object.
	 * @param WC_Product|null $product Product object.
	 */
	public static function filter_sale_flash_mobile( string $html, $post, $product ): string {
		if ( ! self::is_mobile_storefront() || ! $product instanceof WC_Product ) {
			return $html;
		}

		if ( null !== self::get_amazon_price_for_product( $product ) ) {
			return '';
		}

		return $html;
	}

	public static function enqueue_mobile_styles(): void {
		if ( is_admin() || is_cart() || is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'mmi-aps-frontend-mobile',
			MMI_APS_URL . 'assets/css/frontend-mobile.css',
			array(),
			MMI_APS_VERSION
		);
	}

	/**
	 * @param float  $price      Variation price.
	 * @param object $variation  Variation product.
	 * @param object $product    Parent product.
	 */
	public static function filter_variation_price( $price, $variation, $product ) {
		$amazon_price = self::get_amazon_price_for_product( $variation );
		if ( null === $amazon_price ) {
			$amazon_price = self::get_amazon_price_for_product( $product );
		}
		return null !== $amazon_price ? $amazon_price : $price;
	}

	/**
	 * @param float  $price      Variation regular price.
	 * @param object $variation  Variation product.
	 * @param object $product    Parent product.
	 */
	public static function filter_variation_regular_price( $price, $variation, $product ) {
		$target = $variation ?: $product;
		$filtered = self::filter_regular_price( $price, $target );
		if ( (string) $filtered !== (string) $price ) {
			return $filtered;
		}
		return self::filter_regular_price( $price, $product );
	}

	/**
	 * @param float  $price      Variation sale price.
	 * @param object $variation  Variation product.
	 * @param object $product    Parent product.
	 */
	public static function filter_variation_sale_price( $price, $variation, $product ) {
		$target = $variation ?: $product;
		$filtered = self::filter_sale_price( $price, $target );
		if ( '' !== $filtered || self::get_amazon_price_for_product( $target ) ) {
			return $filtered;
		}
		return self::filter_sale_price( $price, $product );
	}

	/**
	 * @param WC_Product|null $product Product.
	 */
	private static function get_amazon_price_for_product( $product ): ?float {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		return MMI_APS_Product_Meta::get_amazon_price( $product->get_id() );
	}

	/**
	 * @param WC_Product|null $product Product.
	 */
	private static function get_amazon_original_for_product( $product ): ?float {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		return MMI_APS_Product_Meta::get_amazon_original_price( $product->get_id() );
	}

	/**
	 * Mobile storefront only — backend and desktop keep full price data.
	 */
	private static function is_mobile_storefront(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		return wp_is_mobile();
	}
}
