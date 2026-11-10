<?php
/**
 * Devices site: Related Articles carousel above ReHub Related Products.
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_latest_news_footer' ), 15 );
	}

	public static function enqueue_assets(): void {
		if ( ! self::is_devices_product_page() ) {
			return;
		}

		wp_enqueue_style(
			'mmi-latest',
			MMI_CSL_URL . 'assets/css/latest.css',
			array(),
			MMI_CSL_VERSION
		);

		$swiper_loaded = wp_script_is( 'swiper', 'enqueued' ) || wp_script_is( 'swiper-js', 'enqueued' );
		if ( ! $swiper_loaded ) {
			wp_enqueue_style(
				'swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
				array(),
				'11.0.0'
			);
			wp_enqueue_script(
				'swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
				array(),
				'11.0.0',
				true
			);
		}

		wp_enqueue_script(
			'mmi-csl-related-news',
			MMI_CSL_URL . 'assets/js/related-news.js',
			array(),
			MMI_CSL_VERSION,
			true
		);

		$product_id = self::get_current_product_id();
		wp_localize_script(
			'mmi-csl-related-news',
			'MmiCslNews',
			array(
				'productId' => $product_id,
				'restUrl'   => trailingslashit( MMI_CSL_Settings::get_news_rest_url() ) . 'mmi-csl/v1/news-by-product/',
				'heading'   => __( 'Related Articles', 'mmi-content-slider' ),
				'prevLabel' => __( 'Previous', 'mmi-content-slider' ),
				'nextLabel' => __( 'Next', 'mmi-content-slider' ),
			)
		);
	}

	private static function is_devices_product_page(): bool {
		if ( ! MMI_CSL_Sites::is_devices_site() ) {
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
		if ( class_exists( 'MMI_CSL_Remote' ) ) {
			$items = MMI_CSL_Remote::get_news_items_for_product( $product_id, 12 );
		}

		echo '<div id="mmi-latest-news-mount" class="mmi-latest-news-mount" data-product-id="' . esc_attr( (string) $product_id ) . '">';
		if ( ! empty( $items ) ) {
			self::print_carousel_markup( $items );
		}
		echo '</div>';
	}

	/**
	 * @param array<int,array{id:int,title:string,url:string,date:string,image:string}> $items News rows.
	 */
	private static function print_carousel_markup( array $items ): void {
		?>
		<section class="related-news-slider mmi-device-latest-news" aria-labelledby="mmi-latest-news-heading">
			<h3 id="mmi-latest-news-heading" class="mmi-latest-news-title"><?php esc_html_e( 'Related Articles', 'mmi-content-slider' ); ?></h3>
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
				<button type="button" class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Previous', 'mmi-content-slider' ); ?>"></button>
				<button type="button" class="swiper-button-next" aria-label="<?php esc_attr_e( 'Next', 'mmi-content-slider' ); ?>"></button>
			</div>
		</section>
		<?php
	}
}
