<?php
/**
 * Shortcode for PAF / Elementor pages: [mmi_mobile_list]
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Shortcode {

	public static function register(): void {
		add_shortcode( 'mmi_mobile_list', array( __CLASS__, 'render' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
	}

	public static function query_vars( array $vars ): array {
		$vars[] = 'mmi_page';
		return $vars;
	}

	/**
	 * @param array<string,string>|string $atts Shortcode attributes.
	 */
	public static function render( $atts ): string {
		MMI_MLL_Plugin::enqueue_assets();

		$atts = shortcode_atts(
			array(
				'category'     => '',
				'brand'        => '',
				'price_range'  => '',
				'mobile_type'  => '',
				'ids'          => '',
				'per_page'     => (string) MMI_MLL_Settings::per_page_default(),
				'paginate'     => '1',
				'orderby'      => 'date',
				'order'        => 'DESC',
			),
			$atts,
			'mmi_mobile_list'
		);

		$per_page = max( 1, min( 48, (int) $atts['per_page'] ) );
		$paged    = self::current_page();

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
		);

		$tax_query = array();
		if ( '' !== $atts['category'] ) {
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			);
		}
		if ( '' !== $atts['brand'] ) {
			$tax_query[] = array(
				'taxonomy' => 'product_brand',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['brand'] ) ),
			);
		}
		if ( '' !== $atts['price_range'] ) {
			$tax_query[] = array(
				'taxonomy' => 'price_range',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['price_range'] ) ),
			);
		}
		if ( '' !== $atts['mobile_type'] ) {
			$tax_query[] = array(
				'taxonomy' => 'mobile_type',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['mobile_type'] ) ),
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		if ( '' !== $atts['ids'] ) {
			$ids = array_filter( array_map( 'intval', explode( ',', $atts['ids'] ) ) );
			if ( ! empty( $ids ) ) {
				$args['post__in'] = $ids;
				$args['orderby']  = 'post__in';
				unset( $args['paged'] );
			}
		}

		$query    = new WP_Query( $args );
		$products = array();
		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post );
			if ( $product instanceof WC_Product ) {
				$products[] = $product;
			}
		}

		$pagination = '';
		if ( '1' === $atts['paginate'] && $query->max_num_pages > 1 ) {
			$pagination = paginate_links(
				array(
					'base'      => self::pagination_base(),
					'format'    => '?mmi_page=%#%',
					'current'   => $paged,
					'total'     => $query->max_num_pages,
					'type'      => 'list',
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
				)
			);
		}

		wp_reset_postdata();

		return MMI_MLL_Renderer::render_list( $products, array( 'pagination' => (string) $pagination ) );
	}

	private static function current_page(): int {
		$paged = get_query_var( 'mmi_page' );
		if ( empty( $paged ) && isset( $_GET['mmi_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$paged = (int) wp_unslash( $_GET['mmi_page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$paged = max( 1, (int) $paged );
		return $paged;
	}

	private static function pagination_base(): string {
		global $wp;
		$path = '';
		if ( isset( $wp->request ) && is_string( $wp->request ) ) {
			$path = $wp->request;
		}
		$home = user_trailingslashit( home_url( $path ) );
		return trailingslashit( $home ) . '%_%';
	}
}