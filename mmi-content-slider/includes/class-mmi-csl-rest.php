<?php
/**
 * REST API on news site: linked articles for a devices product ID.
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Rest {

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		if ( ! MMI_CSL_Sites::is_news_site() ) {
			return;
		}

		register_rest_route(
			'mmi-csl/v1',
			'/news-by-product/(?P<product_id>\d+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'news_by_product' ),
				'args'                => array(
					'product_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'per_page'   => array(
						'type'              => 'integer',
						'default'           => 12,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public static function news_by_product( WP_REST_Request $request ): WP_REST_Response {
		$product_id = (int) $request['product_id'];
		$limit      = max( 1, min( 30, (int) $request->get_param( 'per_page' ) ) );

		$ids = MMI_CSL_Sites::find_news_post_ids_for_product( $product_id, $limit );

		$posts = array();
		foreach ( $ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}
			$posts[] = array(
				'id'    => (int) $post->ID,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'date'  => get_the_date( 'c', $post ),
				'image' => get_the_post_thumbnail_url( $post, 'medium' ),
			);
		}

		return new WP_REST_Response(
			array(
				'product_id' => $product_id,
				'posts'      => $posts,
			),
			200
		);
	}
}
