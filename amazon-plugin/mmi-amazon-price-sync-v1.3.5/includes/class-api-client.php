<?php
/**
 * RapidAPI client for Amazon India product prices.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_API_Client {

	/**
	 * Fetch product data from RapidAPI by ASIN.
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

		$api_key = MMI_APS_Settings::get_api_key();
		if ( '' === $api_key ) {
			return array(
				'success' => false,
				'message' => __( 'RapidAPI key is not configured. Go to WooCommerce → Amazon Price Sync.', 'mmi-amazon-price-sync' ),
			);
		}

		$host     = MMI_APS_Settings::get_api_host();
		$endpoint = MMI_APS_Settings::get_api_endpoint();
		$country  = MMI_APS_Settings::get_country();
		$language = MMI_APS_Settings::get_language();

		if ( ! self::is_allowed_host( $host ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid API host. Only RapidAPI domains are allowed.', 'mmi-amazon-price-sync' ),
			);
		}

		$endpoint = '/' . ltrim( preg_replace( '/[^a-zA-Z0-9\/_-]/', '', $endpoint ), '/' );
		$base_url = 'https://' . untrailingslashit( $host ) . $endpoint;

		$url = add_query_arg(
			array(
				'asin'               => $asin,
				'country'            => $country,
				'autoselect_variant' => 'true',
				'language'           => $language,
			),
			$base_url
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 20,
				'sslverify' => true,
				'headers'   => array(
					'Content-Type'    => 'application/json',
					'x-rapidapi-key'  => $api_key,
					'x-rapidapi-host' => $host,
					'Accept'          => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( 200 !== $code ) {
			$message = self::extract_error_message( $json, $body, $code );
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		if ( ! is_array( $json ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid JSON response from RapidAPI.', 'mmi-amazon-price-sync' ),
			);
		}

		$parsed = self::parse_response( $json );
		if ( null === $parsed['price'] ) {
			return array(
				'success' => false,
				'message' => __( 'Could not find product price in API response.', 'mmi-amazon-price-sync' ),
			);
		}

		return array(
			'success' => true,
			'data'    => $parsed,
		);
	}

	/**
	 * Parse API response into normalized product data.
	 *
	 * @param array<string,mixed> $json Raw API response.
	 * @return array{asin:string,title:string,price:?float,original_price:?float,currency:string,delivery:string}
	 */
	private static function parse_response( array $json ): array {
		$data = $json;

		if ( isset( $json['data'] ) && is_array( $json['data'] ) ) {
			$data = $json['data'];
		}

		$price_raw          = self::find_value( $data, array( 'product_price', 'price', 'current_price', 'buybox_price' ) );
		$original_price_raw = self::find_value( $data, array( 'product_original_price', 'original_price', 'list_price', 'product_list_price', 'was_price' ) );
		$title              = (string) self::find_value( $data, array( 'product_title', 'title', 'name' ), '' );
		$currency           = (string) self::find_value( $data, array( 'currency', 'product_currency' ), 'INR' );
		$delivery           = (string) self::find_value( $data, array( 'delivery_price', 'delivery', 'delivery_info', 'shipping' ), '' );
		$asin               = (string) self::find_value( $data, array( 'asin', 'product_asin' ), '' );

		$price          = self::parse_price( $price_raw );
		$original_price = self::parse_price( $original_price_raw );

		if ( null !== $original_price && null !== $price && $original_price <= $price ) {
			$original_price = null;
		}

		return array(
			'asin'           => $asin,
			'title'          => $title,
			'price'          => $price,
			'original_price' => $original_price,
			'currency'       => $currency,
			'delivery'       => $delivery,
		);
	}

	/**
	 * Find first non-empty value from candidate keys.
	 *
	 * @param array<string,mixed> $data Data array.
	 * @param string[]            $keys Candidate keys.
	 * @param mixed               $default Default value.
	 * @return mixed
	 */
	private static function find_value( array $data, array $keys, $default = null ) {
		foreach ( $keys as $key ) {
			if ( isset( $data[ $key ] ) && '' !== $data[ $key ] && null !== $data[ $key ] ) {
				return $data[ $key ];
			}
		}
		return $default;
	}

	/**
	 * Convert price string like "25,149" or "₹25,149.00" to float.
	 *
	 * @param mixed $value Raw price value.
	 */
	public static function parse_price( $value ): ?float {
		if ( null === $value || '' === $value ) {
			return null;
		}

		if ( is_numeric( $value ) ) {
			return round( (float) $value, 2 );
		}

		$clean = preg_replace( '/[^\d.,]/', '', (string) $value );
		if ( '' === $clean ) {
			return null;
		}

		// Indian format: 1,25,149 or 25,149 — commas are thousand separators.
		if ( false !== strpos( $clean, ',' ) && false === strpos( $clean, '.' ) ) {
			$clean = str_replace( ',', '', $clean );
		} elseif ( false !== strpos( $clean, ',' ) && false !== strpos( $clean, '.' ) ) {
			// 1,234.56 — comma is thousands separator.
			$clean = str_replace( ',', '', $clean );
		} else {
			$clean = str_replace( ',', '', $clean );
		}

		if ( ! is_numeric( $clean ) ) {
			return null;
		}

		return round( (float) $clean, 2 );
	}

	/**
	 * Allow only RapidAPI hosts to prevent SSRF.
	 */
	private static function is_allowed_host( string $host ): bool {
		$host = strtolower( trim( $host ) );
		return (bool) preg_match( '/^[a-z0-9.-]+\.rapidapi\.com$/', $host );
	}

	/**
	 * Build a user-facing error message from API response.
	 *
	 * @param mixed  $json Decoded JSON.
	 * @param string $body Raw body.
	 * @param int    $code HTTP status code.
	 */
	private static function extract_error_message( $json, string $body, int $code ): string {
		if ( is_array( $json ) ) {
			foreach ( array( 'message', 'error', 'detail', 'msg' ) as $key ) {
				if ( ! empty( $json[ $key ] ) && is_string( $json[ $key ] ) ) {
					return sprintf(
						/* translators: 1: HTTP status code, 2: error message */
						__( 'API error (%1$d): %2$s', 'mmi-amazon-price-sync' ),
						$code,
						$json[ $key ]
					);
				}
			}
		}

		if ( 403 === $code ) {
			return __( 'API error (403): Not subscribed to this API. Use host real-time-amazon-data.p.rapidapi.com and endpoint /product-details, then subscribe on RapidAPI.', 'mmi-amazon-price-sync' );
		}

		return sprintf(
			/* translators: 1: HTTP status code */
			__( 'API request failed with HTTP status %d.', 'mmi-amazon-price-sync' ),
			$code
		);
	}
}
