<?php
/**
 * WooCommerce admin screen to install TV attributes.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_TV_Admin {

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 60 );
		add_action( 'admin_post_mmi_tv_install_attributes', array( __CLASS__, 'handle_install' ) );
	}

	public static function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			'TV Attributes',
			'TV Attributes',
			'manage_woocommerce',
			'mmi-tv-attributes',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function handle_install(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to install TV attributes.', 'mmi-tv-attributes' ) );
		}

		check_admin_referer( 'mmi_tv_install_attributes' );

		$result = MMI_TV_Installer::install();
		set_transient( 'mmi_tv_install_result', $result, 60 );

		wp_safe_redirect( admin_url( 'admin.php?page=mmi-tv-attributes&installed=1' ) );
		exit;
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$result   = get_transient( 'mmi_tv_install_result' );
		$groups   = MMI_TV_Schema::groups();
		$by_slug  = array();
		foreach ( MMI_TV_Schema::attributes() as $attr ) {
			$by_slug[ $attr['slug'] ] = $attr;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WooCommerce TV Attributes', 'mmi-tv-attributes' ); ?></h1>
			<p><?php esc_html_e( 'Mobile attributes stay as they are. This creates a separate TV set (slugs start with tv-) so phone filters and TV filters never mix.', 'mmi-tv-attributes' ); ?></p>
			<p><?php esc_html_e( 'Mobile ke attributes same rahenge. TV ke liye naye attributes banenge (slug tv- se start). Phir Products → Attributes mein TV list dikhegi.', 'mmi-tv-attributes' ); ?></p>

			<?php if ( is_array( $result ) ) : ?>
				<?php delete_transient( 'mmi_tv_install_result' ); ?>
				<div class="notice notice-success">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: created count, 2: already existing count, 3: new terms, 4: category status */
								__( 'Done. New attributes: %1$d. Already present: %2$d. New terms: %3$d. TVs category: %4$s.', 'mmi-tv-attributes' ),
								(int) $result['created'],
								(int) $result['existing'],
								(int) $result['terms'],
								(string) $result['category']
							)
						);
						?>
					</p>
					<?php if ( ! empty( $result['errors'] ) ) : ?>
						<ul>
							<?php foreach ( $result['errors'] as $error ) : ?>
								<li><?php echo esc_html( $error ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'mmi_tv_install_attributes' ); ?>
				<input type="hidden" name="action" value="mmi_tv_install_attributes" />
				<?php submit_button( __( 'Create / update TV attributes', 'mmi-tv-attributes' ) ); ?>
			</form>

			<p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=product_attributes' ) ); ?>">
					<?php esc_html_e( 'Open Products → Attributes', 'mmi-tv-attributes' ); ?>
				</a>
			</p>

			<h2><?php esc_html_e( 'How to use on a TV product', 'mmi-tv-attributes' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Click the button above once (safe to run again).', 'mmi-tv-attributes' ); ?></li>
				<li><?php esc_html_e( 'Edit a TV product → Product data → Attributes → Custom product attribute dropdown → pick TV attributes (Screen Size, Resolution, Operating System, …).', 'mmi-tv-attributes' ); ?></li>
				<li><?php esc_html_e( 'Set values, tick Visible on the product page, then Save attributes and Update the product.', 'mmi-tv-attributes' ); ?></li>
				<li><?php esc_html_e( 'Put the product in the TVs category so the grouped spec table (like mobiles) is used.', 'mmi-tv-attributes' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'TV attribute groups', 'mmi-tv-attributes' ); ?></h2>
			<?php foreach ( $groups as $group => $slugs ) : ?>
				<h3><?php echo esc_html( $group ); ?></h3>
				<table class="widefat striped" style="max-width:880px;margin-bottom:16px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Label', 'mmi-tv-attributes' ); ?></th>
							<th><?php esc_html_e( 'Slug (taxonomy)', 'mmi-tv-attributes' ); ?></th>
							<th><?php esc_html_e( 'Preset terms', 'mmi-tv-attributes' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $slugs as $slug ) : ?>
							<?php $attr = $by_slug[ $slug ]; ?>
							<tr>
								<td><?php echo esc_html( $attr['label'] ); ?></td>
								<td><code>pa_<?php echo esc_html( $slug ); ?></code></td>
								<td><?php echo $attr['terms'] ? esc_html( implode( ', ', $attr['terms'] ) ) : '—'; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
