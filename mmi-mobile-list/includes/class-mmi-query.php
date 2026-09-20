<?php
/**
 * Price buckets and WooCommerce product queries.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Query {

	/**
	 * Preset buckets (ceiling labels → inclusive min/max for storefront price).
	 *
	 * @return array<string, array{min: float, max: float}>
	 */
	public static function preset_buckets() {
		return array(
			'under-10000' => array( 'min' => 1, 'max' => 9999 ),
			'under-15000' => array( 'min' => 10000, 'max' => 14999 ),
			'under-20000' => array( 'min' => 15000, 'max' => 19999 ),
			'under-25000' => array( 'min' => 20000, 'max' => 24999 ),
			'under-30000' => array( 'min' => 25000, 'max' => 29999 ),
		);
	}

	/**
	 * Convert shortcode min/max (bucket ceilings) to inclusive WooCommerce prices.
	 *
	 * @param string $min_raw   Shortcode min_price.
	 * @param string $max_raw   Shortcode max_price.
	 * @param string $exact_raw Shortcode exact flag.
	 * @return array{min: float|null, max: float|null}|null Null if no price filter.
	 */
	public static function resolve_price_range( $min_raw, $max_raw, $exact_raw = '' ) {
		$exact = in_array( strtolower( (string) $exact_raw ), array( '1', 'yes', 'true' ), true );

		$min_in = '' !== $min_raw ? floatval( $min_raw ) : null;
		$max_in = '' !== $max_raw ? floatval( $max_raw ) : null;

		if ( null !== $min_in && null === $max_in ) {
			$next_ceiling = array(
				10000 => 15000,
				15000 => 20000,
				20000 => 25000,
				25000 => 30000,
			);
			$key = (int) $min_in;
			if ( isset( $next_ceiling[ $key ] ) ) {
				$max_in = (float) $next_ceiling[ $key ];
			}
		}

		if ( null === $min_in && null === $max_in ) {
			return null;
		}

		if ( $exact ) {
			return array(
				'min' => $min_in ?? 0,
				'max' => $max_in,
			);
		}

		$min_out = null !== $min_in ? $min_in : 1;

		if ( null === $max_in ) {
			return array(
				'min' => $min_out,
				'max' => null,
			);
		}

		$max_out = self::ceiling_to_inclusive_max( $max_in );

		if ( null === $min_in ) {
			return array(
				'min' => 1,
				'max' => $max_out,
			);
		}

		return array(
			'min' => $min_out,
			'max' => $max_out,
		);
	}

	/**
	 * @param string $bucket Bucket key.
	 * @return array{min: float, max: float}|null
	 */
	public static function resolve_bucket( $bucket ) {
		$bucket = sanitize_title( $bucket );
		$all    = self::preset_buckets();

		return isset( $all[ $bucket ] ) ? $all[ $bucket ] : null;
	}

	/**
	 * @param float $ceiling Ceiling from shortcode.
	 * @return float
	 */
	private static function ceiling_to_inclusive_max( $ceiling ) {
		if ( $ceiling >= 1000 && floor( $ceiling ) === $ceiling && 0 === (int) $ceiling % 1000 ) {
			return $ceiling - 1;
		}

		if ( $ceiling >= 1000 && floor( $ceiling ) === $ceiling ) {
			return $ceiling - 1;
		}

		return $ceiling;
	}

	/**
	 * @param string $brand Brand slug.
	 * @return array<string, mixed>|null
	 */
	public static function build_brand_tax_clause( $brand ) {
		if ( '' === $brand ) {
			return null;
		}

		$brand_tax = self::brand_taxonomy();
		if ( ! $brand_tax ) {
			return null;
		}

		return array(
			'taxonomy' => $brand_tax,
			'field'    => 'slug',
			'terms'    => sanitize_title( $brand ),
		);
	}

	/**
	 * @return string|null
	 */
	public static function brand_taxonomy() {
		$candidates = array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'brand' );

		foreach ( $candidates as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				return $tax;
			}
		}

		if ( taxonomy_exists( 'pa_brand' ) ) {
			return 'pa_brand';
		}

		return null;
	}

	/**
	 * Fetch listing payload (cached). Runs wc_get_products only on cache miss.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array{cards: array<int, array<string, mixed>>, pages: int}
	 */
	public static function get_listing( $args ) {
		$per_page = max( 1, min( 50, (int) $args['per_page'] ) );
		$page     = max( 1, (int) $args['page'] );
		$anchor   = isset( $args['details_anchor'] ) ? (string) $args['details_anchor'] : '';

		$cache_payload = array(
			'v'             => MMI_ML_VERSION,
			'price_min'     => $args['price_min'],
			'price_max'     => $args['price_max'],
			'category'      => $args['category'],
			'brand'         => $args['brand'],
			'per_page'      => $per_page,
			'page'          => $page,
			'details_anchor'=> $anchor,
		);

		$cache_key = MMI_ML_Cache::list_key( $cache_payload );
		$cached    = MMI_ML_Cache::get_list( $cache_key );

		if ( false !== $cached && isset( $cached['cards'], $cached['pages'] ) ) {
			return $cached;
		}

		$query_args = array(
			'status'   => 'publish',
			'limit'    => $per_page,
			'page'     => $page,
			'orderby'  => 'price',
			'order'    => 'ASC',
			'return'   => 'objects',
			'paginate' => true,
		);

		if ( null !== $args['price_min'] ) {
			$query_args['min_price'] = $args['price_min'];
		}
		if ( null !== $args['price_max'] ) {
			$query_args['max_price'] = $args['price_max'];
		}

		if ( '' !== $args['category'] ) {
			$query_args['category'] = array( $args['category'] );
		}

		$brand_tax_query = self::build_brand_tax_clause( $args['brand'] );
		if ( ! empty( $brand_tax_query ) ) {
			$query_args['tax_query'] = array( $brand_tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$result = wc_get_products( $query_args );

		$products = isset( $result->products ) ? $result->products : array();
		$pages    = isset( $result->max_num_pages ) ? (int) $result->max_num_pages : 1;

		$cards      = array();
		$price_min  = $args['price_min'];
		$price_max  = $args['price_max'];

		foreach ( $products as $product ) {
			$list_price = $product->get_price();
			if ( '' === $list_price ) {
				continue;
			}

			$list_price_f = (float) $list_price;

			if ( null !== $price_min && $list_price_f < (float) $price_min ) {
				continue;
			}
			if ( null !== $price_max && $list_price_f > (float) $price_max ) {
				continue;
			}

			$card = MMI_ML_Card::data_from_product( $product, $anchor );
			if ( $card ) {
				$cards[] = $card;
			}
		}

		$out = array(
			'cards' => $cards,
			'pages' => max( 1, $pages ),
		);

		MMI_ML_Cache::set_list( $cache_key, $out );

		return $out;
	}
}

add_action(
	'woocommerce_update_product',
	function () {
		MMI_ML_Cache::schedule_flush();
	}
);
add_action(
	'woocommerce_new_product',
	function () {
		MMI_ML_Cache::schedule_flush();
	}
);
add_action(
	'woocommerce_trash_product',
	function () {
		MMI_ML_Cache::schedule_flush();
	}
);
