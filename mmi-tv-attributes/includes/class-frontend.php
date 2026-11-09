<?php
/**
 * Grouped specification table for TV products, matching the mobile specs-wrapper markup.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_TV_Frontend {

	public static function init(): void {
		add_filter( 'wc_get_template', array( __CLASS__, 'maybe_swap_additional_information' ), 20, 5 );
	}

	/**
	 * @param mixed $template
	 * @param mixed $template_name
	 * @param mixed $args
	 * @param mixed $template_path
	 * @param mixed $default_path
	 * @return mixed
	 */
	public static function maybe_swap_additional_information( $template, $template_name, $args, $template_path, $default_path ) {
		unset( $args, $template_path, $default_path );

		if ( 'single-product/product-attributes.php' !== $template_name ) {
			return $template;
		}

		if ( ! self::is_tv_product() ) {
			return $template;
		}

		return MMI_TV_PATH . 'templates/product-attributes.php';
	}

	public static function is_tv_product( $product = null ): bool {
		if ( ! $product && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( get_the_ID() );
		}

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return false;
		}

		$product_id = $product->get_id();
		if ( has_term( MMI_TV_Installer::CAT_SLUG, 'product_cat', $product_id ) ) {
			return true;
		}

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! is_a( $attribute, 'WC_Product_Attribute' ) ) {
				continue;
			}
			$name = $attribute->get_name();
			if ( 0 === strpos( $name, 'pa_tv-' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<string, array<int, array{label:string, value:string}>>
	 */
	public static function grouped_attributes( WC_Product $product ): array {
		$values_by_slug = array();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! is_a( $attribute, 'WC_Product_Attribute' ) || ! $attribute->get_visible() ) {
				continue;
			}

			$name = $attribute->get_name();
			if ( 0 !== strpos( $name, 'pa_' ) ) {
				continue;
			}

			$slug = substr( $name, 3 );
			if ( $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) );
				$value = implode( ', ', $terms );
			} else {
				$value = implode( ', ', $attribute->get_options() );
			}

			if ( '' === $value ) {
				continue;
			}

			$values_by_slug[ $slug ] = array(
				'label' => wc_attribute_label( $name, $product ),
				'value' => $value,
			);
		}

		$grouped = array();
		foreach ( MMI_TV_Schema::groups() as $group => $slugs ) {
			foreach ( $slugs as $slug ) {
				if ( empty( $values_by_slug[ $slug ] ) ) {
					continue;
				}
				$grouped[ $group ][] = $values_by_slug[ $slug ];
				unset( $values_by_slug[ $slug ] );
			}
		}

		if ( $values_by_slug ) {
			foreach ( $values_by_slug as $row ) {
				$grouped['Other'][] = $row;
			}
		}

		return $grouped;
	}
}
