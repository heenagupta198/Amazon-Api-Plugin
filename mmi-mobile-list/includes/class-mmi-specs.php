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
	 * Inline SVG icons (no Font Awesome dependency).
	 *
	 * @param string $name Icon key.
	 * @return string
	 */
	public static function icon_svg( $name ) {
		$icons = array(
			'chip'    => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="5" y="5" width="14" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3" stroke="currentColor" stroke-width="1.6"/></svg>',
			'memory'  => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="4" y="7" width="16" height="10" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 7V5M12 7V5M16 7V5M8 17v2M12 17v2M16 17v2" stroke="currentColor" stroke-width="1.6"/></svg>',
			'camera'  => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M4 8h4l2-2h4l2 2h4v10H4V8z" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="13" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
			'selfie'  => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="7" y="3" width="10" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="14" r="2" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
			'battery' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="3" y="8" width="16" height="8" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M21 11v2" stroke="currentColor" stroke-width="1.6"/><path d="M7 12h6" stroke="currentColor" stroke-width="1.6"/></svg>',
			'display' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="5" y="4" width="14" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 20h4" stroke="currentColor" stroke-width="1.6"/></svg>',
		);

		return isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['chip'];
	}

	/**
	 * Render one spec row.
	 *
	 * @param string $icon_key Icon key for SVG.
	 * @param string $value    Spec text.
	 * @return string
	 */
	public static function render_row( $icon_key, $value ) {
		if ( '' === $value ) {
			return '';
		}

		return sprintf(
			'<div class="mmi-product-spec"><span class="mmi-spec-icon">%s</span><span class="mmi-spec-text">%s</span></div>',
			self::icon_svg( $icon_key ),
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
