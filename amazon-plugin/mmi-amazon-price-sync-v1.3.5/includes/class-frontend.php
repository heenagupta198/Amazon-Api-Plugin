<?php
/**
 * Frontend price display — show Amazon price without overwriting WC price fields.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Frontend {

	/** @var array<int,bool> */
	private static $amazon_product_cache = array();

	public static function init(): void {
		add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_get_regular_price', array( __CLASS__, 'filter_regular_price' ), 20, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'filter_sale_price' ), 20, 2 );
		add_filter( 'woocommerce_product_is_on_sale', array( __CLASS__, 'filter_is_on_sale' ), 20, 2 );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_price_html' ), 60, 2 );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_unavailable_price_html' ), 55, 2 );
		add_filter( 'woocommerce_sale_flash', array( __CLASS__, 'filter_sale_flash' ), 20, 3 );

		add_filter( 'woocommerce_variation_prices_price', array( __CLASS__, 'filter_variation_price' ), 20, 3 );
		add_filter( 'woocommerce_variation_prices_regular_price', array( __CLASS__, 'filter_variation_regular_price' ), 20, 3 );
		add_filter( 'woocommerce_variation_prices_sale_price', array( __CLASS__, 'filter_variation_sale_price' ), 20, 3 );

		add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_print_cleanup_script' ), 50 );
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
		$amazon_price = self::get_amazon_price_for_product( $product );

		if ( null === $amazon_price ) {
			return $price;
		}

		if ( self::should_show_buying_price_only( $product ) ) {
			return (string) $amazon_price;
		}

		$amazon_original_price = self::get_amazon_original_for_product( $product );
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
		$amazon_price = self::get_amazon_price_for_product( $product );

		if ( null === $amazon_price ) {
			return $price;
		}

		if ( self::should_show_buying_price_only( $product ) ) {
			return '';
		}

		$amazon_original_price = self::get_amazon_original_for_product( $product );
		if ( null !== $amazon_original_price && $amazon_original_price > $amazon_price ) {
			return (string) $amazon_price;
		}

		return '';
	}

	/**
	 * @param bool            $on_sale Whether product is on sale.
	 * @param WC_Product|null $product Product object.
	 */
	public static function filter_is_on_sale( $on_sale, $product ): bool {
		$amazon_price = self::get_amazon_price_for_product( $product );

		if ( null !== $amazon_price && self::should_show_buying_price_only( $product ) ) {
			return false;
		}

		$amazon_original_price = self::get_amazon_original_for_product( $product );
		if ( null !== $amazon_price && null !== $amazon_original_price && $amazon_original_price > $amazon_price ) {
			return true;
		}

		return (bool) $on_sale;
	}

	/**
	 * Output a single buying price (no strikethrough MRP markup).
	 *
	 * @param string     $html    Price HTML.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public static function filter_price_html( $html, $product ) {
		if ( ! is_string( $html ) ) {
			$html = '';
		}

		if ( ! $product instanceof WC_Product || MMI_APS_Product_Meta::is_unavailable( $product->get_id() ) ) {
			return $html;
		}

		if ( ! self::should_show_buying_price_only( $product ) ) {
			return $html;
		}

		$amazon_price = self::get_amazon_price_for_product( $product );
		if ( null === $amazon_price ) {
			return $html;
		}

		return wc_price( $amazon_price );
	}

	/**
	 * Show unavailable message when Amazon has no buyable price.
	 *
	 * @param string     $html    Price HTML.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public static function filter_unavailable_price_html( $html, $product ) {
		if ( ! is_string( $html ) ) {
			$html = '';
		}

		if ( ! $product instanceof WC_Product || ! self::is_storefront_context() ) {
			return $html;
		}

		$product_id = $product->get_id();
		if ( '' === MMI_APS_Product_Meta::get_asin( $product_id ) || ! MMI_APS_Product_Meta::is_unavailable( $product_id ) ) {
			return $html;
		}

		return '<span class="mmi-aps-unavailable">' . esc_html__( 'Currently Unavailable', 'mmi-amazon-price-sync' ) . '</span>';
	}

	/**
	 * Hide WooCommerce / theme sale badge (e.g. -44%) for Amazon-priced products.
	 *
	 * @param string          $html    Badge HTML.
	 * @param WP_Post         $post    Post object.
	 * @param WC_Product|null $product Product object.
	 * @return string
	 */
	public static function filter_sale_flash( $html, $post, $product ) {
		if ( ! is_string( $html ) ) {
			$html = '';
		}

		if ( ! $product instanceof WC_Product || ! self::should_show_buying_price_only( $product ) ) {
			return $html;
		}

		if ( null !== self::get_amazon_price_for_product( $product ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * @param array<int,string> $classes Body classes.
	 * @return array<int,string>
	 */
	public static function add_body_class( array $classes ): array {
		if ( ! is_product() ) {
			return $classes;
		}

		$product_id = get_queried_object_id();
		if ( self::is_amazon_product( $product_id ) ) {
			$classes[] = 'mmi-aps-buy-price-only';
		}

		if ( MMI_APS_Product_Meta::is_unavailable( $product_id ) && '' !== MMI_APS_Product_Meta::get_asin( $product_id ) ) {
			$classes[] = 'mmi-aps-unavailable-product';
		}

		return $classes;
	}

	public static function enqueue_styles(): void {
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
	 * DOM cleanup for ReHub templates that bypass WooCommerce price filters.
	 */
	public static function maybe_print_cleanup_script(): void {
		if ( is_admin() || ! is_product() ) {
			return;
		}

		$product_id = get_queried_object_id();
		if ( ! $product_id ) {
			return;
		}

		$is_unavailable = MMI_APS_Product_Meta::is_unavailable( $product_id ) && '' !== MMI_APS_Product_Meta::get_asin( $product_id );
		if ( ! $is_unavailable && ! self::is_amazon_product( $product_id ) ) {
			return;
		}

		?>
		<script>
		(function () {
			var unavailableText = <?php echo wp_json_encode( __( 'Currently Unavailable', 'mmi-amazon-price-sync' ) ); ?>;

			function hideOfferExtras() {
				<?php if ( $is_unavailable ) : ?>
				document.querySelectorAll('.woo-price-area, .re_wooinner_cta_wrapper .rh_price_wrapper, .wpsm_price_wrapper, .mobile_price').forEach(function (area) {
					area.innerHTML = '<span class="mmi-aps-unavailable">' + unavailableText + '</span>';
				});
				<?php endif; ?>

				var selectors = [
					'.woo-price-area del',
					'.re_wooinner_cta_wrapper del',
					'.rh_price_wrapper del',
					'.wpsm_price_wrapper del',
					'.mobile_price del',
					'.price del',
					'.woo-price-area .rehub_offer_product_price_old',
					'.woo-price-area .price_old',
					'.woo-price-area .old_price',
					'.woo-price-area .greycolor',
					'.woo-price-area .retail-price',
					'.woo-price-area .was-price',
					'#woo-button-area',
					'.woo-button-area',
					'.grid_onsale',
					'.onsale',
					'.rh-label-string',
					'.rh-label-type-round',
					'.rh-label-type-square',
					'.percentage_count',
					'.sale_bar',
					'.overlay_post_formats.sale_format',
					'.title_badges .rh-label'
				];

				selectors.forEach(function (selector) {
					document.querySelectorAll(selector).forEach(function (el) {
						el.style.setProperty('display', 'none', 'important');
					});
				});

				document.querySelectorAll('.woo-price-area').forEach(function (area) {
					var prices = area.querySelectorAll('.price_count, .woocommerce-Price-amount, .amount');
					if (prices.length > 1) {
						for (var i = 1; i < prices.length; i++) {
							var node = prices[i];
							var wrapper = node.closest('span, div, p');
							if (wrapper && wrapper !== prices[0].closest('span, div, p')) {
								wrapper.style.setProperty('display', 'none', 'important');
							}
						}
					}
				});

				document.querySelectorAll('.woo-price-area ins').forEach(function (el) {
					var parent = el.parentNode;
					if (!parent) {
						return;
					}
					while (el.firstChild) {
						parent.insertBefore(el.firstChild, el);
					}
					el.remove();
				});
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', hideOfferExtras);
			} else {
				hideOfferExtras();
			}

			window.setTimeout(hideOfferExtras, 400);
			window.setTimeout(hideOfferExtras, 1200);
		})();
		</script>
		<?php
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
		$target   = $variation ?: $product;
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
		$target   = $variation ?: $product;
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

	private static function is_amazon_product( int $product_id ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		if ( MMI_APS_Product_Meta::is_unavailable( $product_id ) && '' !== MMI_APS_Product_Meta::get_asin( $product_id ) ) {
			return true;
		}

		if ( isset( self::$amazon_product_cache[ $product_id ] ) ) {
			return self::$amazon_product_cache[ $product_id ];
		}

		$has_amazon = '' !== MMI_APS_Product_Meta::get_asin( $product_id )
			&& null !== MMI_APS_Product_Meta::get_amazon_price( $product_id );

		self::$amazon_product_cache[ $product_id ] = $has_amazon;

		return $has_amazon;
	}

	private static function is_storefront_context(): bool {
		return ! is_admin() && ! wp_doing_cron();
	}

	/**
	 * Frontend only — backend/meta prices stay intact.
	 *
	 * @param WC_Product|null $product Product object.
	 */
	private static function should_show_buying_price_only( $product = null ): bool {
		if ( ! self::is_storefront_context() ) {
			return false;
		}

		if ( $product instanceof WC_Product ) {
			return self::is_amazon_product( $product->get_id() );
		}

		return is_product() && self::is_amazon_product( get_queried_object_id() );
	}
}
