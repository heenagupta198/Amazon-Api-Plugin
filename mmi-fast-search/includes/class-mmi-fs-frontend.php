<?php
/**
 * Enqueue overlay UI and disable heavy tagDiv live search.
 */

defined( 'ABSPATH' ) || exit;

class MMI_FS_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'print_critical_css' ), 2 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		$s = MMI_FS_Settings::get();
		if ( ! empty( $s['enabled'] ) && ! is_admin() ) {
			$classes[] = 'mmi-fs-enabled';
			if ( ! empty( $s['replace_tagdiv'] ) ) {
				$classes[] = 'mmi-fs-tagdiv';
			}
		}

		return $classes;
	}

	public static function print_critical_css(): void {
		$s = MMI_FS_Settings::get();
		if ( is_admin() || empty( $s['enabled'] ) ) {
			return;
		}
		?>
<style id="mmi-fs-critical">
/* Overlay: inlined so TagDiv/WP Rocket cannot leave search unstyled (fixes giant SVG) */
.mmi-fs-root:not(.is-open){display:none!important}
.mmi-fs-root.is-open{position:fixed!important;inset:0!important;z-index:999999!important;display:flex!important;align-items:flex-start!important;justify-content:center!important;padding:16px!important;background:transparent!important}
.mmi-fs-backdrop{position:absolute!important;inset:0!important;background:rgba(8,12,24,.58)!important}
.mmi-fs-dialog{position:relative!important;width:min(720px,100%)!important;margin-top:clamp(24px,10vh,96px)!important;background:#fff!important;border-radius:16px!important;overflow:hidden!important;box-shadow:0 24px 80px rgba(15,23,42,.22)!important;max-height:calc(100dvh - 32px)!important;display:flex!important;flex-direction:column!important}
.mmi-fs-form{display:flex!important;flex-wrap:nowrap!important;border-bottom:1px solid rgba(15,23,42,.1)!important}
.mmi-fs-input-wrap{display:flex!important;align-items:center!important;gap:10px!important;flex:1!important;min-width:0!important;padding:14px 16px!important}
.mmi-fs-search-ico{width:20px!important;height:20px!important;max-width:20px!important;max-height:20px!important;flex:0 0 20px!important;display:block!important;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none'%3E%3Ccircle cx='10.5' cy='10.5' r='7.5' stroke='%2364748b' stroke-width='2'/%3E%3Cpath d='M16.5 16.5L21 21' stroke='%2364748b' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") center/contain no-repeat!important}
.mmi-fs-root svg,.mmi-fs-input-wrap svg{width:20px!important;height:20px!important;max-width:20px!important;max-height:20px!important;flex:0 0 20px!important}
.mmi-fs-input{flex:1!important;border:0!important;outline:none!important;font-size:17px!important;min-width:0!important;background:transparent!important}
.mmi-fs-submit{border:0!important;background:#0f172a!important;color:#fff!important;font-weight:600!important;padding:14px 20px!important;cursor:pointer!important}
.mmi-fs-body{overflow:auto!important;max-height:min(58vh,480px)!important}
.mmi-fs-close{position:absolute!important;top:10px!important;right:10px!important;z-index:2!important;width:40px!important;height:40px!important;border:0!important;border-radius:999px!important;background:#f1f5f9!important;cursor:pointer!important}
@media (max-width:767px){
.mmi-fs-root.is-open{padding:0!important;align-items:stretch!important}
.mmi-fs-dialog{margin:0!important;width:100%!important;max-width:none!important;height:100dvh!important;max-height:100dvh!important;border-radius:0!important}
.mmi-fs-form{flex-direction:column!important}
.mmi-fs-submit{width:100%!important}
.mmi-fs-body{max-height:none!important;flex:1!important}
.mmi-fs-grid{grid-template-columns:1fr!important}
}
<?php if ( ! empty( $s['replace_tagdiv'] ) ) : ?>
body.mmi-fs-tagdiv .td-drop-down-search,
body.mmi-fs-tagdiv .tdb-drop-down-search,
body.mmi-fs-tagdiv .tdb-drop-down-search-inner,
body.mmi-fs-tagdiv .tdb-search-form,
body.mmi-fs-tagdiv .tdb-search-form-inner,
body.mmi-fs-tagdiv .tdb-aj-search,
body.mmi-fs-tagdiv .tdb-aj-search-results,
body.mmi-fs-tagdiv .tdb-aj-search-inner,
body.mmi-fs-tagdiv .td-search-background,
body.mmi-fs-tagdiv .td-search-close,
body.mmi-fs-tagdiv .tdb-search-form-popup,
body.mmi-fs-tagdiv .tdb-header-search .dropdown-menu,
body.mmi-fs-tagdiv .td-header-search-wrap .td-drop-down-search{
	display:none!important;visibility:hidden!important;max-height:0!important;overflow:hidden!important;
	width:0!important;max-width:0!important;opacity:0!important;pointer-events:none!important;
}
body.mmi-fs-tagdiv .tdb-drop-down-search-open,
body.mmi-fs-tagdiv .tdb-drop-down-search.tdb-drop-down-search-open{
	width:0!important;max-width:0!important;
}
body.mmi-fs-tagdiv .tdb-head-search-btn,
body.mmi-fs-tagdiv .tdb-header-search-button,
body.mmi-fs-tagdiv .tdb-header-search-button-mob,
body.mmi-fs-tagdiv .td-search-icon,
body.mmi-fs-tagdiv .td-icon-search,
body.mmi-fs-tagdiv .tdb-search-icon{
	pointer-events:auto!important;visibility:visible!important;display:inline-flex!important;
}
<?php endif; ?>
</style>
		<?php
	}

	public static function enqueue(): void {
		if ( is_admin() ) {
			return;
		}

		$s = MMI_FS_Settings::get();
		if ( empty( $s['enabled'] ) ) {
			return;
		}

		$index_url = MMI_FS_Index::get_index_url();
		if ( '' === $index_url ) {
			if ( ! get_transient( 'mmi_fs_rebuild_scheduled' ) ) {
				set_transient( 'mmi_fs_rebuild_scheduled', '1', 15 * MINUTE_IN_SECONDS );
				if ( ! wp_next_scheduled( 'mmi_fs_rebuild_index' ) ) {
					wp_schedule_single_event( time() + 30, 'mmi_fs_rebuild_index' );
				}
			}
		}

		if ( ! empty( $s['replace_tagdiv'] ) ) {
			self::dequeue_tagdiv_search();
		}

		$css_file = MMI_FS_PATH . 'assets/css/search.css';
		$css_ver  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : MMI_FS_VERSION;

		wp_enqueue_style(
			'mmi-fast-search',
			MMI_FS_URL . 'assets/css/search.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'mmi-fast-search',
			MMI_FS_URL . 'assets/js/search.js',
			array(),
			MMI_FS_VERSION,
			true
		);

		$search_base = (string) $s['search_url'];
		if ( '' === $search_base ) {
			$search_base = home_url( '/' );
		}

		wp_localize_script(
			'mmi-fast-search',
			'MmiFastSearch',
			array(
				'indexUrl'      => $index_url,
				'preload'       => ! empty( $s['preload_index'] ),
				'replaceTagdiv' => ! empty( $s['replace_tagdiv'] ),
				'debounce'      => (int) $s['debounce_ms'],
				'limit'         => (int) $s['results_limit'],
				'searchUrl'     => $search_base,
				'mobileBp'      => 768,
				'i18n'          => array(
					'placeholder' => __( 'Search news…', 'mmi-fast-search' ),
					'empty'       => __( 'Type to search articles', 'mmi-fast-search' ),
					'noResults'   => __( 'No matches — try another keyword', 'mmi-fast-search' ),
					'viewAll'     => __( 'View all results', 'mmi-fast-search' ),
					'posts'       => __( 'Posts', 'mmi-fast-search' ),
					'loading'     => __( 'Loading search index…', 'mmi-fast-search' ),
					'indexPending'=> __( 'Search index is building. Try again in a minute or rebuild from Settings → MMI Fast Search.', 'mmi-fast-search' ),
					'open'        => __( 'Open search', 'mmi-fast-search' ),
					'close'       => __( 'Close', 'mmi-fast-search' ),
				),
			)
		);

		if ( ! empty( $s['preload_index'] ) && '' !== $index_url ) {
			add_action(
				'wp_head',
				static function () use ( $index_url ): void {
					echo '<link rel="preload" as="fetch" href="' . esc_url( $index_url ) . '" crossorigin="anonymous" />' . "\n";
				},
				6
			);
		}
	}

	private static function dequeue_tagdiv_search(): void {
		$handles = apply_filters(
			'mmi_fs_dequeue_tagdiv_scripts',
			array(
				'tdSearch',
				'tdAjaxSearch',
				'td-live-search',
				'tdLiveSearch',
				'tdbLiveSearch',
				'tdb_search',
				'tdbSearch',
			)
		);

		foreach ( $handles as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}