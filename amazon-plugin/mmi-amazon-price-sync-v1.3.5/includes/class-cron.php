<?php
/**
 * WP-Cron automatic Amazon price sync — batched, locked, low server impact.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Cron {

	public const CRON_HOOK            = 'mmi_aps_auto_sync';
	public const CRON_CONTINUE_HOOK   = 'mmi_aps_continue_sync';
	public const ENSURE_CHECK_TRANSIENT = 'mmi_aps_cron_ensure_checked';

	public static function init(): void {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedules' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_auto_sync' ) );
		add_action( self::CRON_CONTINUE_HOOK, array( __CLASS__, 'run_continue_sync' ) );
		add_action( 'update_option_' . MMI_APS_Settings::OPTION_AUTO_SYNC, array( __CLASS__, 'maybe_reschedule' ), 10, 0 );
		add_action( 'update_option_' . MMI_APS_Settings::OPTION_SYNC_INTERVAL, array( __CLASS__, 'maybe_reschedule' ), 10, 0 );
		add_action( 'admin_init', array( __CLASS__, 'ensure_scheduled' ) );
	}

	/**
	 * @param array<string,array<string,mixed>> $schedules Cron schedules.
	 * @return array<string,array<string,mixed>>
	 */
	public static function add_schedules( array $schedules ): array {
		$schedules['mmi_aps_six_hours'] = array(
			'interval' => 6 * HOUR_IN_SECONDS,
			'display'  => __( 'Every 6 Hours', 'mmi-amazon-price-sync' ),
		);
		$schedules['mmi_aps_twelve_hours'] = array(
			'interval' => 12 * HOUR_IN_SECONDS,
			'display'  => __( 'Every 12 Hours', 'mmi-amazon-price-sync' ),
		);

		return $schedules;
	}

	public static function activate(): void {
		self::schedule();
	}

	public static function deactivate(): void {
		self::unschedule();
		wp_clear_scheduled_hook( self::CRON_CONTINUE_HOOK );
		delete_option( MMI_APS_Sync::OPTION_CRON_OFFSET );
		MMI_APS_Sync::release_lock();
	}

	public static function schedule(): void {
		self::unschedule();

		if ( ! MMI_APS_Settings::is_auto_sync_enabled() ) {
			return;
		}

		$interval = MMI_APS_Settings::get_sync_interval();
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, self::CRON_HOOK );
		}
	}

	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
		}
	}

	public static function maybe_reschedule(): void {
		self::schedule();
	}

	/**
	 * Check cron schedule once per hour in admin only — avoids frontend overhead.
	 */
	public static function ensure_scheduled(): void {
		if ( get_transient( self::ENSURE_CHECK_TRANSIENT ) ) {
			return;
		}

		set_transient( self::ENSURE_CHECK_TRANSIENT, 1, HOUR_IN_SECONDS );

		if ( MMI_APS_Settings::is_auto_sync_enabled() && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			self::schedule();
		}
	}

	public static function run_auto_sync(): void {
		if ( ! MMI_APS_Settings::is_auto_sync_enabled() || '' === MMI_APS_Settings::get_api_key() ) {
			return;
		}

		delete_option( MMI_APS_Sync::OPTION_CRON_OFFSET );
		delete_option( 'mmi_aps_cron_progress' );
		self::run_sync_cycle();
	}

	public static function run_continue_sync(): void {
		if ( ! MMI_APS_Settings::is_auto_sync_enabled() || '' === MMI_APS_Settings::get_api_key() ) {
			return;
		}

		self::run_sync_cycle();
	}

	private static function run_sync_cycle(): void {
		$completed = MMI_APS_Sync::sync_cron_step();

		if ( ! $completed && ! wp_next_scheduled( self::CRON_CONTINUE_HOOK ) ) {
			wp_schedule_single_event( time() + ( 3 * MINUTE_IN_SECONDS ), self::CRON_CONTINUE_HOOK );
		}
	}

	/**
	 * Human-readable next run time.
	 */
	public static function get_next_run_label(): string {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( ! $timestamp ) {
			return __( 'Not scheduled', 'mmi-amazon-price-sync' );
		}

		return wp_date( 'j M Y, g:i A', $timestamp );
	}
}
