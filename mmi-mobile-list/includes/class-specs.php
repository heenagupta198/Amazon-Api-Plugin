<?php
/**
 * Read highlight specs from WooCommerce product attributes (pa_*).
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Specs {

	/**
	 * @param WC_Product|int $product Product object or ID.
	 * @return array{processor:string, memory:string, rear:string, front:string, battery:string, display:string, release_date:string, is_upcoming:bool}
	 */
	public static function get_card_data( $product ): array {
		$product = self::resolve_product( $product );
		if ( ! $product ) {
			return self::empty_card();
		}

		$processor = self::attr_value( $product, 'pa_processor' );
		if ( '' === $processor ) {
			$processor = self::attr_value( $product, 'pa_chipset' );
		}

		$ram      = self::attr_value( $product, 'pa_ram' );
		$storage  = self::attr_value( $product, 'pa_internal-memory' );
		if ( '' === $storage ) {
			$storage = self::attr_value( $product, 'pa_storage' );
		}
		$memory = self::join_specs( $ram, $storage, ' | ' );

		$rear  = self::attr_value( $product, 'pa_rear-camera' );
		$front = self::attr_value( $product, 'pa_front-camera' );

		$battery = self::attr_value( $product, 'pa_battery' );
		if ( '' === $battery ) {
			$battery = self::attr_value( $product, 'pa_capacity' );
		}
		$charging = self::attr_value( $product, 'pa_quick-charging' );
		if ( '' === $charging ) {
			$charging = self::attr_value( $product, 'pa_charging' );
		}
		$battery_line = self::join_specs( $battery, $charging, ' | ' );

		$display_size = self::attr_value( $product, 'pa_display' );
		if ( '' === $display_size ) {
			$display_size = self::attr_value( $product, 'pa_screen-size' );
		}
		$display_type = self::attr_value( $product, 'pa_display-type' );
		$display_line = self::join_specs( $display_size, $display_type, ' | ' );

		$release = '';
		if ( MMI_MLL_Settings::show_release_date() ) {
			$release = self::format_release_date( $product );
		}

		$price_html = $product->get_price_html();
		$is_upcoming = ( '' === $product->get_price() && '' === $price_html ) || has_term( 'upcoming-mobiles', 'product_cat', $product->get_id() );

		$data = array(
			'processor'    => $processor,
			'memory'       => $memory,
			'rear'         => $rear,
			'front'        => $front,
			'battery'      => $battery_line,
			'display'      => $display_line,
			'release_date' => $release,
			'is_upcoming'  => $is_upcoming,
		);

		return apply_filters( 'mmi_mll_card_specs', $data, $product );
	}

	/**
	 * @param WC_Product|int $product Product.
	 */
	private static function resolve_product( $product ): ?WC_Product {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( (int) $product );
		}
		return $product instanceof WC_Product ? $product : null;
	}

	private static function empty_card(): array {
		return array(
			'processor'    => '',
			'memory'       => '',
			'rear'         => '',
			'front'        => '',
			'battery'      => '',
			'display'      => '',
			'release_date' => '',
			'is_upcoming'  => false,
		);
	}

	private static function attr_value( WC_Product $product, string $taxonomy ): string {
		$value = $product->get_attribute( $taxonomy );
		if ( is_string( $value ) ) {
			$value = trim( $value );
			if ( '' !== $value && '-' !== $value ) {
				return $value;
			}
		}

		$attributes = $product->get_attributes();
		if ( empty( $attributes[ $taxonomy ] ) ) {
			return '';
		}

		$attr = $attributes[ $taxonomy ];
		if ( ! $attr->is_taxonomy() ) {
			$options = $attr->get_options();
			if ( is_array( $options ) && ! empty( $options[0] ) ) {
				return trim( (string) $options[0] );
			}
			return '';
		}

		$terms = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}
		$terms = array_filter(
			array_map(
				static function ( $name ) {
					$name = trim( (string) $name );
					return ( '' === $name || '-' === $name ) ? '' : $name;
				},
				$terms
			)
		);
		return implode( ' ', $terms );
	}

	private static function join_specs( string $a, string $b, string $sep ): string {
		$a = trim( $a );
		$b = trim( $b );
		if ( '' === $a ) {
			return $b;
		}
		if ( '' === $b || $a === $b ) {
			return $a;
		}
		return $a . $sep . $b;
	}

	private static function format_release_date( WC_Product $product ): string {
		$launch = self::attr_value( $product, 'pa_launch-date' );
		if ( '' !== $launch ) {
			$launch = preg_replace( '/\s*\(Official\)\s*$/i', '', $launch );
			$launch = trim( (string) $launch );
			if ( '' !== $launch ) {
				$ts = strtotime( $launch );
				if ( $ts ) {
					return gmdate( 'j M, Y', $ts );
				}
				return $launch;
			}
		}

		$post = get_post( $product->get_id() );
		if ( $post && ! empty( $post->post_date ) ) {
			$ts = strtotime( $post->post_date );
			if ( $ts ) {
				return gmdate( 'j M, Y', $ts );
			}
		}

		return '';
	}
}