<?php
/**
 * Create WooCommerce global attributes, terms, and the TVs product category.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_TV_Installer {

	public const OPTION_INSTALLED = 'mmi_tv_attributes_installed';
	public const CAT_SLUG         = 'tvs';

	/**
	 * @return array{created:int, existing:int, terms:int, category:string, errors:array<int,string>}
	 */
	public static function install(): array {
		$result = array(
			'created'  => 0,
			'existing' => 0,
			'terms'    => 0,
			'category' => '',
			'errors'   => array(),
		);

		if ( ! function_exists( 'wc_create_attribute' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$result['errors'][] = 'WooCommerce is not active.';
			return $result;
		}

		$existing = array();
		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			$existing[ $taxonomy->attribute_name ] = (int) $taxonomy->attribute_id;
		}

		foreach ( MMI_TV_Schema::attributes() as $attr ) {
			$slug     = $attr['slug'];
			$taxonomy = wc_attribute_taxonomy_name( $slug );

			if ( isset( $existing[ $slug ] ) ) {
				++$result['existing'];
			} else {
				$attribute_id = wc_create_attribute(
					array(
						'name'         => $attr['label'],
						'slug'         => $slug,
						'type'         => 'select',
						'order_by'     => $attr['order_by'],
						'has_archives' => $attr['archives'],
					)
				);

				if ( is_wp_error( $attribute_id ) ) {
					$result['errors'][] = $attr['label'] . ': ' . $attribute_id->get_error_message();
					continue;
				}

				++$result['created'];
			}

			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy(
					$taxonomy,
					array( 'product' ),
					array(
						'labels'       => array(
							'name' => $attr['label'],
						),
						'hierarchical' => false,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					)
				);
			}

			$order = 0;
			foreach ( $attr['terms'] as $term_name ) {
				if ( term_exists( $term_name, $taxonomy ) ) {
					continue;
				}
				$inserted = wp_insert_term(
					$term_name,
					$taxonomy,
					array(
						'slug' => sanitize_title( $term_name ),
					)
				);
				if ( is_wp_error( $inserted ) ) {
					$result['errors'][] = $attr['label'] . ' / ' . $term_name . ': ' . $inserted->get_error_message();
					continue;
				}
				if ( 'menu_order' === $attr['order_by'] && isset( $inserted['term_id'] ) ) {
					update_term_meta( (int) $inserted['term_id'], 'order', $order );
				}
				++$order;
				++$result['terms'];
			}
		}

		delete_transient( 'wc_attribute_taxonomies' );
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			wc_get_attribute_taxonomies();
		}

		$result['category'] = self::ensure_category();
		update_option( self::OPTION_INSTALLED, MMI_TV_VERSION, false );

		return $result;
	}

	private static function ensure_category(): string {
		$existing = term_exists( self::CAT_SLUG, 'product_cat' );
		if ( $existing ) {
			return 'existing';
		}

		$created = wp_insert_term(
			'TVs',
			'product_cat',
			array(
				'slug'        => self::CAT_SLUG,
				'description' => 'Smart TVs and LED TVs',
			)
		);

		return is_wp_error( $created ) ? $created->get_error_message() : 'created';
	}
}
