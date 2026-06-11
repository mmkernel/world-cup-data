<?php
/**
 * Admin settings page.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders plugin settings.
 */
class WCD_Admin {

	/**
	 * API client.
	 *
	 * @var WCD_API
	 */
	private $api;

	/**
	 * Constructor.
	 *
	 * @param WCD_API $api API client.
	 */
	public function __construct( WCD_API $api ) {
		$this->api = $api;

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'render_activation_notice' ) );
		add_action( 'admin_post_wcd_clear_cache', array( $this, 'handle_clear_cache' ) );
		add_action( 'admin_post_wcd_refresh_data', array( $this, 'handle_refresh_data' ) );
		add_action( 'update_option_wcd_cache_duration', array( $this, 'handle_cache_duration_update' ), 10, 3 );
	}

	/**
	 * Adds a top-level admin menu item for the plugin.
	 */
	public function add_settings_page() {
		add_menu_page(
			__( 'World Cup Data', 'world-cup-data' ),
			__( 'World Cup Data', 'world-cup-data' ),
			'manage_options',
			'world-cup-data',
			array( $this, 'render_settings_page' ),
			'dashicons-calendar-alt',
			58
		);
	}

	/**
	 * Shows a one-time notice after plugin activation.
	 */
	public function render_activation_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'wcd_activation_notice' ) ) {
			return;
		}

		delete_transient( 'wcd_activation_notice' );

		$settings_url = admin_url( 'admin.php?page=world-cup-data' );
		$message      = sprintf(
			/* translators: %s: Settings page link. */
			__( 'Enter your football-data.org API v4 token on the %s page.', 'world-cup-data' ),
			'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'World Cup Data settings', 'world-cup-data' ) . '</a>'
		);
		?>
		<div class="notice notice-info is-dismissible">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Registers settings and fields.
	 */
	public function register_settings() {
		register_setting(
			'wcd_settings',
			'wcd_api_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_api_token' ),
				'default'           => '',
			)
		);

		register_setting(
			'wcd_settings',
			'wcd_cache_duration',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_cache_duration' ),
				'default'           => 30,
			)
		);

		register_setting(
			'wcd_settings',
			'wcd_timezone',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_timezone' ),
				'default'           => wp_timezone_string(),
			)
		);

		register_setting(
			'wcd_settings',
			'wcd_language',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_language' ),
				'default'           => 'en',
			)
		);

		add_settings_section(
			'wcd_api_section',
			__( 'API Settings', 'world-cup-data' ),
			array( $this, 'render_section_description' ),
			'world-cup-data'
		);

		add_settings_field(
			'wcd_api_token',
			__( 'API Token', 'world-cup-data' ),
			array( $this, 'render_api_token_field' ),
			'world-cup-data',
			'wcd_api_section'
		);

		add_settings_field(
			'wcd_timezone',
			__( 'Timezone', 'world-cup-data' ),
			array( $this, 'render_timezone_field' ),
			'world-cup-data',
			'wcd_api_section'
		);

		add_settings_field(
			'wcd_language',
			__( 'Language', 'world-cup-data' ),
			array( $this, 'render_language_field' ),
			'world-cup-data',
			'wcd_api_section'
		);

		add_settings_field(
			'wcd_cache_duration',
			__( 'Cache Duration', 'world-cup-data' ),
			array( $this, 'render_cache_duration_field' ),
			'world-cup-data',
			'wcd_api_section'
		);
	}

	/**
	 * Sanitizes the API token.
	 *
	 * @param string $value Raw token.
	 * @return string
	 */
	public function sanitize_api_token( $value ) {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * Sanitizes cache duration, defaulting to 30 minutes.
	 *
	 * @param mixed $value Raw duration.
	 * @return int
	 */
	public function sanitize_cache_duration( $value ) {
		$value = absint( $value );

		return $value > 0 ? $value : 30;
	}

	/**
	 * Reschedules background refreshes when cache duration changes.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $value     New option value.
	 * @param string $option   Option name.
	 */
	public function handle_cache_duration_update( $old_value, $value, $option ) {
		unset( $old_value, $value, $option );

		wp_clear_scheduled_hook( WCD_API::CRON_HOOK );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'wcd_cache_duration', WCD_API::CRON_HOOK );
	}

	/**
	 * Sanitizes selected timezone.
	 *
	 * @param string $value Raw timezone.
	 * @return string
	 */
	public function sanitize_timezone( $value ) {
		$value     = sanitize_text_field( wp_unslash( $value ) );
		$timezones = timezone_identifiers_list();

		return in_array( $value, $timezones, true ) ? $value : wp_timezone_string();
	}

	/**
	 * Sanitizes selected language.
	 *
	 * @param string $value Raw language code.
	 * @return string
	 */
	public function sanitize_language( $value ) {
		$value             = sanitize_key( wp_unslash( $value ) );
		$allowed_languages = array_keys( $this->get_language_options() );

		return in_array( $value, $allowed_languages, true ) ? $value : 'en';
	}

	/**
	 * Renders settings section text.
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Enter your football-data.org API v4 token. API responses are cached to support the free tier rate limits.', 'world-cup-data' ) . '</p>';
	}

	/**
	 * Renders API token field.
	 */
	public function render_api_token_field() {
		$value = (string) get_option( 'wcd_api_token', '' );

		printf(
			'<input type="password" class="regular-text" id="wcd_api_token" name="wcd_api_token" value="%s" autocomplete="off" />',
			esc_attr( $value )
		);
		echo '<p class="description">' . esc_html__( 'Stored securely in WordPress options and used only for server-side API requests.', 'world-cup-data' ) . '</p>';
	}

	/**
	 * Renders cache duration field.
	 */
	public function render_cache_duration_field() {
		$value = absint( get_option( 'wcd_cache_duration', 30 ) );

		printf(
			'<input type="number" min="1" step="1" id="wcd_cache_duration" name="wcd_cache_duration" value="%d" /> <span>%s</span>',
			$value,
			esc_html__( 'minutes', 'world-cup-data' )
		);
	}

	/**
	 * Renders timezone dropdown.
	 */
	public function render_timezone_field() {
		$current_timezone = (string) get_option( 'wcd_timezone', wp_timezone_string() );
		$timezones        = timezone_identifiers_list();
		?>
		<select id="wcd_timezone" name="wcd_timezone">
			<?php foreach ( $timezones as $timezone ) : ?>
				<option value="<?php echo esc_attr( $timezone ); ?>" <?php selected( $current_timezone, $timezone ); ?>>
					<?php echo esc_html( str_replace( '_', ' ', $timezone ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php echo esc_html__( 'Used to display match dates and times on the frontend.', 'world-cup-data' ); ?></p>
		<?php
	}

	/**
	 * Renders language dropdown.
	 */
	public function render_language_field() {
		$current_language = (string) get_option( 'wcd_language', 'en' );
		$languages        = $this->get_language_options();
		?>
		<select id="wcd_language" name="wcd_language">
			<?php foreach ( $languages as $code => $label ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current_language, $code ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php echo esc_html__( 'Saved for frontend display preferences.', 'world-cup-data' ); ?></p>
		<?php
	}

	/**
	 * Returns allowed language options.
	 *
	 * @return array
	 */
	private function get_language_options() {
		return array(
			'en' => __( 'English', 'world-cup-data' ),
			'de' => __( 'German', 'world-cup-data' ),
			'fr' => __( 'French', 'world-cup-data' ),
			'es' => __( 'Spanish', 'world-cup-data' ),
			'hr' => __( 'Croatian', 'world-cup-data' ),
			'bs' => __( 'Bosnian', 'world-cup-data' ),
			'pt' => __( 'Portuguese', 'world-cup-data' ),
			'ja' => __( 'Japanese', 'world-cup-data' ),
			'tr' => __( 'Turkish', 'world-cup-data' ),
		);
	}

	/**
	 * Renders the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$cache_cleared = isset( $_GET['wcd_cache_cleared'] ) ? absint( $_GET['wcd_cache_cleared'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$data_refreshed = isset( $_GET['wcd_data_refreshed'] ) ? absint( $_GET['wcd_data_refreshed'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$last_success   = $this->api->get_last_success_time();
		$last_error     = $this->api->get_last_error();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'World Cup Data', 'world-cup-data' ); ?></h1>

			<?php if ( 1 === $cache_cleared ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'World Cup Data cache cleared.', 'world-cup-data' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( 1 === $data_refreshed ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'World Cup Data refresh completed.', 'world-cup-data' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( 2 === $data_refreshed ) : ?>
				<div class="notice notice-warning is-dismissible">
					<p><?php echo esc_html__( 'World Cup Data refresh did not receive new API data. Existing fallback data was kept.', 'world-cup-data' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'wcd_settings' );
				do_settings_sections( 'world-cup-data' );
				submit_button();
				?>
			</form>

			<hr />

			<h2><?php echo esc_html__( 'Shortcodes', 'world-cup-data' ); ?></h2>
			<p><?php echo esc_html__( 'Use the unified shortcode in posts, pages, or widget areas.', 'world-cup-data' ); ?></p>
			<table class="widefat striped" style="max-width: 900px;">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Shortcode or URL', 'world-cup-data' ); ?></th>
						<th><?php echo esc_html__( 'Description', 'world-cup-data' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>[worldcup]</code></td>
						<td><?php echo esc_html__( 'Shows the tabbed World Cup interface with Upcoming, Live, Results, Tables, and team filtering.', 'world-cup-data' ); ?></td>
					</tr>
					<tr>
						<td><code>[worldcup default_tab="results"]</code></td>
						<td><?php echo esc_html__( 'Opens the interface on a specific tab. Supported values: upcoming, live, results, tables.', 'world-cup-data' ); ?></td>
					</tr>
					<tr>
						<td><code>[worldcup display_only="upcoming,live"]</code></td>
						<td><?php echo esc_html__( 'Renders only selected tabs. Supported values: upcoming, live, results, tables.', 'world-cup-data' ); ?></td>
					</tr>
					<tr>
						<td><code>[worldcup display_only="upcoming,live,results" limit="5"]</code></td>
						<td><?php echo esc_html__( 'Limits Upcoming, Live, and Results match lists. Tables ignore the limit.', 'world-cup-data' ); ?></td>
					</tr>
					<tr>
						<td><code>[worldcup_today]</code></td>
						<td><?php echo esc_html__( 'Shows only today\'s upcoming and live World Cup matches from cached data.', 'world-cup-data' ); ?></td>
					</tr>
					<tr>
						<td><code>[worldcup_today show_finished="yes" limit="5" title="Today&apos;s World Cup Matches"]</code></td>
						<td><?php echo esc_html__( 'Optional compact homepage view with finished matches, a limit, and a custom title.', 'world-cup-data' ); ?></td>
					</tr>
					<tr>
						<td><code>?team=Croatia</code></td>
						<td><?php echo esc_html__( 'Preselects a team in the frontend filter.', 'world-cup-data' ); ?></td>
					</tr>
					<tr>
						<td><code>?team=Brazil&amp;tab=results</code></td>
						<td><?php echo esc_html__( 'Preselects both a team and a tab without making additional API requests.', 'world-cup-data' ); ?></td>
					</tr>
				</tbody>
			</table>

			<hr />

			<h2><?php echo esc_html__( 'Cached API Data', 'world-cup-data' ); ?></h2>
			<p><?php echo esc_html__( 'Frontend shortcodes render only stored data. API requests run in WP-Cron or from the manual refresh button.', 'world-cup-data' ); ?></p>
			<table class="widefat striped" style="max-width: 900px;">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Last successful fetch', 'world-cup-data' ); ?></th>
						<td>
							<?php
							echo $last_success
								? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_success ) )
								: esc_html__( 'Never', 'world-cup-data' );
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Last API error', 'world-cup-data' ); ?></th>
						<td><?php echo '' !== $last_error ? esc_html( $last_error ) : esc_html__( 'None', 'world-cup-data' ); ?></td>
					</tr>
				</tbody>
			</table>
			<div style="margin-top: 1rem;">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-right: 0.5rem;">
					<input type="hidden" name="action" value="wcd_refresh_data" />
					<?php wp_nonce_field( 'wcd_refresh_data', 'wcd_refresh_data_nonce' ); ?>
					<?php submit_button( __( 'Refresh Data Now', 'world-cup-data' ), 'primary', 'submit', false ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
					<input type="hidden" name="action" value="wcd_clear_cache" />
					<?php wp_nonce_field( 'wcd_clear_cache', 'wcd_clear_cache_nonce' ); ?>
					<?php submit_button( __( 'Clear Cache', 'world-cup-data' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles the clear cache form submission.
	 */
	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage World Cup Data settings.', 'world-cup-data' ) );
		}

		check_admin_referer( 'wcd_clear_cache', 'wcd_clear_cache_nonce' );

		$this->api->clear_cache();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => 'world-cup-data',
					'wcd_cache_cleared' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handles the manual data refresh form submission.
	 */
	public function handle_refresh_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage World Cup Data settings.', 'world-cup-data' ) );
		}

		check_admin_referer( 'wcd_refresh_data', 'wcd_refresh_data_nonce' );

		$success = $this->api->refresh_data( true );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'               => 'world-cup-data',
					'wcd_data_refreshed' => $success ? 1 : 2,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
