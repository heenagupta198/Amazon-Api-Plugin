<?php
/**
 * Replace WooCommerce archive loop with spec cards on enabled taxonomies.
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Archive {

	public static function register(): void {
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'wp', array( __CLASS__, 'setup_archive_hooks' ), 20 );
	}

	public static function body_class( array $classes ): array {
		if ( MMI_MLL_Settings::is_spec_archive() ) {
			$classes[] = 'mmi-spec-archive';
		}
		return $classes;
	}

	public static function setup_archive_hooks(): void {
		if ( ! MMI_MLL_Settings::is_spec_archive() ) {
			return;
		}

		add_filter( 'wc_get_template_part', array( __CLASS__, 'product_template' ), 20, 3 );
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'open_list' ), 5 );
		add_action( 'woocommerce_after_shop_loop', array( __CLASS__, 'close_list' ), 99 );
		add_filter( 'woocommerce_product_subcategories', array( __CLASS__, 'hide_subcategories' ), 20 );
	}

	/**
	 * @param string $template Path.
	 * @param string $slug     Slug.
	 * @param string $name     Name.
	 */
	public static function product_template( $template, $slug, $name ) {
		if ( 'content' === $slug && 'product' === $name ) {
			$custom = MMI_MLL_PATH . 'templates/content-product-mmi-card.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		return $template;
	}

	public static function open_list(): void {
		echo '<div class="mmi-mobile-list mmi-archive-list">';
	}

	public static function close_list(): void {
		echo '</div>';
	}

	/**
	 * @param array<int,mixed> $subcategories Subcategories.
	 * @return array<int,mixed>
	 */
	public static function hide_subcategories( $subcategories ): array {
		return array();
	}
}