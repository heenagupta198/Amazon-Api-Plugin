<?php
/**
 * Plugin settings — RapidAPI credentials and defaults.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Settings {

	public const OPTION_API_KEY      = 'mmi_aps_rapidapi_key';
	public const OPTION_API_HOST     = 'mmi_aps_rapidapi_host';
	public const OPTION_API_ENDPOINT = 'mmi_aps_api_endpoint';
	public const OPTION_COUNTRY      = 'mmi_aps_country';
	public const OPTION_LANGUAGE     = 'mmi_aps_language';
	public const OPTION_AUTO_SYNC    = 'mmi_aps_auto_sync_enabled';
	public const OPTION_SYNC_INTERVAL = 'mmi_aps_sync_interval';
	public const OPTION_SYNC_DELAY   = 'mmi_aps_sync_delay';
	public const OPTION_BATCH_SIZE   = 'mmi_aps_sync_batch_size';
	public const OPTION_AFFILIATE_ENABLED   = 'mmi_aps_affiliate_enabled';
	public const OPTION_AFFILIATE_TAG       = 'mmi_aps_affiliate_tag';
	public const OPTION_AFFILIATE_BTN_TEXT  = 'mmi_aps_affiliate_button_text';
	public const OPTION_AFFILIATE_SHOP      = 'mmi_aps_affiliate_show_shop';
	public const OPTION_AFFILIATE_DISCLOSURE = 'mmi_aps_affiliate_disclosure';
	public const OPTION_CRON_BATCH_SIZE      = 'mmi_aps_cron_batch_size';

	public const DEFAULT_HOST     = 'real-time-amazon-data.p.rapidapi.com';
	public const DEFAULT_ENDPOINT = '/product-details';

	public static function init(): void {
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'woocommerce_admin_field_mmi_aps_constant_notice', array( __CLASS__, 'render_constant_notice_field' ) );
	}

	/**
	 * Render read-only notice when API key is defined in wp-config.php.
	 *
	 * @param array<string,mixed> $value Field definition.
	 */
	public static function render_constant_notice_field( array $value ): void {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
			<td class="forminp">
				<p class="description">
					<?php esc_html_e( 'API key is defined via MMI_APS_RAPIDAPI_KEY in wp-config.php.', 'mmi-amazon-price-sync' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Register settings tab under WooCommerce.
	 *
	 * @param array $settings Existing settings pages.
	 * @return array
	 */
	public static function add_settings_page( array $settings ): array {
		require_once MMI_APS_PATH . 'includes/class-wc-settings-amazon-price.php';
		$settings[] = new MMI_APS_WC_Settings_Amazon_Price();
		return $settings;
	}

	/**
	 * Get RapidAPI key — wp-config constant takes priority.
	 */
	public static function get_api_key(): string {
		if ( defined( 'MMI_APS_RAPIDAPI_KEY' ) && MMI_APS_RAPIDAPI_KEY ) {
			return trim( (string) MMI_APS_RAPIDAPI_KEY );
		}

		return trim( (string) get_option( self::OPTION_API_KEY, '' ) );
	}

	public static function get_api_host(): string {
		$host = trim( (string) get_option( self::OPTION_API_HOST, self::DEFAULT_HOST ) );
		return $host ?: self::DEFAULT_HOST;
	}

	public static function get_api_endpoint(): string {
		$endpoint = trim( (string) get_option( self::OPTION_API_ENDPOINT, self::DEFAULT_ENDPOINT ) );
		return $endpoint ?: self::DEFAULT_ENDPOINT;
	}

	public static function get_country(): string {
		$country = trim( (string) get_option( self::OPTION_COUNTRY, 'IN' ) );
		return $country ?: 'IN';
	}

	public static function get_language(): string {
		$language = trim( (string) get_option( self::OPTION_LANGUAGE, 'en_IN' ) );
		return $language ?: 'en_IN';
	}

	public static function is_auto_sync_enabled(): bool {
		return 'yes' === get_option( self::OPTION_AUTO_SYNC, 'yes' );
	}

	public static function get_sync_interval(): string {
		$interval = (string) get_option( self::OPTION_SYNC_INTERVAL, 'mmi_aps_twelve_hours' );
		$allowed  = array( 'hourly', 'mmi_aps_six_hours', 'mmi_aps_twelve_hours', 'daily' );
		return in_array( $interval, $allowed, true ) ? $interval : 'mmi_aps_twelve_hours';
	}

	public static function get_sync_delay(): int {
		$delay = (int) get_option( self::OPTION_SYNC_DELAY, 2 );
		return max( 1, min( 10, $delay ) );
	}

	public static function get_batch_size(): int {
		$size = (int) get_option( self::OPTION_BATCH_SIZE, 10 );
		return max( 1, min( 50, $size ) );
	}

	public static function is_affiliate_enabled(): bool {
		return 'yes' === get_option( self::OPTION_AFFILIATE_ENABLED, 'no' );
	}

	public static function get_affiliate_tag(): string {
		return trim( (string) get_option( self::OPTION_AFFILIATE_TAG, '' ) );
	}

	public static function get_affiliate_button_text(): string {
		$text = trim( (string) get_option( self::OPTION_AFFILIATE_BTN_TEXT, '' ) );
		return $text ?: __( 'Go to Store', 'mmi-amazon-price-sync' );
	}

	public static function show_affiliate_on_shop(): bool {
		return 'yes' === get_option( self::OPTION_AFFILIATE_SHOP, 'no' );
	}

	public static function show_affiliate_disclosure(): bool {
		return 'yes' === get_option( self::OPTION_AFFILIATE_DISCLOSURE, 'yes' );
	}

	public static function get_cron_batch_size(): int {
		$size = (int) get_option( self::OPTION_CRON_BATCH_SIZE, 15 );
		return max( 5, min( 50, $size ) );
	}

	/**
	 * Sanitize affiliate tag — alphanumeric and hyphens only.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_affiliate_tag( $value ): string {
		$tag = strtolower( trim( sanitize_text_field( (string) $value ) ) );
		if ( '' === $tag ) {
			return '';
		}

		return preg_match( '/^[a-z0-9-]{3,40}$/', $tag ) ? $tag : '';
	}
}
