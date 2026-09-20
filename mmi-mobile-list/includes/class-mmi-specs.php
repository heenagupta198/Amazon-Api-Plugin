<?php
/**
 * Product attribute / spec helpers.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Specs {

	/**
	 * Normalize attribute label for keyword matching.
	 *
	 * @param string $text Raw label.
	 * @return string
	 */
	public static function normalize_key( $text ) {
		$text = strtolower( wp_strip_all_tags( (string) $text ) );
		return preg_replace( '/[^a-z0-9]+/', '', $text );
	}

	/**
	 * All WooCommerce attributes for a product.
	 *
	 * @param WC_Product|null $product Product.
	 * @return array<int, array{label:string,name:string,value:string}>
	 */
	public static function get_product_specs( $product ) {
		$specs = array();

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return $specs;
		}

		$attributes = $product->get_attributes();
		if ( empty( $attributes ) ) {
			return $specs;
		}

		foreach ( $attributes as $attribute_name => $attribute ) {
			$value = $product->get_attribute( $attribute_name );
			if ( '' === $value ) {
				continue;
			}

			$specs[] = array(
				'label' => wc_attribute_label( $attribute_name ),
				'name'  => $attribute_name,
				'value' => trim( wp_strip_all_tags( $value ) ),
			);
		}

		return $specs;
	}

	/**
	 * Find first spec whose label/name contains a keyword.
	 *
	 * @param WC_Product $product      Product.
	 * @param string[]   $keywords     Search terms.
	 * @param string     $value_regex  Optional value pattern.
	 * @return string
	 */
	public static function find_spec( $product, $keywords, $value_regex = '' ) {
		$specs = self::get_product_specs( $product );
		if ( empty( $specs ) ) {
			return '';
		}

		foreach ( $keywords as $keyword ) {
			$needle = self::normalize_key( $keyword );

			foreach ( $specs as $spec ) {
				$haystack = self::normalize_key( $spec['label'] . ' ' . $spec['name'] );
				if ( false === strpos( $haystack, $needle ) ) {
					continue;
				}

				if ( $value_regex && ! preg_match( $value_regex, $spec['value'] ) ) {
					continue;
				}

				return $spec['value'];
			}
		}

		return '';
	}

	/**
	 * Render one spec row.
	 *
	 * @param string $icon  Font Awesome class.
	 * @param string $value Spec text.
	 * @return string
	 */
	public static function render_row( $icon, $value ) {
		if ( '' === $value ) {
			return '';
		}

		return sprintf(
			'<div class="mmi-product-spec"><span class="mmi-spec-icon"><i class="%s" aria-hidden="true"></i></span><span class="mmi-spec-text">%s</span></div>',
			esc_attr( $icon ),
			esc_html( $value )
		);
	}

	/**
	 * Build display strings for a product card.
	 *
	 * @param WC_Product $product Product.
	 * @return array<string, string>
	 */
	public static function card_fields( $product ) {
		$screen = self::find_spec(
			$product,
			array( 'screen size', 'display size', 'screen' ),
			'/\d/'
		);
		$display_type = self::find_spec(
			$product,
			array( 'display type', 'display technology' )
		);

		$display = $screen;
		if ( $display_type && false === stripos( (string) $display, $display_type ) ) {
			$display = $display ? $display . ' | ' . $display_type : $display_type;
		}

		$processor = self::find_spec(
			$product,
			array( 'chipset', 'processor', 'cpu' )
		);

		$ram     = self::find_spec( $product, array( 'ram', 'ram memory' ) );
		$storage = self::find_spec(
			$product,
			array( 'internal memory', 'storage', 'rom' )
		);

		$ram_storage = $ram;
		if ( $storage ) {
			$ram_storage = $ram_storage ? $ram_storage . ' | ' . $storage : $storage;
		}

		$rear  = self::find_spec(
			$product,
			array( 'rear camera', 'primary camera', 'main camera' )
		);
		$front = self::find_spec(
			$product,
			array( 'front camera', 'selfie camera' )
		);

		$battery = self::find_spec(
			$product,
			array( 'battery capacity', 'battery', 'capacity' ),
			'/mah/i'
		);
		$charging = self::find_spec(
			$product,
			array( 'quick charging', 'fast charging' )
		);

		$battery_spec = $battery;
		if ( $charging && false === stripos( (string) $battery_spec, $charging ) ) {
			$battery_spec = $battery_spec ? $battery_spec . ' | ' . $charging : $charging;
		}

		return array(
			'display'      => $display,
			'processor'    => $processor,
			'ram_storage'  => $ram_storage,
			'rear_camera'  => $rear,
			'front_camera' => $front,
			'battery'      => $battery_spec,
		);
	}
}
