<?php
/**
 * Post edit screen meta box.
 *
 * @package MMI_Related_Articles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Related Articles meta box.
 */
class MMI_RA_Meta_Box {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Add meta box to post screen (sidebar, near Publish).
	 */
	public static function register() {
		add_meta_box(
			'mmi_related_articles',
			__( 'Related Articles', 'mmi-related-articles' ),
			array( __CLASS__, 'render' ),
			'post',
			'side',
			'high'
		);
	}

	/**
	 * @param WP_Post $post Post.
	 */
	public static function render( $post ) {
		wp_nonce_field( 'mmi_ra_save', 'mmi_ra_save_nonce' );

		$ids = self::get_saved_ids( $post->ID );
		?>
		<div class="mmi-ra-metabox" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">
			<p class="mmi-ra-label"><?php esc_html_e( 'Search Articles', 'mmi-related-articles' ); ?></p>
			<div class="mmi-ra-search-wrap">
				<input type="search" id="mmi-ra-search" class="mmi-ra-search" placeholder="<?php esc_attr_e( 'Search by title…', 'mmi-related-articles' ); ?>" autocomplete="off" />
			</div>

			<fieldset class="mmi-ra-priority">
				<legend><?php esc_html_e( 'Date priority', 'mmi-related-articles' ); ?></legend>
				<label><input type="radio" name="mmi_ra_priority" value="7" checked /> <?php esc_html_e( 'Last 7 days', 'mmi-related-articles' ); ?></label>
				<label><input type="radio" name="mmi_ra_priority" value="15" /> <?php esc_html_e( 'Last 15 days', 'mmi-related-articles' ); ?></label>
				<label><input type="radio" name="mmi_ra_priority" value="all" /> <?php esc_html_e( 'All time', 'mmi-related-articles' ); ?></label>
			</fieldset>

			<p class="mmi-ra-actions-top">
				<button type="button" class="button" id="mmi-ra-auto-suggest"><?php esc_html_e( 'Auto suggest', 'mmi-related-articles' ); ?></button>
			</p>

			<div class="mmi-ra-results-header"><?php esc_html_e( 'Search results', 'mmi-related-articles' ); ?></div>
			<div id="mmi-ra-results" class="mmi-ra-results" aria-live="polite"></div>

			<p class="mmi-ra-actions">
				<button type="button" class="button button-primary" id="mmi-ra-add-selected" disabled><?php esc_html_e( 'Add selected', 'mmi-related-articles' ); ?></button>
			</p>

			<div class="mmi-ra-selected-header"><?php esc_html_e( 'Selected related articles', 'mmi-related-articles' ); ?></div>
			<ul id="mmi-ra-selected" class="mmi-ra-selected">
				<?php
				foreach ( $ids as $id ) {
					$related = get_post( $id );
					if ( ! $related || 'publish' !== $related->post_status ) {
						continue;
					}
					self::render_selected_item( $related );
				}
				?>
			</ul>
			<p class="mmi-ra-selected-empty<?php echo empty( $ids ) ? '' : ' hidden'; ?>"><?php esc_html_e( 'No articles selected yet.', 'mmi-related-articles' ); ?></p>

			<p class="mmi-ra-actions-bottom">
				<button type="button" class="button" id="mmi-ra-remove-selected" disabled><?php esc_html_e( 'Remove selected', 'mmi-related-articles' ); ?></button>
				<button type="button" class="button-link-delete" id="mmi-ra-clear-all"><?php esc_html_e( 'Clear all', 'mmi-related-articles' ); ?></button>
			</p>

			<input type="hidden" name="mmi_ra_related_ids" id="mmi-ra-related-ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * @param WP_Post $post Related post.
	 */
	private static function render_selected_item( $post ) {
		$id    = (int) $post->ID;
		$thumb = MMI_RA_Query::get_thumb_url( $id );
		$date  = get_the_date( '', $post );
		$title = get_the_title( $post );
		?>
		<li class="mmi-ra-selected-item" data-id="<?php echo esc_attr( (string) $id ); ?>">
			<span class="mmi-ra-drag" title="<?php esc_attr_e( 'Drag to reorder', 'mmi-related-articles' ); ?>" aria-hidden="true">☰</span>
			<label class="mmi-ra-selected-check">
				<input type="checkbox" class="mmi-ra-selected-remove-cb" />
			</label>
			<img src="<?php echo esc_url( $thumb ); ?>" alt="" width="40" height="40" loading="lazy" />
			<span class="mmi-ra-selected-text">
				<strong><?php echo esc_html( $title ); ?></strong>
				<small><?php echo esc_html( $date ); ?></small>
			</span>
			<button type="button" class="mmi-ra-remove-one button-link" aria-label="<?php esc_attr_e( 'Remove', 'mmi-related-articles' ); ?>">✕</button>
		</li>
		<?php
	}

	/**
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function get_saved_ids( $post_id ) {
		$raw = get_post_meta( $post_id, MMI_RA_META_KEY, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return array_values(
			array_filter(
				array_map( 'absint', $raw ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		);
	}

	/**
	 * Save meta on post update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['mmi_ra_save_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmi_ra_save_nonce'] ) ), 'mmi_ra_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || 'post' !== $post->post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['mmi_ra_related_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['mmi_ra_related_ids'] ) ) : '';
		$ids = array();

		if ( '' !== $raw ) {
			foreach ( explode( ',', $raw ) as $part ) {
				$id = absint( trim( $part ) );
				if ( $id && $id !== (int) $post_id ) {
					$ids[] = $id;
				}
			}
		}

		$ids = array_values( array_unique( $ids ) );

		if ( empty( $ids ) ) {
			delete_post_meta( $post_id, MMI_RA_META_KEY );
			return;
		}

		update_post_meta( $post_id, MMI_RA_META_KEY, $ids );
	}

	/**
	 * @param string $hook Admin hook.
	 */
	public static function enqueue( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'mmi-ra-admin',
			MMI_RA_URL . 'assets/css/admin.css',
			array(),
			MMI_RA_VERSION
		);

		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'mmi-ra-admin',
			MMI_RA_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			MMI_RA_VERSION,
			true
		);

		wp_localize_script(
			'mmi-ra-admin',
			'mmiRaAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mmi_ra_admin' ),
				'i18n'    => array(
					'searching'    => __( 'Searching…', 'mmi-related-articles' ),
					'noResults'    => __( 'No articles found.', 'mmi-related-articles' ),
					'typeToSearch' => __( 'Type at least 2 characters to search.', 'mmi-related-articles' ),
					'error'        => __( 'Search failed. Please try again.', 'mmi-related-articles' ),
					'recentFirst'  => __( 'Recent matches', 'mmi-related-articles' ),
					'older'        => __( 'Older matches', 'mmi-related-articles' ),
				),
			)
		);
	}
}
