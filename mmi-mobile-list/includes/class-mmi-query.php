<?php
/**
 * Price buckets and product queries (_regular_price for external/affiliate).
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Query {

	/**
	 * Default products per page from settings.
	 */
	public static function default_per_page() {
		$n = (int) get_option( 'mmi_ml_per_page', 10 );
		return max( 1, min( 50, $n ) );
	}

	/**
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
	 * @param string $min_raw   min_price.
	 * @param string $max_raw   max_price.
	 * @param string $exact_raw exact flag.
	 * @return array{min: float|null, max: float|null}|null
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
	 * @param string $bucket Bucket slug.
	 * @return array{min: float, max: float}|null
	 */
	public static function resolve_bucket( $bucket ) {
		$bucket = sanitize_title( $bucket );
		if ( 'upcoming' === $bucket ) {
			return null;
		}
		$all = self::preset_buckets();

		return isset( $all[ $bucket ] ) ? $all[ $bucket ] : null;
	}

	/**
	 * @param float $ceiling Bucket ceiling.
	 * @return float
	 */
	private static function ceiling_to_inclusive_max( $ceiling ) {
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
	 * Build WP_Query args using WooCommerce regular price meta.
	 *
	 * @param array<string, mixed> $args Args.
	 * @return array<string, mixed>
	 */
	public static function build_wp_query_args( $args ) {
		$per_page = max( 1, min( 50, (int) $args['per_page'] ) );
		$page     = max( 1, (int) $args['page'] );
		$mode     = isset( $args['list_mode'] ) ? $args['list_mode'] : 'priced';

		$meta_query = array();

		if ( 'upcoming' === $mode ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_regular_price',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_regular_price',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => '_regular_price',
					'value'   => '0',
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			);
		} else {
			$meta_query[] = array(
				'key'     => '_regular_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			);

			if ( null !== $args['price_min'] ) {
				$meta_query[] = array(
					'key'     => '_regular_price',
					'value'   => $args['price_min'],
					'compare' => '>=',
					'type'    => 'NUMERIC',
				);
			}

			if ( null !== $args['price_max'] ) {
				$meta_query[] = array(
					'key'     => '_regular_price',
					'value'   => $args['price_max'],
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
			}
		}

		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
		}

		$wp_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'meta_query'             => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		);

		if ( 'upcoming' === $mode ) {
			$wp_args['orderby'] = 'date';
			$wp_args['order']   = 'DESC';
		} else {
			$wp_args['meta_key'] = '_regular_price';
			$wp_args['orderby']  = 'meta_value_num';
			$wp_args['order']    = 'ASC';
		}

		$tax_query = array();

		if ( '' !== $args['category'] ) {
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $args['category'],
			);
		}

		$brand_clause = self::build_brand_tax_clause( $args['brand'] );
		if ( $brand_clause ) {
			$tax_query[] = $brand_clause;
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$wp_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return $wp_args;
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array{cards: array<int, array<string, mixed>>, pages: int, total: int}
	 */
	public static function get_listing( $args ) {
		$per_page = max( 1, min( 50, (int) $args['per_page'] ) );
		$page     = max( 1, (int) $args['page'] );
		$anchor   = isset( $args['details_anchor'] ) ? (string) $args['details_anchor'] : '';
		$mode     = isset( $args['list_mode'] ) ? $args['list_mode'] : 'priced';

		$cache_payload = array(
			'v'              => MMI_ML_VERSION,
			'list_mode'      => $mode,
			'price_min'      => $args['price_min'],
			'price_max'      => $args['price_max'],
			'category'       => $args['category'],
			'brand'          => $args['brand'],
			'per_page'       => $per_page,
			'page'           => $page,
			'details_anchor' => $anchor,
		);

		$cache_key = MMI_ML_Cache::list_key( $cache_payload );
		$cached    = MMI_ML_Cache::get_list( $cache_key );

		if ( false !== $cached && isset( $cached['cards'], $cached['pages'] ) ) {
			return $cached;
		}

		$query = new WP_Query( self::build_wp_query_args( $args ) );

		$pages = max( 1, (int) $query->max_num_pages );
		$total = (int) $query->found_posts;

		$cards  = array();
		$is_upcoming = ( 'upcoming' === $mode );

		foreach ( $query->posts as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$card = MMI_ML_Card::data_from_product( $product, $anchor, $is_upcoming );
			if ( $card ) {
				$cards[] = $card;
			}
		}

		wp_reset_postdata();

		$out = array(
			'cards' => $cards,
			'pages' => $pages,
			'total' => $total,
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
