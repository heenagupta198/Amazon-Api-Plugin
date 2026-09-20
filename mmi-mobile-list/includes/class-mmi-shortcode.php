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

		wp_register_script(
			'mmi-mobile-list',
			MMI_ML_URL . 'assets/js/frontend.js',
			array(),
			MMI_ML_VERSION,
			true
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
		wp_enqueue_script( 'mmi-mobile-list' );

		$atts = shortcode_atts(
			array(
				'min_price'      => '',
				'max_price'      => '',
				'bucket'         => '',
				'category'       => '',
				'brand'          => '',
				'exact'          => '',
				'per_page'       => '20',
				'details_anchor' => '',
			),
			$atts,
			'mmi_mobile_list'
		);

		$category = sanitize_title( $atts['category'] );
		$brand    = sanitize_title( $atts['brand'] );
		$anchor   = sanitize_title( $atts['details_anchor'] );

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

		$html_cache_key = MMI_ML_Cache::list_key(
			array(
				'html'           => 1,
				'v'              => MMI_ML_VERSION,
				'price_min'      => $price_min,
				'price_max'      => $price_max,
				'category'       => $category,
				'brand'          => $brand,
				'per_page'       => $atts['per_page'],
				'page'           => $page,
				'details_anchor' => $anchor,
			)
		);

		$cached_html = get_transient( $html_cache_key );
		if ( is_string( $cached_html ) && '' !== $cached_html ) {
			return $cached_html;
		}

		$result = MMI_ML_Query::get_listing(
			array(
				'price_min'      => $price_min,
				'price_max'      => $price_max,
				'category'       => $category,
				'brand'          => $brand,
				'per_page'       => $atts['per_page'],
				'page'           => $page,
				'details_anchor' => $anchor,
			)
		);

		ob_start();

		if ( empty( $result['cards'] ) ) {
			echo '<div class="mmi-no-products">';
			esc_html_e( 'No mobile phones found in this price range.', 'mmi-mobile-list' );
			echo '</div>';
			$html = ob_get_clean();
			set_transient( $html_cache_key, $html, MMI_ML_Cache::ttl() );
			return $html;
		}

		echo '<div class="mmi-mobile-list">';

		$index = 0;
		foreach ( $result['cards'] as $card ) {
			echo MMI_ML_Card::render( $card, $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			++$index;
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

		$html = ob_get_clean();
		set_transient( $html_cache_key, $html, MMI_ML_Cache::ttl() );

		return $html;
	}
}
