<?php
/**
 * Per-variation Amazon ASIN fields.
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

		$asin        = MMI_APS_Product_Meta::get_asin( $variation_id );
		$stock       = MMI_APS_Product_Meta::get_availability_status( $variation_id );
		$price       = MMI_APS_Product_Meta::get_stored_amazon_price( $variation_id );
		?>
		<div class="form-row form-row-full mmi-aps-variation-marketplace" data-variation-id="<?php echo esc_attr( (string) $variation_id ); ?>">
			<p class="form-field form-row form-row-full">
				<label><?php esc_html_e( 'Amazon ASIN (variant)', 'mmi-amazon-price-sync' ); ?></label>
				<input type="text" class="short mmi-aps-var-asin" name="mmi_amazon_asin_var[<?php echo esc_attr( (string) $loop ); ?>]" value="<?php echo esc_attr( $asin ); ?>" maxlength="10" placeholder="B0XXXXXXXX" />
				<button type="button" class="button button-small mmi-aps-var-fetch-amazon" data-variation-id="<?php echo esc_attr( (string) $variation_id ); ?>"><?php esc_html_e( 'Fetch Amazon', 'mmi-amazon-price-sync' ); ?></button>
			</p>
			<p class="mmi-aps-var-fetch-msg" style="margin:4px 0 8px;"></p>
			<?php if ( '' !== $asin ) : ?>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: stock label, 2: price */
							__( 'Amazon: %1$s — %2$s', 'mmi-amazon-price-sync' ),
							MMI_APS_Product_Meta::get_availability_label( $stock ),
							$price ? MMI_APS_Product_Meta::format_inr( $price ) : '—'
						)
					);
					?>
				</p>
			<?php endif; ?>
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
	}
}