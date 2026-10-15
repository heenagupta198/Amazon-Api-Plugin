<?php
/**
 * Fast, low-load product title search on the devices subsite.
 */

defined( 'ABSPATH' ) || exit;

class MMI_CSL_Product_Search {

	const CACHE_TTL     = 300;
	const MAX_TERM_LEN  = 80;
	const MAX_RESULTS   = 30;
	const RATE_LIMIT    = 40;
	const RATE_WINDOW   = 60;

	/**
	 * @return string[]
	 */
	public static function get_post_types(): array {
		$types = array( 'product', 'devices' );
		/**
		 * Filter product post types searched on the devices site.
		 *
		 * @param string[] $types Post type names.
		 */
		return array_values( array_unique( apply_filters( 'mmi_csl_product_post_types', $types ) ) );
	}

	/**
	 * @return array<int,array{id:int,label:string}>
	 */
	public static function search( string $term, int $limit = self::MAX_RESULTS ): array {
		global $wpdb;

		$term  = trim( wp_strip_all_tags( $term ) );
		$term  = mb_substr( $term, 0, self::MAX_TERM_LEN );
		$limit = max( 1, min( self::MAX_RESULTS, $limit ) );

		if ( '' === $term ) {
			return array();
		}

		$cache_key = 'mmi_csl_q_' . get_current_blog_id() . '_' . md5( strtolower( $term ) . '|' . $limit );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$types = self::get_post_types();
		if ( empty( $types ) ) {
			return array();
		}

		$items = array();

		if ( preg_match( '/^\d+$/', $term ) ) {
			$items = self::query_by_id( (int) $term, $types );
		} else {
			$items = self::query_by_title_tokens( $term, $types, $limit, true );
			if ( empty( $items ) ) {
				$items = self::query_by_title_tokens( $term, $types, $limit, false );
			}
		}

		set_transient( $cache_key, $items, self::CACHE_TTL );

		return $items;
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,label:string}>
	 */
	private static function query_by_id( int $id, array $types ): array {
		global $wpdb;

		if ( $id <= 0 ) {
			return array();
		}

		$in     = self::sql_in_placeholders( $types );
		$sql    = "SELECT ID, post_title FROM {$wpdb->posts}
			WHERE ID = %d AND post_status = 'publish' AND post_type IN ($in) LIMIT 1";
		$params = array_merge( array( $id ), $types );
		$row    = $wpdb->get_row( $wpdb->prepare( $sql, $params ) );

		if ( ! $row ) {
			return array();
		}

		return array( self::format_row( $row ) );
	}

	/**
	 * @param string[] $types Post types.
	 * @return array<int,array{id:int,label:string}>
	 */
	private static function query_by_title_tokens( string $term, array $types, int $limit, bool $require_all_tokens ): array {
		global $wpdb;

		$tokens = self::tokenize( $term );
		if ( empty( $tokens ) ) {
			return array();
		}

		$in = self::sql_in_placeholders( $types );

		if ( $require_all_tokens ) {
			$where  = array( "post_status = 'publish'", "post_type IN ($in)" );
			$params = $types;

			foreach ( $tokens as $token ) {
				$where[]  = 'LOWER(post_title) LIKE %s';
				$params[] = '%' . $wpdb->esc_like( $token ) . '%';
			}

			$sql = 'SELECT ID, post_title FROM ' . $wpdb->posts . ' WHERE ' . implode( ' AND ', $where ) .
				' ORDER BY post_title ASC LIMIT %d';
			$params[] = $limit;

			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		} else {
			$like_parts = array();
			$params     = $types;

			foreach ( $tokens as $token ) {
				$like_parts[] = 'LOWER(post_title) LIKE %s';
				$params[]     = '%' . $wpdb->esc_like( $token ) . '%';
			}

			$params[] = $limit;
			$sql      = "SELECT ID, post_title FROM {$wpdb->posts}
				WHERE post_status = 'publish' AND post_type IN ($in)
				AND (" . implode( ' OR ', $like_parts ) . ")
				ORDER BY post_title ASC LIMIT %d";

			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}

		if ( empty( $rows ) ) {
			return array();
		}

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = self::format_row( $row );
		}

		return $items;
	}

	/**
	 * @return string[]
	 */
	private static function tokenize( string $term ): array {
		$term = strtolower( $term );
		$term = preg_replace( '/[^\p{L}\p{N}\s\-\+]/u', ' ', $term );
		if ( ! is_string( $term ) ) {
			return array();
		}

		$parts = preg_split( '/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array();
		}

		$tokens = array();
		foreach ( $parts as $part ) {
			$part = trim( $part, '-' );
			if ( '' !== $part ) {
				$tokens[] = $part;
			}
		}

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * @param string[] $values Values for IN (...).
	 */
	private static function sql_in_placeholders( array $values ): string {
		return implode( ', ', array_fill( 0, count( $values ), '%s' ) );
	}

	/**
	 * @param object $row DB row with ID and post_title.
	 * @return array{id:int,label:string}
	 */
	private static function format_row( object $row ): array {
		$id    = (int) $row->ID;
		$title = isset( $row->post_title ) ? (string) $row->post_title : '';

		return array(
			'id'    => $id,
			'label' => sprintf( '#%d — %s', $id, $title ),
		);
	}

	public static function check_rate_limit(): bool {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}

		$key   = 'mmi_csl_rl_' . $user_id;
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
