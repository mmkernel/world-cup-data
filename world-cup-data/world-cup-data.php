<?php
/**
 * Plugin Name: World Cup Data
 * Plugin URI: https://MasteryMesh.com
 * Description: Displays FIFA World Cup fixtures, results, and standings from football-data.org API v4.
 * Version: 1.0.0
 * Author: Momcilo Milic
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
require_once WCD_PLUGIN_DIR . 'includes/class-wcd-shortcodes.php';

/**
 * Bootstraps the plugin.
 */
function wcd_init_plugin() {
	$api = new WCD_API();

	if ( is_admin() ) {
		new WCD_Admin( $api );
	}

	new WCD_Shortcodes( $api );
}
add_action( 'plugins_loaded', 'wcd_init_plugin' );

/**
 * Adds default options on activation without overwriting existing settings.
 */
function wcd_activate_plugin() {
	if ( false === get_option( 'wcd_cache_duration' ) ) {
		add_option( 'wcd_cache_duration', 30 );
	}

	set_transient( 'wcd_activation_notice', 1, MINUTE_IN_SECONDS );
}
register_activation_hook( __FILE__, 'wcd_activate_plugin' );
