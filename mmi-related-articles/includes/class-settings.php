<?php
/**
 * Plugin settings (mobile carousel).
 *
 * @package MMI_Related_Articles
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings API.
 */
class MMI_RA_Settings {

	const OPTION_KEY = 'mmi_ra_settings';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	/**
	 * Add settings page.
	 */
	public static function menu() {
		add_options_page(
			__( 'Related Articles', 'mmi-related-articles' ),
			__( 'Related Articles', 'mmi-related-articles' ),
			'manage_options',
			'mmi-ra-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register options.
	 */
	public static function register() {
		register_setting(
			'mmi_ra_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'mmi_ra_mobile',
			__( 'Mobile carousel', 'mmi-related-articles' ),
			static function () {
				echo '<p>' . esc_html__( 'Control how many related article cards appear on phones. A small peek of the next card hints that more articles are available.', 'mmi-related-articles' ) . '</p>';
			},
			'mmi-ra-settings'
		);

		add_settings_field(
			'mobile_visible',
			__( 'Cards on mobile', 'mmi-related-articles' ),
			array( __CLASS__, 'field_mobile_visible' ),
			'mmi-ra-settings',
			'mmi_ra_mobile'
		);
	}

	/**
	 * @return array<string, int>
	 */
	public static function defaults() {
		return array(
			'mobile_visible' => 2,
		);
	}

	/**
	 * @return array<string, int>
	 */
	public static function get_all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * @return int 2 or 3.
	 */
	public static function get_mobile_visible() {
		$val = (int) self::get_all()['mobile_visible'];
		return in_array( $val, array( 2, 3 ), true ) ? $val : 2;
	}

	/**
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, int>
	 */
	public static function sanitize( $input ) {
		$out = self::defaults();
		if ( ! is_array( $input ) ) {
			return $out;
		}
		$mobile = isset( $input['mobile_visible'] ) ? (int) $input['mobile_visible'] : 2;
		$out['mobile_visible'] = in_array( $mobile, array( 2, 3 ), true ) ? $mobile : 2;
		return $out;
	}

	/**
	 * Mobile cards field.
	 */
	public static function field_mobile_visible() {
		$val = self::get_mobile_visible();
		?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[mobile_visible]" value="2" <?php checked( $val, 2 ); ?> />
				<?php esc_html_e( '2 cards + peek of next (recommended)', 'mmi-related-articles' ); ?>
			</label><br />
			<label>
				<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[mobile_visible]" value="3" <?php checked( $val, 3 ); ?> />
				<?php esc_html_e( '3 compact cards + peek', 'mmi-related-articles' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Settings page markup.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MMI Related Articles', 'mmi-related-articles' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'mmi_ra_settings_group' );
				do_settings_sections( 'mmi-ra-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}