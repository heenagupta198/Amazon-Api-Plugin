<?php
/**
 * Per-variation Amazon ASIN and Flipkart URL fields.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Variation_Marketplace {

	public static function init(): void {
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'render_variation_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variation_fields' ), 10, 2 );
	}

	/**
	 * @param int     $loop           Variation loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post.
	 */
	public static function render_variation_fields( int $loop, array $variation_data, $variation ): void {
		$variation_id = $variation instanceof WP_Post ? (int) $variation->ID : 0;
		if ( $variation_id <= 0 ) {
			return;
		}

		$asin         = MMI_APS_Product_Meta::get_asin( $variation_id );
		$flipkart_url = MMI_APS_Flipkart_Product_Meta::get_product_url( $variation_id );
		$sale_status  = (string) get_post_meta( $variation_id, MMI_APS_Plugin::META_SALE_STATUS, true );
		?>
		<div class="form-row form-row-full mmi-aps-variation-marketplace">
			<p class="form-field form-row form-row-first">
				<label><?php esc_html_e( 'Amazon ASIN (variant)', 'mmi-amazon-price-sync' ); ?></label>
				<input type="text" class="short" name="mmi_amazon_asin_var[<?php echo esc_attr( (string) $loop ); ?>]" value="<?php echo esc_attr( $asin ); ?>" maxlength="10" placeholder="B0XXXXXXXX" />
			</p>
			<p class="form-field form-row form-row-last">
				<label><?php esc_html_e( 'Flipkart URL (variant)', 'mmi-amazon-price-sync' ); ?></label>
				<input type="url" class="short" name="mmi_flipkart_url_var[<?php echo esc_attr( (string) $loop ); ?>]" value="<?php echo esc_attr( $flipkart_url ); ?>" placeholder="https://www.flipkart.com/..." />
			</p>
			<p class="form-field form-row form-row-full">
				<label><?php esc_html_e( 'Sale status override', 'mmi-amazon-price-sync' ); ?></label>
				<select name="mmi_sale_status_var[<?php echo esc_attr( (string) $loop ); ?>]">
					<option value=""><?php esc_html_e( 'Auto (from launch date + stock)', 'mmi-amazon-price-sync' ); ?></option>
					<?php foreach ( MMI_APS_Marketplace_Status::STATUSES as $status ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $sale_status, $status ); ?>><?php echo esc_html( MMI_APS_Marketplace_Status::get_label( $status ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		<?php
	}

	/**
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Admin loop index.
	 */
	public static function save_variation_fields( int $variation_id, int $loop ): void {
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		if ( isset( $_POST['mmi_amazon_asin_var'][ $loop ] ) ) {
			$asin = strtoupper( sanitize_text_field( wp_unslash( (string) $_POST['mmi_amazon_asin_var'][ $loop ] ) ) );
			if ( '' === $asin || preg_match( '/^[A-Z0-9]{10}$/', $asin ) ) {
				update_post_meta( $variation_id, MMI_APS_Plugin::META_ASIN, $asin );
			}
		}

		if ( isset( $_POST['mmi_flipkart_url_var'][ $loop ] ) ) {
			$url = esc_url_raw( wp_unslash( (string) $_POST['mmi_flipkart_url_var'][ $loop ] ) );
			update_post_meta( $variation_id, MMI_APS_Plugin::META_FLIPKART_URL, $url );
		}

		if ( isset( $_POST['mmi_sale_status_var'][ $loop ] ) ) {
			$status = sanitize_text_field( wp_unslash( (string) $_POST['mmi_sale_status_var'][ $loop ] ) );
			if ( '' === $status || in_array( $status, MMI_APS_Marketplace_Status::STATUSES, true ) ) {
				update_post_meta( $variation_id, MMI_APS_Plugin::META_SALE_STATUS, $status );
			}
		}
	}
}