<?php
/**
 * URLs for two separate WordPress installs (news + devices).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Settings {

	const OPTION_DEVICES_URL = 'mmi_csl_devices_site_url';
	const OPTION_NEWS_URL    = 'mmi_csl_news_site_url';

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_settings(): void {
		register_setting( 'mmi_csl_settings', self::OPTION_DEVICES_URL, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_url' ) ) );
		register_setting( 'mmi_csl_settings', self::OPTION_NEWS_URL, array( 'sanitize_callback' => array( __CLASS__, 'sanitize_url' ) ) );
	}

	public static function register_menu(): void {
		add_options_page(
			__( 'MMI Device News', 'mmi-content-slider' ),
			__( 'MMI Device News', 'mmi-content-slider' ),
			'manage_options',
			'mmi-csl-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MMI Device News — Site URLs', 'mmi-content-slider' ); ?></h1>
			<p><?php esc_html_e( 'Use this when news (mymobileindia.com) and devices (/devices/) are separate WordPress installs with separate databases.', 'mmi-content-slider' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'mmi_csl_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="mmi_csl_devices_site_url"><?php esc_html_e( 'Devices site URL', 'mmi-content-slider' ); ?></label></th>
						<td>
							<input type="url" class="regular-text" id="mmi_csl_devices_site_url" name="<?php echo esc_attr( self::OPTION_DEVICES_URL ); ?>" value="<?php echo esc_attr( self::get_devices_site_url() ); ?>" />
							<p class="description"><?php esc_html_e( 'WooCommerce store, e.g. https://www.mymobileindia.com/devices', 'mmi-content-slider' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mmi_csl_news_site_url"><?php esc_html_e( 'News site URL', 'mmi-content-slider' ); ?></label></th>
						<td>
							<input type="url" class="regular-text" id="mmi_csl_news_site_url" name="<?php echo esc_attr( self::OPTION_NEWS_URL ); ?>" value="<?php echo esc_attr( self::get_news_site_url() ); ?>" />
							<p class="description"><?php esc_html_e( 'English news site, e.g. https://www.mymobileindia.com', 'mmi-content-slider' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function sanitize_url( string $url ): string {
		$url = esc_url_raw( trim( $url ) );
		return untrailingslashit( $url );
	}

	public static function get_devices_site_url(): string {
		$stored = (string) get_option( self::OPTION_DEVICES_URL, '' );
		if ( '' !== $stored ) {
			return untrailingslashit( $stored );
		}

		if ( MMI_CSL_Sites::is_devices_site() ) {
			return untrailingslashit( home_url() );
		}

		/**
		 * Default devices WooCommerce site URL (separate WP install).
		 *
		 * @param string $url Devices site URL without trailing slash.
		 */
		return untrailingslashit( apply_filters( 'mmi_csl_default_devices_url', 'https://www.mymobileindia.com/devices' ) );
	}

	public static function get_news_site_url(): string {
		$stored = (string) get_option( self::OPTION_NEWS_URL, '' );
		if ( '' !== $stored ) {
			return untrailingslashit( $stored );
		}

		if ( MMI_CSL_Sites::is_news_site() ) {
			return untrailingslashit( home_url() );
		}

		$home = untrailingslashit( home_url() );
		if ( false !== strpos( $home, '/devices' ) ) {
			$guess = preg_replace( '#/devices.*$#', '', $home );
			if ( is_string( $guess ) && '' !== $guess ) {
				return $guess;
			}
		}

		return untrailingslashit( apply_filters( 'mmi_csl_default_news_url', 'https://www.mymobileindia.com' ) );
	}

	public static function get_devices_rest_url(): string {
		return self::get_devices_site_url() . '/wp-json';
	}

	public static function get_news_rest_url(): string {
		return self::get_news_site_url() . '/wp-json';
	}
}
