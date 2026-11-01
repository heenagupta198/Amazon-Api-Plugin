<?php
/**
 * Disables TagDiv slider / pagination on the legacy Feature block.
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end patch for existing Newspaper / TagDiv modules.
 */
final class MMI_FN_Tagdiv_Static {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_patch' ), 100 );
	}

	/**
	 * Load patch CSS/JS on the front end when enabled.
	 */
	public static function enqueue_patch(): void {
		if ( is_admin() ) {
			return;
		}
		$s = MMI_FN_Settings::get();
		if ( empty( $s['patch_tagdiv_block'] ) && empty( $s['hide_legacy_block'] ) ) {
			return;
		}

		$block_class = sanitize_html_class( $s['tagdiv_block_class'] );
		if ( '' === $block_class ) {
			return;
		}

		$desktop = max( 1, (int) $s['desktop_count'] );
		$mobile  = max( 1, (int) $s['mobile_count'] );
		$hide    = ! empty( $s['hide_legacy_block'] );

		wp_register_style( 'mmi-feature-news-tagdiv-patch', false, array(), MMI_FN_VERSION );
		wp_enqueue_style( 'mmi-feature-news-tagdiv-patch' );

		$css = self::build_css( $block_class, $desktop, $mobile, $hide );
		wp_add_inline_style( 'mmi-feature-news-tagdiv-patch', $css );

		if ( ! empty( $s['patch_tagdiv_block'] ) && ! $hide ) {
			wp_register_script( 'mmi-feature-news-tagdiv-patch', false, array(), MMI_FN_VERSION, true );
			wp_enqueue_script( 'mmi-feature-news-tagdiv-patch' );
			wp_add_inline_script(
				'mmi-feature-news-tagdiv-patch',
				self::build_js( $block_class ),
				'after'
			);
		}
	}

	/**
	 * @param string $block_class TagDiv unique class e.g. tdi_59.
	 * @param int    $desktop     Visible posts on desktop.
	 * @param int    $mobile      Visible posts on mobile.
	 * @param bool   $hide        Hide entire legacy block.
	 */
	private static function build_css( string $block_class, int $desktop, int $mobile, bool $hide ): string {
		$sel = '.' . $block_class;
		if ( $hide ) {
			return $sel . '{display:none!important;}';
		}

		$desktop_hide = $desktop + 1;
		$mobile_hide  = $mobile + 1;

		return <<<CSS
{$sel} .td-block-pagination,
{$sel} .td-next-prev-wrap,
{$sel} .td-pulldown-syle-default {
	display: none !important;
}
{$sel} .td_block_inner {
	overflow: visible !important;
	transform: none !important;
	animation: none !important;
}
{$sel} .td_block_inner .td_module_wrap {
	flex-shrink: 1 !important;
}
@media (min-width: 768px) {
	{$sel} .td_block_inner .td_module_wrap:nth-child(n+{$desktop_hide}) {
		display: none !important;
	}
}
@media (max-width: 767px) {
	{$sel} .td_block_inner .td_module_wrap:nth-child(n+{$mobile_hide}) {
		display: none !important;
	}
}
CSS;
	}

	/**
	 * @param string $block_class TagDiv block class.
	 */
	private static function build_js( string $block_class ): string {
		$class = esc_js( $block_class );
		return <<<JS
(function () {
	var root = document.querySelector('.{$class}');
	if (!root) return;
	root.querySelectorAll('.td-block-pagination a, .td-next-prev-wrap a').forEach(function (el) {
		el.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); }, true);
	});
	if (window.jQuery && jQuery.fn.owlCarousel) {
		jQuery(root).find('.owl-carousel').each(function () {
			try { jQuery(this).trigger('destroy.owl.carousel'); } catch (e) {}
		});
	}
})();
JS;
	}
}
