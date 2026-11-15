<?php
/**
 * Plugin bootstrap.
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		MMI_MLL_Shortcode::register();
		MMI_MLL_Archive::register();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_filter( 'loop_shop_per_page', array( $this, 'shop_per_page' ), 20 );

		if ( is_admin() ) {
			MMI_MLL_Admin::register();
		}
	}

	public function enqueue_frontend(): void {
		if ( ! $this->needs_assets() ) {
			return;
		}
		self::enqueue_assets();
	}

	public static function enqueue_assets(): void {
		wp_enqueue_style(
			'mmi-mobile-list',
			MMI_MLL_URL . 'assets/css/frontend.css',
			array(),
			MMI_MLL_VERSION
		);
		wp_enqueue_script(
			'mmi-mobile-list',
			MMI_MLL_URL . 'assets/js/frontend.js',
			array(),
			MMI_MLL_VERSION,
			true
		);
	}

	private function needs_assets(): bool {
		if ( MMI_MLL_Settings::is_spec_archive() ) {
			return true;
		}
		$post = get_post();
		if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'mmi_mobile_list' ) ) {
			return true;
		}
		if ( $post instanceof WP_Post && str_contains( $post->post_content, '[mmi_mobile_list' ) ) {
			return true;
		}
		return (bool) apply_filters( 'mmi_mll_enqueue_assets', false );
	}

	public function shop_per_page( int $per_page ): int {
		if ( MMI_MLL_Settings::is_spec_archive() ) {
			return MMI_MLL_Settings::per_page_default();
		}
		return $per_page;
	}
}