<?php
/**
 * Plugin settings — RapidAPI credentials and defaults.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Settings {

	public const OPTION_API_PROVIDER         = 'mmi_aps_api_provider';
	public const OPTION_CREATORS_CLIENT_ID   = 'mmi_aps_creators_client_id';
	public const OPTION_CREATORS_CLIENT_SECRET = 'mmi_aps_creators_client_secret';
	public const OPTION_CREATORS_VERSION     = 'mmi_aps_creators_version';
	public const OPTION_MARKETPLACE          = 'mmi_aps_marketplace';
	public const OPTION_API_KEY      = 'mmi_aps_rapidapi_key';
	public const OPTION_API_HOST     = 'mmi_aps_rapidapi_host';
	public const OPTION_API_ENDPOINT = 'mmi_aps_api_endpoint';
	public const OPTION_COUNTRY      = 'mmi_aps_country';
	public const OPTION_LANGUAGE     = 'mmi_aps_language';
	public const OPTION_SYNC_ENABLED = 'mmi_aps_sync_enabled';
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

	public const DEFAULT_HOST       = 'real-time-amazon-data.p.rapidapi.com';
	public const DEFAULT_ENDPOINT   = '/product-details';
	public const DEFAULT_MARKETPLACE = 'www.amazon.in';
	public const DEFAULT_CREATORS_VERSION = '3.2';

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
	public static function use_creators_api(): bool {
		$provider = trim( (string) get_option( self::OPTION_API_PROVIDER, 'creators' ) );
		return 'rapidapi' !== $provider;
	}

	public static function get_api_provider(): string {
		return self::use_creators_api() ? 'creators' : 'rapidapi';
	}

	public static function get_creators_client_id(): string {
		if ( defined( 'MMI_APS_CREATORS_CLIENT_ID' ) && MMI_APS_CREATORS_CLIENT_ID ) {
			return trim( (string) MMI_APS_CREATORS_CLIENT_ID );
		}

		return trim( (string) get_option( self::OPTION_CREATORS_CLIENT_ID, '' ) );
	}

	public static function get_creators_client_secret(): string {
		if ( defined( 'MMI_APS_CREATORS_CLIENT_SECRET' ) && MMI_APS_CREATORS_CLIENT_SECRET ) {
			return trim( (string) MMI_APS_CREATORS_CLIENT_SECRET );
		}

		return trim( (string) get_option( self::OPTION_CREATORS_CLIENT_SECRET, '' ) );
	}

	public static function get_creators_version(): string {
		if ( defined( 'MMI_APS_CREATORS_VERSION' ) && MMI_APS_CREATORS_VERSION ) {
			return trim( (string) MMI_APS_CREATORS_VERSION );
		}

		$version = trim( (string) get_option( self::OPTION_CREATORS_VERSION, self::DEFAULT_CREATORS_VERSION ) );
		return $version ?: self::DEFAULT_CREATORS_VERSION;
	}

	public static function get_creators_token_endpoint(): string {
		$version = self::get_creators_version();
		$prefix  = substr( $version, 0, 3 );

		if ( '3.1' === $prefix ) {
			return 'https://api.amazon.com/auth/o2/token';
		}

		if ( '3.3' === $prefix ) {
			return 'https://api.amazon.co.jp/auth/o2/token';
		}

		return 'https://api.amazon.co.uk/auth/o2/token';
	}

	public static function get_marketplace(): string {
		$marketplace = trim( (string) get_option( self::OPTION_MARKETPLACE, self::DEFAULT_MARKETPLACE ) );
		return $marketplace ?: self::DEFAULT_MARKETPLACE;
	}

	public static function get_partner_tag(): string {
		return self::get_affiliate_tag();
	}

	public static function is_api_configured(): bool {
		if ( self::use_creators_api() ) {
			return '' !== self::get_creators_client_id()
				&& '' !== self::get_creators_client_secret()
				&& '' !== self::get_partner_tag();
		}

		return '' !== self::get_api_key();
	}

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

	/**
	 * Master switch — when off, no Amazon API price calls (cron, manual fetch, bulk sync).
	 * Affiliate links on the storefront are unaffected.
	 */
	public static function is_price_sync_enabled(): bool {
		return 'yes' === get_option( self::OPTION_SYNC_ENABLED, 'yes' );
	}

	public static function is_auto_sync_enabled(): bool {
		if ( ! self::is_price_sync_enabled() ) {
			return false;
		}

		return 'yes' === get_option( self::OPTION_AUTO_SYNC, 'yes' );
	}

	public static function get_sync_interval(): string {
		$interval = (string) get_option( self::OPTION_SYNC_INTERVAL, 'mmi_aps_twelve_hours' );
		$allowed  = array( 'hourly', 'mmi_aps_six_hours', 'mmi_aps_twelve_hours', 'daily' );
		return in_array( $interval, $allowed, true ) ? $interval : 'mmi_aps_twelve_hours';
	}

	public static function get_sync_delay(): int {
		$delay = (int) get_option( self::OPTION_SYNC_DELAY, 2 );
		$min   = self::use_creators_api() ? 2 : 1;
		return max( $min, min( 10, $delay ) );
	}

	public static function get_batch_size(): int {
		$size = (int) get_option( self::OPTION_BATCH_SIZE, 10 );
		return max( 1, min( 50, $size ) );
	}

	public static function is_affiliate_enabled(): bool {
		return 'yes' === get_option( self::OPTION_AFFILIATE_ENABLED, 'no' );
	}

	public static function get_affiliate_tag(): string {
		if ( defined( 'MMI_APS_AFFILIATE_TAG' ) && MMI_APS_AFFILIATE_TAG ) {
			return self::sanitize_affiliate_tag( MMI_APS_AFFILIATE_TAG );
		}

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
