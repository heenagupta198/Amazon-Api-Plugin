<?php
/**
 * Enqueue overlay UI and disable heavy tagDiv live search.
 */

defined( 'ABSPATH' ) || exit;

class MMI_FS_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'print_critical_css' ), 5 );
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
body.mmi-fs-tagdiv .td-drop-down-search,
body.mmi-fs-tagdiv .tdb-drop-down-search,
body.mmi-fs-tagdiv .td-search-background,
body.mmi-fs-tagdiv .td-search-close,
body.mmi-fs-tagdiv .tdb-search-form-popup {display:none!important;visibility:hidden!important;pointer-events:none!important}
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
			// Never block frontend requests with a full rebuild — schedule once in the background.
			if ( ! get_transient( 'mmi_fs_rebuild_scheduled' ) ) {
				set_transient( 'mmi_fs_rebuild_scheduled', '1', 15 * MINUTE_IN_SECONDS );
				if ( ! wp_next_scheduled( 'mmi_fs_rebuild_index' ) ) {
					wp_schedule_single_event( time() + 30, 'mmi_fs_rebuild_index' );
				}
			}
			return;
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
				'i18n'          => array(
					'placeholder' => __( 'Search news…', 'mmi-fast-search' ),
					'empty'       => __( 'Type to search articles', 'mmi-fast-search' ),
					'noResults'   => __( 'No matches — try another keyword', 'mmi-fast-search' ),
					'viewAll'     => __( 'View all results', 'mmi-fast-search' ),
					'posts'       => __( 'Posts', 'mmi-fast-search' ),
					'loading'     => __( 'Loading search index…', 'mmi-fast-search' ),
					'open'        => __( 'Open search', 'mmi-fast-search' ),
					'close'       => __( 'Close', 'mmi-fast-search' ),
				),
			)
		);

		if ( ! empty( $s['preload_index'] ) ) {
			wp_register_style( 'mmi-fs-preload', false, array(), MMI_FS_VERSION );
			wp_enqueue_style( 'mmi-fs-preload' );
			wp_add_inline_style(
				'mmi-fs-preload',
				sprintf(
					'link[rel="preload"][as="fetch"][href="%s"]{display:none}',
					esc_url( $index_url )
				)
			);
			// Preload hint via link tag in head.
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
			)
		);

		foreach ( $handles as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}
