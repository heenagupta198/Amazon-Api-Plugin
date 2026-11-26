<?php
/**
 * Flipkart product meta — URL, synced price, availability (per product / variation).
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Flipkart_Product_Meta {

	/** @var array<int,string> */
	private static $url_cache = array();

	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_flipkart_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_flipkart_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_parent_meta' ) );
		add_action( 'wp_ajax_mmi_aps_fetch_flipkart_price', array( __CLASS__, 'ajax_fetch_price' ) );
	}

	/**
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public static function add_flipkart_tab( array $tabs ): array {
		$tabs['mmi_flipkart_price'] = array(
			'label'    => __( 'Flipkart Price', 'mmi-amazon-price-sync' ),
			'target'   => 'mmi_flipkart_price_product_data',
			'class'    => array(),
			'priority' => 76,
		);

		return $tabs;
	}

	public static function render_flipkart_panel(): void {
		global $post;
		$product_id = $post ? (int) $post->ID : 0;
		?>
		<div id="mmi_flipkart_price_product_data" class="panel woocommerce_options_panel hidden">
			<?php self::render_fields( $product_id ); ?>
		</div>
		<?php
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function render_fields( int $product_id ): void {
		$url           = self::get_product_url( $product_id );
		$price         = get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_PRICE, true );
		$original      = get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_ORIGINAL_PRICE, true );
		$last_updated  = get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_LAST_UPDATED, true );
		$availability  = self::get_availability_status( $product_id );
		$pending       = MMI_APS_Flipkart_API_Client::get_pending_message();

		echo '<div class="options_group">';
		woocommerce_wp_text_input(
			array(
				'id'          => 'mmi_flipkart_product_url',
				'label'       => __( 'Flipkart Product URL', 'mmi-amazon-price-sync' ),
				'description' => __( 'Full Flipkart listing URL for this variant (or parent simple product).', 'mmi-amazon-price-sync' ),
				'value'       => $url,
				'desc_tip'    => true,
				'placeholder' => 'https://www.flipkart.com/...',
			)
		);

		if ( '' !== $pending && ! MMI_APS_Flipkart_API_Client::is_configured() ) {
			echo '<p class="form-field"><label>&nbsp;</label><span class="description" style="color:#b32d2e;">' . esc_html( $pending ) . '</span></p>';
		}

		?>
		<p class="form-field mmi-aps-flipkart-actions">
			<label>&nbsp;</label>
			<button type="button" class="button button-primary mmi-aps-fetch-flipkart-price" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>">
				<?php esc_html_e( 'Fetch Price from Flipkart', 'mmi-amazon-price-sync' ); ?>
			</button>
			<span class="spinner mmi-aps-flipkart-fetch-spinner" style="float:none;margin:0 8px;"></span>
			<span class="mmi-aps-flipkart-fetch-message"></span>
		</p>

		<div class="mmi-aps-flipkart-synced-data">
			<p>
				<strong><?php esc_html_e( 'Status:', 'mmi-amazon-price-sync' ); ?></strong>
				<span class="mmi-aps-flipkart-display-status mmi-aps-flipkart-display-status--<?php echo esc_attr( $availability ); ?>">
					<?php echo esc_html( self::get_availability_label( $availability ) ); ?>
				</span>
			</p>
			<p><strong><?php esc_html_e( 'Flipkart Price:', 'mmi-amazon-price-sync' ); ?></strong> <span class="mmi-aps-flipkart-display-price"><?php echo $price ? esc_html( MMI_APS_Product_Meta::format_inr( (float) $price ) ) : '—'; ?></span></p>
			<p><strong><?php esc_html_e( 'MRP:', 'mmi-amazon-price-sync' ); ?></strong> <span class="mmi-aps-flipkart-display-original-price"><?php echo $original ? esc_html( MMI_APS_Product_Meta::format_inr( (float) $original ) ) : '—'; ?></span></p>
			<p><strong><?php esc_html_e( 'Last Updated:', 'mmi-amazon-price-sync' ); ?></strong> <span class="mmi-aps-flipkart-display-last-updated"><?php echo $last_updated ? esc_html( MMI_APS_Product_Meta::format_datetime( (int) $last_updated ) ) : '—'; ?></span></p>
		</div>
		<p class="mmi-aps-panel-note">
			<?php esc_html_e( 'Flipkart prices sync automatically once your Flipkart Affiliate API is approved. Until then, save the product URL so the storefront can show Flipkart Buy links when you enable manual affiliate URLs.', 'mmi-amazon-price-sync' ); ?>
		</p>
		<?php
		echo '</div>';
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function save_parent_meta( int $product_id ): void {
		if ( ! isset( $_POST['mmi_flipkart_product_url'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		$url = esc_url_raw( wp_unslash( (string) $_POST['mmi_flipkart_product_url'] ) );
		update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_URL, $url );
		unset( self::$url_cache[ $product_id ] );
	}

	public static function ajax_fetch_price(): void {
		check_ajax_referer( 'mmi_aps_fetch_price', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$url        = isset( $_POST['flipkart_url'] ) ? esc_url_raw( wp_unslash( (string) $_POST['flipkart_url'] ) ) : '';

		if ( ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mmi-amazon-price-sync' ) ) );
		}

		if ( '' === $url ) {
			$url = self::get_product_url( $product_id );
		}

		if ( '' === $url ) {
			wp_send_json_error( array( 'message' => __( 'Flipkart product URL is required.', 'mmi-amazon-price-sync' ) ) );
		}

		$result = MMI_APS_Sync::sync_flipkart_product( $product_id, $url );

		if ( ! $result['success'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ?? __( 'Failed to fetch Flipkart price.', 'mmi-amazon-price-sync' ) ) );
		}

		$data = $result['data'];
		$availability = (string) ( $data['availability'] ?? 'unavailable' );

		wp_send_json_success(
			array(
				'message'            => __( 'Flipkart data saved.', 'mmi-amazon-price-sync' ),
				'availability'       => $availability,
				'availability_label' => self::get_availability_label( $availability ),
				'price'              => $data['price'] ?? null,
				'price_formatted'    => ! empty( $data['price'] ) ? MMI_APS_Product_Meta::format_inr( (float) $data['price'] ) : '—',
				'original_formatted' => ! empty( $data['original_price'] ) ? MMI_APS_Product_Meta::format_inr( (float) $data['original_price'] ) : '—',
				'last_updated'       => MMI_APS_Product_Meta::format_datetime( time() ),
			)
		);
	}

	/**
	 * Persist Flipkart API payload on a product or variation.
	 *
	 * @param int                   $product_id Product ID.
	 * @param string                $url        Listing URL.
	 * @param array<string,mixed>   $data       Parsed data.
	 */
	public static function save_flipkart_data( int $product_id, string $url, array $data ): void {
		$availability = (string) ( $data['availability'] ?? 'unavailable' );
		$has_price    = ! empty( $data['price'] ) && (float) $data['price'] > 0;

		update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_URL, $url );
		if ( ! empty( $data['product_url'] ) ) {
			update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_AFFILIATE_URL, (string) $data['product_url'] );
		}
		update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_AVAILABILITY, $availability );
		update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_PRICE, $has_price ? $data['price'] : '' );
		update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_ORIGINAL_PRICE, $has_price && ! empty( $data['original_price'] ) ? $data['original_price'] : '' );
		update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_TITLE, $data['title'] ?? '' );
		update_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_LAST_UPDATED, time() );
		unset( self::$url_cache[ $product_id ] );
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function get_product_url( int $product_id ): string {
		if ( isset( self::$url_cache[ $product_id ] ) ) {
			return self::$url_cache[ $product_id ];
		}

		self::$url_cache[ $product_id ] = esc_url_raw( (string) get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_URL, true ) );
		return self::$url_cache[ $product_id ];
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function get_affiliate_url( int $product_id ): string {
		$affiliate = esc_url_raw( (string) get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_AFFILIATE_URL, true ) );
		if ( '' !== $affiliate ) {
			return $affiliate;
		}

		$url = self::get_product_url( $product_id );
		if ( '' === $url ) {
			return '';
		}

		return MMI_APS_Flipkart_API_Client::build_affiliate_url( $url );
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function get_flipkart_price( int $product_id ): ?float {
		if ( ! self::is_price_fresh( $product_id ) ) {
			return null;
		}

		if ( in_array( self::get_availability_status( $product_id ), array( 'unavailable', 'out_of_stock' ), true ) ) {
			return null;
		}

		$price = get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_PRICE, true );
		if ( '' === $price || null === $price ) {
			return null;
		}

		return (float) $price;
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function get_availability_status( int $product_id ): string {
		$status = (string) get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_AVAILABILITY, true );
		$allowed = array( 'in_stock', 'out_of_stock', 'unavailable' );
		return in_array( $status, $allowed, true ) ? $status : 'unavailable';
	}

	/**
	 * @param string $status Availability slug.
	 */
	public static function get_availability_label( string $status ): string {
		$labels = array(
			'in_stock'    => __( 'In Stock', 'mmi-amazon-price-sync' ),
			'out_of_stock' => __( 'Out of Stock', 'mmi-amazon-price-sync' ),
			'unavailable' => __( 'Currently Unavailable', 'mmi-amazon-price-sync' ),
		);

		return $labels[ $status ] ?? $labels['unavailable'];
	}

	/**
	 * @param int $product_id Product ID.
	 */
	public static function is_price_fresh( int $product_id ): bool {
		$updated = (int) get_post_meta( $product_id, MMI_APS_Plugin::META_FLIPKART_LAST_UPDATED, true );
		if ( $updated <= 0 ) {
			return false;
		}

		$max_age = MMI_APS_Settings::get_price_max_age_seconds();
		return ( time() - $updated ) <= $max_age;
	}
}