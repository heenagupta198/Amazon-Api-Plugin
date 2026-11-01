<?php
/**
 * Tech news hero: 1 large + 3 side (ReHub /devices). Mobile: lead + horizontal slides.
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hero shortcode — Tech News from English site.
 */
final class MMI_FN_Shortcode_Hero {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_shortcode( 'mmi_feature_hero', array( __CLASS__, 'render' ) );
		add_shortcode( 'mmi_featured_hero', array( __CLASS__, 'render' ) );
		add_shortcode( 'mmi_tech_news_hero', array( __CLASS__, 'render' ) );
	}

	/**
	 * @param array<string, string> $atts Attributes.
	 */
	public static function render( $atts = array() ): string {
		MMI_FN_Shortcode::enqueue_styles();

		$settings = MMI_FN_Settings::get();
		$atts     = shortcode_atts(
			array(
				'title'           => $settings['hero_title'],
				'category'        => $settings['category_slug'],
				'count'           => '4',
				'offset'          => '0',
				'view_all_label'  => $settings['view_all_label'],
				'view_all_url'    => '',
				'show_header'     => ! empty( $settings['hero_show_header'] ) ? '1' : '0',
				'show_view_all'   => ! empty( $settings['hero_show_view_all'] ) ? '1' : '0',
			),
			$atts,
			'mmi_feature_hero'
		);

		$count  = max( 1, min( 4, (int) $atts['count'] ) );
		$offset = max( 0, (int) $atts['offset'] );
		$items  = MMI_FN_Remote_Posts::get_posts( sanitize_title( $atts['category'] ), $count, $offset );

		if ( empty( $items ) ) {
			return MMI_FN_Remote_Posts::empty_message( 'mmi_feature_hero' );
		}

		$lead = $items[0];
		$side = array_slice( $items, 1, 3 );

		$view_all_url  = $atts['view_all_url'] ? esc_url( $atts['view_all_url'] ) : esc_url( MMI_FN_Settings::view_all_url() );
		$title         = trim( (string) $atts['title'] );
		$view_label    = esc_html( $atts['view_all_label'] );
		$show_header   = '0' !== $atts['show_header'];
		$show_view_all = '0' !== $atts['show_view_all'];
		$aria          = $title ? $title : __( 'Tech News', 'mmi-feature-news' );

		$uid = 'mmi-fh-' . wp_unique_id();

		ob_start();
		?>
		<section id="<?php echo esc_attr( $uid ); ?>" class="mmi-feature-hero mmi-tech-news-hero" aria-label="<?php echo esc_attr( $aria ); ?>">
			<?php if ( $show_header && ( '' !== $title || $show_view_all ) ) : ?>
				<header class="mmi-feature-hero__header">
					<?php if ( '' !== $title ) : ?>
						<h2 class="mmi-feature-hero__title"><?php echo esc_html( $title ); ?></h2>
					<?php else : ?>
						<span class="mmi-feature-hero__title-spacer" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( $show_view_all ) : ?>
						<a class="mmi-feature-hero__view-all" href="<?php echo $view_all_url; ?>"><?php echo $view_label; ?></a>
					<?php endif; ?>
				</header>
			<?php endif; ?>
			<div class="mmi-feature-hero__layout">
				<article class="mmi-feature-hero__lead">
					<a class="mmi-feature-hero__lead-image" href="<?php echo esc_url( $lead->link ); ?>">
						<?php if ( $lead->image_url ) : ?>
							<img src="<?php echo esc_url( $lead->image_url ); ?>" alt="" loading="lazy" decoding="async" />
						<?php endif; ?>
					</a>
					<div class="mmi-feature-hero__lead-body">
						<time class="mmi-feature-hero__date" datetime="<?php echo esc_attr( gmdate( 'c', $lead->timestamp ) ); ?>">
							<?php echo esc_html( MMI_FN_Remote_Posts::human_time_ago( $lead ) ); ?>
						</time>
						<h3 class="mmi-feature-hero__lead-title">
							<a href="<?php echo esc_url( $lead->link ); ?>"><?php echo esc_html( $lead->title ); ?></a>
						</h3>
					</div>
				</article>
				<?php if ( ! empty( $side ) ) : ?>
					<div class="mmi-feature-hero__side" role="list">
						<?php foreach ( $side as $item ) : ?>
							<article class="mmi-feature-hero__side-item" role="listitem">
								<a class="mmi-feature-hero__side-thumb" href="<?php echo esc_url( $item->link ); ?>">
									<?php if ( $item->image_url ) : ?>
										<img src="<?php echo esc_url( $item->image_url ); ?>" alt="" loading="lazy" decoding="async" />
									<?php endif; ?>
								</a>
								<div class="mmi-feature-hero__side-body">
									<time class="mmi-feature-hero__date" datetime="<?php echo esc_attr( gmdate( 'c', $item->timestamp ) ); ?>">
										<?php echo esc_html( MMI_FN_Remote_Posts::human_time_ago( $item ) ); ?>
									</time>
									<h3 class="mmi-feature-hero__side-title">
										<a href="<?php echo esc_url( $item->link ); ?>"><?php echo esc_html( $item->title ); ?></a>
									</h3>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
