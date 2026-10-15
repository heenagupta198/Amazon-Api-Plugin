<?php
/**
 * Devices subsite: Latest News carousel on single product pages.
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'woocommerce_before_single_product_related', array( __CLASS__, 'render_latest_news' ), 5 );
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'render_latest_news' ), 19 );
		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'render_latest_news' ), 12 );
		add_action( 'rehub_after_single_product', array( __CLASS__, 'render_latest_news' ), 10 );
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

		wp_enqueue_script(
			'mmi-latest',
			MMI_CSL_URL . 'assets/js/latest.js',
			array(),
			MMI_CSL_VERSION,
			true
		);

		if ( ! wp_script_is( 'swiper', 'enqueued' ) ) {
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
			wp_script_add_data( 'swiper', 'strategy', 'defer' );
		}

		wp_enqueue_script(
			'mmi-csl-related-news',
			MMI_CSL_URL . 'assets/js/related-news.js',
			array( 'swiper' ),
			MMI_CSL_VERSION,
			true
		);
	}

	private static function is_devices_product_page(): bool {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return false;
		}

		return MMI_CSL_Sites::is_devices_site();
	}

	/** @var bool */
	private static $rendered = false;

	public static function render_latest_news(): void {
		if ( self::$rendered ) {
			return;
		}

		if ( ! self::is_devices_product_page() ) {
			return;
		}

		if ( ! class_exists( 'WC_Product' ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = (int) $product->get_id();
		$posts      = MMI_CSL_Sites::get_news_for_product( $product_id, 12 );

		if ( empty( $posts ) ) {
			return;
		}

		$news_blog = MMI_CSL_Sites::get_news_blog_id();
		$switched  = false;

		if ( is_multisite() && get_current_blog_id() !== $news_blog ) {
			switch_to_blog( $news_blog );
			$switched = true;
		}

		?>
		<section class="related-news-slider mmi-device-latest-news" aria-labelledby="mmi-latest-news-heading">
			<h3 id="mmi-latest-news-heading" class="mmi-latest-news-title"><?php esc_html_e( 'Latest News', 'mmi-content-slider' ); ?></h3>
			<div class="swiper related-news-swiper">
				<div class="swiper-wrapper">
					<?php
					foreach ( $posts as $news_post ) :
						$post_id   = (int) $news_post->ID;
						$permalink = get_permalink( $post_id );
						$title     = get_the_title( $post_id );
						$thumb     = get_the_post_thumbnail_url( $post_id, 'medium' );
						$date      = get_the_date( '', $post_id );
						?>
						<div class="swiper-slide">
							<article class="news-box">
								<a href="<?php echo esc_url( $permalink ); ?>" class="news-box-link" rel="bookmark">
									<?php if ( $thumb ) : ?>
										<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" width="280" height="158" />
									<?php endif; ?>
									<h4 class="news-box-title"><?php echo esc_html( $title ); ?></h4>
									<?php if ( $date ) : ?>
										<time class="news-box-date" datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>"><?php echo esc_html( $date ); ?></time>
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

		if ( $switched ) {
			restore_current_blog();
		}

		self::$rendered = true;
	}
}
