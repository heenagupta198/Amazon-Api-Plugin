<?php
/**
 * Enqueue overlay UI and disable heavy tagDiv live search.
 */

defined( 'ABSPATH' ) || exit;

class MMI_FS_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'print_critical_css' ), 3 );
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
		if ( is_admin() || empty( $s['enabled'] ) || empty( $s['replace_tagdiv'] ) ) {
			return;
		}
		?>
<style id="mmi-fs-critical">
/* TagDiv Cloud Library header search — hide dropdown/AJAX UI, keep magnifying glass */
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

		wp_enqueue_style(
			'mmi-fast-search',
			MMI_FS_URL . 'assets/css/search.css',
			array(),
			MMI_FS_VERSION
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