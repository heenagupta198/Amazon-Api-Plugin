<?php
/**
 * Post editor: link articles to device products (Select2 multi-search).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Admin {

	public static function init(): void {
		add_action( 'admin_notices', array( __CLASS__, 'devices_plugin_notice' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ), 20 );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_mmi_csl_search_products', array( __CLASS__, 'ajax_search_products' ) );
		add_action( 'wp_ajax_mmi_csl_resolve_products', array( __CLASS__, 'ajax_resolve_products' ) );
	}

	public static function register_meta_box(): void {
		if ( ! MMI_CSL_Sites::is_news_site() ) {
			return;
		}

		add_meta_box(
			'mmi_csl_linked_products',
			__( 'Linked Device Products', 'mmi-content-slider' ),
			array( __CLASS__, 'render_meta_box' ),
			'post',
			'side',
			'default'
		);
	}

	/**
	 * @param WP_Post $post Post object.
	 */
	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'mmi_csl_save_products', 'mmi_csl_nonce' );

		$selected_ids = MMI_CSL_Sites::get_linked_product_ids( (int) $post->ID );
		$selected     = self::hydrate_products( $selected_ids );
		?>
		<div class="mmi-csl-tabs" role="tablist">
			<button type="button" class="mmi-csl-tab is-active" data-tab="search" role="tab"><?php esc_html_e( 'Search', 'mmi-content-slider' ); ?></button>
			<button type="button" class="mmi-csl-tab" data-tab="advanced" role="tab"><?php esc_html_e( 'Advanced', 'mmi-content-slider' ); ?></button>
		</div>
		<div class="mmi-csl-panel is-active" data-panel="search">
			<p class="description">
				<?php
				printf(
					/* translators: %s: devices site URL */
					esc_html__( 'Live search from %s (separate WordPress / WooCommerce). Link the exact product ID from that site.', 'mmi-content-slider' ),
					esc_html( MMI_CSL_Settings::get_devices_site_url() )
				);
				?>
			</p>
			<div class="mmi-csl-brand-row" id="mmi-csl-brand-row" aria-label="<?php esc_attr_e( 'Quick brand filter', 'mmi-content-slider' ); ?>"></div>
			<label for="mmi-csl-product-search" class="mmi-csl-label"><?php esc_html_e( 'Add product', 'mmi-content-slider' ); ?></label>
			<select id="mmi-csl-product-search" class="mmi-csl-search" style="width:100%;"></select>
			<p class="mmi-csl-label mmi-csl-selected-heading"><?php esc_html_e( 'Linked to this article (drag to reorder)', 'mmi-content-slider' ); ?></p>
			<ul id="mmi-csl-selected-list" class="mmi-csl-selected-list">
				<?php foreach ( $selected as $item ) : ?>
					<li data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
						<span class="mmi-csl-grip" aria-hidden="true">⋮⋮</span>
						<span class="mmi-csl-selected-label"><?php echo esc_html( $item['label'] ); ?></span>
						<button type="button" class="button-link mmi-csl-remove" aria-label="<?php esc_attr_e( 'Remove', 'mmi-content-slider' ); ?>">&times;</button>
						<input type="hidden" name="mmi_csl_product_ids[]" value="<?php echo esc_attr( (string) $item['id'] ); ?>" />
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="mmi-csl-hint description"><?php esc_html_e( 'Type OPPO then pick model, or use a brand button. OPPO A76 is not on devices — use a listed model.', 'mmi-content-slider' ); ?></p>
		</div>
		<div class="mmi-csl-panel" data-panel="advanced" hidden>
			<p class="description"><?php esc_html_e( 'Paste product IDs (comma, space, or new line). Click Add to merge with your selection.', 'mmi-content-slider' ); ?></p>
			<textarea id="mmi-csl-bulk-ids" class="widefat" rows="3" placeholder="11789, 21421&#10;59385"></textarea>
			<p>
				<button type="button" class="button" id="mmi-csl-bulk-add"><?php esc_html_e( 'Add IDs', 'mmi-content-slider' ); ?></button>
				<button type="button" class="button-link" id="mmi-csl-clear-all"><?php esc_html_e( 'Clear all', 'mmi-content-slider' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * @param int[] $ids Product IDs on devices blog.
	 * @return array<int,array{id:int,label:string}>
	 */
	private static function hydrate_products( array $ids ): array {
		if ( empty( $ids ) ) {
			return array();
		}

		return MMI_CSL_Remote::resolve_product_ids( $ids );
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param WP_Post $post   Post object.
	 */
	public static function save_meta_box( int $post_id, WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		if ( ! MMI_CSL_Sites::is_news_site() ) {
			return;
		}

		if ( ! isset( $_POST['mmi_csl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmi_csl_nonce'] ) ), 'mmi_csl_save_products' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$old_ids = MMI_CSL_Sites::get_linked_product_ids( $post_id );
		$raw_ids = isset( $_POST['mmi_csl_product_ids'] ) ? (array) wp_unslash( $_POST['mmi_csl_product_ids'] ) : array();

		MMI_CSL_Sites::save_linked_product_ids( $post_id, $raw_ids );
		$new_ids = MMI_CSL_Sites::get_linked_product_ids( $post_id );

		if ( is_multisite() ) {
			MMI_CSL_Sync::sync_diff( $post_id, $old_ids, $new_ids );
		}

		foreach ( array_merge( $old_ids, $new_ids ) as $pid ) {
			delete_transient( 'mmi_csl_news_p_' . (int) $pid );
		}
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function before_delete_post( int $post_id, WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		if ( ! MMI_CSL_Sites::is_news_site() ) {
			return;
		}

		$linked = MMI_CSL_Sites::get_linked_product_ids( $post_id );
		if ( ! empty( $linked ) ) {
			MMI_CSL_Sync::detach_news_post( $post_id, $linked );
		}
	}

	/**
	 * @param string $hook_suffix Admin hook suffix.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		if ( ! MMI_CSL_Sites::is_news_site() ) {
			return;
		}

		wp_enqueue_style(
			'mmi-csl-select2',
			'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css',
			array(),
			'4.0.13'
		);

		wp_enqueue_style(
			'mmi-csl-admin',
			MMI_CSL_URL . 'assets/css/admin.css',
			array( 'mmi-csl-select2' ),
			MMI_CSL_VERSION
		);

		wp_enqueue_script(
			'mmi-csl-select2',
			'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js',
			array( 'jquery' ),
			'4.0.13',
			true
		);

		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'mmi-csl-admin',
			MMI_CSL_URL . 'assets/js/admin.js',
			array( 'jquery', 'mmi-csl-select2', 'jquery-ui-sortable' ),
			MMI_CSL_VERSION,
			true
		);

		$brands = array();
		$brands = MMI_CSL_Remote::get_brands();

		wp_localize_script(
			'mmi-csl-admin',
			'MmiCslAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mmi_csl_search' ),
				'brands'  => $brands,
				'i18n'    => array(
					'placeholder'   => __( 'Search devices site: model or ID…', 'mmi-content-slider' ),
					'searching'     => __( 'Searching devices…', 'mmi-content-slider' ),
					'noResults'     => __( 'No product on /devices/', 'mmi-content-slider' ),
					'inputTooShort' => __( 'Type brand or model (min 2 chars)', 'mmi-content-slider' ),
					'error'         => __( 'Search failed. Try again.', 'mmi-content-slider' ),
					'alreadyAdded'  => __( 'Already linked', 'mmi-content-slider' ),
					'emptySelected' => __( 'No products linked yet', 'mmi-content-slider' ),
				),
			)
		);
	}

	public static function ajax_search_products(): void {
		check_ajax_referer( 'mmi_csl_search', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'mmi-content-slider' ) ), 403 );
		}

		if ( ! MMI_CSL_Product_Search::check_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many searches. Please wait a moment.', 'mmi-content-slider' ) ), 429 );
		}

		$term = self::get_request_term();

		if ( strlen( $term ) < 1 ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$items = MMI_CSL_Remote::search_products( $term );

		if ( empty( $items ) && is_multisite() ) {
			$devices_blog = MMI_CSL_Sites::get_devices_blog_id();
			if ( get_current_blog_id() !== $devices_blog ) {
				switch_to_blog( $devices_blog );
				$items = MMI_CSL_Product_Search::search( $term );
				restore_current_blog();
			}
		}

		wp_send_json_success( array( 'items' => $items ) );
	}

	public static function ajax_resolve_products(): void {
		check_ajax_referer( 'mmi_csl_search', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'mmi-content-slider' ) ), 403 );
		}

		$raw = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';
		preg_match_all( '/\d+/', $raw, $matches );
		$ids = isset( $matches[0] ) ? array_map( 'intval', $matches[0] ) : array();

		if ( empty( $ids ) ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$items = MMI_CSL_Remote::resolve_product_ids( $ids );

		wp_send_json_success( array( 'items' => $items ) );
	}

	public static function devices_plugin_notice(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( ! MMI_CSL_Sites::is_news_site() ) {
			return;
		}

		if ( ! MMI_CSL_Remote::devices_api_reachable() ) {
			echo '<div class="notice notice-error"><p>';
			printf(
				/* translators: 1: settings url 2: devices url */
				esc_html__( 'MMI Device News cannot reach the devices store API. Check %1$s and ensure %2$s allows REST (wp-json).', 'mmi-content-slider' ),
				'<a href="' . esc_url( admin_url( 'options-general.php?page=mmi-csl-settings' ) ) . '">' . esc_html__( 'Settings → MMI Device News', 'mmi-content-slider' ) . '</a>',
				esc_html( MMI_CSL_Settings::get_devices_site_url() )
			);
			echo '</p></div>';
		}
	}

	private static function get_request_term(): string {
		$source = array();
		if ( isset( $_POST['term'] ) ) {
			$source = wp_unslash( $_POST['term'] );
		} elseif ( isset( $_GET['term'] ) ) {
			$source = wp_unslash( $_GET['term'] );
		}

		return trim( sanitize_text_field( (string) $source ) );
	}
}
