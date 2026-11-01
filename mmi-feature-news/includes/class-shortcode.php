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
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue styles when shortcode is present on the page.
	 */
	public static function enqueue_assets(): void {
		if ( ! self::page_has_shortcode() ) {
			return;
		}
		wp_enqueue_style(
			'mmi-feature-news',
			MMI_FN_URL . 'assets/css/feature-news.css',
			array(),
			MMI_FN_VERSION
		);
	}

	/**
	 * Detect shortcode in main query content (and TagDiv templates when possible).
	 */
	private static function page_has_shortcode(): bool {
		if ( is_admin() ) {
			return false;
		}
		$post = get_queried_object();
		if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'mmi_feature_news' ) ) {
			return true;
		}
		return is_front_page() || is_home();
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, string> $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		wp_enqueue_style(
			'mmi-feature-news',
			MMI_FN_URL . 'assets/css/feature-news.css',
			array(),
			MMI_FN_VERSION
		);

		$settings = MMI_FN_Settings::get();
		$atts     = shortcode_atts(
			array(
				'title'          => $settings['section_title'],
				'category'       => $settings['category_slug'],
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
		$limit   = max( $desktop, $mobile );

		$cat = get_category_by_slug( sanitize_title( $atts['category'] ) );
		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( $cat ) {
			$args['cat'] = (int) $cat->term_id;
		} else {
			$args['category_name'] = sanitize_title( $atts['category'] );
		}

		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return '';
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
				while ( $query->have_posts() ) {
					$query->the_post();
					++$index;
					self::render_card( $index );
				}
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Single card markup.
	 *
	 * @param int $index 1-based index in the grid.
	 */
	private static function render_card( int $index ): void {
		$permalink = get_permalink();
		$title     = get_the_title();
		$author    = get_the_author();
		$time_ago  = self::human_time_ago( get_the_time( 'U' ) );
		$thumb     = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
		if ( ! $thumb ) {
			$thumb = get_the_post_thumbnail_url( get_the_ID(), 'full' );
		}
		?>
		<article class="mmi-feature-news__card" data-mmi-fn-index="<?php echo esc_attr( (string) $index ); ?>">
			<a class="mmi-feature-news__image-link" href="<?php echo esc_url( $permalink ); ?>" aria-hidden="true" tabindex="-1">
				<?php if ( $thumb ) : ?>
					<img class="mmi-feature-news__image" src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" decoding="async" />
				<?php endif; ?>
			</a>
			<div class="mmi-feature-news__body">
				<div class="mmi-feature-news__meta">
					<span class="mmi-feature-news__author"><?php echo esc_html( sprintf( __( 'By %s', 'mmi-feature-news' ), $author ) ); ?></span>
					<time class="mmi-feature-news__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( $time_ago ); ?></time>
				</div>
				<h3 class="mmi-feature-news__card-title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>
			</div>
		</article>
		<?php
	}

	/**
	 * @param int|string $post_time Unix timestamp.
	 */
	private static function human_time_ago( $post_time ): string {
		$post_time = (int) $post_time;
		if ( $post_time <= 0 ) {
			return '';
		}
		$diff = human_time_diff( $post_time, (int) current_time( 'timestamp' ) );
		return sprintf(
			__( '%s ago', 'mmi-feature-news' ),
			$diff
		);
	}
}
