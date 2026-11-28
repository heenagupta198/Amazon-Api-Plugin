<?php
/**
 * Routes Amazon product fetches to RapidAPI or Creators API based on saved provider.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Amazon_API {

	/**
	 * @param string $asin Amazon ASIN.
	 * @return array{success:bool,data?:array,message?:string}
	 */
	public static function fetch_product( string $asin ): array {
		if ( MMI_APS_Settings::use_creators_api() ) {
			return MMI_APS_Creators_API_Client::fetch_product( $asin );
		}

		return MMI_APS_API_Client::fetch_product( $asin );
	}

	public static function get_active_provider_label(): string {
		return MMI_APS_Settings::use_creators_api()
			? __( 'Amazon Creators API', 'mmi-amazon-price-sync' )
			: __( 'RapidAPI', 'mmi-amazon-price-sync' );
	}
}