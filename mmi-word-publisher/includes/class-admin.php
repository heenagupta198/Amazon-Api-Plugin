<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MMI_Word_Publisher_Admin {

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function menu(): void {
		add_options_page(
			__( 'MMI Word Publisher', 'mmi-word-publisher' ),
			__( 'MMI Word Publisher', 'mmi-word-publisher' ),
			'manage_options',
			'mmi-word-publisher',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings(): void {
		register_setting( 'mmi_word_publisher', 'mmi_word_publisher_manifest_note', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$manifest_url = MMI_Word_Publisher_Addin_Serve::manifest_url();
		$addin_base   = MMI_WORD_PUBLISHER_URL . 'add-in/';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MMI Word Publisher', 'mmi-word-publisher' ); ?></h1>
			<p><?php esc_html_e( 'Writers use the Microsoft Word add-in to send articles as drafts. Images are added later in WordPress.', 'mmi-word-publisher' ); ?></p>

			<h2><?php esc_html_e( 'WordPress (each writer)', 'mmi-word-publisher' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Users → All Users → open the writer profile.', 'mmi-word-publisher' ); ?></li>
				<li><?php esc_html_e( 'Application Passwords → name: Word Article Transfer → Add New Application Password.', 'mmi-word-publisher' ); ?></li>
				<li><?php esc_html_e( 'Copy the generated password once (spaces optional in the add-in).', 'mmi-word-publisher' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Add-in files (HTTPS)', 'mmi-word-publisher' ); ?></h2>
			<table class="widefat striped" style="max-width:900px">
				<tr>
					<th><?php esc_html_e( 'Manifest URL (central deploy / IT)', 'mmi-word-publisher' ); ?></th>
					<td><code><?php echo esc_html( $manifest_url ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Add-in folder', 'mmi-word-publisher' ); ?></th>
					<td><code><?php echo esc_html( $addin_base ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'REST draft endpoint', 'mmi-word-publisher' ); ?></th>
					<td><code><?php echo esc_html( rest_url( 'mmi-word-publisher/v1/draft' ) ); ?></code></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Microsoft Word — two ways', 'mmi-word-publisher' ); ?></h2>
			<h3><?php esc_html_e( 'A) Centralized (recommended for 2+ writers)', 'mmi-word-publisher' ); ?></h3>
			<p><?php esc_html_e( 'Microsoft 365 admin uploads the manifest once; assigned users get the add-in automatically in Word (no visit to each PC).', 'mmi-word-publisher' ); ?></p>
			<h3><?php esc_html_e( 'B) Per computer (manual)', 'mmi-word-publisher' ); ?></h3>
			<p><?php esc_html_e( 'Each writer: Insert → Get Add-ins → My Add-ins → Upload My Add-in → select manifest.xml (or use manifest URL if your Word build supports it).', 'mmi-word-publisher' ); ?></p>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( $manifest_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Open manifest.xml', 'mmi-word-publisher' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( plugin_dir_path( MMI_WORD_PUBLISHER_FILE ) . 'SETUP-HINDI.md' ); ?>">
					<?php esc_html_e( 'Full setup guide (SETUP-HINDI.md in plugin folder)', 'mmi-word-publisher' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
