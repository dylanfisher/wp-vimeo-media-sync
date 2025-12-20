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
	 * Vimeo client instance.
	 *
	 * @since    1.0.0
	 * @var      Vimeo_Media_Sync_Vimeo_Client|null
	 */
	private $vimeo_client;

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
	 * Initialize Vimeo meta keys for new video attachments.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 */
	public function initialize_video_attachment_meta( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $this->is_video_attachment( $post ) ) {
			return;
		}

		foreach ( $this->get_vimeo_meta_keys() as $meta_key ) {
			if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
				update_post_meta( $post_id, $meta_key, '' );
			}
		}
	}

	/**
	 * Upload newly added video attachments to Vimeo.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 */
	public function maybe_upload_video_to_vimeo( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $this->is_video_attachment( $post ) ) {
			return;
		}

		$this->initialize_video_attachment_meta( $post_id );

		$existing_uri = get_post_meta( $post_id, '_vimeo_media_sync_uri', true );
		$existing_id  = get_post_meta( $post_id, '_vimeo_media_sync_video_id', true );
		if ( '' !== $existing_uri || '' !== $existing_id ) {
			return;
		}

		$token = $this->get_access_token();
		if ( '' === $token ) {
			$this->update_vimeo_status( $post_id, 'missing_token', 'No Vimeo access token configured.' );
			return;
		}

		$video_url = wp_get_attachment_url( $post_id );
		if ( ! $video_url ) {
			$this->update_vimeo_status( $post_id, 'error', 'Attachment URL not available.' );
			return;
		}

		$client = $this->get_vimeo_client();
		$project = $client->get_or_create_project( 'Wordpress' );
		if ( ! $project || empty( $project['uri'] ) ) {
			$this->update_vimeo_status( $post_id, 'error', 'Unable to access Vimeo folder.' );
			return;
		}

		update_post_meta( $post_id, '_vimeo_media_sync_upload_source', esc_url_raw( $video_url ) );
		$this->update_vimeo_status( $post_id, 'queued', '' );

		$title = get_the_title( $post_id );
		$description = $post ? $post->post_content : '';
		$response = $client->create_video_from_url( $video_url, $title, $description );

		if ( ! $response['success'] ) {
			$this->update_vimeo_status( $post_id, 'error', $response['error'] );
			return;
		}

		$body = $response['body'];
		$video_uri = isset( $body['uri'] ) ? $body['uri'] : '';
		$video_id = $this->extract_vimeo_id_from_uri( $video_uri );
		$status = isset( $body['status'] ) ? $body['status'] : '';

		if ( '' === $video_uri ) {
			$this->update_vimeo_status( $post_id, 'error', 'Vimeo response missing video URI.' );
			return;
		}

		update_post_meta( $post_id, '_vimeo_media_sync_uri', $video_uri );
		update_post_meta( $post_id, '_vimeo_media_sync_video_id', $video_id );
		update_post_meta( $post_id, '_vimeo_media_sync_link', isset( $body['link'] ) ? esc_url_raw( $body['link'] ) : '' );
		update_post_meta( $post_id, '_vimeo_media_sync_response', $body );

		$add_to_project = $client->add_video_to_project( $project['uri'], $video_uri );
		if ( ! $add_to_project['success'] ) {
			$this->update_vimeo_status( $post_id, $this->map_vimeo_status( $status ), $add_to_project['error'] );
			return;
		}

		$this->update_vimeo_status( $post_id, $this->map_vimeo_status( $status ), '' );
		$this->schedule_status_check( $post_id, 2 * MINUTE_IN_SECONDS );
	}

	/**
	 * Check Vimeo processing status for in-flight uploads.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 */
	public function check_vimeo_processing_status( $post_id = 0 ) {
		$token = $this->get_access_token();
		if ( '' === $token ) {
			return;
		}

		if ( $post_id ) {
			$attachments = array( (int) $post_id );
		} else {
			$attachments = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'video',
					'posts_per_page' => 50,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'     => '_vimeo_media_sync_status',
							'value'   => array( 'queued', 'uploading', 'processing' ),
							'compare' => 'IN',
						),
					),
				)
			);
		}

		if ( empty( $attachments ) ) {
			return;
		}

		$client = $this->get_vimeo_client();

		foreach ( $attachments as $attachment_id ) {
			$attachment = get_post( $attachment_id );
			if ( ! $this->is_video_attachment( $attachment ) ) {
				continue;
			}

			$video_uri = get_post_meta( $attachment_id, '_vimeo_media_sync_uri', true );
			if ( '' === $video_uri ) {
				$video_id = get_post_meta( $attachment_id, '_vimeo_media_sync_video_id', true );
				$video_uri = $video_id ? '/videos/' . $video_id : '';
			}

			if ( '' === $video_uri ) {
				continue;
			}

			$response = $client->get_video( $video_uri );
			if ( ! $response['success'] ) {
				$this->update_vimeo_status( $attachment_id, 'error', $response['error'] );
				continue;
			}

			$body = $response['body'];
			update_post_meta( $attachment_id, '_vimeo_media_sync_response', $body );
			update_post_meta( $attachment_id, '_vimeo_media_sync_link', isset( $body['link'] ) ? esc_url_raw( $body['link'] ) : '' );
			update_post_meta( $attachment_id, '_vimeo_media_sync_duration', isset( $body['duration'] ) ? (int) $body['duration'] : '' );
			update_post_meta( $attachment_id, '_vimeo_media_sync_privacy', isset( $body['privacy']['view'] ) ? sanitize_text_field( $body['privacy']['view'] ) : '' );
			update_post_meta( $attachment_id, '_vimeo_media_sync_files', isset( $body['files'] ) ? $body['files'] : array() );

			$status = isset( $body['status'] ) ? $body['status'] : '';
			$transcode_status = isset( $body['transcode']['status'] ) ? $body['transcode']['status'] : '';
			$mapped = $this->map_vimeo_status( $status );
			$this->update_vimeo_status( $attachment_id, $mapped, '' );
			if ( 'ready' === $mapped ) {
				update_post_meta( $attachment_id, '_vimeo_media_sync_synced_at', current_time( 'mysql' ) );
			}

			$delay = $this->calculate_poll_delay( $attachment, $mapped, $transcode_status );
			if ( $delay > 0 ) {
				$this->schedule_status_check( $attachment_id, $delay );
			}
		}
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

	/**
	 * Schedule a single status check for an attachment.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 * @param    int $delay_seconds Delay in seconds.
	 */
	private function schedule_status_check( $post_id, $delay_seconds ) {
		if ( $delay_seconds <= 0 ) {
			return;
		}

		$timestamp = time() + (int) $delay_seconds;
		$hook_args = array( (int) $post_id );

		if ( ! wp_next_scheduled( 'vimeo_media_sync_check_status', $hook_args ) ) {
			wp_schedule_single_event( $timestamp, 'vimeo_media_sync_check_status', $hook_args );
		}
	}

	/**
	 * Determine next poll delay using an aggressive backoff strategy.
	 *
	 * @since    1.0.0
	 * @param    WP_Post $attachment Attachment post.
	 * @param    string  $status Local status label.
	 * @param    string  $transcode_status Vimeo transcode status.
	 * @return   int Delay in seconds.
	 */
	private function calculate_poll_delay( $attachment, $status, $transcode_status ) {
		if ( 'in_progress' === $transcode_status || in_array( $status, array( 'queued', 'uploading', 'processing' ), true ) ) {
			return 2 * MINUTE_IN_SECONDS;
		}

		$created_at = strtotime( $attachment->post_date_gmt ? $attachment->post_date_gmt : $attachment->post_date );
		if ( ! $created_at ) {
			return 0;
		}

		$hours = ( time() - $created_at ) / HOUR_IN_SECONDS;
		if ( $hours < 0.5 ) {
			return 5 * MINUTE_IN_SECONDS;
		}
		if ( $hours < 1 ) {
			return 10 * MINUTE_IN_SECONDS;
		}
		if ( $hours < 12 ) {
			return 60 * MINUTE_IN_SECONDS;
		}
		if ( $hours < 24 ) {
			return 120 * MINUTE_IN_SECONDS;
		}

		return 0;
	}

	/**
	 * List Vimeo-related attachment meta keys.
	 *
	 * @since    1.0.0
	 * @return   string[]
	 */
	private function get_vimeo_meta_keys() {
		return array(
			'_vimeo_media_sync_video_id',
			'_vimeo_media_sync_uri',
			'_vimeo_media_sync_link',
			'_vimeo_media_sync_status',
			'_vimeo_media_sync_synced_at',
			'_vimeo_media_sync_error',
			'_vimeo_media_sync_upload_source',
			'_vimeo_media_sync_duration',
			'_vimeo_media_sync_privacy',
			'_vimeo_media_sync_files',
			'_vimeo_media_sync_response',
		);
	}

	/**
	 * Get a Vimeo client instance.
	 *
	 * @since    1.0.0
	 * @return   Vimeo_Media_Sync_Vimeo_Client
	 */
	private function get_vimeo_client() {
		if ( null === $this->vimeo_client ) {
			$this->vimeo_client = new Vimeo_Media_Sync_Vimeo_Client( $this->get_access_token() );
		}

		return $this->vimeo_client;
	}

	/**
	 * Update status and error meta fields.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id Attachment ID.
	 * @param    string $status Status label.
	 * @param    string $error Error message.
	 */
	private function update_vimeo_status( $post_id, $status, $error ) {
		if ( '' !== $status ) {
			update_post_meta( $post_id, '_vimeo_media_sync_status', $status );
		}
		update_post_meta( $post_id, '_vimeo_media_sync_error', sanitize_text_field( $error ) );
	}

	/**
	 * Map Vimeo API status to local status label.
	 *
	 * @since    1.0.0
	 * @param    string $status Vimeo status.
	 * @return   string
	 */
	private function map_vimeo_status( $status ) {
		switch ( $status ) {
			case 'available':
				return 'ready';
			case 'uploading':
				return 'uploading';
			case 'transcoding':
				return 'processing';
			default:
				return $status ? $status : 'queued';
		}
	}

	/**
	 * Extract the numeric Vimeo ID from a URI.
	 *
	 * @since    1.0.0
	 * @param    string $uri Vimeo URI.
	 * @return   string
	 */
	private function extract_vimeo_id_from_uri( $uri ) {
		if ( preg_match( '/\/videos\/(\d+)/', $uri, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Check whether the attachment is a video.
	 *
	 * @since    1.0.0
	 * @param    WP_Post|null $post Attachment post.
	 * @return   bool
	 */
	private function is_video_attachment( $post ) {
		return $post instanceof WP_Post && wp_attachment_is( 'video', $post );
	}

}
