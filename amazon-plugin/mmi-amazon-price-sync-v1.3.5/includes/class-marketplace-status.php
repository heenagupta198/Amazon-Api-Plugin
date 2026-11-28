<?php
/**
 * Product lifecycle status — Upcoming, Launched, Sale Not Started, Available to Buy, Discontinued.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Marketplace_Status {

	/** @var string[] */
	public const STATUSES = array(
		'upcoming',
		'launched',
		'sale_not_started',
		'available_to_buy',
		'discontinued',
	);

	/**
	 * Resolved status slug for storefront.
	 *
	 * @param int $product_id Product or variation ID.
	 */
	public static function resolve( int $product_id ): string {
		$override = (string) get_post_meta( $product_id, MMI_APS_Plugin::META_SALE_STATUS, true );
		if ( '' !== $override && in_array( $override, self::STATUSES, true ) ) {
			return $override;
		}

		if ( self::is_discontinued_product( $product_id ) ) {
			return 'discontinued';
		}

		$launch_ts = self::get_launch_timestamp( $product_id );
		if ( null !== $launch_ts && $launch_ts > time() ) {
			return 'upcoming';
		}

		if ( self::has_buyable_marketplace_listing( $product_id ) ) {
			return 'available_to_buy';
		}

		if ( null !== $launch_ts && $launch_ts <= time() ) {
			return 'sale_not_started';
		}

		return 'launched';
	}

	/**
	 * Human label for a status slug.
	 *
	 * @param string $status Status slug.
	 */
	public static function get_label( string $status ): string {
		$labels = array(
			'upcoming'         => __( 'Upcoming', 'mmi-amazon-price-sync' ),
			'launched'         => __( 'Launched', 'mmi-amazon-price-sync' ),
			'sale_not_started' => __( 'Sale Not Started', 'mmi-amazon-price-sync' ),
			'available_to_buy' => __( 'Available to Buy', 'mmi-amazon-price-sync' ),
			'discontinued'     => __( 'Discontinued', 'mmi-amazon-price-sync' ),
		);

		return $labels[ $status ] ?? $labels['launched'];
	}

	/**
	 * Whether any configured marketplace has a fresh in-stock price.
	 *
	 * @param int $product_id Product or variation ID.
	 */
	public static function has_buyable_marketplace_listing( int $product_id ): bool {
		$snapshot = MMI_APS_Marketplace_Frontend::get_platform_snapshot( $product_id );

		foreach ( $snapshot['platforms'] as $platform ) {
			if ( ! empty( $platform['show_price'] ) && ! empty( $platform['buy_url'] ) && 'in_stock' === ( $platform['availability'] ?? '' ) ) {
				return true;
			}
			if ( ! empty( $platform['show_price'] ) && ! empty( $platform['buy_url'] ) && 'available' === ( $platform['availability'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $product_id Product ID.
	 */
	private static function get_launch_timestamp( int $product_id ): ?int {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}

		$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product_id;
		$parent    = wc_get_product( $parent_id );
		if ( ! $parent ) {
			return null;
		}

		$launch = $parent->get_attribute( 'launch-date' );
		if ( '' === $launch ) {
			$launch = $parent->get_attribute( 'pa_launch-date' );
		}

		if ( '' === $launch ) {
			return null;
		}

		$launch = preg_replace( '/\s*\(Official\)\s*$/i', '', $launch );
		$launch = trim( (string) $launch );
		if ( '' === $launch ) {
			return null;
		}

		$ts = strtotime( $launch );
		return false !== $ts ? (int) $ts : null;
	}

	/**
	 * @param int $product_id Product ID.
	 */
	private static function is_discontinued_product( int $product_id ): bool {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}

		$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product_id;
		$parent    = wc_get_product( $parent_id );
		if ( ! $parent ) {
			return false;
		}

		$status_attr = strtolower( $parent->get_attribute( 'pa_status' ) );
		if ( false !== strpos( $status_attr, 'discontinu' ) ) {
			return true;
		}

		return get_post_meta( $parent_id, MMI_APS_Plugin::META_SALE_STATUS, true ) === 'discontinued';
	}
}