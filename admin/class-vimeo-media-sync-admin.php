<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://www.dylanfisher.com/
 * @since      1.0.0
 *
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/admin
 * @author     Dylan Fisher <hi@dylanfisher.com>
 */
class Vimeo_Media_Sync_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the plugin dashboard page.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		add_menu_page(
			__( 'Vimeo Media Sync', 'vimeo-media-sync' ),
			__( 'Vimeo Sync', 'vimeo-media-sync' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_dashboard' ),
			'dashicons-video-alt3',
			26
		);

	}

	/**
	 * Render an admin notice if no access token is configured.
	 *
	 * @since    1.0.0
	 */
	public function maybe_render_missing_token_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'toplevel_page_' . $this->plugin_name !== $screen->id ) {
			return;
		}

		if ( '' !== $this->get_access_token() ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<?php echo esc_html__( 'Vimeo Media Sync is not configured. Add a personal access token in wp-config.php or as an environment variable.', 'vimeo-media-sync' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Retrieve the access token from wp-config.php or the environment.
	 *
	 * @since    1.0.0
	 * @return   string Access token or empty string.
	 */
	public function get_access_token() {
		if ( defined( 'VIMEO_MEDIA_SYNC_ACCESS_TOKEN' ) && VIMEO_MEDIA_SYNC_ACCESS_TOKEN ) {
			return (string) VIMEO_MEDIA_SYNC_ACCESS_TOKEN;
		}

		$env_token = getenv( 'VIMEO_MEDIA_SYNC_ACCESS_TOKEN' );
		if ( false !== $env_token && '' !== $env_token ) {
			return (string) $env_token;
		}

		return '';
	}

	/**
	 * Render the plugin dashboard page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_dashboard() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/vimeo-media-sync-admin-display.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Vimeo_Media_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Vimeo_Media_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/vimeo-media-sync-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Vimeo_Media_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Vimeo_Media_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vimeo-media-sync-admin.js', array( 'jquery' ), $this->version, false );

	}

}
