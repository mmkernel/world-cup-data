<?php
/**
 * Plugin Name: World Cup Data WordPress Plugin
 * Plugin URI: https://MasteryMesh.com
 * Description: Displays FIFA World Cup fixtures, results, and standings from football-data.org API v4.
 * Version: 1.0.0
 * Author: Momcilo Milic - MasteryMesh.com
 * Author URI: https://MasteryMesh.com
 * Text Domain: world-cup-data
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCD_VERSION', '1.0.0' );
define( 'WCD_PLUGIN_FILE', __FILE__ );
define( 'WCD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCD_API_BASE_URL', 'https://api.football-data.org/v4' );
define( 'WCD_COMPETITION_CODE', 'WC' );

require_once WCD_PLUGIN_DIR . 'includes/class-wcd-api.php';
require_once WCD_PLUGIN_DIR . 'includes/class-wcd-admin.php';
require_once WCD_PLUGIN_DIR . 'includes/class-wcd-matches.php';
require_once WCD_PLUGIN_DIR . 'includes/class-wcd-standings.php';
require_once WCD_PLUGIN_DIR . 'includes/class-wcd-filters.php';
require_once WCD_PLUGIN_DIR . 'includes/class-wcd-tabs.php';
require_once WCD_PLUGIN_DIR . 'includes/class-wcd-shortcodes.php';

/**
 * Registers the plugin cron interval from the cache duration setting.
 *
 * @param array $schedules Registered cron schedules.
 * @return array
 */
function wcd_register_cron_schedule( $schedules ) {
	$api      = new WCD_API();
	$interval = $api->get_cache_duration_seconds();

	$schedules['wcd_cache_duration'] = array(
		'interval' => $interval,
		'display'  => __( 'World Cup Data cache duration', 'world-cup-data' ),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'wcd_register_cron_schedule' );

/**
 * Bootstraps the plugin.
 */
function wcd_init_plugin() {
	$api = new WCD_API();

	wcd_schedule_refresh_event();

	if ( is_admin() ) {
		new WCD_Admin( $api );
	}

	new WCD_Shortcodes( $api );
}
add_action( 'plugins_loaded', 'wcd_init_plugin' );

/**
 * Refreshes World Cup data in the background.
 */
function wcd_refresh_data_event() {
	$api = new WCD_API();
	$api->refresh_data();
}
add_action( WCD_API::CRON_HOOK, 'wcd_refresh_data_event' );

/**
 * Schedules the background refresh event.
 */
function wcd_schedule_refresh_event() {
	if ( ! wp_next_scheduled( WCD_API::CRON_HOOK ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'wcd_cache_duration', WCD_API::CRON_HOOK );
	}
}

/**
 * Adds default options on activation without overwriting existing settings.
 */
function wcd_activate_plugin() {
	if ( false === get_option( 'wcd_cache_duration' ) ) {
		add_option( 'wcd_cache_duration', 30 );
	}

	if ( false === get_option( 'wcd_timezone' ) ) {
		add_option( 'wcd_timezone', wp_timezone_string() );
	}

	if ( false === get_option( 'wcd_language' ) ) {
		add_option( 'wcd_language', 'en' );
	}

	set_transient( 'wcd_activation_notice', 1, MINUTE_IN_SECONDS );
	wcd_schedule_refresh_event();
}
register_activation_hook( __FILE__, 'wcd_activate_plugin' );

/**
 * Clears the background refresh event on deactivation.
 */
function wcd_deactivate_plugin() {
	wp_clear_scheduled_hook( WCD_API::CRON_HOOK );
}
register_deactivation_hook( __FILE__, 'wcd_deactivate_plugin' );
