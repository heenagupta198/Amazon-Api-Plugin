<?php
/**
 * Preload hero / featured image for faster LCP on AMP.
 *
 * @package MMI_AMP_Performance
 */

defined( 'ABSPATH' ) || exit;

/**
 * LCP preload for AMP head.
 */
final class MMI_AMP_LCP_Preload {

	/**
	 * Hook AMP and generic head (AMP for WP).
	 */
	public static function register(): void {
		add_action( 'amp_post_template_head', array( __CLASS__, 'output_preload' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'output_preload' ), 1 );
		add_filter( 'amp_post_template_data', array( __CLASS__, 'add_optimizer_hero' ), 10, 2 );
	}

	/**
	 * Mark featured image as hero for the official AMP plugin optimizer.
	 *
	 * @param array<string,mixed> $data Template data.
	 * @param object              $post Post object.
	 * @return array<string,mixed>
	 */
	public static function add_optimizer_hero( array $data, $post ): array {
		if ( ! MMI_AMP_Context::is_amp_singular() ) {
			return $data;
		}
		$data['mmi_amp_hero_attachment_id'] = (int) get_post_thumbnail_id( $post );
		return $data;
	}

	/**
	 * Print link rel=preload for the LCP image (mobile-first size).
	 */
	public static function output_preload(): void {
		if ( ! MMI_AMP_Context::is_amp_singular() ) {
			return;
		}

		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		$attachment_id = (int) get_post_thumbnail_id();
		if ( ! $attachment_id ) {
			return;
		}

		$size = apply_filters( 'mmi_amp_perf_lcp_image_size', 'medium_large' );
		$src  = wp_get_attachment_image_url( $attachment_id, $size );
		if ( ! $src ) {
			return;
		}

		$srcset = wp_get_attachment_image_srcset( $attachment_id, $size );
		$sizes  = wp_get_attachment_image_sizes( $attachment_id, $size );

		$attrs = array(
			'rel'            => 'preload',
			'as'             => 'image',
			'href'           => $src,
			'fetchpriority'  => 'high',
		);

		if ( $srcset ) {
			$attrs['imagesrcset'] = $srcset;
		}
		if ( $sizes ) {
			$attrs['imagesizes'] = $sizes;
		}

		echo '<link';
		foreach ( $attrs as $key => $value ) {
			echo ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}
		echo '>' . "\n";
	}
}
