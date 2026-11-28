<?php
/**
 * Flipkart Affiliate Product API — activates when affiliate token is approved.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Flipkart_API_Client {

	private const API_BASE = 'https://affiliate-api.flipkart.net/affiliate/1.0/product.json';

	/**
	 * Whether Flipkart API credentials are configured and enabled.
	 */
	public static function is_configured(): bool {
		if ( ! MMI_APS_Settings::is_flipkart_api_enabled() ) {
			return false;
		}

		$affiliate_id = MMI_APS_Settings::get_flipkart_affiliate_id();
		$token        = MMI_APS_Settings::get_flipkart_affiliate_token();

		return '' !== $affiliate_id && '' !== $token;
	}

	/**
	 * User-facing reason when sync is not available yet.
	 */
	public static function get_pending_message(): string {
		if ( ! MMI_APS_Settings::is_flipkart_api_enabled() ) {
			return __( 'Flipkart Affiliate API is pending activation. Enable it under WooCommerce → Marketplace Pricing once your Flipkart affiliate account is approved.', 'mmi-amazon-price-sync' );
		}

		if ( ! self::is_configured() ) {
			return __( 'Add Flipkart Affiliate ID and Token in WooCommerce → Marketplace Pricing after Flipkart approves your account.', 'mmi-amazon-price-sync' );
		}

		return '';
	}

	/**
	 * Fetch product data by Flipkart product URL or pid slug.
	 *
	 * @param string $product_url Full Flipkart product URL or path.
	 * @return array{success:bool,data?:array,message?:string}
	 */
	public static function fetch_product( string $product_url ): array {
		$product_url = esc_url_raw( trim( $product_url ) );

		if ( '' === $product_url ) {
			return array(
				'success' => false,
				'message' => __( 'Flipkart product URL is missing.', 'mmi-amazon-price-sync' ),
			);
		}

		if ( ! self::is_configured() ) {
			return array(
				'success' => false,
				'message' => self::get_pending_message(),
			);
		}

		if ( false === strpos( $product_url, 'flipkart.com' ) ) {
			$product_url = 'https://www.flipkart.com/' . ltrim( $product_url, '/' );
		}

		$url = add_query_arg(
			array( 'id' => $product_url ),
			self::API_BASE
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 20,
				'sslverify' => true,
				'headers'   => array(
					'Fk-Affiliate-Id'    => MMI_APS_Settings::get_flipkart_affiliate_id(),
					'Fk-Affiliate-Token' => MMI_APS_Settings::get_flipkart_affiliate_token(),
					'Accept'             => 'application/json',
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

		if ( 200 !== $code || ! is_array( $json ) ) {
			$message = is_array( $json ) && ! empty( $json['errorMessage'] )
				? (string) $json['errorMessage']
				: sprintf(
					/* translators: %d: HTTP status */
					__( 'Flipkart API request failed with HTTP status %d.', 'mmi-amazon-price-sync' ),
					$code
				);

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		$parsed = self::parse_response( $json, $product_url );

		return array(
			'success' => true,
			'data'    => $parsed,
		);
	}

	/**
	 * @param array<string,mixed> $json        API response.
	 * @param string              $product_url Canonical URL used for lookup.
	 * @return array<string,mixed>
	 */
	private static function parse_response( array $json, string $product_url ): array {
		$info    = $json['productBaseInfoV1'] ?? array();
		$price   = isset( $info['flipkartSellingPrice']['amount'] ) ? (float) $info['flipkartSellingPrice']['amount'] : null;
		$mrp     = isset( $info['flipkartSpecialPrice']['amount'] ) ? (float) $info['flipkartSpecialPrice']['amount'] : null;
		$title   = isset( $info['title'] ) ? (string) $info['title'] : '';
		$in_stock = ! empty( $json['productBaseInfoV1']['inStock'] );
		$affiliate_url = isset( $json['productBaseInfoV1']['productUrl'] ) ? (string) $json['productBaseInfoV1']['productUrl'] : $product_url;

		if ( null === $price || $price <= 0 ) {
			$availability = 'unavailable';
		} elseif ( $in_stock ) {
			$availability = 'in_stock';
		} else {
			$availability = 'out_of_stock';
		}

		if ( null !== $mrp && null !== $price && $mrp <= $price ) {
			$mrp = null;
		}

		return array(
			'product_url'    => $affiliate_url ?: $product_url,
			'title'          => $title,
			'price'          => $price,
			'original_price' => $mrp,
			'availability'   => $availability,
			'available'      => 'in_stock' === $availability,
			'currency'       => isset( $info['flipkartSellingPrice']['currency'] ) ? (string) $info['flipkartSellingPrice']['currency'] : 'INR',
		);
	}

	/**
	 * Build tracked affiliate URL when API did not return one.
	 *
	 * @param string $product_url Product page URL.
	 */
	public static function build_affiliate_url( string $product_url ): string {
		$product_url = esc_url_raw( trim( $product_url ) );
		if ( '' === $product_url ) {
			return '';
		}

		$affid = MMI_APS_Settings::get_flipkart_affiliate_id();
		if ( '' === $affid ) {
			return $product_url;
		}

		return add_query_arg( 'affid', rawurlencode( $affid ), $product_url );
	}
}