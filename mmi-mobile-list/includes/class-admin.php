<?php
/**
 * Settings screen: archive toggles, download zip, shortcode help.
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Admin {

	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_mmi_mll_download_zip', array( __CLASS__, 'download_zip' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function menu(): void {
		add_options_page(
			__( 'MMI Mobile List', 'mmi-mobile-list' ),
			__( 'MMI Mobile List', 'mmi-mobile-list' ),
			'manage_options',
			MMI_MLL_Settings::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings(): void {
		register_setting(
			'mmi_mll_settings',
			MMI_MLL_Settings::OPTION_ARCHIVES,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_archives' ),
				'default'           => array(),
			)
		);
		register_setting(
			'mmi_mll_settings',
			MMI_MLL_Settings::OPTION_BRAND_ARCHIVES,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => static function ( $value ) {
					return ! empty( $value );
				},
				'default'           => true,
			)
		);
		register_setting(
			'mmi_mll_settings',
			MMI_MLL_Settings::OPTION_PER_PAGE,
			array(
				'type'              => 'integer',
				'sanitize_callback' => static function ( $value ) {
					return max( 1, min( 48, (int) $value ) );
				},
				'default'           => 12,
			)
		);
		register_setting(
			'mmi_mll_settings',
			MMI_MLL_Settings::OPTION_RELEASE_DATE,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => static function ( $value ) {
					return ! empty( $value );
				},
				'default'           => true,
			)
		);
	}

	/**
	 * @param mixed $input Raw option.
	 * @return array<string,bool>
	 */
	public static function sanitize_archives( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$out   = array();
		foreach ( MMI_MLL_Settings::DEFAULT_ARCHIVE_SLUGS as $slug => $label ) {
			$out[ $slug ] = ! empty( $input[ $slug ] );
		}
		return $out;
	}

	public static function enqueue( string $hook ): void {
		if ( 'settings_page_' . MMI_MLL_Settings::MENU_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'mmi-mll-admin',
			MMI_MLL_URL . 'assets/css/admin.css',
			array(),
			MMI_MLL_VERSION
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$archives = MMI_MLL_Settings::get_archive_map();
		$brand    = MMI_MLL_Settings::brand_archives_enabled();
		$per_page = MMI_MLL_Settings::per_page_default();
		$release  = MMI_MLL_Settings::show_release_date();

		$download_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=mmi_mll_download_zip' ),
			'mmi_mll_download_zip'
		);
		$github_url = MMI_MLL_Settings::github_zip_url();
		?>
		<div class="wrap mmi-mll-admin">
			<h1><?php esc_html_e( 'MMI Mobile List — Spec cards', 'mmi-mobile-list' ); ?></h1>
			<p class="description">
				<?php esc_html_e( '91mobiles-style spec cards for category archives and PAF pages. Uses WooCommerce product attributes (pa_processor, pa_ram, etc.).', 'mmi-mobile-list' ); ?>
			</p>

			<div class="mmi-mll-download-card">
				<h2><?php esc_html_e( 'Download plugin', 'mmi-mobile-list' ); ?></h2>
				<p>
					<a class="button button-primary button-hero" href="<?php echo esc_url( $download_url ); ?>">
						<?php esc_html_e( 'Download ZIP (this site)', 'mmi-mobile-list' ); ?>
					</a>
					<a class="button button-secondary" href="<?php echo esc_url( $github_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'GitHub direct zip', 'mmi-mobile-list' ); ?>
					</a>
				</p>
				<p class="description">
					<code><?php echo esc_html( $github_url ); ?></code>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'mmi_mll_settings' ); ?>

				<h2><?php esc_html_e( 'Archive pages (spec layout)', 'mmi-mobile-list' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Best / Latest / Upcoming', 'mmi-mobile-list' ); ?></th>
						<td>
							<?php foreach ( MMI_MLL_Settings::DEFAULT_ARCHIVE_SLUGS as $slug => $label ) : ?>
								<label class="mmi-mll-check">
									<input
										type="checkbox"
										name="<?php echo esc_attr( MMI_MLL_Settings::OPTION_ARCHIVES ); ?>[<?php echo esc_attr( $slug ); ?>]"
										value="1"
										<?php checked( ! empty( $archives[ $slug ] ) ); ?>
									/>
									<?php echo esc_html( $label ); ?>
									<code>/<?php echo esc_html( $slug ); ?>/</code>
								</label><br />
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Brand pages', 'mmi-mobile-list' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MMI_MLL_Settings::OPTION_BRAND_ARCHIVES ); ?>" value="0" />
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr( MMI_MLL_Settings::OPTION_BRAND_ARCHIVES ); ?>"
									value="1"
									<?php checked( $brand ); ?>
								/>
								<?php esc_html_e( 'Use spec cards on all product_brand archives (e.g. /brand/vivo/)', 'mmi-mobile-list' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mmi_mll_per_page"><?php esc_html_e( 'Products per page', 'mmi-mobile-list' ); ?></label></th>
						<td>
							<input type="number" id="mmi_mll_per_page" name="<?php echo esc_attr( MMI_MLL_Settings::OPTION_PER_PAGE ); ?>" value="<?php echo esc_attr( (string) $per_page ); ?>" min="1" max="48" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Release date', 'mmi-mobile-list' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MMI_MLL_Settings::OPTION_RELEASE_DATE ); ?>" value="0" />
							<label>
								<input type="checkbox" name="<?php echo esc_attr( MMI_MLL_Settings::OPTION_RELEASE_DATE ); ?>" value="1" <?php checked( $release ); ?> />
								<?php esc_html_e( 'Show “Release Date” under title (from pa_launch-date or post date)', 'mmi-mobile-list' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'PAF / custom pages (shortcode)', 'mmi-mobile-list' ); ?></h2>
				<p><?php esc_html_e( 'Add to Elementor Shortcode widget or page content:', 'mmi-mobile-list' ); ?></p>
				<pre class="mmi-mll-code">[mmi_mobile_list price_range="rs-10000-rs-20000" per_page="5" paginate="1"]</pre>
				<pre class="mmi-mll-code">[mmi_mobile_list category="best-mobiles" per_page="12"]</pre>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function download_zip(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'mmi-mobile-list' ) );
		}
		check_admin_referer( 'mmi_mll_download_zip' );

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'ZipArchive is not available on this server.', 'mmi-mobile-list' ) );
		}

		$zip      = new ZipArchive();
		$tmp_file = wp_tempnam( 'mmi-mobile-list' );
		if ( ! $zip->open( $tmp_file, ZipArchive::OVERWRITE ) ) {
			wp_die( esc_html__( 'Could not create zip file.', 'mmi-mobile-list' ) );
		}

		$root = MMI_MLL_PATH;
		$it   = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $it as $file ) {
			/** @var SplFileInfo $file */
			$path = $file->getPathname();
			if ( str_contains( $path, '.git' ) ) {
				continue;
			}
			$local = 'mmi-mobile-list/' . substr( $path, strlen( $root ) );
			if ( $file->isDir() ) {
				$zip->addEmptyDir( $local );
			} else {
				$zip->addFile( $path, $local );
			}
		}

		$zip->close();

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="mmi-mobile-list.zip"' );
		header( 'Content-Length: ' . filesize( $tmp_file ) );
		readfile( $tmp_file );
		wp_delete_file( $tmp_file );
		exit;
	}
}