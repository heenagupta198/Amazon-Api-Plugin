<?php
/**
 * Post editor: link articles to device products (search + multi-select).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Admin {

	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ), 20 );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
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
			<?php esc_html_e( 'Search phones on the Devices site. Selected products will show this article in their Latest News section (above Related Products).', 'mmi-content-slider' ); ?>
		</p>
		<p>
			<label for="mmi-csl-product-search" class="screen-reader-text"><?php esc_html_e( 'Search products', 'mmi-content-slider' ); ?></label>
			<input type="text" id="mmi-csl-product-search" class="widefat" placeholder="<?php esc_attr_e( 'Search by name or ID…', 'mmi-content-slider' ); ?>" autocomplete="off" />
		</p>
		<ul id="mmi-csl-product-results" class="mmi-csl-results" aria-live="polite"></ul>
		<ul id="mmi-csl-product-selected" class="mmi-csl-selected">
			<?php foreach ( $selected as $item ) : ?>
				<li data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
					<span class="mmi-csl-selected-label"><?php echo esc_html( $item['label'] ); ?></span>
					<button type="button" class="button-link mmi-csl-remove" aria-label="<?php esc_attr_e( 'Remove', 'mmi-content-slider' ); ?>">&times;</button>
					<input type="hidden" name="mmi_csl_product_ids[]" value="<?php echo esc_attr( (string) $item['id'] ); ?>" />
				</li>
			<?php endforeach; ?>
		</ul>
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

		$raw_ids = isset( $_POST['mmi_csl_product_ids'] ) ? (array) wp_unslash( $_POST['mmi_csl_product_ids'] ) : array();
		MMI_CSL_Sites::save_linked_product_ids( $post_id, $raw_ids );
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
			'mmi-csl-admin',
			MMI_CSL_URL . 'assets/css/admin.css',
			array(),
			MMI_CSL_VERSION
		);

		wp_enqueue_script(
			'mmi-csl-admin',
			MMI_CSL_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MMI_CSL_VERSION,
			true
		);

		wp_localize_script(
			'mmi-csl-admin',
			'MmiCslAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mmi_csl_search' ),
			)
		);
	}

	public static function ajax_search_products(): void {
		check_ajax_referer( 'mmi_csl_search', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden', 'mmi-content-slider' ) ), 403 );
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

		$args = array(
			'post_type'              => array( 'product' ),
			'post_status'            => 'publish',
			'posts_per_page'         => 25,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( ctype_digit( $term ) ) {
			$args['post__in'] = array( (int) $term );
		} else {
			$args['s'] = $term;
		}

		$query = new WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $product_post ) {
			$items[] = array(
				'id'    => (int) $product_post->ID,
				'label' => sprintf( '#%d — %s', (int) $product_post->ID, get_the_title( $product_post ) ),
			);
		}

		wp_reset_postdata();

		if ( $switched ) {
			restore_current_blog();
		}

		wp_send_json_success( array( 'items' => $items ) );
	}
}
