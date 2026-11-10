<?php
/**
 * Cross-site product search & news (separate WordPress + DB installs).
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Remote {

	const TIMEOUT = 20;

	/**
	 * @return array<int,array{id:int,label:string}>
	 */
	public static function search_products( string $term ): array {
		$term = trim( $term );
		if ( '' === $term ) {
			return array();
		}

		$cache_key = 'mmi_csl_remote_q_' . md5( strtolower( $term ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$items = array();

		if ( preg_match( '/^\d+$/', $term ) ) {
			$one = self::fetch_product_by_id( (int) $term );
			$items = $one ? array( $one ) : array();
		} else {
			$items = self::fetch_search_results( $term );
		}

		set_transient( $cache_key, $items, 90 );

		return $items;
	}

	/**
	 * @param int[] $ids Product IDs on devices site.
	 * @return array<int,array{id:int,label:string}>
	 */
	public static function resolve_product_ids( array $ids ): array {
		$out = array();
		foreach ( $ids as $id ) {
			$item = self::fetch_product_by_id( (int) $id );
			if ( $item ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	/**
	 * @return array<int,array{slug:string,name:string,count:int}>
	 */
	public static function get_brands(): array {
		$cache_key = 'mmi_csl_remote_brands';
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url      = MMI_CSL_Settings::get_devices_rest_url() . '/wp/v2/product_brand?per_page=15&orderby=count&order=desc';
		$response = self::get_json( $url );
		$brands   = array();

		if ( is_array( $response ) ) {
			foreach ( $response as $row ) {
				if ( empty( $row['slug'] ) || empty( $row['name'] ) ) {
					continue;
				}
				$brands[] = array(
					'slug'  => (string) $row['slug'],
					'name'  => (string) $row['name'],
					'count' => isset( $row['count'] ) ? (int) $row['count'] : 0,
				);
			}
		}

		set_transient( $cache_key, $brands, HOUR_IN_SECONDS );

		return $brands;
	}

	/**
	 * @return array<int,array{id:int,title:string,url:string,date:string,image:string}>
	 */
	public static function get_news_items_for_product( int $product_id, int $limit = 12 ): array {
		$product_id = max( 0, $product_id );
		if ( $product_id <= 0 ) {
			return array();
		}

		$cache_key = 'mmi_csl_news_p_' . $product_id;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url  = MMI_CSL_Settings::get_news_rest_url() . '/mmi-csl/v1/news-by-product/' . $product_id . '?per_page=' . (int) $limit;
		$body = self::get_json( $url );

		if ( ! is_array( $body ) || empty( $body['posts'] ) || ! is_array( $body['posts'] ) ) {
			return array();
		}

		$items = array();
		foreach ( $body['posts'] as $row ) {
			if ( empty( $row['id'] ) || empty( $row['url'] ) ) {
				continue;
			}
			$items[] = array(
				'id'    => (int) $row['id'],
				'title' => isset( $row['title'] ) ? (string) $row['title'] : '',
				'url'   => (string) $row['url'],
				'date'  => isset( $row['date'] ) ? (string) $row['date'] : '',
				'image' => isset( $row['image'] ) ? (string) $row['image'] : '',
			);
		}

		set_transient( $cache_key, $items, 120 );

		return $items;
	}

	/**
	 * @return array{id:int,label:string}|null
	 */
	private static function fetch_product_by_id( int $id ): ?array {
		if ( $id <= 0 ) {
			return null;
		}

		$url = MMI_CSL_Settings::get_devices_rest_url() . '/wp/v2/product/' . $id . '?_fields=id,title,status';
		$row = self::get_json( $url );

		if ( ! is_array( $row ) || empty( $row['id'] ) ) {
			return null;
		}

		if ( isset( $row['status'] ) && 'publish' !== $row['status'] ) {
			return null;
		}

		$title = self::extract_title( $row );

		return array(
			'id'    => (int) $row['id'],
			'label' => sprintf( '#%d — %s', (int) $row['id'], $title ),
		);
	}

	/**
	 * @return array<int,array{id:int,label:string}>
	 */
	private static function fetch_search_results( string $term ): array {
		$items = array();

		$url = add_query_arg(
			array(
				'search'   => $term,
				'subtype'  => 'product',
				'per_page' => 30,
			),
			MMI_CSL_Settings::get_devices_rest_url() . '/wp/v2/search'
		);

		$rows = self::get_json( $url );
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( empty( $row['id'] ) || ( isset( $row['subtype'] ) && 'product' !== $row['subtype'] ) ) {
					continue;
				}
				$title = isset( $row['title'] ) ? wp_strip_all_tags( (string) $row['title'] ) : '';
				$items[] = array(
					'id'    => (int) $row['id'],
					'label' => sprintf( '#%d — %s', (int) $row['id'], $title ),
				);
			}
		}

		if ( ! empty( $items ) ) {
			return $items;
		}

		// Fallback: WooCommerce products endpoint title search (if exposed).
		$url2 = add_query_arg(
			array(
				'search'   => $term,
				'per_page' => 30,
				'_fields'  => 'id,name',
			),
			MMI_CSL_Settings::get_devices_rest_url() . '/wc/v3/products'
		);

		$wc = self::get_json( $url2 );
		if ( is_array( $wc ) ) {
			foreach ( $wc as $row ) {
				if ( empty( $row['id'] ) ) {
					continue;
				}
				$title = isset( $row['name'] ) ? (string) $row['name'] : '';
				$items[] = array(
					'id'    => (int) $row['id'],
					'label' => sprintf( '#%d — %s', (int) $row['id'], $title ),
				);
			}
		}

		return $items;
	}

	/**
	 * @return array<string,mixed>|array<int,mixed>|null
	 */
	private static function get_json( string $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * @param array<string,mixed> $row API row.
	 */
	private static function extract_title( array $row ): string {
		if ( isset( $row['title']['rendered'] ) ) {
			return wp_strip_all_tags( (string) $row['title']['rendered'] );
		}
		if ( isset( $row['title'] ) && is_string( $row['title'] ) ) {
			return wp_strip_all_tags( $row['title'] );
		}
		return '';
	}

	public static function devices_api_reachable(): bool {
		$url      = MMI_CSL_Settings::get_devices_rest_url();
		$response = wp_remote_get( $url, array( 'timeout' => 8 ) );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		return 200 === (int) wp_remote_retrieve_response_code( $response );
	}
}
