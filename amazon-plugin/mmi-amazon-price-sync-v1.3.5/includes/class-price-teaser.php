<?php
/**
 * Summary price strip — ₹ price (In Stock) + See Prices → PRICES tab.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Price_Teaser {

	public static function init(): void {
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render' ), 9 );
		add_action( 'rh_woo_single_product_price', array( __CLASS__, 'render' ), 8 );
	}

	public static function render(): void {
		if ( ! MMI_APS_Settings::show_see_prices_teaser() ) {
			return;
		}

		if ( is_admin() || ! is_singular( array( 'product', 'devices' ) ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		$summary = self::summarize_product( $product );
		if ( null === $summary ) {
			return;
		}

		MMI_APS_Prices_Table::enqueue_assets();

		$status_class = $summary['in_stock'] ? 'in-stock' : 'out-of-stock';
		$status_text  = $summary['in_stock']
			? __( 'In Stock', 'mmi-amazon-price-sync' )
			: __( 'Out of Stock', 'mmi-amazon-price-sync' );
		$tab_href     = MMI_APS_Product_Tabs::get_prices_tab_anchor();
		?>
		<div class="mmi-91-teaser">
			<div class="mmi-91-teaser__main">
				<?php if ( null !== $summary['price'] ) : ?>
					<span class="mmi-91-teaser__price"><?php echo esc_html( MMI_APS_Product_Meta::format_inr( $summary['price'] ) ); ?></span>
				<?php endif; ?>
				<span class="mmi-91-teaser__status mmi-91-teaser__status--<?php echo esc_attr( $status_class ); ?>">
					(<?php echo esc_html( $status_text ); ?>)
				</span>
				<?php if ( $summary['variant_count'] > 0 ) : ?>
					<p class="mmi-91-teaser__stores">
						<?php
						printf(
							/* translators: %d: number of variants with Amazon ASIN */
							esc_html( _n( 'Available in %d variant on Amazon', 'Available in %d variants on Amazon', $summary['variant_count'], 'mmi-amazon-price-sync' ) ),
							(int) $summary['variant_count']
						);
						?>
					</p>
				<?php endif; ?>
			</div>
			<a class="mmi-91-teaser__see-prices mmi-aps-open-prices-tab" href="<?php echo esc_attr( $tab_href ); ?>">
				<?php esc_html_e( 'See Prices', 'mmi-amazon-price-sync' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * @param WC_Product $product Product.
	 * @return array{price:?float,in_stock:bool,variant_count:int}|null
	 */
	private static function summarize_product( WC_Product $product ): ?array {
		$prices    = array();
		$in_stock  = false;
		$variants  = 0;

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				$variation_id = (int) $variation_id;
				if ( '' === MMI_APS_Product_Meta::get_asin( $variation_id ) ) {
					continue;
				}
				++$variants;
				if ( MMI_APS_Product_Meta::is_in_stock( $variation_id ) ) {
					$in_stock = true;
					$p = MMI_APS_Product_Meta::get_stored_amazon_price( $variation_id );
					if ( null !== $p ) {
						$prices[] = $p;
					}
				}
			}
		} else {
			if ( '' === MMI_APS_Product_Meta::get_asin( $product->get_id() ) ) {
				return null;
			}
			$variants = 1;
			$in_stock = MMI_APS_Product_Meta::is_in_stock( $product->get_id() );
			$p        = MMI_APS_Product_Meta::get_stored_amazon_price( $product->get_id() );
			if ( null !== $p ) {
				$prices[] = $p;
			}
		}

		if ( $variants <= 0 ) {
			return null;
		}

		return array(
			'price'         => ! empty( $prices ) ? min( $prices ) : null,
			'in_stock'      => $in_stock,
			'variant_count' => $variants,
		);
	}
}