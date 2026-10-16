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
	 * Resolve list mode from shortcode.
	 *
	 * @param array<string, string> $atts Attributes.
	 * @return string priced|upcoming
	 */
	private static function list_mode( $atts ) {
		$list = strtolower( trim( (string) $atts['list'] ) );
		if ( in_array( $list, array( 'upcoming', 'no-price', 'noprice' ), true ) ) {
			return 'upcoming';
		}
		$bucket = sanitize_title( $atts['bucket'] );
		if ( 'upcoming' === $bucket ) {
			return 'upcoming';
		}
		return 'priced';
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		wp_enqueue_style( 'mmi-mobile-list' );
		wp_enqueue_script( 'mmi-mobile-list' );

		$default_per = (string) MMI_ML_Query::default_per_page();

		$atts = shortcode_atts(
			array(
				'min_price'      => '',
				'max_price'      => '',
				'bucket'         => '',
				'list'           => '',
				'category'       => '',
				'brand'          => '',
				'exact'          => '',
				'per_page'       => $default_per,
				'details_anchor' => '',
			),
			$atts,
			'mmi_mobile_list'
		);

		$category   = sanitize_title( $atts['category'] );
		$brand      = sanitize_title( $atts['brand'] );
		$anchor     = sanitize_title( $atts['details_anchor'] );
		$list_mode  = self::list_mode( $atts );
		$per_page   = max( 1, min( 50, (int) $atts['per_page'] ) );

		$price_min = null;
		$price_max = null;

		if ( 'priced' === $list_mode ) {
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
		}

		$page = isset( $_GET['mmi_page'] ) ? max( 1, intval( wp_unslash( $_GET['mmi_page'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$cache_args = array(
			'html'           => 1,
			'v'              => MMI_ML_VERSION,
			'list_mode'      => $list_mode,
			'price_min'      => $price_min,
			'price_max'      => $price_max,
			'category'       => $category,
			'brand'          => $brand,
			'per_page'       => $per_page,
			'page'           => $page,
			'details_anchor' => $anchor,
		);

		$html_cache_key = MMI_ML_Cache::list_key( $cache_args );
		$cached_html    = get_transient( $html_cache_key );
		if ( is_string( $cached_html ) && '' !== $cached_html ) {
			return $cached_html;
		}

		$query_args = array(
			'list_mode'      => $list_mode,
			'price_min'      => $price_min,
			'price_max'      => $price_max,
			'category'       => $category,
			'brand'          => $brand,
			'per_page'       => $per_page,
			'page'           => $page,
			'details_anchor' => $anchor,
		);

		$result = MMI_ML_Query::get_listing( $query_args );

		if ( $page > $result['pages'] && $result['pages'] > 0 ) {
			$page                    = $result['pages'];
			$query_args['page']      = $page;
			$cache_args['page']      = $page;
			$html_cache_key          = MMI_ML_Cache::list_key( $cache_args );
			$result                  = MMI_ML_Query::get_listing( $query_args );
		}

		ob_start();

		if ( empty( $result['cards'] ) ) {
			echo '<div class="mmi-no-products">';
			if ( 'upcoming' === $list_mode ) {
				esc_html_e( 'No upcoming mobiles found.', 'mmi-mobile-list' );
			} else {
				esc_html_e( 'No mobile phones found in this price range.', 'mmi-mobile-list' );
			}
			echo '</div>';
			$html = ob_get_clean();
			if ( 1 === $page ) {
				set_transient( $html_cache_key, $html, MMI_ML_Cache::ttl() );
			}
			return $html;
		}

		echo '<div class="mmi-mobile-list">';

		$index = 0;
		foreach ( $result['cards'] as $card ) {
			echo MMI_ML_Card::render( $card, $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			++$index;
		}

		echo '</div>';

		if ( $result['pages'] > 1 ) {
			$pagination = paginate_links(
				array(
					'base'      => add_query_arg( 'mmi_page', '%#%' ),
					'format'    => '',
					'current'   => $page,
					'total'     => $result['pages'],
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
		}

		$html = ob_get_clean();
		set_transient( $html_cache_key, $html, MMI_ML_Cache::ttl() );

		return $html;
	}
}
