<?php
/**
 * Build and render product card markup (91mobiles-style).
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Card {

	/**
	 * Regular price used for affiliate/external listings.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function listing_regular_price( $product ) {
		$regular = $product->get_regular_price();
		if ( '' === $regular || null === $regular ) {
			return '';
		}
		return (string) $regular;
	}

	/**
	 * @param WC_Product $product  Product.
	 * @param string     $anchor   Details hash.
	 * @param bool       $upcoming Upcoming list (no price).
	 * @return array<string, mixed>|null
	 */
	public static function data_from_product( $product, $anchor = '', $upcoming = false ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return null;
		}

		$regular = self::listing_regular_price( $product );

		if ( ! $upcoming && '' === $regular ) {
			return null;
		}

		if ( $upcoming && '' !== $regular && (float) $regular > 0 ) {
			return null;
		}

		$product_id = $product->get_id();
		$permalink  = get_permalink( $product_id );
		$details    = self::details_url( $permalink, $anchor );
		$specs_url  = self::details_url( $permalink, $anchor ? $anchor : 'specifications' );

		$thumb_id = $product->get_image_id();
		$thumb    = '';
		if ( $thumb_id ) {
			$thumb = wp_get_attachment_image_url( $thumb_id, 'medium' );
			if ( ! $thumb ) {
				$thumb = wp_get_attachment_image_url( $thumb_id, 'woocommerce_thumbnail' );
			}
		}

		$fields = MMI_ML_Specs::card_fields( $product );

		$release = '';
		$date    = $product->get_date_created();
		if ( $date ) {
			$release = $date->date_i18n( 'j M, Y' );
		}

		$price_html = '';
		if ( '' !== $regular ) {
			$price_html = wc_price( $regular, array( 'decimals' => 0 ) );
		}

		return array(
			'id'           => $product_id,
			'title'        => $product->get_name(),
			'permalink'    => $permalink,
			'details_url'  => $details,
			'specs_url'    => $specs_url,
			'price'        => '' !== $regular ? (float) $regular : 0,
			'price_html'   => $price_html,
			'upcoming'     => $upcoming,
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
	 * @param array<string, mixed> $card  Card data.
	 * @param int                  $index Index.
	 * @return string
	 */
	public static function render( $card, $index = 0 ) {
		$menu_id = 'mmi-menu-' . (int) $card['id'] . '-' . (int) $index;

		ob_start();
		?>
		<article class="mmi-mobile-card" data-product-id="<?php echo esc_attr( (string) $card['id'] ); ?>">
			<header class="mmi-card-header">
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
								/* translators: %s: date */
								__( 'Release Date: %s', 'mmi-mobile-list' ),
								$card['release_date']
							)
						);
						?>
					</p>
				<?php endif; ?>
			</header>

			<div class="mmi-card-body">
				<div class="mmi-image-column">
					<a class="mmi-product-image-link" href="<?php echo esc_url( $card['permalink'] ); ?>">
						<?php if ( ! empty( $card['thumb_url'] ) ) : ?>
							<img
								class="mmi-product-image"
								src="<?php echo esc_url( $card['thumb_url'] ); ?>"
								alt="<?php echo esc_attr( $card['thumb_alt'] ); ?>"
								loading="lazy"
								decoding="async"
							/>
						<?php else : ?>
							<div class="mmi-no-image"><?php esc_html_e( 'No Image', 'mmi-mobile-list' ); ?></div>
						<?php endif; ?>
					</a>
				</div>

				<div class="mmi-specs-panel">
					<div class="mmi-specs-toolbar">
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
							<a href="<?php echo esc_url( $card['specs_url'] ); ?>">
								<?php esc_html_e( 'Specifications', 'mmi-mobile-list' ); ?>
							</a>
						</div>
					</div>

					<div class="mmi-product-specs">
						<?php
						echo MMI_ML_Specs::render_row( 'chip', $card['fields']['processor'] );
						echo MMI_ML_Specs::render_row( 'memory', $card['fields']['ram_storage'] );
						echo MMI_ML_Specs::render_row( 'camera', $card['fields']['rear_camera'] );
						echo MMI_ML_Specs::render_row( 'selfie', $card['fields']['front_camera'] );
						echo MMI_ML_Specs::render_row( 'battery', $card['fields']['battery'] );
						echo MMI_ML_Specs::render_row( 'display', $card['fields']['display'] );
						?>
					</div>

					<div class="mmi-specs-footer">
						<a class="mmi-view-all-specs" href="<?php echo esc_url( $card['specs_url'] ); ?>">
							<?php esc_html_e( 'View All Specs', 'mmi-mobile-list' ); ?>
						</a>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $card['upcoming'] ) ) : ?>
				<div class="mmi-card-price-bar mmi-price-upcoming">
					<?php esc_html_e( 'Upcoming', 'mmi-mobile-list' ); ?>
				</div>
			<?php elseif ( ! empty( $card['price_html'] ) ) : ?>
				<div class="mmi-card-price-bar">
					<?php echo wp_kses_post( $card['price_html'] ); ?>
				</div>
			<?php endif; ?>
		</article>
		<?php
		return (string) ob_get_clean();
	}
}
