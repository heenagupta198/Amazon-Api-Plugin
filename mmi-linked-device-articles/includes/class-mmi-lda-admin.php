<?php
/**
 * Post editor: link articles to device products (Select2 multi-search).
 */

defined( 'ABSPATH' ) || exit;

class MMI_LDA_Admin {

	public static function init(): void {
		add_action( 'admin_notices', array( __CLASS__, 'devices_plugin_notice' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ), 20 );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 20, 2 );
		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_mmi_lda_search_products', array( __CLASS__, 'ajax_search_products' ) );
		add_action( 'wp_ajax_mmi_lda_resolve_products', array( __CLASS__, 'ajax_resolve_products' ) );
		add_action( 'wp_ajax_mmi_lda_save_links', array( __CLASS__, 'ajax_save_links' ) );
	}

	public static function register_meta_box(): void {
		if ( ! MMI_LDA_Sites::is_news_site() ) {
			return;
		}

		add_meta_box(
			'mmi_lda_linked_products',
			__( 'Linked Device Products', 'mmi-linked-device-articles' ),
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
		wp_nonce_field( 'mmi_lda_save_products', 'mmi_lda_nonce' );
		echo '<input type="hidden" name="mmi_lda_box" value="1" />';
		echo '<input type="hidden" name="mmi_lda_cleared" id="mmi-lda-cleared" value="0" />';

		$selected_ids = MMI_LDA_Sites::get_linked_product_ids( (int) $post->ID );
		$selected     = self::hydrate_products( $selected_ids );
		?>
		<div class="mmi-lda-tabs" role="tablist">
			<button type="button" class="mmi-lda-tab is-active" data-tab="search" role="tab"><?php esc_html_e( 'Search', 'mmi-linked-device-articles' ); ?></button>
			<button type="button" class="mmi-lda-tab" data-tab="advanced" role="tab"><?php esc_html_e( 'Advanced', 'mmi-linked-device-articles' ); ?></button>
		</div>
		<div class="mmi-lda-panel is-active" data-panel="search">
			<p class="description">
				<?php
				printf(
					/* translators: %s: devices site URL */
					esc_html__( 'Live search from %s (separate WordPress / WooCommerce). Link the exact product ID from that site.', 'mmi-linked-device-articles' ),
					esc_html( MMI_LDA_Settings::get_devices_site_url() )
				);
				?>
			</p>
			<div class="mmi-lda-brand-row" id="mmi-lda-brand-row" aria-label="<?php esc_attr_e( 'Quick brand filter', 'mmi-linked-device-articles' ); ?>"></div>
			<label for="mmi-lda-product-search" class="mmi-lda-label"><?php esc_html_e( 'Add product', 'mmi-linked-device-articles' ); ?></label>
			<select id="mmi-lda-product-search" class="mmi-lda-search" style="width:100%;"></select>
			<p class="mmi-lda-label mmi-lda-selected-heading"><?php esc_html_e( 'Linked to this article (drag to reorder)', 'mmi-linked-device-articles' ); ?></p>
			<ul id="mmi-lda-selected-list" class="mmi-lda-selected-list">
				<?php foreach ( $selected as $item ) : ?>
					<li data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
						<span class="mmi-lda-grip" aria-hidden="true">⋮⋮</span>
						<span class="mmi-lda-selected-label"><?php echo esc_html( $item['label'] ); ?></span>
						<button type="button" class="button-link mmi-lda-remove" aria-label="<?php esc_attr_e( 'Remove', 'mmi-linked-device-articles' ); ?>">&times;</button>
						<input type="hidden" name="mmi_lda_product_ids[]" value="<?php echo esc_attr( (string) $item['id'] ); ?>" />
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="mmi-lda-hint description"><?php esc_html_e( 'Type OPPO then pick model, or use a brand button. OPPO A76 is not on devices — use a listed model.', 'mmi-linked-device-articles' ); ?></p>
		</div>
		<div class="mmi-lda-panel" data-panel="advanced" hidden>
			<p class="description"><?php esc_html_e( 'Paste product IDs (comma, space, or new line). Click Add to merge with your selection.', 'mmi-linked-device-articles' ); ?></p>
			<textarea id="mmi-lda-bulk-ids" class="widefat" rows="3" placeholder="11789, 21421&#10;59385"></textarea>
			<p>
				<button type="button" class="button" id="mmi-lda-bulk-add"><?php esc_html_e( 'Add IDs', 'mmi-linked-device-articles' ); ?></button>
				<button type="button" class="button-link" id="mmi-lda-clear-all"><?php esc_html_e( 'Clear all', 'mmi-linked-device-articles' ); ?></button>
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

		$resolved = MMI_LDA_Remote::resolve_product_ids( $ids );
		$by_id    = array();
		foreach ( $resolved as $item ) {
			$by_id[ (int) $item['id'] ] = $item;
		}

		$out = array();
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id <= 0 ) {
				continue;
			}
			if ( isset( $by_id[ $id ] ) ) {
				$out[] = $by_id[ $id ];
				continue;
			}
			$out[] = array(
				'id'    => $id,
				'label' => sprintf( '#%d', $id ),
			);
		}

		return $out;
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param WP_Post $post   Post object.
	 */
	public static function save_meta_box( int $post_id, WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		if ( ! MMI_LDA_Sites::is_news_site() ) {
			return;
		}

		$has_nonce = isset( $_POST['mmi_lda_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmi_lda_nonce'] ) ), 'mmi_lda_save_products' );
		if ( ! $has_nonce ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$cleared = isset( $_POST['mmi_lda_cleared'] ) && '1' === (string) wp_unslash( $_POST['mmi_lda_cleared'] );
		$raw_ids = self::posted_product_ids();

		self::persist_links( $post_id, $raw_ids, $cleared );
	}

	/**
	 * @return int[]
	 */
	private static function posted_product_ids(): array {
		$raw = array();
		if ( isset( $_POST['mmi_lda_product_ids'] ) ) {
			$raw = (array) wp_unslash( $_POST['mmi_lda_product_ids'] );
		} elseif ( isset( $_POST['mmi_csl_product_ids'] ) ) {
			$raw = (array) wp_unslash( $_POST['mmi_csl_product_ids'] );
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $raw ) ) ) );
	}

	/**
	 * Persist linked IDs. Never delete existing links unless the editor explicitly cleared them.
	 *
	 * @param int   $post_id Post ID.
	 * @param int[] $raw_ids IDs from the editor.
	 */
	public static function persist_links( int $post_id, array $raw_ids, bool $cleared = false ): void {
		$old_ids = MMI_LDA_Sites::get_linked_product_ids( $post_id );

		if ( empty( $raw_ids ) && ! $cleared ) {
			return;
		}

		MMI_LDA_Sites::save_linked_product_ids( $post_id, $raw_ids );
		$new_ids = MMI_LDA_Sites::get_linked_product_ids( $post_id );

		if ( is_multisite() ) {
			MMI_LDA_Sync::sync_diff( $post_id, $old_ids, $new_ids );
		}

		foreach ( array_merge( $old_ids, $new_ids ) as $pid ) {
			delete_transient( 'mmi_lda_news_p_' . (int) $pid );
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

		if ( ! MMI_LDA_Sites::is_news_site() ) {
			return;
		}

		$linked = MMI_LDA_Sites::get_linked_product_ids( $post_id );
		if ( ! empty( $linked ) ) {
			MMI_LDA_Sync::detach_news_post( $post_id, $linked );
		}
	}

	/**
	 * @param string $hook_suffix Admin hook suffix.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		if ( ! MMI_LDA_Sites::is_news_site() ) {
			return;
		}

		wp_enqueue_style(
			'mmi-lda-select2',
			'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css',
			array(),
			'4.0.13'
		);

		wp_enqueue_style(
			'mmi-lda-admin',
			MMI_LDA_URL . 'assets/css/admin.css',
			array( 'mmi-lda-select2' ),
			MMI_LDA_VERSION
		);

		wp_enqueue_script(
			'mmi-lda-select2',
			'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js',
			array( 'jquery' ),
			'4.0.13',
			true
		);

		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'mmi-lda-admin',
			MMI_LDA_URL . 'assets/js/admin.js',
			array( 'jquery', 'mmi-lda-select2', 'jquery-ui-sortable' ),
			MMI_LDA_VERSION,
			true
		);

		$brands = array();
		$brands = MMI_LDA_Remote::get_brands();

		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_id = ( $screen && isset( $screen->id ) && in_array( $screen->base, array( 'post', 'post-new' ), true ) ) ? get_the_ID() : 0;
		if ( ! $post_id && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = absint( wp_unslash( $_GET['post'] ) );
		}

		wp_localize_script(
			'mmi-lda-admin',
			'MmiLdaAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mmi_lda_search' ),
				'postId'  => (int) $post_id,
				'brands'  => $brands,
				'i18n'    => array(
					'placeholder'   => __( 'Search devices site: model or ID…', 'mmi-linked-device-articles' ),
					'searching'     => __( 'Searching devices…', 'mmi-linked-device-articles' ),
					'noResults'     => __( 'No product on /devices/', 'mmi-linked-device-articles' ),
					'inputTooShort' => __( 'Type brand or model (min 2 chars)', 'mmi-linked-device-articles' ),
					'error'         => __( 'Search failed. Try again.', 'mmi-linked-device-articles' ),
					'alreadyAdded'  => __( 'Already linked', 'mmi-linked-device-articles' ),
					'emptySelected' => __( 'No products linked yet', 'mmi-linked-device-articles' ),
				),
			)
		);
	}

	public static function ajax_search_products(): void {
		check_ajax_referer( 'mmi_lda_search', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'mmi-linked-device-articles' ) ), 403 );
		}

		if ( ! MMI_LDA_Product_Search::check_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many searches. Please wait a moment.', 'mmi-linked-device-articles' ) ), 429 );
		}

		$term = self::get_request_term();

		if ( strlen( $term ) < 1 ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$items = MMI_LDA_Remote::search_products( $term );

		if ( empty( $items ) && is_multisite() ) {
			$devices_blog = MMI_LDA_Sites::get_devices_blog_id();
			if ( get_current_blog_id() !== $devices_blog ) {
				switch_to_blog( $devices_blog );
				$items = MMI_LDA_Product_Search::search( $term );
				restore_current_blog();
			}
		}

		wp_send_json_success( array( 'items' => $items ) );
	}

	public static function ajax_resolve_products(): void {
		check_ajax_referer( 'mmi_lda_search', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'mmi-linked-device-articles' ) ), 403 );
		}

		$raw = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';
		preg_match_all( '/\d+/', $raw, $matches );
		$ids = isset( $matches[0] ) ? array_map( 'intval', $matches[0] ) : array();

		if ( empty( $ids ) ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$items = MMI_LDA_Remote::resolve_product_ids( $ids );
		if ( empty( $items ) ) {
			foreach ( $ids as $id ) {
				$items[] = array(
					'id'    => (int) $id,
					'label' => sprintf( '#%d', (int) $id ),
				);
			}
		}

		wp_send_json_success( array( 'items' => $items ) );
	}

	public static function ajax_save_links(): void {
		check_ajax_referer( 'mmi_lda_search', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'mmi-linked-device-articles' ) ), 403 );
		}

		if ( ! MMI_LDA_Sites::is_news_site() ) {
			wp_send_json_error( array( 'message' => __( 'Wrong site', 'mmi-linked-device-articles' ) ), 400 );
		}

		$cleared = isset( $_POST['cleared'] ) && '1' === (string) wp_unslash( $_POST['cleared'] );
		$raw     = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';
		preg_match_all( '/\d+/', $raw, $matches );
		$ids = isset( $matches[0] ) ? array_map( 'intval', $matches[0] ) : array();

		self::persist_links( $post_id, $ids, $cleared );

		wp_send_json_success(
			array(
				'ids' => MMI_LDA_Sites::get_linked_product_ids( $post_id ),
			)
		);
	}

	public static function devices_plugin_notice(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( ! MMI_LDA_Sites::is_news_site() ) {
			return;
		}

		if ( function_exists( 'mmi_csl_init' ) ) {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e( 'MMI Content Slider is also active on this news site. It can erase Linked Device Products when you click Update. Deactivate MMI Content Slider on the English news site only. Keep it on Devices.', 'mmi-linked-device-articles' );
			echo '</p></div>';
		}

		if ( ! MMI_LDA_Remote::devices_api_reachable() ) {
			echo '<div class="notice notice-error"><p>';
			printf(
				/* translators: 1: settings url 2: devices url */
				esc_html__( 'MMI Linked Device Articles cannot reach the devices store API. Check %1$s and ensure %2$s allows REST (wp-json).', 'mmi-linked-device-articles' ),
				'<a href="' . esc_url( admin_url( 'options-general.php?page=mmi-lda-settings' ) ) . '">' . esc_html__( 'Settings → MMI Linked Device Articles', 'mmi-linked-device-articles' ) . '</a>',
				esc_html( MMI_LDA_Settings::get_devices_site_url() )
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
