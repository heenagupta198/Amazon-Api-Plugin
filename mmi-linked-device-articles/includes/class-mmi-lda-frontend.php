<?php
/**
 * Devices site: Related Articles carousel above ReHub Related Products.
 */

defined( 'ABSPATH' ) || exit;

class MMI_LDA_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_latest_news_footer' ), 15 );
	}

	public static function enqueue_assets(): void {
		if ( ! self::is_devices_product_page() ) {
			return;
		}

		wp_enqueue_style(
			'mmi-lda-latest',
			MMI_LDA_URL . 'assets/css/latest.css',
			array(),
			MMI_LDA_VERSION
		);

		$swiper_loaded = wp_script_is( 'swiper', 'enqueued' ) || wp_script_is( 'swiper-js', 'enqueued' ) || wp_script_is( 'mmi-lda-swiper', 'enqueued' );
		if ( ! $swiper_loaded ) {
			wp_enqueue_style(
				'mmi-lda-swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
				array(),
				'11.0.0'
			);
			wp_enqueue_script(
				'mmi-lda-swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
				array(),
				'11.0.0',
				true
			);
		}

		wp_enqueue_script(
			'mmi-lda-related-news',
			MMI_LDA_URL . 'assets/js/related-news.js',
			array(),
			MMI_LDA_VERSION,
			true
		);

		$product_id = self::get_current_product_id();
		wp_localize_script(
			'mmi-lda-related-news',
			'MmiLdaNews',
			array(
				'productId' => $product_id,
				'restUrl'   => trailingslashit( MMI_LDA_Settings::get_news_rest_url() ) . 'mmi-lda/v1/news-by-product/',
				'heading'   => __( 'Related Articles', 'mmi-linked-device-articles' ),
				'prevLabel' => __( 'Previous', 'mmi-linked-device-articles' ),
				'nextLabel' => __( 'Next', 'mmi-linked-device-articles' ),
			)
		);
	}

	private static function is_devices_product_page(): bool {
		if ( ! MMI_LDA_Sites::is_devices_site() ) {
			return false;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		return is_singular( array( 'product', 'devices' ) );
	}

	private static function get_current_product_id(): int {
		if ( function_exists( 'wc_get_product' ) ) {
			global $product;
			if ( $product instanceof WC_Product ) {
				return (int) $product->get_id();
			}

			$from_query = wc_get_product( get_queried_object_id() );
			if ( $from_query instanceof WC_Product ) {
				return (int) $from_query->get_id();
			}
		}

		return (int) get_queried_object_id();
	}

	public static function render_latest_news_footer(): void {
		if ( ! self::is_devices_product_page() ) {
			return;
		}

		$product_id = self::get_current_product_id();
		if ( $product_id <= 0 ) {
			return;
		}

		$items = array();
		if ( class_exists( 'MMI_LDA_Remote' ) ) {
			$items = MMI_LDA_Remote::get_news_items_for_product( $product_id, 12 );
		}

		echo '<div id="mmi-lda-related-mount" class="mmi-lda-related-mount" data-product-id="' . esc_attr( (string) $product_id ) . '">';
		if ( ! empty( $items ) ) {
			self::print_carousel_markup( $items );
		}
		echo '</div>';
		?>
		<script>
		(function () {
			var mount = document.getElementById('mmi-lda-related-mount');
			var anchor = document.querySelector('#related-section-woo-area, .related-woo-area');
			if (mount && anchor && anchor.parentNode) {
				anchor.parentNode.insertBefore(mount, anchor);
			}
		})();
		</script>
		<?php
	}

	/**
	 * @param array<int,array{id:int,title:string,url:string,date:string,image:string}> $items News rows.
	 */
	private static function print_carousel_markup( array $items ): void {
		?>
		<section class="related-news-slider mmi-device-latest-news" aria-labelledby="mmi-latest-news-heading">
			<h3 id="mmi-latest-news-heading" class="mmi-latest-news-title"><?php esc_html_e( 'Related Articles', 'mmi-linked-device-articles' ); ?></h3>
			<div class="swiper mmi-related-news-swiper">
				<div class="swiper-wrapper">
					<?php foreach ( $items as $item ) : ?>
						<div class="swiper-slide">
							<article class="news-box">
								<a href="<?php echo esc_url( $item['url'] ); ?>" class="news-box-link" rel="bookmark">
									<?php if ( ! empty( $item['image'] ) ) : ?>
										<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" loading="lazy" width="280" height="158" />
									<?php endif; ?>
									<h4 class="news-box-title"><?php echo esc_html( $item['title'] ); ?></h4>
									<?php if ( ! empty( $item['date'] ) ) : ?>
										<time class="news-box-date" datetime="<?php echo esc_attr( $item['date'] ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item['date'] ) ) ); ?></time>
									<?php endif; ?>
								</a>
							</article>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Previous', 'mmi-linked-device-articles' ); ?>"></button>
				<button type="button" class="swiper-button-next" aria-label="<?php esc_attr_e( 'Next', 'mmi-linked-device-articles' ); ?>"></button>
			</div>
		</section>
		<?php
	}
}
