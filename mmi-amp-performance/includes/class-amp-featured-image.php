<?php
/**
 * Tighter srcset / hero hints for AMP featured images in content.
 *
 * @package MMI_AMP_Performance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adjust post thumbnail markup on AMP only.
 */
final class MMI_AMP_Featured_Image {

	/**
	 * Register filters.
	 */
	public static function register(): void {
		add_filter( 'post_thumbnail_html', array( __CLASS__, 'filter_thumbnail_html' ), 20, 5 );
		add_filter( 'the_content', array( __CLASS__, 'mark_first_content_image_hero' ), 5 );
		add_filter( 'amp_content_sanitizers', array( __CLASS__, 'maybe_disable_auto_lightbox' ), 20 );
	}

	/**
	 * Use a smaller default size in AMP thumbnail output when theme prints featured image.
	 *
	 * @param string       $html              Thumbnail HTML.
	 * @param int          $post_id           Post ID.
	 * @param int          $post_thumbnail_id Attachment ID.
	 * @param string|int[] $size              Size.
	 * @param string|array $attr              Attributes.
	 */
	public static function filter_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ): string {
		if ( ! MMI_AMP_Context::is_amp_request() || ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$amp_size = apply_filters( 'mmi_amp_perf_content_image_size', 'medium_large', $post_id );
		if ( $size === $amp_size ) {
			return self::ensure_data_hero( $html );
		}

		$replacement = wp_get_attachment_image(
			$post_thumbnail_id,
			$amp_size,
			false,
			array(
				'class'     => is_array( $attr ) && isset( $attr['class'] ) ? $attr['class'] : '',
				'data-hero' => '',
				'decoding'  => 'async',
			)
		);

		return $replacement ? $replacement : self::ensure_data_hero( $html );
	}

	/**
	 * Add data-hero to the first large in-content image when no featured image block exists.
	 *
	 * @param string $content Post content.
	 */
	public static function mark_first_content_image_hero( $content ): string {
		if ( ! MMI_AMP_Context::is_amp_request() || ! is_string( $content ) || has_post_thumbnail() ) {
			return $content;
		}

		if ( false !== strpos( $content, 'data-hero' ) ) {
			return $content;
		}

		$pattern = '/<img([^>]+)>/i';
		if ( ! preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
			return $content;
		}

		$tag = $matches[0][0];
		if ( false !== stripos( $tag, 'data-hero' ) ) {
			return $content;
		}

		$new_tag = preg_replace( '/<img/i', '<img data-hero=""', $tag, 1 );
		if ( ! is_string( $new_tag ) ) {
			return $content;
		}

		return substr_replace( $content, $new_tag, $matches[0][1], strlen( $tag ) );
	}

	/**
	 * Auto lightbox can delay LCP paint on some AMP builds.
	 *
	 * @param array<string,bool|array> $sanitizers Sanitizers.
	 * @return array<string,bool|array>
	 */
	public static function maybe_disable_auto_lightbox( array $sanitizers ): array {
		if ( ! MMI_AMP_Context::is_amp_request() ) {
			return $sanitizers;
		}
		if ( apply_filters( 'mmi_amp_perf_disable_auto_lightbox', true ) ) {
			unset( $sanitizers['AMP_Auto_Lightbox_Sanitizer'] );
		}
		return $sanitizers;
	}

	/**
	 * Ensure data-hero attribute exists on img tag.
	 *
	 * @param string $html Image HTML.
	 */
	private static function ensure_data_hero( string $html ): string {
		if ( false !== stripos( $html, 'data-hero' ) ) {
			return $html;
		}
		return preg_replace( '/<img/i', '<img data-hero=""', $html, 1 ) ?? $html;
	}
}
