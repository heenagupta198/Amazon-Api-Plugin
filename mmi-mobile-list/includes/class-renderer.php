<?php
/**
 * HTML output for mobile spec cards.
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Renderer {

	/**
	 * Render a list of products.
	 *
	 * @param WC_Product[] $products    Products.
	 * @param array        $pagination  Optional paginate_links() output wrapper.
	 */
	public static function render_list( array $products, array $args = array() ): string {
		if ( empty( $products ) ) {
			return '<div class="mmi-no-products">' . esc_html__( 'No mobiles found.', 'mmi-mobile-list' ) . '</div>';
		}

		$menu_index = 0;
		$html       = '<div class="mmi-mobile-list">';

		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$html .= self::render_card( $product, $menu_index );
			++$menu_index;
		}

		$html .= '</div>';

		if ( ! empty( $args['pagination'] ) ) {
			$html .= '<div class="mmi-pagination">' . $args['pagination'] . '</div>';
		}

		return $html;
	}

	public static function render_card( WC_Product $product, int $menu_index = 0 ): string {
		$specs    = MMI_MLL_Specs::get_card_data( $product );
		$id       = $product->get_id();
		$url      = get_permalink( $id );
		$title    = $product->get_name();
		$spec_url = $url . '#specifications';

		$image = self::product_image_html( $product, $title );

		$menu_id = 'mmi-menu-' . $id . '-' . $menu_index;

		ob_start();
		?>
		<article class="mmi-mobile-card" data-product-id="<?php echo esc_attr( (string) $id ); ?>">
			<header class="mmi-card-header">
				<h3 class="mmi-product-title">
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>
				<?php if ( '' !== $specs['release_date'] ) : ?>
					<p class="mmi-release-date">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: formatted date */
								__( 'Release Date: %s', 'mmi-mobile-list' ),
								$specs['release_date']
							)
						);
						?>
					</p>
				<?php endif; ?>
			</header>

			<div class="mmi-card-body">
				<div class="mmi-image-column">
					<a class="mmi-product-image-link" href="<?php echo esc_url( $url ); ?>">
						<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
							<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'All Details', 'mmi-mobile-list' ); ?></a>
							<a href="<?php echo esc_url( $spec_url ); ?>"><?php esc_html_e( 'Specifications', 'mmi-mobile-list' ); ?></a>
						</div>
					</div>

					<div class="mmi-product-specs">
						<?php
						self::spec_row( 'processor', $specs['processor'] );
						self::spec_row( 'memory', $specs['memory'] );
						self::spec_row( 'rear', $specs['rear'] );
						self::spec_row( 'front', $specs['front'] );
						self::spec_row( 'battery', $specs['battery'] );
						self::spec_row( 'display', $specs['display'] );
						?>
					</div>

					<div class="mmi-specs-footer">
						<a class="mmi-view-all-specs" href="<?php echo esc_url( $spec_url ); ?>">
							<?php esc_html_e( 'View All Specs', 'mmi-mobile-list' ); ?>
						</a>
					</div>
				</div>
			</div>

			<?php echo self::price_bar_html( $product, $specs['is_upcoming'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	private static function spec_row( string $icon, string $text ): void {
		$text = trim( $text );
		if ( '' === $text ) {
			return;
		}
		?>
		<div class="mmi-product-spec">
			<span class="mmi-spec-icon"><?php echo MMI_MLL_Icons::render( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="mmi-spec-text"><?php echo esc_html( $text ); ?></span>
		</div>
		<?php
	}

	private static function product_image_html( WC_Product $product, string $alt ): string {
		$image_id = $product->get_image_id();
		if ( ! $image_id ) {
			return '<span class="mmi-no-image">' . esc_html__( 'No image', 'mmi-mobile-list' ) . '</span>';
		}

		$src = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
		if ( ! $src ) {
			$src = wp_get_attachment_image_url( $image_id, 'medium' );
		}
		if ( ! $src ) {
			return '<span class="mmi-no-image">' . esc_html__( 'No image', 'mmi-mobile-list' ) . '</span>';
		}

		return sprintf(
			'<img class="mmi-product-image" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
			esc_url( $src ),
			esc_attr( $alt )
		);
	}

	private static function price_bar_html( WC_Product $product, bool $is_upcoming ): string {
		$html = $product->get_price_html();
		if ( $is_upcoming && '' === trim( wp_strip_all_tags( $html ) ) ) {
			return '<div class="mmi-card-price-bar"><span class="mmi-price-upcoming">' . esc_html__( 'Coming Soon', 'mmi-mobile-list' ) . '</span></div>';
		}
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return '';
		}
		return '<div class="mmi-card-price-bar">' . $html . '</div>';
	}
}