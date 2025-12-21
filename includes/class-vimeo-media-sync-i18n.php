<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.dylanfisher.com/
 * @since      1.0.0
 *
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/includes
 * @author     Dylan Fisher <hi@dylanfisher.com>
 */
class Vimeo_Media_Sync_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'vimeo-media-sync',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
