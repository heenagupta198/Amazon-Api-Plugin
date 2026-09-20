<?php
/**
 * Build and render product card markup (91mobiles-style).
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Card {

	/**
	 * Build cacheable card data from a product (one WC load per product per cache miss).
	 *
	 * @param WC_Product $product Product.
	 * @param string     $anchor  Optional hash on details URL.
	 * @return array<string, mixed>|null
	 */
	public static function data_from_product( $product, $anchor = '' ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return null;
		}

		$price = $product->get_price();
		if ( '' === $price ) {
			return null;
		}

		$product_id = $product->get_id();
		$permalink  = get_permalink( $product_id );
		$details    = self::details_url( $permalink, $anchor );

		$thumb_id = $product->get_image_id();
		$thumb    = '';
		if ( $thumb_id ) {
			$thumb = wp_get_attachment_image_url( $thumb_id, 'medium' );
		}

		$fields = MMI_ML_Specs::card_fields( $product );

		$release = '';
		$date    = $product->get_date_created();
		if ( $date ) {
			$release = $date->date_i18n( 'j M, Y' );
		}

		return array(
			'id'           => $product_id,
			'title'        => $product->get_name(),
			'permalink'    => $permalink,
			'details_url'  => $details,
			'price'        => (float) $price,
			'price_html'   => wc_price( $price, array( 'decimals' => 0 ) ),
			'thumb_url'    => $thumb ? $thumb : '',
			'thumb_alt'    => $product->get_name(),
			'release_date' => $release,
			'fields'       => $fields,
		);
	}

	/**
	 * @param string $permalink Product URL.
	 * @param string $anchor    Hash without #.
	 * @return string
	 */
	public static function details_url( $permalink, $anchor = '' ) {
		$anchor = ltrim( (string) $anchor, '#' );
		if ( '' === $anchor ) {
			$anchor = apply_filters( 'mmi_ml_default_details_anchor', '' );
		}
		if ( '' === $anchor ) {
			return $permalink;
		}
		return $permalink . '#' . sanitize_title( $anchor );
	}

	/**
	 * Render one card from prebuilt data (no DB).
	 *
	 * @param array<string, mixed> $card Card data.
	 * @param int                  $index Menu index for unique IDs.
	 * @return string
	 */
	public static function render( $card, $index = 0 ) {
		$menu_id = 'mmi-menu-' . (int) $card['id'] . '-' . (int) $index;

		ob_start();
		?>
		<article class="mmi-mobile-card" data-product-id="<?php echo esc_attr( (string) $card['id'] ); ?>">
			<header class="mmi-card-header">
				<div class="mmi-card-heading">
					<h3 class="mmi-product-title">
						<a href="<?php echo esc_url( $card['permalink'] ); ?>">
							<?php echo esc_html( $card['title'] ); ?>
						</a>
					</h3>
					<?php if ( ! empty( $card['release_date'] ) ) : ?>
						<p class="mmi-release-date">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: formatted date */
									__( 'Release Date: %s', 'mmi-mobile-list' ),
									$card['release_date']
								)
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			</header>

			<div class="mmi-card-body">
				<div class="mmi-product-image-wrap">
					<a href="<?php echo esc_url( $card['permalink'] ); ?>">
						<?php if ( ! empty( $card['thumb_url'] ) ) : ?>
							<img
								class="mmi-product-image"
								src="<?php echo esc_url( $card['thumb_url'] ); ?>"
								alt="<?php echo esc_attr( $card['thumb_alt'] ); ?>"
								loading="lazy"
								decoding="async"
								width="190"
								height="210"
							/>
						<?php else : ?>
							<div class="mmi-no-image"><?php esc_html_e( 'No Image', 'mmi-mobile-list' ); ?></div>
						<?php endif; ?>
					</a>
				</div>

				<div class="mmi-product-info">
					<div class="mmi-info-toolbar">
						<button
							type="button"
							class="mmi-kebab-btn"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $menu_id ); ?>"
							aria-label="<?php esc_attr_e( 'More options', 'mmi-mobile-list' ); ?>"
						>
							<span aria-hidden="true"></span>
							<span aria-hidden="true"></span>
							<span aria-hidden="true"></span>
						</button>
						<div class="mmi-kebab-menu" id="<?php echo esc_attr( $menu_id ); ?>" hidden>
							<a href="<?php echo esc_url( $card['details_url'] ); ?>">
								<?php esc_html_e( 'All Details', 'mmi-mobile-list' ); ?>
							</a>
						</div>
					</div>

					<div class="mmi-product-specs">
						<?php
						echo MMI_ML_Specs::render_row( 'fas fa-microchip', $card['fields']['processor'] );
						echo MMI_ML_Specs::render_row( 'fas fa-memory', $card['fields']['ram_storage'] );
						echo MMI_ML_Specs::render_row( 'fas fa-camera', $card['fields']['rear_camera'] );
						echo MMI_ML_Specs::render_row( 'fas fa-camera-retro', $card['fields']['front_camera'] );
						echo MMI_ML_Specs::render_row( 'fas fa-battery-full', $card['fields']['battery'] );
						echo MMI_ML_Specs::render_row( 'fas fa-mobile-alt', $card['fields']['display'] );
						?>
					</div>

					<p class="mmi-view-all-specs-wrap">
						<a class="mmi-view-all-specs" href="<?php echo esc_url( $card['details_url'] ); ?>">
							<?php esc_html_e( 'View All Specs', 'mmi-mobile-list' ); ?>
						</a>
					</p>
				</div>
			</div>

			<footer class="mmi-card-footer">
				<div class="mmi-product-price">
					<?php echo wp_kses_post( $card['price_html'] ); ?>
				</div>
				<a class="mmi-view-details" href="<?php echo esc_url( $card['details_url'] ); ?>">
					<?php esc_html_e( 'View Details', 'mmi-mobile-list' ); ?>
				</a>
			</footer>
		</article>
		<?php
		return (string) ob_get_clean();
	}
}
