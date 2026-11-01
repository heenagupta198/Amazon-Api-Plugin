<?php
/**
 * Plugin settings.
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings API for Feature News.
 */
final class MMI_FN_Settings {

	public const OPTION = 'mmi_feature_news_settings';

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'section_title'       => 'Feature News',
			'category_slug'       => 'feature',
			'desktop_count'       => 4,
			'mobile_count'        => 1,
			'view_all_label'      => 'View All',
			'view_all_url'        => '',
			'patch_tagdiv_block'  => 1,
			'tagdiv_block_class'  => 'tdi_59',
			'hide_legacy_block'   => 0,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Register option fields.
	 */
	public static function register_settings(): void {
		register_setting(
			'mmi_feature_news',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$out['section_title']      = sanitize_text_field( $input['section_title'] ?? $out['section_title'] );
		$out['category_slug']      = sanitize_title( $input['category_slug'] ?? $out['category_slug'] );
		$out['desktop_count']      = max( 1, min( 12, (int) ( $input['desktop_count'] ?? $out['desktop_count'] ) ) );
		$out['mobile_count']       = max( 1, min( 4, (int) ( $input['mobile_count'] ?? $out['mobile_count'] ) ) );
		$out['view_all_label']     = sanitize_text_field( $input['view_all_label'] ?? $out['view_all_label'] );
		$out['view_all_url']       = esc_url_raw( $input['view_all_url'] ?? $out['view_all_url'] );
		$out['patch_tagdiv_block'] = empty( $input['patch_tagdiv_block'] ) ? 0 : 1;
		$out['hide_legacy_block']  = empty( $input['hide_legacy_block'] ) ? 0 : 1;

		$class = sanitize_html_class( $input['tagdiv_block_class'] ?? $out['tagdiv_block_class'] );
		if ( '' === $class ) {
			$class = 'tdi_59';
		}
		$out['tagdiv_block_class'] = $class;

		return $out;
	}

	/**
	 * Resolved View All URL.
	 */
	public static function view_all_url(): string {
		$s = self::get();
		if ( ! empty( $s['view_all_url'] ) ) {
			return $s['view_all_url'];
		}
		$slug = $s['category_slug'];
		if ( $slug ) {
			$term = get_category_by_slug( $slug );
			if ( $term && ! is_wp_error( $term ) ) {
				$link = get_category_link( $term->term_id );
				if ( $link && ! is_wp_error( $link ) ) {
					return $link;
				}
			}
			return home_url( '/' . $slug . '/' );
		}
		return home_url( '/' );
	}

	/**
	 * Settings page.
	 */
	public static function add_menu(): void {
		add_options_page(
			__( 'MMI Feature News', 'mmi-feature-news' ),
			__( 'MMI Feature News', 'mmi-feature-news' ),
			'manage_options',
			'mmi-feature-news',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render settings UI.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::get();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MMI Feature News', 'mmi-feature-news' ); ?></h1>
			<p><?php esc_html_e( 'Use shortcode [mmi_feature_news] on the homepage (TagDiv Composer → Raw HTML / Shortcode block). Legacy TagDiv “Feature” slider can be patched or hidden below.', 'mmi-feature-news' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'mmi_feature_news' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Section title', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[section_title]" type="text" class="regular-text" value="<?php echo esc_attr( $s['section_title'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Category slug', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[category_slug]" type="text" class="regular-text" value="<?php echo esc_attr( $s['category_slug'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Posts on desktop', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[desktop_count]" type="number" min="1" max="12" value="<?php echo esc_attr( (string) $s['desktop_count'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Posts on mobile', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[mobile_count]" type="number" min="1" max="4" value="<?php echo esc_attr( (string) $s['mobile_count'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'View All label', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[view_all_label]" type="text" class="regular-text" value="<?php echo esc_attr( $s['view_all_label'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'View All URL', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[view_all_url]" type="url" class="large-text" placeholder="<?php echo esc_attr( self::view_all_url() ); ?>" value="<?php echo esc_attr( $s['view_all_url'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Patch old TagDiv block', 'mmi-feature-news' ); ?></th>
						<td>
							<label>
								<input name="<?php echo esc_attr( self::OPTION ); ?>[patch_tagdiv_block]" type="checkbox" value="1" <?php checked( 1, (int) $s['patch_tagdiv_block'] ); ?> />
								<?php esc_html_e( 'Disable slider / pagination / auto-advance on existing block (CSS + JS)', 'mmi-feature-news' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'TagDiv block CSS class', 'mmi-feature-news' ); ?></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[tagdiv_block_class]" type="text" class="regular-text" value="<?php echo esc_attr( $s['tagdiv_block_class'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Example: tdi_59 (inspect the old Feature module on the homepage).', 'mmi-feature-news' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hide legacy block', 'mmi-feature-news' ); ?></th>
						<td>
							<label>
								<input name="<?php echo esc_attr( self::OPTION ); ?>[hide_legacy_block]" type="checkbox" value="1" <?php checked( 1, (int) $s['hide_legacy_block'] ); ?> />
								<?php esc_html_e( 'Hide the old TagDiv block when using the shortcode (recommended after placing [mmi_feature_news]).', 'mmi-feature-news' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
