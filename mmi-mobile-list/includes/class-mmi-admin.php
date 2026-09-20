<?php
/**
 * Settings: cache TTL and manual purge.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_ML_Admin {

	/**
	 * Register hooks.
	 */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Settings page.
	 */
	public static function menu() {
		add_options_page(
			__( 'MMI Mobile List', 'mmi-mobile-list' ),
			__( 'MMI Mobile List', 'mmi-mobile-list' ),
			'manage_options',
			'mmi-mobile-list',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register option.
	 */
	public static function register_settings() {
		register_setting(
			'mmi_ml_settings',
			'mmi_ml_cache_ttl',
			array(
				'type'              => 'integer',
				'sanitize_callback' => function ( $value ) {
					$value = (int) $value;
					return max( 300, min( DAY_IN_SECONDS, $value ) );
				},
				'default'           => 6 * HOUR_IN_SECONDS,
			)
		);
	}

	/**
	 * Settings UI.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if (
			isset( $_POST['mmi_ml_flush'] ) &&
			check_admin_referer( 'mmi_ml_flush_cache' )
		) {
			MMI_ML_Cache::flush_all();
			echo '<div class="notice notice-success"><p>';
			esc_html_e( 'List cache cleared.', 'mmi-mobile-list' );
			echo '</p></div>';
		}

		$ttl = MMI_ML_Cache::ttl();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MMI Mobile Price List', 'mmi-mobile-list' ); ?></h1>
			<p><?php esc_html_e( 'Cached listings reduce database load. Clear cache after bulk price updates.', 'mmi-mobile-list' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'mmi_ml_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="mmi_ml_cache_ttl"><?php esc_html_e( 'Cache duration (seconds)', 'mmi-mobile-list' ); ?></label>
						</th>
						<td>
							<input
								name="mmi_ml_cache_ttl"
								id="mmi_ml_cache_ttl"
								type="number"
								min="300"
								max="<?php echo esc_attr( (string) DAY_IN_SECONDS ); ?>"
								value="<?php echo esc_attr( (string) $ttl ); ?>"
								class="regular-text"
							/>
							<p class="description">
								<?php esc_html_e( 'Default: 21600 (6 hours). Same visitor traffic reuses cached HTML — no repeat product queries.', 'mmi-mobile-list' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<form method="post">
				<?php wp_nonce_field( 'mmi_ml_flush_cache' ); ?>
				<p>
					<button type="submit" name="mmi_ml_flush" class="button button-secondary">
						<?php esc_html_e( 'Clear listing cache now', 'mmi-mobile-list' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
