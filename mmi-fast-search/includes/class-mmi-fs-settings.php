<?php
/**
 * Plugin settings (lightweight defaults; optional settings screen).
 */

defined( 'ABSPATH' ) || exit;

class MMI_FS_Settings {

	const OPTION = 'mmi_fs_settings';

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'           => true,
			'replace_tagdiv'    => true,
			'max_posts'         => 4000,
			'days_limit'        => 0,
			'preload_index'     => false,
			'post_types'        => array( 'post' ),
			'search_url'        => '',
			'debounce_ms'       => 40,
			'results_limit'     => 12,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	public static function register_settings(): void {
		register_setting(
			'mmi_fs_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * @param array<string,mixed>|mixed $input Raw option.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ): array {
		$out     = self::defaults();
		$input   = is_array( $input ) ? $input : array();
		$out['enabled']        = ! empty( $input['enabled'] );
		$out['replace_tagdiv'] = ! empty( $input['replace_tagdiv'] );
		$out['preload_index']  = ! empty( $input['preload_index'] );
		$out['max_posts']      = max( 500, min( 15000, (int) ( $input['max_posts'] ?? 4000 ) ) );
		$out['days_limit']     = max( 0, min( 3650, (int) ( $input['days_limit'] ?? 0 ) ) );
		$out['debounce_ms']    = max( 20, min( 200, (int) ( $input['debounce_ms'] ?? 40 ) ) );
		$out['results_limit']  = max( 4, min( 24, (int) ( $input['results_limit'] ?? 12 ) ) );
		$out['search_url']     = esc_url_raw( (string) ( $input['search_url'] ?? '' ) );

		$types = array();
		if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $pt ) {
				$pt = sanitize_key( (string) $pt );
				if ( $pt && post_type_exists( $pt ) ) {
					$types[] = $pt;
				}
			}
		}
		$out['post_types'] = ! empty( $types ) ? $types : array( 'post' );

		return $out;
	}

	public static function admin_menu(): void {
		add_options_page(
			__( 'MMI Fast Search', 'mmi-fast-search' ),
			__( 'MMI Fast Search', 'mmi-fast-search' ),
			'manage_options',
			'mmi-fast-search',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s      = self::get();
		$index  = MMI_FS_Index::get_index_meta();
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$sel    = $s['post_types'];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MMI Fast Frontend Search', 'mmi-fast-search' ); ?></h1>
			<p><?php esc_html_e( 'Client-side search index — typing does not hit PHP/MySQL (light on CPU & RAM).', 'mmi-fast-search' ); ?></p>
			<?php if ( ! empty( $index['count'] ) ) : ?>
				<p>
					<strong><?php esc_html_e( 'Index:', 'mmi-fast-search' ); ?></strong>
					<?php
					printf(
						/* translators: 1: post count 2: file size 3: date */
						esc_html__( '%1$d posts · %2$s · updated %3$s', 'mmi-fast-search' ),
						(int) $index['count'],
						esc_html( (string) ( $index['size_h'] ?? '' ) ),
						esc_html( (string) ( $index['updated_h'] ?? '' ) )
					);
					?>
				</p>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'mmi_fs_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable on frontend', 'mmi-fast-search' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enabled]" value="1" <?php checked( $s['enabled'] ); ?> /> <?php esc_html_e( 'On', 'mmi-fast-search' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Replace tagDiv search', 'mmi-fast-search' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[replace_tagdiv]" value="1" <?php checked( $s['replace_tagdiv'] ); ?> /> <?php esc_html_e( 'Intercept magnifying glass & hide default dropdown', 'mmi-fast-search' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post types', 'mmi-fast-search' ); ?></th>
						<td>
							<?php foreach ( $types as $obj ) : ?>
								<label style="display:block;margin:2px 0;">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[post_types][]" value="<?php echo esc_attr( $obj->name ); ?>" <?php checked( in_array( $obj->name, $sel, true ) ); ?> />
									<?php echo esc_html( $obj->labels->singular_name . ' (' . $obj->name . ')' ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max posts in index', 'mmi-fast-search' ); ?></th>
						<td><input type="number" min="500" max="15000" name="<?php echo esc_attr( self::OPTION ); ?>[max_posts]" value="<?php echo esc_attr( (string) $s['max_posts'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Only last N days (0 = all)', 'mmi-fast-search' ); ?></th>
						<td><input type="number" min="0" max="3650" name="<?php echo esc_attr( self::OPTION ); ?>[days_limit]" value="<?php echo esc_attr( (string) $s['days_limit'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Preload index in browser', 'mmi-fast-search' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[preload_index]" value="1" <?php checked( $s['preload_index'] ); ?> /> <?php esc_html_e( 'Fetch JSON on page load (faster first open; slightly more bandwidth)', 'mmi-fast-search' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Full search URL', 'mmi-fast-search' ); ?></th>
						<td><input type="url" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[search_url]" value="<?php echo esc_attr( (string) $s['search_url'] ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" /><p class="description"><?php esc_html_e( '“View all results” link. Leave empty for site home + ?s= query.', 'mmi-fast-search' ); ?></p></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mmi_fs_rebuild_index_now" />
				<?php wp_nonce_field( 'mmi_fs_rebuild' ); ?>
				<?php submit_button( __( 'Rebuild index now', 'mmi-fast-search' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}

add_action(
	'admin_post_mmi_fs_rebuild_index_now',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'mmi-fast-search' ) );
		}
		check_admin_referer( 'mmi_fs_rebuild' );
		MMI_FS_Index::rebuild();
		wp_safe_redirect( add_query_arg( 'mmi_fs_rebuilt', '1', wp_get_referer() ?: admin_url( 'options-general.php?page=mmi-fast-search' ) ) );
		exit;
	}
);
