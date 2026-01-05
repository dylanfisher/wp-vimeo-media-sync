<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.dylanfisher.com/
 * @since      1.0.0
 *
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/includes
 * @author     Dylan Fisher <hi@dylanfisher.com>
 */
class Vimeo_Media_Sync {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Vimeo_Media_Sync_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'VIMEO_MEDIA_SYNC_VERSION' ) ) {
			$this->version = VIMEO_MEDIA_SYNC_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'vimeo-media-sync';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Vimeo_Media_Sync_Loader. Orchestrates the hooks of the plugin.
	 * - Vimeo_Media_Sync_i18n. Defines internationalization functionality.
	 * - Vimeo_Media_Sync_Admin. Defines all hooks for the admin area.
	 * - Vimeo_Media_Sync_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vimeo-media-sync-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vimeo-media-sync-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-vimeo-media-sync-admin.php';

		/**
		 * Vimeo API client wrapper.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vimeo-media-sync-vimeo-client.php';

		/**
		 * Frontend helper utilities.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vimeo-media-sync-helpers.php';

		$this->loader = new Vimeo_Media_Sync_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Vimeo_Media_Sync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Vimeo_Media_Sync_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Vimeo_Media_Sync_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'maybe_render_missing_token_notice' );
		$this->loader->add_action( 'add_attachment', $plugin_admin, 'initialize_video_attachment_meta' );
		$this->loader->add_action( 'add_attachment', $plugin_admin, 'maybe_upload_video_to_vimeo', 20, 1 );
		$this->loader->add_action( 'delete_attachment', $plugin_admin, 'maybe_delete_vimeo_video', 10, 1 );
		$this->loader->add_action( 'vimeo_media_sync_check_status', $plugin_admin, 'check_vimeo_processing_status' );
		$this->loader->add_action( 'add_meta_boxes_attachment', $plugin_admin, 'register_attachment_metabox' );
		$this->loader->add_action( 'admin_post_vimeo_media_sync_refresh_status', $plugin_admin, 'handle_refresh_status' );
		$this->loader->add_action( 'admin_post_vimeo_media_sync_sync_missing', $plugin_admin, 'handle_sync_missing' );
		$this->loader->add_action( 'wp_ajax_vimeo_media_sync_sync_attachment', $plugin_admin, 'ajax_sync_attachment' );
		$this->loader->add_action( 'wp_ajax_vimeo_media_sync_sync_missing', $plugin_admin, 'ajax_sync_missing' );
		$this->loader->add_action( 'wp_ajax_vimeo_media_sync_render_details', $plugin_admin, 'ajax_render_details' );
		$this->loader->add_action( 'wp_ajax_vimeo_media_sync_clear_all_metadata', $plugin_admin, 'ajax_clear_all_metadata' );
		$this->loader->add_action( 'wp_ajax_vimeo_media_sync_delete_all_videos', $plugin_admin, 'ajax_delete_all_videos' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Vimeo_Media_Sync_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
