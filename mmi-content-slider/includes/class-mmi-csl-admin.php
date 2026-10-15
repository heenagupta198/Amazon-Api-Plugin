<?php
/**
 * Post editor: link articles to device products (Select2 multi-search).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Admin {

	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ), 20 );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_mmi_csl_search_products', array( __CLASS__, 'ajax_search_products' ) );
	}

	public static function register_meta_box(): void {
		if ( (int) get_current_blog_id() !== MMI_CSL_Sites::get_news_blog_id() ) {
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
		<p class="description">
			<?php esc_html_e( 'Type to search devices (name or ID). Select multiple products — news appears on each product page under Latest News.', 'mmi-content-slider' ); ?>
		</p>
		<select id="mmi-csl-product-select" name="mmi_csl_product_ids[]" class="mmi-csl-select" multiple="multiple" style="width:100%;">
			<?php foreach ( $selected as $item ) : ?>
				<option value="<?php echo esc_attr( (string) $item['id'] ); ?>" selected="selected"><?php echo esc_html( $item['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="mmi-csl-hint description"><?php esc_html_e( 'Tip: search “OPPO Find X8” or paste product ID (e.g. 21421).', 'mmi-content-slider' ); ?></p>
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

		$devices_blog = MMI_CSL_Sites::get_devices_blog_id();
		$switched     = false;

		if ( is_multisite() && get_current_blog_id() !== $devices_blog ) {
			switch_to_blog( $devices_blog );
			$switched = true;
		}

		$out = array();
		foreach ( $ids as $id ) {
			$title = get_the_title( $id );
			if ( ! $title ) {
				continue;
			}
			$out[] = array(
				'id'    => (int) $id,
				'label' => sprintf( '#%d — %s', (int) $id, $title ),
			);
		}

		if ( $switched ) {
			restore_current_blog();
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

		if ( (int) get_current_blog_id() !== MMI_CSL_Sites::get_news_blog_id() ) {
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

		MMI_CSL_Sync::sync_diff( $post_id, $old_ids, $new_ids );
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function before_delete_post( int $post_id, WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		if ( (int) get_current_blog_id() !== MMI_CSL_Sites::get_news_blog_id() ) {
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

		if ( (int) get_current_blog_id() !== MMI_CSL_Sites::get_news_blog_id() ) {
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

		wp_enqueue_script(
			'mmi-csl-admin',
			MMI_CSL_URL . 'assets/js/admin.js',
			array( 'jquery', 'mmi-csl-select2' ),
			MMI_CSL_VERSION,
			true
		);

		wp_localize_script(
			'mmi-csl-admin',
			'MmiCslAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mmi_csl_search' ),
				'i18n'    => array(
					'placeholder'   => __( 'Search devices by name or ID…', 'mmi-content-slider' ),
					'searching'     => __( 'Searching…', 'mmi-content-slider' ),
					'noResults'     => __( 'No products found', 'mmi-content-slider' ),
					'inputTooShort' => __( 'Type at least 2 characters or a product ID', 'mmi-content-slider' ),
					'error'         => __( 'Search failed. Try again.', 'mmi-content-slider' ),
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

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		$term = trim( $term );

		if ( strlen( $term ) < 1 ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$devices_blog = MMI_CSL_Sites::get_devices_blog_id();
		$switched     = false;

		if ( is_multisite() && get_current_blog_id() !== $devices_blog ) {
			switch_to_blog( $devices_blog );
			$switched = true;
		}

		$items = MMI_CSL_Product_Search::search( $term );

		if ( $switched ) {
			restore_current_blog();
		}

		wp_send_json_success( array( 'items' => $items ) );
	}
}
