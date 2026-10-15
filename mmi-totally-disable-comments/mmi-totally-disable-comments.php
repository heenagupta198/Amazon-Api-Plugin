<?php
/**
 * Plugin Name: MMI Totally Disable Comments
 * Plugin URI:  https://github.com/yogesh/comapre-Plugin
 * Description: Stops all comment and pingback submission (wp-comments-post.php, REST API, XML-RPC). Hides comment UI. Lightweight — one file, no database writes on each request.
 * Version:     1.0.0
 * Author:      Yogesh
 * Text Domain: mmi-totally-disable-comments
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package MMI_Totally_Disable_Comments
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hard-disable WordPress comments site-wide.
 */
final class MMI_Totally_Disable_Comments {

	/**
	 * Bootstrap hooks.
	 */
	public static function boot(): void {
		// As early as WordPress allows (before most plugins).
		add_action( 'muplugins_loaded', array( self::class, 'block_comment_post_entry' ), 0 );
		add_action( 'plugins_loaded', array( self::class, 'block_comment_post_entry' ), 0 );

		add_filter( 'comments_open', array( self::class, 'comments_closed' ), 99999, 2 );
		add_filter( 'pings_open', array( self::class, 'comments_closed' ), 99999, 2 );
		add_filter( 'comments_array', '__return_empty_array', 99999 );

		add_filter( 'preprocess_comment', array( self::class, 'reject_submission' ), 0 );
		add_filter( 'pre_comment_approved', array( self::class, 'reject_approval' ), 0, 2 );

		add_filter( 'rest_endpoints', array( self::class, 'remove_rest_comment_routes' ) );
		add_filter( 'rest_pre_insert_comment', array( self::class, 'rest_reject' ), 0, 2 );

		add_filter( 'xmlrpc_methods', array( self::class, 'strip_xmlrpc_comment_methods' ) );
		add_filter( 'xmlrpc_enabled', '__return_false' );

		add_action( 'init', array( self::class, 'remove_post_type_comment_support' ), 100 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'dequeue_comment_reply_script' ), 100 );

		add_action( 'admin_menu', array( self::class, 'remove_admin_comments_menu' ) );
		add_action( 'admin_init', array( self::class, 'block_comments_admin_screen' ) );
		add_action( 'wp_dashboard_setup', array( self::class, 'remove_recent_comments_widget' ) );

		add_filter( 'feed_links_show_comments_feed', '__return_false' );

		register_activation_hook( __FILE__, array( self::class, 'on_activate' ) );
	}

	/**
	 * Block direct hits to wp-comments-post.php (common bot target).
	 */
	public static function block_comment_post_entry(): void {
		if ( self::is_comments_post_request() ) {
			self::forbidden_exit();
		}
	}

	/**
	 * @return bool
	 */
	private static function is_comments_post_request(): bool {
		if ( ! empty( $_SERVER['SCRIPT_FILENAME'] ) ) {
			$script = basename( wp_unslash( (string) $_SERVER['SCRIPT_FILENAME'] ) );
			if ( 'wp-comments-post.php' === $script ) {
				return true;
			}
		}

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$uri = wp_unslash( (string) $_SERVER['REQUEST_URI'] );
			if ( false !== strpos( $uri, 'wp-comments-post.php' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Minimal 403 response (no HTML theme load).
	 */
	private static function forbidden_exit(): void {
		if ( ! headers_sent() ) {
			status_header( 403 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=UTF-8' );
		}
		exit( 'Comments are disabled.' );
	}

	/**
	 * @param bool $open    Whether comments are open.
	 * @param int  $post_id Post ID.
	 * @return bool
	 */
	public static function comments_closed( $open, $post_id = 0 ) {
		return false;
	}

	/**
	 * @param array<string, mixed> $commentdata Comment data.
	 * @return array<string, mixed>
	 */
	public static function reject_submission( $commentdata ) {
		self::forbidden_exit();
		return $commentdata;
	}

	/**
	 * @param int|string $approved    Approval status.
	 * @param array      $commentdata Comment data.
	 * @return string
	 */
	public static function reject_approval( $approved, $commentdata ) {
		return 'spam';
	}

	/**
	 * @param array<string, mixed> $endpoints REST routes.
	 * @return array<string, mixed>
	 */
	public static function remove_rest_comment_routes( $endpoints ) {
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/comments' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * @param stdClass        $prepared  Prepared comment.
	 * @param WP_REST_Request $request   Request.
	 * @return WP_Error
	 */
	public static function rest_reject( $prepared, $request ) {
		return new WP_Error(
			'comments_disabled',
			__( 'Comments are disabled on this site.', 'mmi-totally-disable-comments' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * @param array<string, callable> $methods XML-RPC methods.
	 * @return array<string, callable>
	 */
	public static function strip_xmlrpc_comment_methods( $methods ) {
		unset( $methods['wp.newComment'] );
		return $methods;
	}

	/**
	 * Remove comment support from public post types (themes like Newspaper still output forms without this).
	 */
	public static function remove_post_type_comment_support(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	public static function dequeue_comment_reply_script(): void {
		wp_dequeue_script( 'comment-reply' );
	}

	public static function remove_admin_comments_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	public static function block_comments_admin_screen(): void {
		global $pagenow;
		if ( 'edit-comments.php' === $pagenow || 'comment.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	public static function remove_recent_comments_widget(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Close comments on existing content once (fixes “off for new posts only” in Settings → Discussion).
	 */
	public static function on_activate(): void {
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"UPDATE {$wpdb->posts} SET comment_status = 'closed', ping_status = 'closed' WHERE comment_status != 'closed' OR ping_status != 'closed'"
		);
	}
}

MMI_Totally_Disable_Comments::boot();
