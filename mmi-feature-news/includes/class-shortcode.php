<?php
/**
 * Feature section shortcode only.
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders static Feature grid.
 */
final class MMI_FN_Shortcode {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_shortcode( 'mmi_feature_news', array( __CLASS__, 'render' ) );
		// Old shortcodes removed — return nothing so delete those Elementor widgets.
		add_shortcode( 'mmi_feature_hero', '__return_empty_string' );
		add_shortcode( 'mmi_featured_hero', '__return_empty_string' );
		add_shortcode( 'mmi_tech_news_hero', '__return_empty_string' );
		add_shortcode( 'mmi_featured_news', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue styles when needed.
	 */
	public static function enqueue_assets(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		self::enqueue_styles();
	}

	/**
	 * Public enqueue helper.
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
	 * @param array<string, string> $atts Attributes.
	 */
	public static function render( $atts = array() ): string {
		self::enqueue_styles();

		$settings = MMI_FN_Settings::get();
		$atts     = shortcode_atts(
			array(
				'title'          => $settings['section_title'],
				'category'       => 'feature',
				'desktop_count'  => (string) $settings['desktop_count'],
				'mobile_count'   => (string) $settings['mobile_count'],
				'view_all_label' => $settings['view_all_label'],
				'view_all_url'   => $settings['view_all_url'],
			),
			$atts,
			'mmi_feature_news'
		);

		$desktop = max( 1, (int) $atts['desktop_count'] );
		$mobile  = max( 1, (int) $atts['mobile_count'] );
		$items   = MMI_FN_Remote_Posts::get_posts( 'feature', max( $desktop, $mobile ), 0 );

		if ( empty( $items ) ) {
			return MMI_FN_Remote_Posts::empty_message( 'mmi_feature_news' );
		}

		$view_all_url = esc_url( $atts['view_all_url'] ?: MMI_FN_Settings::view_all_url() );
		$title        = esc_html( $atts['title'] );
		$view_label   = esc_html( $atts['view_all_label'] );
		$uid          = 'mmi-fn-' . wp_unique_id();

		ob_start();
		?>
		<style>
			#<?php echo esc_attr( $uid ); ?> .mmi-feature-news__card:nth-child(n+<?php echo (int) ( $desktop + 1 ); ?>) { display: none; }
			@media (max-width: 767px) {
				#<?php echo esc_attr( $uid ); ?> .mmi-feature-news__card { display: flex; }
				#<?php echo esc_attr( $uid ); ?> .mmi-feature-news__card:nth-child(n+<?php echo (int) ( $mobile + 1 ); ?>) { display: none; }
			}
		</style>
		<section id="<?php echo esc_attr( $uid ); ?>" class="mmi-feature-news">
			<header class="mmi-feature-news__header">
				<h2 class="mmi-feature-news__title"><?php echo $title; ?></h2>
				<a class="mmi-feature-news__view-all" href="<?php echo $view_all_url; ?>"><?php echo $view_label; ?></a>
			</header>
			<div class="mmi-feature-news__grid">
				<?php
				$i = 0;
				foreach ( $items as $item ) {
					++$i;
					self::render_card( $item );
				}
				?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param MMI_FN_Post_Item $item Post.
	 */
	private static function render_card( MMI_FN_Post_Item $item ): void {
		$time_ago = MMI_FN_Remote_Posts::human_time_ago( $item );
		?>
		<article class="mmi-feature-news__card">
			<a class="mmi-feature-news__image-link" href="<?php echo esc_url( $item->link ); ?>">
				<?php if ( $item->image_url ) : ?>
					<img class="mmi-feature-news__image" src="<?php echo esc_url( $item->image_url ); ?>" alt="" loading="lazy" decoding="async" />
				<?php endif; ?>
			</a>
			<div class="mmi-feature-news__body">
				<div class="mmi-feature-news__meta">
					<span><?php echo esc_html( sprintf( __( 'By %s', 'mmi-feature-news' ), $item->author_name ) ); ?></span>
					<time datetime="<?php echo esc_attr( gmdate( 'c', $item->timestamp ) ); ?>"><?php echo esc_html( $time_ago ); ?></time>
				</div>
				<h3 class="mmi-feature-news__card-title">
					<a href="<?php echo esc_url( $item->link ); ?>"><?php echo esc_html( $item->title ); ?></a>
				</h3>
			</div>
		</article>
		<?php
	}
}
