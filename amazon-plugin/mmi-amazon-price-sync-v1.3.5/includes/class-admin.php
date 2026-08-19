<?php
/**
 * Standalone admin pages — visible menu + product meta box fallback.
 */

defined( 'ABSPATH' ) || exit;

class MMI_APS_Admin {

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 99 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_product_meta_box' ), 20 );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_setup_notice' ) );
	}

	public static function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Amazon Price Sync', 'mmi-amazon-price-sync' ),
			__( 'Amazon Price Sync', 'mmi-amazon-price-sync' ),
			'manage_woocommerce',
			'mmi-amazon-price-sync',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings(): void {
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_API_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_API_HOST,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_API_ENDPOINT,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_COUNTRY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_LANGUAGE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_AUTO_SYNC,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
				'default'           => 'yes',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_SYNC_INTERVAL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_SYNC_DELAY,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_sync_delay' ),
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_BATCH_SIZE,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_batch_size' ),
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_AFFILIATE_ENABLED,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_AFFILIATE_TAG,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( 'MMI_APS_Settings', 'sanitize_affiliate_tag' ),
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_AFFILIATE_BTN_TEXT,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_AFFILIATE_SHOP,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_AFFILIATE_DISCLOSURE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			)
		);
		register_setting(
			'mmi_aps_settings',
			MMI_APS_Settings::OPTION_CRON_BATCH_SIZE,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_cron_batch_size' ),
			)
		);
		add_action( 'wp_ajax_mmi_aps_test_api', array( __CLASS__, 'ajax_test_api' ) );
		add_action( 'wp_ajax_mmi_aps_sync_batch', array( __CLASS__, 'ajax_sync_batch' ) );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_yes_no( $value ): string {
		return 'yes' === $value ? 'yes' : 'no';
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_sync_delay( $value ): int {
		return max( 1, min( 10, (int) $value ) );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_batch_size( $value ): int {
		return max( 1, min( 50, (int) $value ) );
	}

	public static function sanitize_cron_batch_size( $value ): int {
		return max( 5, min( 50, (int) $value ) );
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$using_constant = defined( 'MMI_APS_RAPIDAPI_KEY' ) && MMI_APS_RAPIDAPI_KEY;
		$api_key        = MMI_APS_Settings::get_api_key();
		$api_host       = MMI_APS_Settings::get_api_host();
		$api_endpoint   = MMI_APS_Settings::get_api_endpoint();
		$country        = MMI_APS_Settings::get_country();
		$language       = MMI_APS_Settings::get_language();
		$sample_url     = 'https://' . $api_host . '/' . ltrim( $api_endpoint, '/' ) . '?asin=B0H8STM6G2&country=IN&autoselect_variant=true&language=' . rawurlencode( $language );
		$wrong_host     = false !== strpos( $api_host, 'real-time-e-commerce-data' );
		$auto_sync      = MMI_APS_Settings::is_auto_sync_enabled();
		$sync_interval  = MMI_APS_Settings::get_sync_interval();
		$sync_delay     = MMI_APS_Settings::get_sync_delay();
		$batch_size     = MMI_APS_Settings::get_batch_size();
		$cron_batch     = MMI_APS_Settings::get_cron_batch_size();
		$asin_count     = MMI_APS_Sync::count_products_with_asin();
		$last_run       = MMI_APS_Sync::get_last_run();
		$last_summary   = MMI_APS_Sync::get_last_summary();
		$next_cron      = MMI_APS_Cron::get_next_run_label();
		$affiliate_on   = MMI_APS_Settings::is_affiliate_enabled();
		$affiliate_tag  = MMI_APS_Settings::get_affiliate_tag();
		$affiliate_text = (string) get_option( MMI_APS_Settings::OPTION_AFFILIATE_BTN_TEXT, '' );
		$affiliate_shop = MMI_APS_Settings::show_affiliate_on_shop();
		$affiliate_disc = MMI_APS_Settings::show_affiliate_disclosure();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Amazon Price Sync', 'mmi-amazon-price-sync' ); ?></h1>
			<p><?php esc_html_e( 'Connect WooCommerce products to Amazon India prices via RapidAPI. Add an ASIN on each product, then click Fetch Price.', 'mmi-amazon-price-sync' ); ?></p>

			<?php if ( $wrong_host ) : ?>
				<div class="notice notice-error"><p>
					<?php esc_html_e( 'Wrong API host detected. Change Host to real-time-amazon-data.p.rapidapi.com and Endpoint to /product-details, then Save.', 'mmi-amazon-price-sync' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'mmi_aps_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'RapidAPI Key', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<?php if ( $using_constant ) : ?>
								<p class="description"><?php esc_html_e( 'Defined in wp-config.php via MMI_APS_RAPIDAPI_KEY.', 'mmi-amazon-price-sync' ); ?></p>
							<?php else : ?>
								<input type="password" class="regular-text" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_API_KEY ); ?>" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="off" />
								<p class="description"><?php esc_html_e( 'Your x-rapidapi-key from the RapidAPI dashboard.', 'mmi-amazon-price-sync' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'RapidAPI Host', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_API_HOST ); ?>" value="<?php echo esc_attr( $api_host ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'API Endpoint Path', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_API_ENDPOINT ); ?>" value="<?php echo esc_attr( $api_endpoint ); ?>" />
							<p class="description"><?php esc_html_e( 'Use /product-details for Real-Time Amazon Data API.', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Language', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="text" class="small-text" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_LANGUAGE ); ?>" value="<?php echo esc_attr( $language ); ?>" />
							<p class="description"><?php esc_html_e( 'Use en_IN for Amazon India.', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Amazon Country', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="text" class="small-text" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_COUNTRY ); ?>" value="<?php echo esc_attr( $country ); ?>" />
							<p class="description"><?php esc_html_e( 'Use IN for Amazon India.', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Auto Sync', 'mmi-amazon-price-sync' ); ?></h2>
				<p><?php esc_html_e( 'Automatically update prices for all products that have an Amazon ASIN.', 'mmi-amazon-price-sync' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Auto Sync', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AUTO_SYNC ); ?>" value="no" />
							<label>
								<input type="checkbox" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AUTO_SYNC ); ?>" value="yes" <?php checked( $auto_sync ); ?> />
								<?php esc_html_e( 'Run automatic price sync via WP-Cron', 'mmi-amazon-price-sync' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sync Interval', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( MMI_APS_Settings::OPTION_SYNC_INTERVAL ); ?>">
								<option value="hourly" <?php selected( $sync_interval, 'hourly' ); ?>><?php esc_html_e( 'Every hour', 'mmi-amazon-price-sync' ); ?></option>
								<option value="mmi_aps_six_hours" <?php selected( $sync_interval, 'mmi_aps_six_hours' ); ?>><?php esc_html_e( 'Every 6 hours', 'mmi-amazon-price-sync' ); ?></option>
								<option value="mmi_aps_twelve_hours" <?php selected( $sync_interval, 'mmi_aps_twelve_hours' ); ?>><?php esc_html_e( 'Every 12 hours', 'mmi-amazon-price-sync' ); ?></option>
								<option value="daily" <?php selected( $sync_interval, 'daily' ); ?>><?php esc_html_e( 'Daily', 'mmi-amazon-price-sync' ); ?></option>
							</select>
							<p class="description">
								<?php
								printf(
									/* translators: %s: next scheduled run datetime */
									esc_html__( 'Next scheduled run: %s', 'mmi-amazon-price-sync' ),
									esc_html( $next_cron )
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Delay Between API Calls', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="number" class="small-text" min="1" max="10" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_SYNC_DELAY ); ?>" value="<?php echo esc_attr( (string) $sync_delay ); ?>" />
							<?php esc_html_e( 'seconds', 'mmi-amazon-price-sync' ); ?>
							<p class="description"><?php esc_html_e( 'Helps avoid RapidAPI rate limits. 250 products ≈ 8 minutes at 2 seconds.', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cron Batch Size', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="number" class="small-text" min="5" max="50" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_CRON_BATCH_SIZE ); ?>" value="<?php echo esc_attr( (string) $cron_batch ); ?>" />
							<p class="description"><?php esc_html_e( 'Products synced per cron run (server-safe). Remaining products continue automatically every 3 minutes until done.', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Manual Sync Batch Size', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="number" class="small-text" min="1" max="50" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_BATCH_SIZE ); ?>" value="<?php echo esc_attr( (string) $batch_size ); ?>" />
							<p class="description"><?php esc_html_e( 'Products per batch when clicking Sync All Now.', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Affiliate Button (Go to Store)', 'mmi-amazon-price-sync' ); ?></h2>
				<p><?php esc_html_e( 'Send customers to Amazon with your affiliate tag to earn commission. When disabled, no extra code runs on the frontend.', 'mmi-amazon-price-sync' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Affiliate Button', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_ENABLED ); ?>" value="no" />
							<label>
								<input type="checkbox" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_ENABLED ); ?>" value="yes" <?php checked( $affiliate_on ); ?> />
								<?php esc_html_e( 'Show Go to Store button on product pages', 'mmi-amazon-price-sync' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Amazon Affiliate Tag', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_TAG ); ?>" value="<?php echo esc_attr( $affiliate_tag ); ?>" placeholder="mymobileind04-21" />
							<p class="description"><?php esc_html_e( 'Your Amazon Associates tracking ID (Store ID).', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Text', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_BTN_TEXT ); ?>" value="<?php echo esc_attr( $affiliate_text ); ?>" placeholder="<?php esc_attr_e( 'Go to Store', 'mmi-amazon-price-sync' ); ?>" />
							<p class="description"><?php esc_html_e( 'Examples: Go to Store, Buy on Amazon, Buy Now', 'mmi-amazon-price-sync' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show on Shop/Category', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_SHOP ); ?>" value="no" />
							<label>
								<input type="checkbox" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_SHOP ); ?>" value="yes" <?php checked( $affiliate_shop ); ?> />
								<?php esc_html_e( 'Also show button on shop and category listing pages', 'mmi-amazon-price-sync' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Affiliate Disclosure', 'mmi-amazon-price-sync' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_DISCLOSURE ); ?>" value="no" />
							<label>
								<input type="checkbox" name="<?php echo esc_attr( MMI_APS_Settings::OPTION_AFFILIATE_DISCLOSURE ); ?>" value="yes" <?php checked( $affiliate_disc ); ?> />
								<?php esc_html_e( 'Show "As an Amazon Associate we earn from qualifying purchases" below button', 'mmi-amazon-price-sync' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php if ( $affiliate_on && $affiliate_tag ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: sample affiliate URL */
							esc_html__( 'Sample link: %s', 'mmi-amazon-price-sync' ),
							esc_html( 'https://www.amazon.in/dp/B0H8STM6G2?tag=' . $affiliate_tag )
						);
						?>
					</p>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>

			<p><strong><?php esc_html_e( 'Request URL preview:', 'mmi-amazon-price-sync' ); ?></strong><br><code><?php echo esc_html( $sample_url ); ?></code></p>
			<p>
				<button type="button" class="button" id="mmi-aps-test-api"><?php esc_html_e( 'Test API (B0H8STM6G2)', 'mmi-amazon-price-sync' ); ?></button>
				<span class="spinner" id="mmi-aps-test-spinner" style="float:none;"></span>
			</p>
			<div id="mmi-aps-test-result"></div>

			<hr />
			<h2><?php esc_html_e( 'Sync All Products', 'mmi-amazon-price-sync' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %d: number of products with ASIN */
					esc_html__( '%d products have an Amazon ASIN and will be synced.', 'mmi-amazon-price-sync' ),
					(int) $asin_count
				);
				?>
			</p>
			<p>
				<button type="button" class="button button-primary" id="mmi-aps-sync-all"><?php esc_html_e( 'Sync All Now', 'mmi-amazon-price-sync' ); ?></button>
				<span class="spinner" id="mmi-aps-sync-spinner" style="float:none;"></span>
			</p>
			<div id="mmi-aps-sync-progress" style="max-width:480px;display:none;margin:12px 0;">
				<div style="background:#f0f0f1;height:24px;border-radius:4px;overflow:hidden;">
					<div id="mmi-aps-sync-bar" style="background:#2271b1;height:100%;width:0%;transition:width .3s;"></div>
				</div>
				<p id="mmi-aps-sync-status" style="margin:8px 0 0;"></p>
			</div>
			<div id="mmi-aps-sync-result"></div>

			<?php if ( $last_run && $last_summary ) : ?>
				<h3><?php esc_html_e( 'Last Sync', 'mmi-amazon-price-sync' ); ?></h3>
				<ul>
					<li><?php echo esc_html( MMI_APS_Product_Meta::format_datetime( (int) $last_run['time'] ) ); ?> (<?php echo esc_html( $last_run['source'] ); ?>)</li>
					<li>
						<?php
						printf(
							/* translators: 1: success count, 2: failed count, 3: total count */
							esc_html__( 'Updated: %1$d | Failed: %2$d | Total: %3$d', 'mmi-amazon-price-sync' ),
							(int) $last_summary['success'],
							(int) $last_summary['failed'],
							(int) $last_summary['total']
						);
						?>
					</li>
				</ul>
			<?php endif; ?>

			<script>
			jQuery(function($) {
				$('#mmi-aps-test-api').on('click', function() {
					var $btn = $(this);
					var $spinner = $('#mmi-aps-test-spinner');
					var $result = $('#mmi-aps-test-result');
					$btn.prop('disabled', true);
					$spinner.addClass('is-active');
					$result.html('');
					$.post(ajaxurl, {
						action: 'mmi_aps_test_api',
						nonce: '<?php echo esc_js( wp_create_nonce( 'mmi_aps_test_api' ) ); ?>'
					}).done(function(response) {
						if (response.success) {
							$result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
						} else {
							$result.html('<div class="notice notice-error"><p>' + (response.data && response.data.message ? response.data.message : 'Test failed') + '</p></div>');
						}
					}).fail(function() {
						$result.html('<div class="notice notice-error"><p>Test failed</p></div>');
					}).always(function() {
						$btn.prop('disabled', false);
						$spinner.removeClass('is-active');
					});
				});

				var syncing = false;
				$('#mmi-aps-sync-all').on('click', function() {
					if (syncing) return;
					syncing = true;
					var $btn = $(this);
					var $spinner = $('#mmi-aps-sync-spinner');
					var $progress = $('#mmi-aps-sync-progress');
					var $bar = $('#mmi-aps-sync-bar');
					var $status = $('#mmi-aps-sync-status');
					var $result = $('#mmi-aps-sync-result');
					var offset = 0;
					var totalSuccess = 0;
					var totalFailed = 0;
					var totalProducts = 0;

					$btn.prop('disabled', true);
					$spinner.addClass('is-active');
					$progress.show();
					$result.html('');
					$bar.css('width', '0%');
					$status.text('<?php echo esc_js( __( 'Starting sync…', 'mmi-amazon-price-sync' ) ); ?>');

					function runBatch() {
						$.post(ajaxurl, {
							action: 'mmi_aps_sync_batch',
							nonce: '<?php echo esc_js( wp_create_nonce( 'mmi_aps_sync_batch' ) ); ?>',
							offset: offset
						}).done(function(response) {
							if (!response.success) {
								$result.html('<div class="notice notice-error"><p>' + (response.data && response.data.message ? response.data.message : 'Sync failed') + '</p></div>');
								finish();
								return;
							}
							var data = response.data;
							totalSuccess += data.success;
							totalFailed += data.failed;
							totalProducts = data.total;
							offset = data.next_offset;
							var percent = totalProducts ? Math.round((offset / totalProducts) * 100) : 100;
							$bar.css('width', percent + '%');
							$status.text(offset + ' / ' + totalProducts + ' <?php echo esc_js( __( 'products processed', 'mmi-amazon-price-sync' ) ); ?>');
							if (data.done) {
								$result.html('<div class="notice notice-success"><p><?php echo esc_js( __( 'Sync complete!', 'mmi-amazon-price-sync' ) ); ?> ' + totalSuccess + ' <?php echo esc_js( __( 'updated', 'mmi-amazon-price-sync' ) ); ?>, ' + totalFailed + ' <?php echo esc_js( __( 'failed', 'mmi-amazon-price-sync' ) ); ?>.</p></div>');
								finish();
							} else {
								runBatch();
							}
						}).fail(function() {
							$result.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'Sync failed. Please try again.', 'mmi-amazon-price-sync' ) ); ?></p></div>');
							finish();
						});
					}

					function finish() {
						syncing = false;
						$btn.prop('disabled', false);
						$spinner.removeClass('is-active');
					}

					runBatch();
				});
			});
			</script>

			<hr />
			<h2><?php esc_html_e( 'How to use', 'mmi-amazon-price-sync' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Save your RapidAPI key above.', 'mmi-amazon-price-sync' ); ?></li>
				<li><?php esc_html_e( 'Edit any WooCommerce product.', 'mmi-amazon-price-sync' ); ?></li>
				<li><?php esc_html_e( 'Find the Amazon Price Sync box (right sidebar) or Product Data → Amazon Price tab.', 'mmi-amazon-price-sync' ); ?></li>
				<li><?php esc_html_e( 'Enter ASIN and click Fetch Price from Amazon.', 'mmi-amazon-price-sync' ); ?></li>
			</ol>
		</div>
		<?php
	}

	public static function ajax_test_api(): void {
		check_ajax_referer( 'mmi_aps_test_api', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mmi-amazon-price-sync' ) ) );
		}

		$result = MMI_APS_API_Client::fetch_product( 'B0H8STM6G2' );

		if ( ! $result['success'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		$data = $result['data'];
		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: 1: formatted price, 2: product title */
					__( 'Success! Price: %1$s — %2$s', 'mmi-amazon-price-sync' ),
					MMI_APS_Product_Meta::format_inr( (float) $data['price'] ),
					$data['title']
				),
			)
		);
	}

	public static function ajax_sync_batch(): void {
		check_ajax_referer( 'mmi_aps_sync_batch', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mmi-amazon-price-sync' ) ) );
		}

		if ( '' === MMI_APS_Settings::get_api_key() ) {
			wp_send_json_error( array( 'message' => __( 'RapidAPI key is not configured.', 'mmi-amazon-price-sync' ) ) );
		}

		$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;

		if ( 0 === $offset && ! MMI_APS_Sync::acquire_lock() ) {
			wp_send_json_error( array( 'message' => __( 'Another sync is already running. Please wait.', 'mmi-amazon-price-sync' ) ) );
		}

		$limit  = MMI_APS_Settings::get_batch_size();
		$result = MMI_APS_Sync::sync_batch( $offset, $limit );

		if ( $result['done'] ) {
			MMI_APS_Sync::release_lock();
		}

		wp_send_json_success( $result );
	}

	public static function register_product_meta_box(): void {
		add_meta_box(
			'mmi-aps-product-box',
			__( 'Amazon Price Sync', 'mmi-amazon-price-sync' ),
			array( __CLASS__, 'render_product_meta_box' ),
			'product',
			'side',
			'high'
		);
	}

	public static function render_product_meta_box( WP_Post $post ): void {
		MMI_APS_Product_Meta::render_fields( (int) $post->ID, 'meta-box' );
	}

	public static function maybe_show_setup_notice(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( '' !== MMI_APS_Settings::get_api_key() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'woocommerce_page_mmi-amazon-price-sync' === $screen->id ) {
			return;
		}

		$url = admin_url( 'admin.php?page=mmi-amazon-price-sync' );
		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: %s: settings page URL */
			wp_kses_post( __( '<strong>MMI Amazon Price Sync:</strong> Add your RapidAPI key in <a href="%s">WooCommerce → Amazon Price Sync</a>.', 'mmi-amazon-price-sync' ) ),
			esc_url( $url )
		);
		echo '</p></div>';
	}
}
