<?php
/**
 * Fragment cache — avoids repeat WC/DB work on every page view.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Cache {

	const TRANSIENT_PREFIX = 'mmi_ml_v';

	/**
	 * Cache TTL in seconds.
	 */
	public static function ttl() {
		$ttl = (int) get_option( 'mmi_ml_cache_ttl', 6 * HOUR_IN_SECONDS );
		return max( 300, min( DAY_IN_SECONDS, $ttl ) );
	}

	/**
	 * Build stable cache key from query arguments.
	 *
	 * @param array<string, mixed> $args Args.
	 * @return string
	 */
	public static function list_key( $args ) {
		return self::TRANSIENT_PREFIX . md5( wp_json_encode( $args ) );
	}

	/**
	 * @param string $key Cache key.
	 * @return array<string, mixed>|false
	 */
	public static function get_list( $key ) {
		$found = false;
		$data  = wp_cache_get( $key, 'mmi_mobile_list', false, $found );
		if ( $found && is_array( $data ) ) {
			return $data;
		}

		$data = get_transient( $key );
		return is_array( $data ) ? $data : false;
	}

	/**
	 * @param string               $key  Key.
	 * @param array<string, mixed> $data Payload.
	 */
	public static function set_list( $key, $data ) {
		wp_cache_set( $key, $data, 'mmi_mobile_list', self::ttl() );
		set_transient( $key, $data, self::ttl() );
	}

	/**
	 * Delete all plugin list transients (debounced on bulk product updates).
	 */
	public static function flush_all() {
		global $wpdb;

		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'mmi_mobile_list' );
		}

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' ) . self::TRANSIENT_PREFIX . '%',
				$wpdb->esc_like( '_transient_timeout_' ) . self::TRANSIENT_PREFIX . '%'
			)
		);
	}

	/**
	 * Schedule a single flush instead of many during import/sync.
	 */
	public static function schedule_flush() {
		if ( wp_next_scheduled( 'mmi_ml_deferred_flush' ) ) {
			return;
		}
		wp_schedule_single_event( time() + 60, 'mmi_ml_deferred_flush' );
	}
}

add_action(
	'mmi_ml_deferred_flush',
	function () {
		MMI_ML_Cache::flush_all();
	}
);
