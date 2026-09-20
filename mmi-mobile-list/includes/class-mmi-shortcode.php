<?php
/**
 * [mmi_mobile_list] shortcode.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Shortcode {

	/**
	 * Register shortcode and assets.
	 */
	public static function register() {
		add_shortcode( 'mmi_mobile_list', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register stylesheet (enqueued when shortcode runs).
	 */
	public static function register_assets() {
		wp_register_style(
			'mmi-mobile-list',
			MMI_ML_URL . 'assets/css/frontend.css',
			array(),
			MMI_ML_VERSION
		);
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '';
		}

		wp_enqueue_style( 'mmi-mobile-list' );

		$atts = shortcode_atts(
			array(
				'min_price' => '',
				'max_price' => '',
				'bucket'    => '',
				'category'  => '',
				'brand'     => '',
				'exact'     => '',
				'per_page'  => '20',
			),
			$atts,
			'mmi_mobile_list'
		);

		$category = sanitize_title( $atts['category'] );
		$brand    = sanitize_title( $atts['brand'] );

		$price_min = null;
		$price_max = null;

		$bucket_range = MMI_ML_Query::resolve_bucket( $atts['bucket'] );
		if ( $bucket_range ) {
			$price_min = $bucket_range['min'];
			$price_max = $bucket_range['max'];
		} else {
			$range = MMI_ML_Query::resolve_price_range(
				$atts['min_price'],
				$atts['max_price'],
				$atts['exact']
			);
			if ( $range ) {
				$price_min = $range['min'];
				$price_max = $range['max'];
			}
		}

		$page = isset( $_GET['mmi_page'] ) ? max( 1, intval( wp_unslash( $_GET['mmi_page'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = MMI_ML_Query::get_products(
			array(
				'price_min' => $price_min,
				'price_max' => $price_max,
				'category'  => $category,
				'brand'     => $brand,
				'per_page'  => $atts['per_page'],
				'page'      => $page,
			)
		);

		ob_start();

		if ( empty( $result['products'] ) ) {
			echo '<div class="mmi-no-products">';
			esc_html_e( 'No mobile phones found in this price range.', 'mmi-mobile-list' );
			echo '</div>';
			return ob_get_clean();
		}

		echo '<div class="mmi-mobile-list">';

		foreach ( $result['products'] as $product ) {
			if ( ! is_a( $product, 'WC_Product' ) ) {
				continue;
			}

			$list_price = $product->get_price();
			if ( '' === $list_price ) {
				continue;
			}

			$list_price_f = (float) $list_price;

			// Safety net: bucket boundaries (wc_get_products should already filter).
			if ( null !== $price_min && $list_price_f < (float) $price_min ) {
				continue;
			}
			if ( null !== $price_max && $list_price_f > (float) $price_max ) {
				continue;
			}

			$product_id = $product->get_id();
			$permalink  = get_permalink( $product_id );
			$title      = $product->get_name();
			$fields     = MMI_ML_Specs::card_fields( $product );

			$image = get_the_post_thumbnail(
				$product_id,
				'medium',
				array(
					'class'   => 'mmi-product-image',
					'loading' => 'lazy',
				)
			);

			$display_price = wc_price(
				$list_price,
				array( 'decimals' => 0 )
			);

			echo '<div class="mmi-mobile-card">';

			echo '<div class="mmi-product-image-wrap">';
			echo '<a href="' . esc_url( $permalink ) . '">';
			if ( $image ) {
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<div class="mmi-no-image">' . esc_html__( 'No Image', 'mmi-mobile-list' ) . '</div>';
			}
			echo '</a>';
			echo '</div>';

			echo '<div class="mmi-product-info">';
			echo '<h3 class="mmi-product-title"><a href="' . esc_url( $permalink ) . '">';
			echo esc_html( $title );
			echo '</a></h3>';
			echo '<div class="mmi-product-specs">';
			echo MMI_ML_Specs::render_row( 'fas fa-mobile-alt', $fields['display'] );
			echo MMI_ML_Specs::render_row( 'fas fa-microchip', $fields['processor'] );
			echo MMI_ML_Specs::render_row( 'fas fa-memory', $fields['ram_storage'] );
			echo MMI_ML_Specs::render_row( 'fas fa-camera', $fields['rear_camera'] );
			echo MMI_ML_Specs::render_row( 'fas fa-camera-retro', $fields['front_camera'] );
			echo MMI_ML_Specs::render_row( 'fas fa-battery-full', $fields['battery'] );
			echo '</div>';
			echo '</div>';

			echo '<div class="mmi-product-action">';
			echo '<div class="mmi-product-price">';
			echo wp_kses_post( $display_price );
			echo '</div>';
			echo '<a class="mmi-view-details" href="' . esc_url( $permalink ) . '">';
			esc_html_e( 'View Details', 'mmi-mobile-list' );
			echo '</a>';
			echo '</div>';

			echo '</div>';
		}

		echo '</div>';

		$pagination = paginate_links(
			array(
				'base'      => add_query_arg( 'mmi_page', '%#%' ),
				'format'    => '',
				'current'   => $page,
				'total'     => max( 1, $result['pages'] ),
				'type'      => 'list',
				'prev_text' => '← ' . __( 'Previous', 'mmi-mobile-list' ),
				'next_text' => __( 'Next', 'mmi-mobile-list' ) . ' →',
			)
		);

		if ( $pagination ) {
			echo '<div class="mmi-pagination">';
			echo wp_kses_post( $pagination );
			echo '</div>';
		}

		return ob_get_clean();
	}
}
