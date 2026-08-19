<?php
/**
 * WooCommerce product meta — ASIN field, Fetch Price, saved Amazon data.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Product_Meta {

	/** @var array<int,string> */
	private static $asin_cache = array();

	/** @var array<int,?float> */
	private static $price_cache = array();

	/** @var array<int,?float> */
	private static $original_price_cache = array();

	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_product_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_meta' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save_product_meta' ), 20 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_mmi_aps_fetch_price', array( __CLASS__, 'ajax_fetch_price' ) );
	}

	/**
	 * @param array<string,array<string,mixed>> $tabs Product data tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public static function add_product_tab( array $tabs ): array {
		$tabs['mmi_amazon_price'] = array(
			'label'    => __( 'Amazon Price', 'mmi-amazon-price-sync' ),
			'target'   => 'mmi_amazon_price_product_data',
			'class'    => array(),
			'priority' => 75,
		);

		return $tabs;
	}

	public static function render_product_panel(): void {
		global $post;
		$product_id = $post ? (int) $post->ID : 0;
		?>
		<div id="mmi_amazon_price_product_data" class="panel woocommerce_options_panel hidden">
			<?php self::render_fields( $product_id, 'panel' ); ?>
		</div>
		<?php
	}

	/**
	 * Render ASIN + fetch UI for product edit screen.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $context    panel|meta-box
	 */
	public static function render_fields( int $product_id, string $context = 'panel' ): void {
		$suffix           = 'meta-box' === $context ? '-box' : '';
		$asin             = get_post_meta( $product_id, MMI_APS_Plugin::META_ASIN, true );
		$price            = get_post_meta( $product_id, MMI_APS_Plugin::META_PRICE, true );
		$original_price   = get_post_meta( $product_id, MMI_APS_Plugin::META_ORIGINAL_PRICE, true );
		$last_updated     = get_post_meta( $product_id, MMI_APS_Plugin::META_LAST_UPDATED, true );
		$title            = get_post_meta( $product_id, MMI_APS_Plugin::META_TITLE, true );
		$delivery         = get_post_meta( $product_id, MMI_APS_Plugin::META_DELIVERY, true );

		if ( 'panel' === $context ) {
			echo '<div class="options_group">';
			woocommerce_wp_text_input(
				array(
					'id'          => 'mmi_amazon_asin',
					'label'       => __( 'Amazon ASIN', 'mmi-amazon-price-sync' ),
					'description' => __( '10-character Amazon product ID, e.g. B0H8STM6G2', 'mmi-amazon-price-sync' ),
					'value'       => $asin,
					'desc_tip'    => true,
					'placeholder' => 'B0H8STM6G2',
				)
			);
		} else {
			?>
			<p>
				<label for="mmi_amazon_asin<?php echo esc_attr( $suffix ); ?>"><strong><?php esc_html_e( 'Amazon ASIN', 'mmi-amazon-price-sync' ); ?></strong></label>
				<input type="text" class="widefat mmi-aps-asin-input" id="mmi_amazon_asin<?php echo esc_attr( $suffix ); ?>" name="mmi_amazon_asin_sidebar" value="<?php echo esc_attr( $asin ); ?>" placeholder="B0H8STM6G2" maxlength="10" autocomplete="off" />
			</p>
			<?php
		}
		?>
		<p class="form-field mmi-aps-actions">
			<?php if ( 'panel' === $context ) : ?>
				<label>&nbsp;</label>
			<?php endif; ?>
			<button type="button" class="button button-primary mmi-aps-fetch-price" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>" data-asin-input="mmi_amazon_asin<?php echo esc_attr( $suffix ); ?>">
				<?php esc_html_e( 'Fetch Price from Amazon', 'mmi-amazon-price-sync' ); ?>
			</button>
			<span class="spinner mmi-aps-fetch-spinner" style="float:none;margin:0 8px;"></span>
			<span class="mmi-aps-fetch-message"></span>
		</p>

		<div class="mmi-aps-synced-data" data-context="<?php echo esc_attr( $context ); ?>">
			<p><strong><?php esc_html_e( 'Amazon Price:', 'mmi-amazon-price-sync' ); ?></strong> <span class="mmi-aps-display-price"><?php echo $price ? esc_html( self::format_inr( (float) $price ) ) : '—'; ?></span></p>
			<p><strong><?php esc_html_e( 'Original Price:', 'mmi-amazon-price-sync' ); ?></strong> <span class="mmi-aps-display-original-price"><?php echo $original_price ? esc_html( self::format_inr( (float) $original_price ) ) : '—'; ?></span></p>
			<?php if ( 'panel' === $context ) : ?>
				<p class="form-field">
					<label><?php esc_html_e( 'Product Title (Amazon)', 'mmi-amazon-price-sync' ); ?></label>
					<span class="mmi-aps-readonly mmi-aps-readonly--wide mmi-aps-display-title"><?php echo $title ? esc_html( $title ) : '—'; ?></span>
				</p>
				<p class="form-field">
					<label><?php esc_html_e( 'Delivery', 'mmi-amazon-price-sync' ); ?></label>
					<span class="mmi-aps-readonly mmi-aps-display-delivery"><?php echo $delivery ? esc_html( $delivery ) : '—'; ?></span>
				</p>
			<?php else : ?>
				<p><strong><?php esc_html_e( 'Last Updated:', 'mmi-amazon-price-sync' ); ?></strong> <span class="mmi-aps-display-last-updated"><?php echo $last_updated ? esc_html( self::format_datetime( (int) $last_updated ) ) : '—'; ?></span></p>
			<?php endif; ?>
			<?php if ( 'panel' === $context ) : ?>
				<p class="form-field">
					<label><?php esc_html_e( 'Last Updated', 'mmi-amazon-price-sync' ); ?></label>
					<span class="mmi-aps-readonly mmi-aps-display-last-updated"><?php echo $last_updated ? esc_html( self::format_datetime( (int) $last_updated ) ) : '—'; ?></span>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( 'panel' === $context ) : ?>
			<p class="mmi-aps-panel-note">
				<?php esc_html_e( 'Amazon prices are stored as product meta. The storefront displays the Amazon price automatically without overwriting your WooCommerce regular price.', 'mmi-amazon-price-sync' ); ?>
			</p>
			<?php
			echo '</div>';
		endif;
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function save_product_meta( int $product_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $product_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		$asin = self::get_submitted_asin();
		if ( null === $asin ) {
			return;
		}

		if ( '' !== $asin && ! preg_match( '/^[A-Z0-9]{10}$/', $asin ) ) {
			return;
		}

		$existing = self::get_asin( $product_id );
		if ( '' === $asin && '' !== $existing ) {
			// Duplicate form fields can submit an empty value and wipe a valid ASIN.
			return;
		}

		if ( $asin === $existing ) {
			return;
		}

		update_post_meta( $product_id, MMI_APS_Plugin::META_ASIN, $asin );
		self::clear_meta_cache( $product_id );
		MMI_APS_Sync::invalidate_product_count_cache();
	}

	/**
	 * Read ASIN from product edit form (panel + sidebar use different field names).
	 */
	private static function get_submitted_asin(): ?string {
		$candidates = array();

		if ( isset( $_POST['mmi_amazon_asin_sidebar'] ) ) {
			$candidates[] = wp_unslash( $_POST['mmi_amazon_asin_sidebar'] );
		}

		if ( isset( $_POST['mmi_amazon_asin'] ) ) {
			$candidates[] = wp_unslash( $_POST['mmi_amazon_asin'] );
		}

		if ( empty( $candidates ) ) {
			return null;
		}

		foreach ( $candidates as $candidate ) {
			$asin = strtoupper( sanitize_text_field( $candidate ) );
			if ( '' !== $asin ) {
				return $asin;
			}
		}

		return '';
	}

	public static function enqueue_admin_assets( string $hook ): void {
		global $post;

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		wp_enqueue_style(
			'mmi-aps-admin-product',
			MMI_APS_URL . 'assets/css/admin-product.css',
			array(),
			MMI_APS_VERSION
		);

		wp_enqueue_script(
			'mmi-aps-admin-product',
			MMI_APS_URL . 'assets/js/admin-product.js',
			array( 'jquery' ),
			MMI_APS_VERSION,
			true
		);

		wp_localize_script(
			'mmi-aps-admin-product',
			'mmiApsAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mmi_aps_fetch_price' ),
				'i18n'    => array(
					'fetching'  => __( 'Fetching price from Amazon…', 'mmi-amazon-price-sync' ),
					'success'   => __( 'Price updated successfully.', 'mmi-amazon-price-sync' ),
					'noAsin'    => __( 'Please enter an Amazon ASIN first.', 'mmi-amazon-price-sync' ),
					'error'     => __( 'Failed to fetch price.', 'mmi-amazon-price-sync' ),
				),
			)
		);
	}

	public static function ajax_fetch_price(): void {
		check_ajax_referer( 'mmi_aps_fetch_price', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$asin       = isset( $_POST['asin'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['asin'] ) ) ) : '';

		if ( ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mmi-amazon-price-sync' ) ) );
		}

		if ( '' === $asin ) {
			wp_send_json_error( array( 'message' => __( 'ASIN is required.', 'mmi-amazon-price-sync' ) ) );
		}

		$result = MMI_APS_Sync::sync_product( $product_id, $asin );

		if ( ! $result['success'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		$data = $result['data'];

		wp_send_json_success(
			array(
				'message'        => __( 'Price fetched and saved.', 'mmi-amazon-price-sync' ),
				'asin'           => $asin,
				'price'          => $data['price'],
				'price_formatted'=> self::format_inr( (float) $data['price'] ),
				'original_price' => $data['original_price'],
				'original_formatted' => $data['original_price'] ? self::format_inr( (float) $data['original_price'] ) : '—',
				'title'          => $data['title'] ?: '—',
				'delivery'       => $data['delivery'] ?: '—',
				'last_updated'   => self::format_datetime( time() ),
			)
		);
	}

	/**
	 * Save Amazon API data to product meta.
	 *
	 * @param int                   $product_id Product ID.
	 * @param string                $asin       Amazon ASIN.
	 * @param array<string,mixed>   $data       Parsed API data.
	 */
	public static function save_amazon_data( int $product_id, string $asin, array $data ): void {
		update_post_meta( $product_id, MMI_APS_Plugin::META_ASIN, $asin );
		update_post_meta( $product_id, MMI_APS_Plugin::META_PRICE, $data['price'] );
		update_post_meta( $product_id, MMI_APS_Plugin::META_ORIGINAL_PRICE, ! empty( $data['original_price'] ) ? $data['original_price'] : '' );
		update_post_meta( $product_id, MMI_APS_Plugin::META_TITLE, $data['title'] ?? '' );
		update_post_meta( $product_id, MMI_APS_Plugin::META_CURRENCY, $data['currency'] ?? 'INR' );
		update_post_meta( $product_id, MMI_APS_Plugin::META_DELIVERY, $data['delivery'] ?? '' );
		update_post_meta( $product_id, MMI_APS_Plugin::META_LAST_UPDATED, time() );
		self::clear_meta_cache( $product_id );
	}

	private static function clear_meta_cache( int $product_id ): void {
		unset( self::$asin_cache[ $product_id ], self::$price_cache[ $product_id ], self::$original_price_cache[ $product_id ] );
	}

	/**
	 * Format amount as INR for admin display.
	 */
	public static function format_inr( float $amount ): string {
		return '₹' . number_format( $amount, 0 === fmod( $amount, 1 ) ? 0 : 2 );
	}

	/**
	 * Format Unix timestamp for admin display.
	 */
	public static function format_datetime( int $timestamp ): string {
		return wp_date( 'j M Y, g:i A', $timestamp );
	}

	/**
	 * Get stored Amazon ASIN for a product.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function get_asin( int $product_id ): string {
		if ( isset( self::$asin_cache[ $product_id ] ) ) {
			return self::$asin_cache[ $product_id ];
		}

		self::$asin_cache[ $product_id ] = strtoupper( trim( (string) get_post_meta( $product_id, MMI_APS_Plugin::META_ASIN, true ) ) );
		return self::$asin_cache[ $product_id ];
	}

	/**
	 * Get stored Amazon price for a product.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function get_amazon_price( int $product_id ): ?float {
		if ( array_key_exists( $product_id, self::$price_cache ) ) {
			return self::$price_cache[ $product_id ];
		}

		$price = get_post_meta( $product_id, MMI_APS_Plugin::META_PRICE, true );
		if ( '' === $price || null === $price ) {
			self::$price_cache[ $product_id ] = null;
			return null;
		}

		self::$price_cache[ $product_id ] = (float) $price;
		return self::$price_cache[ $product_id ];
	}

	/**
	 * Get stored Amazon original price for a product.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function get_amazon_original_price( int $product_id ): ?float {
		if ( array_key_exists( $product_id, self::$original_price_cache ) ) {
			return self::$original_price_cache[ $product_id ];
		}

		$price = get_post_meta( $product_id, MMI_APS_Plugin::META_ORIGINAL_PRICE, true );
		if ( '' === $price || null === $price ) {
			self::$original_price_cache[ $product_id ] = null;
			return null;
		}

		self::$original_price_cache[ $product_id ] = (float) $price;
		return self::$original_price_cache[ $product_id ];
	}
}
