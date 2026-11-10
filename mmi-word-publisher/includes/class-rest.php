<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MMI_Word_Publisher_REST {

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'maybe_send_cors' ), 10, 4 );
	}

	public static function register_routes(): void {
		register_rest_route(
			'mmi-word-publisher/v1',
			'/draft',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_draft' ),
				'permission_callback' => array( __CLASS__, 'can_publish_draft' ),
				'args'                => array(
					'title'   => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'content' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'wp_kses_post',
					),
				),
			)
		);

		register_rest_route(
			'mmi-word-publisher/v1',
			'/ping',
			array(
				'methods'             => 'GET',
				'callback'            => static function () {
					return array(
						'ok'      => true,
						'site'    => get_bloginfo( 'name' ),
						'version' => MMI_WORD_PUBLISHER_VERSION,
					);
				},
				'permission_callback' => array( __CLASS__, 'can_publish_draft' ),
			)
		);
	}

	/**
	 * Application Password (or cookie) auth; user must be able to create posts.
	 */
	public static function can_publish_draft(): bool {
		return is_user_logged_in() && current_user_can( 'edit_posts' );
	}

	public static function create_draft( WP_REST_Request $request ) {
		$title   = $request->get_param( 'title' );
		$content = $request->get_param( 'content' );

		if ( '' === trim( $title ) ) {
			return new WP_Error( 'mmi_empty_title', __( 'Title is required.', 'mmi-word-publisher' ), array( 'status' => 400 ) );
		}

		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			return new WP_Error( 'mmi_empty_content', __( 'Article body is empty.', 'mmi-word-publisher' ), array( 'status' => 400 ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => 'post',
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_mmi_word_publisher_source', 'word-add-in' );
		update_post_meta( $post_id, '_mmi_word_publisher_at', gmdate( 'c' ) );

		return new WP_REST_Response(
			array(
				'id'       => $post_id,
				'status'   => 'draft',
				'edit_url' => get_edit_post_link( $post_id, 'raw' ),
				'message'  => __( 'Draft saved. Add images in WordPress before publishing.', 'mmi-word-publisher' ),
			),
			201
		);
	}

	/**
	 * Allow the Office task pane (hosted on this site) to call the REST API from the WebView.
	 */
	public static function maybe_send_cors( $served, $result, $request, $server ) {
		$route = $request->get_route();
		if ( strpos( $route, '/mmi-word-publisher/v1' ) === false ) {
			return $served;
		}

		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		if ( $origin && self::is_allowed_addin_origin( $origin ) ) {
			header( 'Access-Control-Allow-Origin: ' . $origin );
			header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
		}

		if ( 'OPTIONS' === $request->get_method() ) {
			status_header( 204 );
			exit;
		}

		return $served;
	}

	private static function is_allowed_addin_origin( string $origin ): bool {
		$site = wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $origin === $site ) {
			return true;
		}
		$allowed = apply_filters( 'mmi_word_publisher_cors_origins', array() );
		return in_array( $origin, $allowed, true );
	}
}
