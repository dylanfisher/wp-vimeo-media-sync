<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.dylanfisher.com/
 * @since             1.0.0
 * @package           Vimeo_Media_Sync
 *
 * @wordpress-plugin
 * Plugin Name:       Vimeo Media Sync
 * Plugin URI:        https://github.com/dylanfisher/wp-vimeo-media-sync
 * Description:       Uploads and synchronizes WordPress videos with Vimeo, enabling automated publishing, background uploads, and Vimeo-hosted playback workflows.
 * Version:           1.2.0
 * Author:            Dylan Fisher
 * Author URI:        https://www.dylanfisher.com//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vimeo-media-sync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version (sourced from the header above).
 */
$vimeo_media_sync_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
define( 'VIMEO_MEDIA_SYNC_VERSION', $vimeo_media_sync_data['Version'] ? $vimeo_media_sync_data['Version'] : '1.0.0' );

/**
 * Load GitHub update checker.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php';

$vimeo_media_sync_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/dylanfisher/wp-vimeo-media-sync/',
	__FILE__,
	'wp-vimeo-media-sync'
);
$vimeo_media_sync_update_checker->setBranch( 'main' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vimeo-media-sync-activator.php
 */
function activate_vimeo_media_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vimeo-media-sync-activator.php';
	Vimeo_Media_Sync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vimeo-media-sync-deactivator.php
 */
function deactivate_vimeo_media_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vimeo-media-sync-deactivator.php';
	Vimeo_Media_Sync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vimeo_media_sync' );
register_deactivation_hook( __FILE__, 'deactivate_vimeo_media_sync' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-vimeo-media-sync.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vimeo_media_sync() {

	$plugin = new Vimeo_Media_Sync();
	$plugin->run();

}
run_vimeo_media_sync();
