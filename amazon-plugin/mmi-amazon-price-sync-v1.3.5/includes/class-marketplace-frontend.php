<?php
/**
 * Storefront — platform-wise Amazon / Flipkart prices, status, and Buy Now links.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Marketplace_Frontend {

	public static function init(): void {
		if ( ! MMI_APS_Settings::is_marketplace_display_enabled() ) {
			return;
		}

		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_marketplace_block' ), 8 );
		add_action( 'rh_woo_single_product_price', array( __CLASS__, 'render_marketplace_block' ), 5 );
		add_filter( 'woocommerce_available_variation', array( __CLASS__, 'append_variation_marketplace_data' ), 20, 3 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_inject_marketplace_block' ), 48 );
	}

	public static function enqueue_assets(): void {
		if ( ! self::is_product_detail_page() ) {
			return;
		}

		wp_enqueue_style(
			'mmi-aps-marketplace',
			MMI_APS_URL . 'assets/css/marketplace-pricing.css',
			array(),
			MMI_APS_VERSION
		);

		wp_enqueue_script(
			'mmi-aps-marketplace-variations',
			MMI_APS_URL . 'assets/js/marketplace-variations.js',
			array( 'jquery' ),
			MMI_APS_VERSION,
			true
		);

		wp_localize_script(
			'mmi-aps-marketplace-variations',
			'mmiApsMarketplace',
			array(
				'staleText' => __( 'Price update in progress', 'mmi-amazon-price-sync' ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $data      Variation data.
	 * @param WC_Product          $product   Parent product.
	 * @param WC_Product_Variation $variation Variation.
	 * @return array<string,mixed>
	 */
	public static function append_variation_marketplace_data( array $data, $product, $variation ): array {
		if ( ! $variation instanceof WC_Product_Variation ) {
			return $data;
		}

		$snapshot = self::get_platform_snapshot( $variation->get_id() );
		foreach ( $snapshot['platforms'] as $key => $platform ) {
			if ( ! empty( $platform['show_price'] ) && isset( $platform['price'] ) ) {
				$snapshot['platforms'][ $key ]['price_html'] = wc_price( (float) $platform['price'] );
			}
			$snapshot['platforms'][ $key ]['buy_text'] = 'amazon' === $key
				? MMI_APS_Settings::get_affiliate_button_text()
				: MMI_APS_Settings::get_flipkart_button_text();
		}

		$data['mmi_marketplace'] = $snapshot;
		$data['mmi_sale_status'] = array(
			'slug'  => MMI_APS_Marketplace_Status::resolve( $variation->get_id() ),
			'label' => MMI_APS_Marketplace_Status::get_label( MMI_APS_Marketplace_Status::resolve( $variation->get_id() ) ),
		);

		return $data;
	}

	public static function render_marketplace_block(): void {
		if ( ! self::is_product_detail_page() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			$html = self::get_block_html( $product->get_id(), true );
		} else {
			$html = self::get_block_html( $product->get_id(), false );
		}

		if ( '' === $html ) {
			return;
		}

		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * ReHub templates that skip standard hooks.
	 */
	public static function maybe_inject_marketplace_block(): void {
		if ( ! self::is_product_detail_page() ) {
			return;
		}

		if ( did_action( 'woocommerce_single_product_summary' ) || did_action( 'rh_woo_single_product_price' ) ) {
			// If block already rendered via hooks, skip inject.
			if ( self::block_was_rendered() ) {
				return;
			}
		}

		$post_id = get_queried_object_id();
		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return;
		}

		$is_variable = $product->is_type( 'variable' );
		$html        = self::get_block_html( $product->get_id(), $is_variable );
		if ( '' === $html ) {
			return;
		}

		?>
		<script>
		(function () {
			var html = <?php echo wp_json_encode( $html ); ?>;
			function inject() {
				if (document.querySelector('.mmi-marketplace-pricing')) {
					return;
				}
				var target = document.querySelector('.woo-price-area, .re_wooinner_cta_wrapper, .rh_price_wrapper, .summary.entry-summary');
				if (!target) {
					return;
				}
				var wrap = document.createElement('div');
				wrap.innerHTML = html;
				target.insertAdjacentElement('afterend', wrap.firstElementChild);
			}
			inject();
			document.addEventListener('DOMContentLoaded', inject);
			window.setTimeout(inject, 500);
		})();
		</script>
		<?php
	}

	private static function block_was_rendered(): bool {
		// Heuristic: render_marketplace_block sets static $rendered — inject is fallback only.
		return false;
	}

	/**
	 * Build HTML for marketplace pricing card.
	 *
	 * @param int  $product_id  Product or parent ID.
	 * @param bool $is_variable Whether parent is variable (empty platform rows until variant chosen).
	 */
	public static function get_block_html( int $product_id, bool $is_variable ): string {
		$display_id = $product_id;
		$product    = wc_get_product( $product_id );
		if ( $product && $product->is_type( 'variable' ) && ! $is_variable ) {
			return '';
		}

		$snapshot = self::get_platform_snapshot( $display_id );
		if ( $is_variable && empty( $snapshot['platforms'] ) ) {
			$snapshot['platforms'] = array(
				'amazon'   => array(
					'configured'         => false,
					'show_price'         => false,
					'availability'       => 'unavailable',
					'availability_label' => __( 'Select variant', 'mmi-amazon-price-sync' ),
					'buy_url'            => '',
				),
				'flipkart' => array(
					'configured'         => false,
					'show_price'         => false,
					'availability'       => 'unavailable',
					'availability_label' => __( 'Select variant', 'mmi-amazon-price-sync' ),
					'buy_url'            => '',
				),
			);
		}

		if ( empty( $snapshot['platforms'] ) ) {
			return '';
		}

		$sale_status = MMI_APS_Marketplace_Status::resolve( $display_id );

		ob_start();
		?>
		<div class="mmi-marketplace-pricing" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>" <?php echo $is_variable ? 'data-variable="1"' : ''; ?>>
			<div class="mmi-marketplace-pricing__status">
				<span class="mmi-marketplace-pricing__status-label"><?php esc_html_e( 'Status', 'mmi-amazon-price-sync' ); ?>:</span>
				<span class="mmi-marketplace-pricing__status-value mmi-marketplace-pricing__status-value--<?php echo esc_attr( $sale_status ); ?>">
					<?php echo esc_html( MMI_APS_Marketplace_Status::get_label( $sale_status ) ); ?>
				</span>
			</div>
			<?php if ( $is_variable ) : ?>
				<p class="mmi-marketplace-pricing__hint"><?php esc_html_e( 'Select RAM and storage to see variant-specific prices and buy links.', 'mmi-amazon-price-sync' ); ?></p>
			<?php endif; ?>
			<div class="mmi-marketplace-pricing__platforms">
				<?php foreach ( $snapshot['platforms'] as $key => $platform ) : ?>
					<?php echo self::render_platform_row( $key, $platform ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param string               $platform_key amazon|flipkart.
	 * @param array<string,mixed>  $platform     Platform snapshot.
	 */
	private static function render_platform_row( string $platform_key, array $platform ): string {
		$label   = 'amazon' === $platform_key ? __( 'Amazon', 'mmi-amazon-price-sync' ) : __( 'Flipkart', 'mmi-amazon-price-sync' );
		$buy_url = isset( $platform['buy_url'] ) ? (string) $platform['buy_url'] : '';

		ob_start();
		?>
		<div class="mmi-marketplace-pricing__row mmi-marketplace-pricing__row--<?php echo esc_attr( $platform_key ); ?>" data-platform="<?php echo esc_attr( $platform_key ); ?>">
			<div class="mmi-marketplace-pricing__store">
				<span class="mmi-marketplace-pricing__store-name"><?php echo esc_html( $label ); ?></span>
				<span class="mmi-marketplace-pricing__availability mmi-marketplace-pricing__availability--<?php echo esc_attr( (string) ( $platform['availability'] ?? 'unavailable' ) ); ?>">
					<?php echo esc_html( (string) ( $platform['availability_label'] ?? '' ) ); ?>
				</span>
			</div>
			<div class="mmi-marketplace-pricing__price-area">
				<?php if ( ! empty( $platform['show_price'] ) && isset( $platform['price'] ) ) : ?>
					<a class="mmi-marketplace-pricing__price" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="nofollow sponsored noopener">
						<?php echo wp_kses_post( wc_price( (float) $platform['price'] ) ); ?>
					</a>
				<?php elseif ( ! empty( $platform['configured'] ) ) : ?>
					<span class="mmi-marketplace-pricing__stale"><?php esc_html_e( 'Price update in progress', 'mmi-amazon-price-sync' ); ?></span>
				<?php else : ?>
					<span class="mmi-marketplace-pricing__na">—</span>
				<?php endif; ?>
				<?php if ( '' !== $buy_url ) : ?>
					<a class="mmi-marketplace-pricing__buy button alt" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="nofollow sponsored noopener">
						<?php echo esc_html( 'amazon' === $platform_key ? MMI_APS_Settings::get_affiliate_button_text() : MMI_APS_Settings::get_flipkart_button_text() ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Normalized per-platform data for a product or variation.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return array{platforms:array<string,array<string,mixed>>}
	 */
	public static function get_platform_snapshot( int $product_id ): array {
		$platforms = array();

		$amazon_asin = MMI_APS_Product_Meta::get_asin( $product_id );
		if ( '' !== $amazon_asin ) {
			$amazon_available = ! MMI_APS_Product_Meta::is_unavailable( $product_id );
			$amazon_price = MMI_APS_Product_Meta::get_stored_amazon_price( $product_id );

			$platforms['amazon'] = array(
				'configured'        => true,
				'price'             => $amazon_price,
				'show_price'        => null !== $amazon_price && $amazon_available,
				'availability'      => $amazon_available ? 'available' : 'unavailable',
				'availability_label' => $amazon_available
					? __( 'In Stock', 'mmi-amazon-price-sync' )
					: MMI_APS_Product_Meta::get_availability_label( 'unavailable' ),
				'buy_url'           => MMI_APS_Affiliate::get_affiliate_url( $product_id ),
			);
		}

		$flipkart_url = MMI_APS_Flipkart_Product_Meta::get_product_url( $product_id );
		if ( '' !== $flipkart_url ) {
			$fk_status = MMI_APS_Flipkart_Product_Meta::get_availability_status( $product_id );
			$fk_price = MMI_APS_Flipkart_Product_Meta::get_stored_flipkart_price( $product_id );

			$platforms['flipkart'] = array(
				'configured'         => true,
				'price'              => $fk_price,
				'show_price'         => null !== $fk_price && 'in_stock' === $fk_status,
				'availability'       => $fk_status,
				'availability_label' => MMI_APS_Flipkart_Product_Meta::get_availability_label( $fk_status ),
				'buy_url'            => MMI_APS_Flipkart_Product_Meta::get_affiliate_url( $product_id ),
			);
		}

		return array(
			'platforms' => $platforms,
		);
	}

	/**
	 * @param int $product_id Product ID.
	 */
	private static function is_amazon_meta_fresh( int $product_id ): bool {
		$updated = (int) get_post_meta( $product_id, MMI_APS_Plugin::META_LAST_UPDATED, true );
		if ( $updated <= 0 ) {
			return false;
		}

		$max_age = MMI_APS_Settings::get_price_max_age_seconds();
		return ( time() - $updated ) <= $max_age;
	}

	private static function is_product_detail_page(): bool {
		if ( is_admin() || is_cart() || is_checkout() ) {
			return false;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		return is_singular( array( 'devices', 'product' ) );
	}
}