<?php
/**
 * 91mobiles-style PRICES tab — variant cards with Amazon rows.
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
			return self::render_variants_setup_notice( $product );
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
					<?php
					$variant_label   = (string) ( $item['variant_label'] ?? '' );
					$platform_count  = count( $item['platforms'] );
					$variant_in_head = '' !== $variant_label && $platform_count > 1;
					?>
					<div class="mmi-91-prices__card">
						<?php if ( $variant_in_head ) : ?>
							<div class="mmi-91-prices__variant">
								<span class="mmi-91-prices__variant-icon" aria-hidden="true"></span>
								<span class="mmi-91-prices__variant-label"><?php echo esc_html( $variant_label ); ?></span>
							</div>
						<?php endif; ?>
						<div class="mmi-91-prices__rows">
							<?php foreach ( $item['platforms'] as $platform_key => $row ) : ?>
								<?php
								$row_variant = $variant_in_head ? '' : $variant_label;
								echo self::render_store_row( $platform_key, $row, $row_variant ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
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
			$variations = array();
			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation instanceof WC_Product_Variation ) {
					continue;
				}

				if ( '' === MMI_APS_Product_Meta::get_asin( $variation->get_id() ) ) {
					continue;
				}

				$variations[] = $variation;
			}

			usort( $variations, array( __CLASS__, 'sort_variations' ) );

			foreach ( $variations as $variation ) {
				$platforms = self::platform_rows_for_id( $variation->get_id() );
				if ( empty( $platforms ) ) {
					continue;
				}

				$label = self::get_variation_label( $variation );
				if ( '' === $label ) {
					$label = self::get_product_listing_label( $variation );
				}

				$items[] = array(
					'variant_label' => $label,
					'platforms'     => $platforms,
				);
			}
		} else {
			$platforms = self::platform_rows_for_id( $product->get_id() );
			if ( ! empty( $platforms ) ) {
				$items[] = array(
					'variant_label' => self::get_product_listing_label( $product ),
					'platforms'     => $platforms,
				);
			}
		}

		return $items;
	}

	/**
	 * Variant label for simple products (title / attributes), e.g. "4 GB + 128 GB".
	 *
	 * @param WC_Product $product Product.
	 */
	public static function get_product_listing_label( WC_Product $product ): string {
		if ( $product instanceof WC_Product_Variation ) {
			return self::get_variation_label( $product );
		}

		$ram_keys     = array( 'pa_ram', 'ram' );
		$storage_keys = array( 'pa_internal-memory', 'pa_storage', 'pa_memory', 'storage', 'internal-memory' );
		$size_keys    = array( 'pa_size', 'size' );

		$ram     = '';
		$storage = '';
		$size    = '';

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute ) {
				continue;
			}
			$name = $attribute->get_name();
			$vals = $attribute->get_options();
			if ( empty( $vals ) ) {
				continue;
			}
			$raw = $vals[0];
			$val = self::resolve_attribute_term_name( $name, $raw );
			if ( in_array( $name, $ram_keys, true ) || substr( $name, -3 ) === 'ram' ) {
				$ram = $val;
			}
			if ( in_array( $name, $storage_keys, true ) || false !== strpos( $name, 'memory' ) || false !== strpos( $name, 'storage' ) ) {
				$storage = $val;
			}
			if ( in_array( $name, $size_keys, true ) ) {
				$size = $val;
			}
		}

		$parts = array();
		if ( ! empty( $ram ) ) {
			$parts[] = self::format_capacity_part( (string) $ram );
		}
		if ( ! empty( $storage ) ) {
			$parts[] = self::format_capacity_part( (string) $storage );
		}
		if ( ! empty( $parts ) ) {
			return implode( ' + ', $parts );
		}
		if ( ! empty( $size ) ) {
			return self::normalize_combined_size_label( (string) $size );
		}

		$from_title = self::extract_capacity_parts( $product->get_name() );
		if ( ! empty( $from_title ) ) {
			return implode( ' + ', $from_title );
		}

		return '';
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

		return $rows;
	}

	/**
	 * @param int $product_id Variation or product ID.
	 * @return array<string,mixed>
	 */
	private static function build_amazon_row( int $product_id ): array {
		$status  = MMI_APS_Product_Meta::get_availability_status( $product_id );
		$price   = MMI_APS_Product_Meta::get_stored_amazon_price( $product_id );
		$buy_url = MMI_APS_Affiliate::get_affiliate_url( $product_id );

		return array(
			'name'          => 'Amazon',
			'price'         => $price,
			'price_html'    => null !== $price ? MMI_APS_Product_Meta::format_inr( $price ) : '',
			'buy_url'       => $buy_url,
			'buy_label'     => __( 'Go To Store', 'mmi-amazon-price-sync' ),
			'availability'  => $status,
			'show_price'    => null !== $price,
			'status_label'  => MMI_APS_Product_Meta::get_availability_label( $status ),
		);
	}

	/**
	 * @param string               $platform_key  amazon|flipkart.
	 * @param array<string,mixed>  $row           Row data.
	 * @param string               $variant_label Variant (91mobiles: logo → variant → price → Go To Store).
	 */
	private static function render_store_row( string $platform_key, array $row, string $variant_label = '' ): string {
		ob_start();
		?>
		<div class="mmi-91-prices__row mmi-91-prices__row--<?php echo esc_attr( $platform_key ); ?><?php echo '' !== $variant_label ? ' mmi-91-prices__row--has-variant' : ''; ?>">
			<div class="mmi-91-prices__store">
				<span class="mmi-91-prices__logo mmi-91-prices__logo--<?php echo esc_attr( $platform_key ); ?>" aria-hidden="true"></span>
				<span class="mmi-91-prices__store-name"><?php echo esc_html( (string) $row['name'] ); ?></span>
			</div>
			<?php if ( '' !== $variant_label ) : ?>
				<div class="mmi-91-prices__variant-inline">
					<span class="mmi-91-prices__variant-icon" aria-hidden="true"></span>
					<span class="mmi-91-prices__variant-label"><?php echo esc_html( $variant_label ); ?></span>
				</div>
			<?php endif; ?>
			<div class="mmi-91-prices__price-wrap">
				<?php if ( ! empty( $row['show_price'] ) && ! empty( $row['price_html'] ) ) : ?>
					<span class="mmi-91-prices__price"><?php echo esc_html( (string) $row['price_html'] ); ?></span>
					<?php if ( 'out_of_stock' === ( $row['availability'] ?? '' ) ) : ?>
						<span class="mmi-91-prices__status mmi-91-prices__status--oos"><?php echo esc_html( (string) ( $row['status_label'] ?? '' ) ); ?></span>
					<?php endif; ?>
				<?php elseif ( ! empty( $row['status_label'] ) ) : ?>
					<span class="mmi-91-prices__status"><?php echo esc_html( (string) $row['status_label'] ); ?></span>
				<?php else : ?>
					<span class="mmi-91-prices__status"><?php esc_html_e( 'Price updating…', 'mmi-amazon-price-sync' ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( 'in_stock' === ( $row['availability'] ?? '' ) && ! empty( $row['buy_url'] ) ) : ?>
				<a class="mmi-91-prices__go" href="<?php echo esc_url( (string) $row['buy_url'] ); ?>" target="_blank" rel="nofollow sponsored noopener">
					<?php echo esc_html( (string) ( $row['buy_label'] ?? __( 'Go To Store', 'mmi-amazon-price-sync' ) ) ); ?>
				</a>
			<?php elseif ( 'out_of_stock' === ( $row['availability'] ?? '' ) ) : ?>
				<span class="mmi-91-prices__oos"><?php esc_html_e( 'Out Of Stock', 'mmi-amazon-price-sync' ); ?></span>
			<?php elseif ( ! empty( $row['buy_url'] ) && 'unavailable' !== ( $row['availability'] ?? '' ) ) : ?>
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
		$ram = self::read_variation_attr( $variation, array( 'pa_ram', 'ram', 'attribute_pa_ram', 'attribute_ram' ) );
		$storage = self::read_variation_attr(
			$variation,
			array( 'pa_internal-memory', 'pa_storage', 'pa_memory', 'storage', 'internal-memory', 'attribute_pa_internal-memory', 'attribute_pa_storage' )
		);
		$size = self::read_variation_attr( $variation, array( 'pa_size', 'size', 'attribute_pa_size', 'attribute_size' ) );

		$parts = array();
		if ( '' !== $ram ) {
			$parts[] = self::format_capacity_part( $ram );
		}
		if ( '' !== $storage ) {
			$parts[] = self::format_capacity_part( $storage );
		}

		$parts = array_filter( $parts );
		if ( ! empty( $parts ) ) {
			return implode( ' + ', $parts );
		}

		if ( '' !== $size ) {
			$size_label = self::normalize_combined_size_label( $size );
			if ( '' !== $size_label ) {
				return $size_label;
			}
		}

		$formatted = wc_get_formatted_variation( $variation, true, false );
		if ( is_string( $formatted ) && '' !== $formatted ) {
			return self::normalize_combined_size_label( wp_strip_all_tags( $formatted ) );
		}

		return '';
	}

	/**
	 * @param WC_Product_Variation $variation Variation.
	 * @param string[]             $keys      Attribute slugs.
	 */
	private static function read_variation_attr( WC_Product_Variation $variation, array $keys ): string {
		foreach ( $keys as $key ) {
			$val = trim( (string) $variation->get_attribute( $key ) );
			if ( '' === $val ) {
				$attrs = $variation->get_attributes();
				$tax   = self::attribute_key_to_taxonomy( $key );
				if ( isset( $attrs[ $tax ] ) ) {
					$val = trim( (string) $attrs[ $tax ] );
				}
			}
			if ( '' !== $val ) {
				$resolved = self::resolve_attribute_term_name( self::attribute_key_to_taxonomy( $key ), $val );
				if ( '' !== $resolved ) {
					return $resolved;
				}
			}
		}
		return '';
	}

	/**
	 * Turn WooCommerce term IDs / slugs into readable labels (e.g. 11514 → "4 GB RAM").
	 *
	 * @param string     $attribute_name Attribute or taxonomy name.
	 * @param string|int $raw            Slug, name, or term ID.
	 */
	private static function resolve_attribute_term_name( string $attribute_name, $raw ): string {
		$value = trim( is_scalar( $raw ) ? (string) $raw : '' );
		if ( '' === $value ) {
			return '';
		}

		$taxonomy = self::attribute_key_to_taxonomy( $attribute_name );

		if ( taxonomy_exists( $taxonomy ) ) {
			$term = null;
			if ( ctype_digit( $value ) ) {
				$term = get_term( (int) $value, $taxonomy );
			}
			if ( ( ! $term || is_wp_error( $term ) ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
			}
			if ( ( ! $term || is_wp_error( $term ) ) ) {
				$term = get_term_by( 'name', $value, $taxonomy );
			}
			if ( $term && ! is_wp_error( $term ) ) {
				return $term->name;
			}
		}

		// Never surface bare numeric term IDs in the storefront.
		if ( ctype_digit( $value ) && (int) $value > 32 ) {
			return '';
		}

		return $value;
	}

	private static function attribute_key_to_taxonomy( string $key ): string {
		if ( 0 === strpos( $key, 'attribute_' ) ) {
			$key = substr( $key, 10 );
		}
		if ( 0 === strpos( $key, 'pa_' ) ) {
			return $key;
		}

		return wc_sanitize_taxonomy_name( $key );
	}

	private static function normalize_combined_size_label( string $value ): string {
		$parsed = self::extract_capacity_parts( $value );
		if ( ! empty( $parsed ) ) {
			return implode( ' + ', $parsed );
		}

		$value = preg_replace( '/\s*\/\s*/', ' + ', $value ) ?? $value;
		$value = preg_replace( '/\s*,\s*/', ' + ', $value ) ?? $value;
		$value = preg_replace( '/\s+/', ' ', $value ) ?? $value;

		$chunks = preg_split( '/\s+\+\s+/', $value ) ?: array();
		$parts  = array();
		foreach ( $chunks as $chunk ) {
			$chunk = trim( (string) $chunk );
			if ( '' === $chunk ) {
				continue;
			}
			$parts[] = self::format_capacity_part( $chunk );
		}

		return ! empty( $parts ) ? implode( ' + ', $parts ) : trim( $value );
	}

	/**
	 * 91mobiles style: "12 GB", "256 GB" (space before GB/TB).
	 */
	public static function format_capacity_part( string $value ): string {
		$value = trim( preg_replace( '/\b(RAM|Storage|INTERNAL MEMORY|INTERNAL-MEMORY|ROM)\b/i', '', $value ) ?? $value );
		$value = preg_replace( '/\s+/', ' ', $value ) ?? $value;

		if ( ctype_digit( $value ) ) {
			return '';
		}

		if ( preg_match( '/^(\d+)\s*(GB|TB)$/i', $value, $m ) ) {
			return $m[1] . ' ' . strtoupper( $m[2] );
		}
		if ( preg_match( '/^(\d+)(GB|TB)$/i', $value, $m ) ) {
			return $m[1] . ' ' . strtoupper( $m[2] );
		}
		if ( preg_match( '/(\d+)\s*(GB|TB)/i', $value, $m ) ) {
			return $m[1] . ' ' . strtoupper( $m[2] );
		}
		// Slugs like "4-gb" or "128gb-internal".
		if ( preg_match( '/(\d+)[\s_-]*(gb|tb)/i', $value, $m ) ) {
			return $m[1] . ' ' . strtoupper( $m[2] );
		}

		return '';
	}

	/**
	 * @return string[]
	 */
	private static function extract_capacity_parts( string $value ): array {
		if ( ! preg_match_all( '/(\d+)\s*(GB|TB)/i', $value, $matches, PREG_SET_ORDER ) ) {
			return array();
		}

		$parts = array();
		foreach ( $matches as $m ) {
			$parts[] = $m[1] . ' ' . strtoupper( $m[2] );
		}

		return $parts;
	}

	/**
	 * Sort variations by RAM then storage (numeric when possible).
	 *
	 * @param WC_Product_Variation $a Variation A.
	 * @param WC_Product_Variation $b Variation B.
	 */
	public static function sort_variations( WC_Product_Variation $a, WC_Product_Variation $b ): int {
		$ram_a = (int) preg_replace( '/\D+/', '', $a->get_attribute( 'pa_ram' ) . $a->get_attribute( 'ram' ) );
		$ram_b = (int) preg_replace( '/\D+/', '', $b->get_attribute( 'pa_ram' ) . $b->get_attribute( 'ram' ) );
		if ( $ram_a !== $ram_b ) {
			return $ram_a <=> $ram_b;
		}

		$sto_a = (int) preg_replace( '/\D+/', '', $a->get_attribute( 'pa_internal-memory' ) . $a->get_attribute( 'pa_storage' ) );
		$sto_b = (int) preg_replace( '/\D+/', '', $b->get_attribute( 'pa_internal-memory' ) . $b->get_attribute( 'pa_storage' ) );

		return $sto_a <=> $sto_b;
	}

	/**
	 * Help text when variable product has no per-variant ASIN prices yet.
	 *
	 * @param WC_Product $product Product.
	 */
	private static function render_variants_setup_notice( WC_Product $product ): string {
		if ( ! $product->is_type( 'variable' ) ) {
			return '';
		}

		if ( count( $product->get_children() ) <= 0 ) {
			return '';
		}

		$title = sprintf(
			/* translators: %s: product name */
			__( '%s Price In India', 'mmi-amazon-price-sync' ),
			$product->get_name()
		);

		ob_start();
		?>
		<div class="mmi-91-prices mmi-91-prices--setup-notice">
			<h2 class="mmi-91-prices__title"><?php echo esc_html( $title ); ?></h2>
			<p><?php esc_html_e( 'Each RAM and storage variant needs its own Amazon ASIN. Once synced, every option appears here with its price and Go To Store link — like 91mobiles.', 'mmi-amazon-price-sync' ); ?></p>
			<?php if ( current_user_can( 'edit_products' ) ) : ?>
				<p><em><?php esc_html_e( 'Admin: Variations tab → enter ASIN per variant → Fetch Amazon (or Fetch all variants on Amazon Price tab).', 'mmi-amazon-price-sync' ); ?></em></p>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function is_eligible_page(): bool {
		if ( is_admin() ) {
			return false;
		}

		return is_singular( array( 'product', 'devices' ) );
	}
}