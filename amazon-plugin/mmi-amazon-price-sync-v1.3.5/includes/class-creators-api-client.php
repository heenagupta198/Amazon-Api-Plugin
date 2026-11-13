<?php
/**
 * Amazon Creators API (official) — OAuth + GetItems for India marketplace.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Creators_API_Client {

	private const API_GET_ITEMS_URL = 'https://creatorsapi.amazon/catalog/v1/getItems';
	private const TRANSIENT_TOKEN   = 'mmi_aps_creators_access_token';
	private const TRANSIENT_THROTTLE = 'mmi_aps_creators_throttle_until';

	/**
	 * Fetch product data by ASIN using GetItems.
	 *
	 * @param string $asin Amazon ASIN.
	 * @return array{success:bool,data?:array,message?:string}
	 */
	public static function fetch_product( string $asin ): array {
		$asin = strtoupper( sanitize_text_field( $asin ) );

		if ( ! preg_match( '/^[A-Z0-9]{10}$/', $asin ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid ASIN. It must be exactly 10 alphanumeric characters.', 'mmi-amazon-price-sync' ),
			);
		}

		$auth_error = self::validate_credentials();
		if ( $auth_error ) {
			return array(
				'success' => false,
				'message' => $auth_error,
			);
		}

		$response = self::get_items( array( $asin ) );
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$parsed = self::parse_item_response( $response, $asin );
		if ( is_wp_error( $parsed ) ) {
			return array(
				'success' => false,
				'message' => $parsed->get_error_message(),
			);
		}

		if ( ! $parsed['available'] && null === $parsed['price'] ) {
			return array(
				'success' => true,
				'data'    => $parsed,
			);
		}

		if ( null === $parsed['price'] ) {
			return array(
				'success' => false,
				'message' => __( 'Could not find product price in Creators API response.', 'mmi-amazon-price-sync' ),
			);
		}

		return array(
			'success' => true,
			'data'    => $parsed,
		);
	}

	/**
	 * @param string[] $asins Up to 10 ASINs (single API transaction).
	 * @return array<string,mixed>|WP_Error
	 */
	private static function get_items( array $asins ) {
		if ( self::is_throttled() ) {
			return new WP_Error(
				'mmi_aps_throttled',
				__( 'Creators API rate limit reached. Please wait and try again.', 'mmi-amazon-price-sync' )
			);
		}

		$token = self::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$marketplace = MMI_APS_Settings::get_marketplace();
		$partner_tag = MMI_APS_Settings::get_partner_tag();

		$body = array(
			'itemIds'      => array_values( array_slice( $asins, 0, 10 ) ),
			'itemIdType'   => 'ASIN',
			'marketplace'  => $marketplace,
			'partnerTag'   => $partner_tag,
			'resources'    => array(
				'itemInfo.title',
				'offersV2.listings.price',
				'offersV2.listings.availability',
			),
		);

		$response = wp_remote_post(
			self::API_GET_ITEMS_URL,
			array(
				'timeout'   => 25,
				'sslverify' => true,
				'headers'   => array(
					'Authorization'  => 'Bearer ' . $token,
					'Content-Type'   => 'application/json',
					'Accept'         => 'application/json',
					'x-marketplace'  => $marketplace,
				),
				'body'      => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$json = json_decode( $raw, true );

		if ( 429 === $code ) {
			set_transient( self::TRANSIENT_THROTTLE, time() + 60, 120 );
			return new WP_Error(
				'mmi_aps_rate_limit',
				__( 'Creators API returned 429 Too Many Requests. Respect 1 TPS / daily TPD limits.', 'mmi-amazon-price-sync' )
			);
		}

		if ( 401 === $code ) {
			delete_transient( self::TRANSIENT_TOKEN );
			return new WP_Error(
				'mmi_aps_unauthorized',
				__( 'Creators API authentication failed. Check Credential ID, Secret, and Version.', 'mmi-amazon-price-sync' )
			);
		}

		if ( 200 !== $code ) {
			return new WP_Error(
				'mmi_aps_api_error',
				self::extract_api_error_message( $json, $raw, $code )
			);
		}

		if ( ! is_array( $json ) ) {
			return new WP_Error(
				'mmi_aps_invalid_json',
				__( 'Invalid JSON from Creators API.', 'mmi-amazon-price-sync' )
			);
		}

		return $json;
	}

	/**
	 * @param array<string,mixed> $json  API response.
	 * @param string              $asin  Expected ASIN.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function parse_item_response( array $json, string $asin ) {
		if ( ! empty( $json['errors'] ) && is_array( $json['errors'] ) ) {
			$first = $json['errors'][0];
			$msg   = is_array( $first ) && ! empty( $first['message'] ) ? (string) $first['message'] : __( 'Creators API error.', 'mmi-amazon-price-sync' );
			return new WP_Error( 'mmi_aps_items_error', $msg );
		}

		$items = $json['itemsResult']['items'] ?? $json['items'] ?? array();
		if ( ! is_array( $items ) || empty( $items ) ) {
			return new WP_Error(
				'mmi_aps_not_found',
				__( 'Product not found in Creators API response.', 'mmi-amazon-price-sync' )
			);
		}

		$item = null;
		foreach ( $items as $candidate ) {
			if ( ! is_array( $candidate ) ) {
				continue;
			}
			$item_asin = strtoupper( (string) ( $candidate['asin'] ?? '' ) );
			if ( $item_asin === $asin ) {
				$item = $candidate;
				break;
			}
		}

		if ( null === $item ) {
			$item = $items[0];
		}

		$title = '';
		if ( isset( $item['itemInfo']['title']['displayValue'] ) ) {
			$title = (string) $item['itemInfo']['title']['displayValue'];
		}

		$listing     = $item['offersV2']['listings'][0] ?? $item['offers']['listings'][0] ?? null;
		$price_block = is_array( $listing ) ? ( $listing['price'] ?? null ) : null;

		$price_amount   = null;
		$original_price = null;
		$currency       = 'INR';
		$availability   = is_array( $listing ) ? (string) ( $listing['availability']['message'] ?? $listing['availability']['type'] ?? '' ) : '';

		if ( is_array( $price_block ) ) {
			$price_amount   = self::money_to_float( $price_block['money'] ?? null );
			$original_price = self::money_to_float( $price_block['savingBasis']['money'] ?? $price_block['savingBasis'] ?? null );

			if ( isset( $price_block['money']['currency'] ) ) {
				$currency = (string) $price_block['money']['currency'];
			}
		}

		if ( null === $price_amount && is_array( $listing ) ) {
			$price_amount = MMI_APS_API_Client::parse_price( $listing['price']['displayAmount'] ?? null );
		}

		$available = null !== $price_amount && $price_amount > 0;
		if ( ! $available ) {
			$available = ! self::looks_unavailable_text( $availability );
			if ( '' === $availability && empty( $listing ) ) {
				$available = false;
			}
		}

		if ( null !== $original_price && null !== $price_amount && $original_price <= $price_amount ) {
			$original_price = null;
		}

		return array(
			'asin'              => $asin,
			'title'             => $title,
			'price'             => $price_amount,
			'original_price'    => $original_price,
			'currency'          => $currency,
			'delivery'          => '',
			'available'         => $available,
			'availability_text' => $availability,
		);
	}

	/**
	 * @param mixed $money Money node from Creators API.
	 */
	private static function money_to_float( $money ): ?float {
		if ( ! is_array( $money ) ) {
			return MMI_APS_API_Client::parse_price( $money );
		}

		if ( isset( $money['amount'] ) && is_numeric( $money['amount'] ) ) {
			return round( (float) $money['amount'], 2 );
		}

		if ( ! empty( $money['displayAmount'] ) ) {
			return MMI_APS_API_Client::parse_price( $money['displayAmount'] );
		}

		return null;
	}

	private static function looks_unavailable_text( string $text ): bool {
		if ( '' === trim( $text ) ) {
			return false;
		}

		$text = strtolower( $text );

		return false !== strpos( $text, 'unavailable' )
			|| false !== strpos( $text, 'out of stock' )
			|| 'outofstock' === str_replace( ' ', '', $text );
	}

	/**
	 * @return string|WP_Error
	 */
	private static function get_access_token() {
		$cached = get_transient( self::TRANSIENT_TOKEN );
		if ( is_array( $cached ) && ! empty( $cached['token'] ) && ! empty( $cached['expires_at'] ) ) {
			if ( time() < ( (int) $cached['expires_at'] - 60 ) ) {
				return (string) $cached['token'];
			}
		}

		$client_id     = MMI_APS_Settings::get_creators_client_id();
		$client_secret = MMI_APS_Settings::get_creators_client_secret();
		$token_url     = MMI_APS_Settings::get_creators_token_endpoint();

		$response = wp_remote_post(
			$token_url,
			array(
				'timeout'   => 20,
				'sslverify' => true,
				'headers'   => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'      => wp_json_encode(
					array(
						'grant_type'    => 'client_credentials',
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
						'scope'         => 'creatorsapi::default',
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$json = json_decode( $raw, true );

		if ( 200 !== $code || ! is_array( $json ) || empty( $json['access_token'] ) ) {
			return new WP_Error(
				'mmi_aps_token_error',
				__( 'Failed to fetch Creators API access token. Check Credential ID, Secret, and Version.', 'mmi-amazon-price-sync' )
			);
		}

		$expires_in = isset( $json['expires_in'] ) ? (int) $json['expires_in'] : 3600;
		set_transient(
			self::TRANSIENT_TOKEN,
			array(
				'token'      => (string) $json['access_token'],
				'expires_at' => time() + max( 300, $expires_in ),
			),
			max( 300, $expires_in - 60 )
		);

		return (string) $json['access_token'];
	}

	private static function validate_credentials(): ?string {
		if ( '' === MMI_APS_Settings::get_creators_client_id() || '' === MMI_APS_Settings::get_creators_client_secret() ) {
			return __( 'Creators API credentials are not configured. Add Credential ID and Secret in WooCommerce → Amazon Price Sync.', 'mmi-amazon-price-sync' );
		}

		if ( '' === MMI_APS_Settings::get_partner_tag() ) {
			return __( 'Partner Tag (Amazon Affiliate Tag) is required for Creators API GetItems.', 'mmi-amazon-price-sync' );
		}

		return null;
	}

	private static function is_throttled(): bool {
		$until = (int) get_transient( self::TRANSIENT_THROTTLE );
		return $until > time();
	}

	/**
	 * @param mixed  $json Decoded JSON.
	 * @param string $body Raw body.
	 * @param int    $code HTTP code.
	 */
	private static function extract_api_error_message( $json, string $body, int $code ): string {
		if ( is_array( $json ) ) {
			if ( ! empty( $json['message'] ) && is_string( $json['message'] ) ) {
				return sprintf(
					/* translators: 1: HTTP code, 2: message */
					__( 'Creators API error (%1$d): %2$s', 'mmi-amazon-price-sync' ),
					$code,
					$json['message']
				);
			}
			if ( ! empty( $json['errors'][0]['message'] ) ) {
				return sprintf(
					/* translators: 1: HTTP code, 2: message */
					__( 'Creators API error (%1$d): %2$s', 'mmi-amazon-price-sync' ),
					$code,
					(string) $json['errors'][0]['message']
				);
			}
		}

		return sprintf(
			/* translators: %d: HTTP status code */
			__( 'Creators API request failed with HTTP status %d.', 'mmi-amazon-price-sync' ),
			$code
		);
	}
}
