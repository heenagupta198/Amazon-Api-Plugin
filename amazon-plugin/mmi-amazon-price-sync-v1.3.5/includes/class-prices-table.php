<?php
/**
 * 91mobiles-style PRICES tab — variant cards with Amazon / Flipkart rows.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Prices_Table {

	public static function init(): void {
		add_shortcode( 'mmi_phone_prices', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'mmi_amazon_prices', array( __CLASS__, 'shortcode' ) );

		if ( MMI_APS_Settings::show_prices_table_on_product() ) {
			add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'render_auto' ), 12 );
		}
	}

	/**
	 * Auto output on product / devices single pages (PRICES tab area).
	 */
	public static function render_auto(): void {
		if ( ! self::is_eligible_page() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product ) {
			return;
		}

		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		echo self::render_product( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param array<string,string> $atts Shortcode attributes.
	 */
	public static function shortcode( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'      => 0,
				'title'   => '',
				'subtitle' => '',
			),
			$atts,
			'mmi_phone_prices'
		);

		$product_id = absint( $atts['id'] );
		if ( ! $product_id ) {
			$product_id = get_the_ID();
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		self::enqueue_assets();

		return self::render_product(
			$product,
			$atts['title'] ?: sprintf(
				/* translators: %s: product name */
				__( '%s Price In India', 'mmi-amazon-price-sync' ),
				$product->get_name()
			),
			$atts['subtitle'] ?: __( 'New Phone Prices', 'mmi-amazon-price-sync' )
		);
	}

	public static function enqueue_assets(): void {
		wp_enqueue_style(
			'mmi-aps-prices-table',
			MMI_APS_URL . 'assets/css/prices-table.css',
			array(),
			MMI_APS_VERSION
		);
	}

	/**
	 * @param WC_Product $product  Product.
	 * @param string     $title    Section title.
	 * @param string     $subtitle Subtitle.
	 */
	public static function render_product( WC_Product $product, string $title = '', string $subtitle = '' ): string {
		if ( '' === $title ) {
			$title = sprintf(
				/* translators: %s: product name */
				__( '%s Price In India', 'mmi-amazon-price-sync' ),
				$product->get_name()
			);
		}
		if ( '' === $subtitle ) {
			$subtitle = __( 'New Phone Prices', 'mmi-amazon-price-sync' );
		}

		$items = self::collect_price_items( $product );
		if ( empty( $items ) ) {
			return '';
		}

		self::enqueue_assets();

		ob_start();
		?>
		<div class="mmi-91-prices">
			<h2 class="mmi-91-prices__title"><?php echo esc_html( $title ); ?></h2>
			<h3 class="mmi-91-prices__subtitle">
				<span class="mmi-91-prices__subtitle-bar" aria-hidden="true"></span>
				<?php echo esc_html( $subtitle ); ?>
			</h3>
			<div class="mmi-91-prices__cards">
				<?php foreach ( $items as $item ) : ?>
					<div class="mmi-91-prices__card">
						<?php if ( ! empty( $item['variant_label'] ) ) : ?>
							<div class="mmi-91-prices__variant">
								<span class="mmi-91-prices__variant-icon" aria-hidden="true"></span>
								<span class="mmi-91-prices__variant-label"><?php echo esc_html( $item['variant_label'] ); ?></span>
							</div>
						<?php endif; ?>
						<div class="mmi-91-prices__rows">
							<?php foreach ( $item['platforms'] as $platform_key => $row ) : ?>
								<?php echo self::render_store_row( $platform_key, $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param WC_Product $product Product.
	 * @return array<int,array{variant_label:string,platforms:array<string,array<string,mixed>>}>
	 */
	private static function collect_price_items( WC_Product $product ): array {
		$items = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation instanceof WC_Product_Variation ) {
					continue;
				}

				$platforms = self::platform_rows_for_id( $variation->get_id() );
				if ( empty( $platforms ) ) {
					continue;
				}

				$items[] = array(
					'variant_label' => self::get_variation_label( $variation ),
					'platforms'     => $platforms,
				);
			}
		} else {
			$platforms = self::platform_rows_for_id( $product->get_id() );
			if ( ! empty( $platforms ) ) {
				$items[] = array(
					'variant_label' => '',
					'platforms'     => $platforms,
				);
			}
		}

		return $items;
	}

	/**
	 * @param int $product_id Product or variation ID.
	 * @return array<string,array<string,mixed>>
	 */
	public static function platform_rows_for_id( int $product_id ): array {
		$rows = array();

		$asin = MMI_APS_Product_Meta::get_asin( $product_id );
		if ( '' !== $asin ) {
			$rows['amazon'] = self::build_amazon_row( $product_id );
		}

		$flipkart_url = MMI_APS_Flipkart_Product_Meta::get_product_url( $product_id );
		if ( '' !== $flipkart_url ) {
			$rows['flipkart'] = self::build_flipkart_row( $product_id );
		}

		return $rows;
	}

	/**
	 * @param int $product_id Variation or product ID.
	 * @return array<string,mixed>
	 */
	private static function build_amazon_row( int $product_id ): array {
		$available = ! MMI_APS_Product_Meta::is_unavailable( $product_id );
		$price     = MMI_APS_Product_Meta::get_stored_amazon_price( $product_id );
		$buy_url   = MMI_APS_Affiliate::get_affiliate_url( $product_id );

		return array(
			'name'          => 'Amazon',
			'price'         => $price,
			'price_html'    => null !== $price ? MMI_APS_Product_Meta::format_inr( $price ) : '',
			'buy_url'       => $buy_url,
			'buy_label'     => MMI_APS_Settings::get_affiliate_button_text() ?: __( 'Go To Store', 'mmi-amazon-price-sync' ),
			'availability'  => $available ? 'in_stock' : 'unavailable',
			'show_price'    => $available && null !== $price,
			'is_stale'      => $price && ! MMI_APS_Product_Meta::is_amazon_price_fresh( $product_id ),
		);
	}

	/**
	 * @param int $product_id Variation or product ID.
	 * @return array<string,mixed>
	 */
	private static function build_flipkart_row( int $product_id ): array {
		$status  = MMI_APS_Flipkart_Product_Meta::get_availability_status( $product_id );
		$price   = MMI_APS_Flipkart_Product_Meta::get_stored_flipkart_price( $product_id );
		$buy_url = MMI_APS_Flipkart_Product_Meta::get_affiliate_url( $product_id );

		return array(
			'name'         => 'Flipkart',
			'price'        => $price,
			'price_html'   => null !== $price ? MMI_APS_Product_Meta::format_inr( $price ) : '',
			'buy_url'      => $buy_url,
			'buy_label'    => MMI_APS_Settings::get_flipkart_button_text(),
			'availability' => $status,
			'show_price'   => 'in_stock' === $status && null !== $price,
			'is_stale'     => $price && ! MMI_APS_Flipkart_Product_Meta::is_price_fresh( $product_id ),
		);
	}

	/**
	 * @param string               $platform_key amazon|flipkart.
	 * @param array<string,mixed>  $row          Row data.
	 */
	private static function render_store_row( string $platform_key, array $row ): string {
		ob_start();
		?>
		<div class="mmi-91-prices__row mmi-91-prices__row--<?php echo esc_attr( $platform_key ); ?>">
			<div class="mmi-91-prices__store">
				<span class="mmi-91-prices__logo mmi-91-prices__logo--<?php echo esc_attr( $platform_key ); ?>" aria-hidden="true"></span>
				<span class="mmi-91-prices__store-name"><?php echo esc_html( (string) $row['name'] ); ?></span>
			</div>
			<div class="mmi-91-prices__price-wrap">
				<?php if ( ! empty( $row['show_price'] ) && ! empty( $row['price_html'] ) ) : ?>
					<span class="mmi-91-prices__price"><?php echo esc_html( (string) $row['price_html'] ); ?></span>
				<?php elseif ( 'unavailable' === ( $row['availability'] ?? '' ) || 'out_of_stock' === ( $row['availability'] ?? '' ) ) : ?>
					<span class="mmi-91-prices__status"><?php esc_html_e( 'Currently Unavailable', 'mmi-amazon-price-sync' ); ?></span>
				<?php else : ?>
					<span class="mmi-91-prices__status"><?php esc_html_e( 'Price updating…', 'mmi-amazon-price-sync' ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $row['buy_url'] ) ) : ?>
				<a class="mmi-91-prices__go" href="<?php echo esc_url( (string) $row['buy_url'] ); ?>" target="_blank" rel="nofollow sponsored noopener">
					<?php echo esc_html( (string) ( $row['buy_label'] ?? __( 'Go To Store', 'mmi-amazon-price-sync' ) ) ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param WC_Product_Variation $variation Variation.
	 */
	public static function get_variation_label( WC_Product_Variation $variation ): string {
		$ram = $variation->get_attribute( 'pa_ram' );
		if ( '' === $ram ) {
			$ram = $variation->get_attribute( 'ram' );
		}

		$storage = $variation->get_attribute( 'pa_internal-memory' );
		if ( '' === $storage ) {
			$storage = $variation->get_attribute( 'pa_storage' );
		}
		if ( '' === $storage ) {
			$storage = $variation->get_attribute( 'storage' );
		}

		$parts = array_filter( array( trim( (string) $ram ), trim( (string) $storage ) ) );
		if ( ! empty( $parts ) ) {
			return implode( ' + ', $parts );
		}

		$formatted = wc_get_formatted_variation( $variation, true, false );
		return is_string( $formatted ) ? wp_strip_all_tags( $formatted ) : '';
	}

	private static function is_eligible_page(): bool {
		if ( is_admin() ) {
			return false;
		}

		return is_singular( array( 'product', 'devices' ) );
	}
}