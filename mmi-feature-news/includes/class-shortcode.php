<?php
/**
 * Feature News shortcode output.
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders static Feature News grid.
 */
final class MMI_FN_Shortcode {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_shortcode( 'mmi_feature_news', array( __CLASS__, 'render' ) );
		add_shortcode( 'mmi_featured_news', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue styles when shortcode is present on the page.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		self::enqueue_styles();
	}

	/**
	 * Public enqueue for hero + news shortcodes.
	 */
	public static function enqueue_styles(): void {
		wp_enqueue_style(
			'mmi-feature-news',
			MMI_FN_URL . 'assets/css/feature-news.css',
			array(),
			MMI_FN_VERSION
		);
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, string> $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		self::enqueue_styles();

		$settings = MMI_FN_Settings::get();
		$atts     = shortcode_atts(
			array(
				'title'          => $settings['section_title'],
				'category'       => $settings['category_slug'],
				'desktop_count'  => (string) $settings['desktop_count'],
				'mobile_count'   => (string) $settings['mobile_count'],
				'offset'         => '0',
				'view_all_label' => $settings['view_all_label'],
				'view_all_url'   => $settings['view_all_url'],
			),
			$atts,
			'mmi_feature_news'
		);

		$desktop = max( 1, (int) $atts['desktop_count'] );
		$mobile  = max( 1, (int) $atts['mobile_count'] );
		$offset  = max( 0, (int) $atts['offset'] );
		$fetch   = max( $desktop, $mobile );

		$items = MMI_FN_Remote_Posts::get_posts( sanitize_title( $atts['category'] ), $fetch, $offset );
		if ( empty( $items ) ) {
			return MMI_FN_Remote_Posts::empty_message( 'mmi_feature_news' );
		}

		$view_all_url = $atts['view_all_url'] ? esc_url( $atts['view_all_url'] ) : esc_url( MMI_FN_Settings::view_all_url() );
		$title        = esc_html( $atts['title'] );
		$view_label   = esc_html( $atts['view_all_label'] );

		$uid = 'mmi-fn-' . wp_unique_id();

		ob_start();
		?>
		<style>
			#<?php echo esc_attr( $uid ); ?> .mmi-feature-news__grid .mmi-feature-news__card:nth-child(n+<?php echo (int) ( $desktop + 1 ); ?>) { display: none; }
			@media (max-width: 767px) {
				#<?php echo esc_attr( $uid ); ?> .mmi-feature-news__grid .mmi-feature-news__card { display: flex; }
				#<?php echo esc_attr( $uid ); ?> .mmi-feature-news__grid .mmi-feature-news__card:nth-child(n+<?php echo (int) ( $mobile + 1 ); ?>) { display: none; }
			}
		</style>
		<section
			id="<?php echo esc_attr( $uid ); ?>"
			class="mmi-feature-news"
			aria-label="<?php echo esc_attr( $title ); ?>"
		>
			<header class="mmi-feature-news__header">
				<h2 class="mmi-feature-news__title"><?php echo $title; ?></h2>
				<a class="mmi-feature-news__view-all" href="<?php echo $view_all_url; ?>"><?php echo $view_label; ?></a>
			</header>
			<div class="mmi-feature-news__grid">
				<?php
				$index = 0;
				foreach ( $items as $item ) {
					++$index;
					self::render_card( $item, $index );
				}
				?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Single card markup.
	 *
	 * @param MMI_FN_Post_Item $item  Post data.
	 * @param int              $index 1-based index in the grid.
	 */
	private static function render_card( MMI_FN_Post_Item $item, int $index ): void {
		$time_ago = MMI_FN_Remote_Posts::human_time_ago( $item );
		?>
		<article class="mmi-feature-news__card" data-mmi-fn-index="<?php echo esc_attr( (string) $index ); ?>">
			<a class="mmi-feature-news__image-link" href="<?php echo esc_url( $item->link ); ?>" aria-hidden="true" tabindex="-1">
				<?php if ( $item->image_url ) : ?>
					<img class="mmi-feature-news__image" src="<?php echo esc_url( $item->image_url ); ?>" alt="" loading="lazy" decoding="async" />
				<?php endif; ?>
			</a>
			<div class="mmi-feature-news__body">
				<div class="mmi-feature-news__meta">
					<span class="mmi-feature-news__author"><?php echo esc_html( sprintf( __( 'By %s', 'mmi-feature-news' ), $item->author_name ) ); ?></span>
					<time class="mmi-feature-news__date" datetime="<?php echo esc_attr( gmdate( 'c', $item->timestamp ) ); ?>"><?php echo esc_html( $time_ago ); ?></time>
				</div>
				<h3 class="mmi-feature-news__card-title">
					<a href="<?php echo esc_url( $item->link ); ?>"><?php echo esc_html( $item->title ); ?></a>
				</h3>
			</div>
		</article>
		<?php
	}
}
