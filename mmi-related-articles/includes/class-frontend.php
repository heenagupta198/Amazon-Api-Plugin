<?php
/**
 * Frontend carousel output (Newspaper / TagDiv friendly hooks).
 *
 * @package MMI_Related_Articles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Frontend renderer.
 */
class MMI_RA_Frontend {

	/**
	 * @var bool
	 */
	private static $rendered = false;

	/**
	 * Register hooks.
	 */
	public static function init() {
		/**
		 * Newspaper / TagDiv: after author box & theme related area, before comments.
		 * Fires on most single templates (matches your screenshot placement).
		 */
		add_action( 'comment_form_before', array( __CLASS__, 'maybe_render' ), 5 );

		// When comments are closed, still show carousel after the article modules.
		add_action( 'loop_end', array( __CLASS__, 'maybe_render_after_loop' ), 25 );

		// Non–TagDiv themes (avoid duplicating inside Newspaper post body).
		if ( ! defined( 'TD_THEME_VERSION' ) ) {
			add_filter( 'the_content', array( __CLASS__, 'append_to_content' ), 1001 );
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Render when comments are disabled (comment_form_before never runs).
	 */
	public static function maybe_render_after_loop() {
		if ( self::$rendered || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return;
		}
		if ( comments_open() || get_comments_number() ) {
			return;
		}
		self::output_carousel( get_the_ID() );
	}

	/**
	 * Render once on supported actions.
	 */
	public static function maybe_render() {
		if ( self::$rendered || ! is_singular( 'post' ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}
		self::output_carousel( $post_id );
	}

	/**
	 * @param string $content Post content.
	 * @return string
	 */
	public static function append_to_content( $content ) {
		if ( self::$rendered || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$html = self::get_carousel_html( get_the_ID() );
		if ( '' === $html ) {
			return $content;
		}

		self::$rendered = true;
		return $content . $html;
	}

	/**
	 * @param int $post_id Post ID.
	 */
	private static function output_carousel( $post_id ) {
		if ( self::$rendered ) {
			return;
		}
		$html = self::get_carousel_html( $post_id );
		if ( '' === $html ) {
			return;
		}
		self::$rendered = true;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in get_carousel_html.
		echo $html;
	}

	/**
	 * @param int $post_id Current post.
	 * @return string
	 */
	public static function get_carousel_html( $post_id ) {
		$post_id = absint( $post_id );
		$ids     = MMI_RA_Meta_Box::get_saved_ids( $post_id );
		if ( empty( $ids ) ) {
			return '';
		}

		$posts = MMI_RA_Query::get_posts_by_ids( $ids );
		if ( empty( $posts ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="mmi-ra-carousel-wrap" aria-label="<?php esc_attr_e( 'Related Articles', 'mmi-related-articles' ); ?>">
			<div class="mmi-ra-carousel-header">
				<h2 class="mmi-ra-carousel-title"><?php esc_html_e( 'RELATED ARTICLES', 'mmi-related-articles' ); ?></h2>
			</div>
			<div class="mmi-ra-carousel" data-mmi-ra-carousel>
				<button type="button" class="mmi-ra-nav mmi-ra-prev" aria-label="<?php esc_attr_e( 'Previous', 'mmi-related-articles' ); ?>">&#10094;</button>
				<div class="mmi-ra-track-viewport">
					<div class="mmi-ra-track">
						<?php foreach ( $posts as $related ) : ?>
							<article class="mmi-ra-card">
								<a href="<?php echo esc_url( get_permalink( $related ) ); ?>" class="mmi-ra-card-link">
									<div class="mmi-ra-card-thumb">
										<?php
										if ( has_post_thumbnail( $related ) ) {
											echo get_the_post_thumbnail( $related, 'medium', array( 'loading' => 'lazy' ) );
										} else {
											echo '<img src="' . esc_url( MMI_RA_Query::get_thumb_url( $related->ID ) ) . '" alt="" loading="lazy" />';
										}
										?>
									</div>
									<h3 class="mmi-ra-card-title"><?php echo esc_html( get_the_title( $related ) ); ?></h3>
									<time class="mmi-ra-card-date" datetime="<?php echo esc_attr( get_the_date( 'c', $related ) ); ?>"><?php echo esc_html( get_the_date( '', $related ) ); ?></time>
								</a>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
				<button type="button" class="mmi-ra-nav mmi-ra-next" aria-label="<?php esc_attr_e( 'Next', 'mmi-related-articles' ); ?>">&#10095;</button>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Enqueue frontend assets on single posts with related IDs.
	 */
	public static function enqueue_assets() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$ids = MMI_RA_Meta_Box::get_saved_ids( get_queried_object_id() );
		if ( empty( $ids ) ) {
			return;
		}

		wp_enqueue_style(
			'mmi-ra-frontend',
			MMI_RA_URL . 'assets/css/frontend.css',
			array(),
			MMI_RA_VERSION
		);

		wp_enqueue_script(
			'mmi-ra-frontend',
			MMI_RA_URL . 'assets/js/frontend.js',
			array(),
			MMI_RA_VERSION,
			true
		);
	}
}
