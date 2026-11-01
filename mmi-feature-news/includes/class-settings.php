<?php
/**
 * Plugin settings (Feature section only).
 *
 * @package MMI_Feature_News
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings API.
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
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'section_title'      => 'Feature',
			'category_slug'      => 'feature',
			'desktop_count'      => 4,
			'mobile_count'       => 1,
			'view_all_label'     => 'View All',
			'view_all_url'       => 'https://www.mymobileindia.com/feature/',
			'use_remote_news'    => 1,
			'news_site_url'      => 'https://www.mymobileindia.com',
			'remote_category_id' => 9,
			'cache_minutes'      => 10,
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
		$out['use_remote_news']    = empty( $input['use_remote_news'] ) ? 0 : 1;
		$out['news_site_url']      = esc_url_raw( $input['news_site_url'] ?? $out['news_site_url'] );
		$out['remote_category_id'] = max( 0, (int) ( $input['remote_category_id'] ?? $out['remote_category_id'] ) );
		$out['cache_minutes']      = max( 1, min( 120, (int) ( $input['cache_minutes'] ?? $out['cache_minutes'] ) ) );

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
		return 'https://www.mymobileindia.com/feature/';
	}

	/**
	 * Settings page.
	 */
	public static function add_menu(): void {
		add_options_page(
			__( 'MMI Feature Section', 'mmi-feature-news' ),
			__( 'MMI Feature Section', 'mmi-feature-news' ),
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
			<h1><?php esc_html_e( 'MMI Feature Section', 'mmi-feature-news' ); ?></h1>
			<p><?php esc_html_e( 'Shortcode: [mmi_feature_news] — loads Feature posts from the English site on /devices/.', 'mmi-feature-news' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'mmi_feature_news' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Section title', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[section_title]" type="text" class="regular-text" value="<?php echo esc_attr( $s['section_title'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'English site URL', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[news_site_url]" type="url" class="large-text" value="<?php echo esc_attr( $s['news_site_url'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Load from English site', 'mmi-feature-news' ); ?></th>
						<td><label><input name="<?php echo esc_attr( self::OPTION ); ?>[use_remote_news]" type="checkbox" value="1" <?php checked( 1, (int) $s['use_remote_news'] ); ?> /> <?php esc_html_e( 'Required on /devices/', 'mmi-feature-news' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Feature category ID', 'mmi-feature-news' ); ?></th>
						<td><input name="<?php echo esc_attr( self::OPTION ); ?>[remote_category_id]" type="number" min="0" value="<?php echo esc_attr( (string) $s['remote_category_id'] ); ?>" /> <code>9</code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Desktop / mobile count', 'mmi-feature-news' ); ?></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[desktop_count]" type="number" min="1" max="12" value="<?php echo esc_attr( (string) $s['desktop_count'] ); ?>" /> /
							<input name="<?php echo esc_attr( self::OPTION ); ?>[mobile_count]" type="number" min="1" max="4" value="<?php echo esc_attr( (string) $s['mobile_count'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'View All', 'mmi-feature-news' ); ?></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[view_all_label]" type="text" class="regular-text" value="<?php echo esc_attr( $s['view_all_label'] ); ?>" />
							<input name="<?php echo esc_attr( self::OPTION ); ?>[view_all_url]" type="url" class="large-text" value="<?php echo esc_attr( $s['view_all_url'] ); ?>" />
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
