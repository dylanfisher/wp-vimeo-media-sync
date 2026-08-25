<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.dylanfisher.com/
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
	 * Hard ceiling, in seconds, on how long one request may spend sending tus chunks.
	 *
	 * @since    1.5.0
	 * @var      float
	 */
	const UPLOAD_TIME_BUDGET_CEILING = 120.0;

	/**
	 * Delay, in seconds, before resuming an upload that still has bytes to send.
	 *
	 * @since    1.5.0
	 * @var      int
	 */
	const UPLOAD_POLL_DELAY = 30;

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
			81
		);

	}

	/**
	 * Register settings for Vimeo sync configuration.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting(
			$this->plugin_name,
			'vimeo_media_sync_privacy',
			array( $this, 'sanitize_privacy_setting' )
		);
		register_setting(
			$this->plugin_name,
			'vimeo_media_sync_delete_on_remove',
			array( $this, 'sanitize_delete_setting' )
		);
		register_setting(
			$this->plugin_name,
			'vimeo_media_sync_folder_name',
			array( $this, 'sanitize_folder_setting' )
		);

		add_settings_section(
			'vimeo_media_sync_config_section',
			__( 'Vimeo Settings', 'vimeo-media-sync' ),
			array( $this, 'render_settings_intro' ),
			$this->plugin_name
		);

		add_settings_field(
			'vimeo_media_sync_privacy',
			__( 'Default Privacy', 'vimeo-media-sync' ),
			array( $this, 'render_privacy_field' ),
			$this->plugin_name,
			'vimeo_media_sync_config_section'
		);

		add_settings_field(
			'vimeo_media_sync_delete_on_remove',
			__( 'Delete on Attachment Removal', 'vimeo-media-sync' ),
			array( $this, 'render_delete_field' ),
			$this->plugin_name,
			'vimeo_media_sync_config_section'
		);

		add_settings_field(
			'vimeo_media_sync_folder_name',
			__( 'Vimeo Folder Name', 'vimeo-media-sync' ),
			array( $this, 'render_folder_field' ),
			$this->plugin_name,
			'vimeo_media_sync_config_section'
		);
	}

	/**
	 * Sanitize the Vimeo privacy setting.
	 *
	 * @since    1.0.0
	 * @param    string $value Raw setting.
	 * @return   string
	 */
	public function sanitize_privacy_setting( $value ) {
		$allowed = array( 'default', 'unlisted', 'public', 'private' );
		$value = sanitize_text_field( $value );

		return in_array( $value, $allowed, true ) ? $value : 'default';
	}

	/**
	 * Sanitize the delete-on-remove setting.
	 *
	 * @since    1.0.0
	 * @param    string $value Raw setting.
	 * @return   string
	 */
	public function sanitize_delete_setting( $value ) {
		return $value ? '1' : '0';
	}

	/**
	 * Sanitize the folder name setting.
	 *
	 * @since    1.0.0
	 * @param    string $value Raw setting.
	 * @return   string
	 */
	public function sanitize_folder_setting( $value ) {
		$value = sanitize_text_field( $value );
		$value = $value ? $value : 'Wordpress';
		$current = get_option( 'vimeo_media_sync_folder_name', 'Wordpress' );
		if ( $current !== $value ) {
			delete_option( 'vimeo_media_sync_folder_uri' );
		}

		return $value;
	}

	/**
	 * Render settings intro text.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_intro() {
		echo '<p>' . esc_html__( 'Configure default Vimeo behavior for new uploads.', 'vimeo-media-sync' ) . '</p>';
	}

	/**
	 * Render the privacy field.
	 *
	 * @since    1.0.0
	 */
	public function render_privacy_field() {
		$value = get_option( 'vimeo_media_sync_privacy', 'unlisted' );
		$options = array(
			'default'  => __( 'Use Vimeo account default', 'vimeo-media-sync' ),
			'unlisted' => __( 'Unlisted', 'vimeo-media-sync' ),
			'public'   => __( 'Public', 'vimeo-media-sync' ),
			'private'  => __( 'Private', 'vimeo-media-sync' ),
		);
		?>
		<select name="vimeo_media_sync_privacy">
			<?php foreach ( $options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php echo esc_html__( 'If Vimeo rejects privacy changes, the upload will retry without a privacy override. A paid Vimeo plan is required for private and unlisted videos.', 'vimeo-media-sync' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the delete-on-remove toggle.
	 *
	 * @since    1.0.0
	 */
	public function render_delete_field() {
		$value = get_option( 'vimeo_media_sync_delete_on_remove', '0' );
		?>
		<label>
			<input type="checkbox" name="vimeo_media_sync_delete_on_remove" value="1" <?php checked( $value, '1' ); ?> />
			<?php echo esc_html__( 'Delete Vimeo videos when the WordPress attachment is deleted.', 'vimeo-media-sync' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the Vimeo folder name field.
	 *
	 * @since    1.0.0
	 */
	public function render_folder_field() {
		$value = get_option( 'vimeo_media_sync_folder_name', 'Wordpress' );
		?>
		<input type="text" class="regular-text" name="vimeo_media_sync_folder_name" value="<?php echo esc_attr( $value ); ?>" />
		<p class="description">
			<?php echo esc_html__( 'Vimeo project (folder) name to store uploaded videos.', 'vimeo-media-sync' ); ?>
		</p>
		<?php
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

		$this->log_debug( sprintf( 'Initializing Vimeo meta for attachment %d', $post_id ) );
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
	 * @param    int  $post_id Attachment ID.
	 * @param    bool $force Force resync even if existing data exists.
	 * @param    bool $retried_expired_upload Whether an expired upload retry has already run.
	 */
	public function maybe_upload_video_to_vimeo( $post_id, $force = false, $retried_expired_upload = false ) {
		$post = get_post( $post_id );
		if ( ! $this->is_video_attachment( $post ) ) {
			return;
		}

		$this->log_debug( sprintf( 'Starting Vimeo upload for attachment %d', $post_id ) );
		$this->initialize_video_attachment_meta( $post_id );

		$existing_uri = get_post_meta( $post_id, '_vimeo_media_sync_uri', true );
		$existing_id  = get_post_meta( $post_id, '_vimeo_media_sync_video_id', true );
		$current_status = get_post_meta( $post_id, '_vimeo_media_sync_status', true );
		$current_error = get_post_meta( $post_id, '_vimeo_media_sync_error', true );

		if ( $force && $this->is_expired_upload_token_error( $current_error ) ) {
			$this->log_debug( sprintf( 'Resetting stored expired Vimeo upload for attachment %d', $post_id ) );
			$this->reset_vimeo_upload_meta( $post_id );
			$existing_uri = '';
			$existing_id = '';
			$current_status = '';
		}

		if ( '' !== $existing_uri || '' !== $existing_id ) {
			$this->log_debug( sprintf( 'Skipping Vimeo upload for attachment %d (existing Vimeo metadata)', $post_id ) );
			return;
		}

		$token = $this->get_access_token();
		if ( '' === $token ) {
			$this->update_vimeo_status( $post_id, 'missing_token', 'No Vimeo access token configured.' );
			$this->log_debug( sprintf( 'Missing Vimeo token for attachment %d', $post_id ) );
			return;
		}

		$file_path = get_attached_file( $post_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			$this->update_vimeo_status( $post_id, 'error', 'Attachment file not available.' );
			$this->log_debug( sprintf( 'Attachment file missing for attachment %d', $post_id ) );
			return;
		}

		$size = filesize( $file_path );
		if ( ! $size ) {
			$this->update_vimeo_status( $post_id, 'error', 'Attachment file size not available.' );
			$this->log_debug( sprintf( 'Attachment file size missing for attachment %d', $post_id ) );
			return;
		}

		$client = $this->get_vimeo_client();
		$folder_name = get_option( 'vimeo_media_sync_folder_name', 'Wordpress' );
		$project = null;
		$project_uri = get_option( 'vimeo_media_sync_folder_uri', '' );
		if ( $project_uri ) {
			$project = $client->get_project( $project_uri );
			if ( $project && isset( $project['name'] ) ) {
				$normalized = strtolower( trim( $folder_name ) );
				if ( strtolower( trim( $project['name'] ) ) !== $normalized ) {
					$project = null;
				}
			}
			if ( ! $project || empty( $project['uri'] ) ) {
				delete_option( 'vimeo_media_sync_folder_uri' );
			}
		}
		if ( ! $project ) {
			$project = $client->get_or_create_project( $folder_name );
			if ( $project && ! empty( $project['uri'] ) ) {
				update_option( 'vimeo_media_sync_folder_uri', $project['uri'] );
			}
		}
		if ( ! $project || empty( $project['uri'] ) ) {
			$this->update_vimeo_status( $post_id, 'error', 'Unable to access Vimeo folder.' );
			$this->log_debug( sprintf( 'Unable to access Vimeo project for attachment %d', $post_id ) );
			return;
		}

		$upload_link = get_post_meta( $post_id, '_vimeo_media_sync_upload_link', true );
		$upload_offset = (int) get_post_meta( $post_id, '_vimeo_media_sync_upload_offset', true );
		$upload_size = (int) get_post_meta( $post_id, '_vimeo_media_sync_upload_size', true );

		if ( $upload_link && $upload_size > 0 && $upload_offset < $upload_size ) {
			$this->update_vimeo_status( $post_id, 'uploading', '' );
			$upload_result = $this->resume_tus_upload( $post_id, $upload_link, $upload_offset, $upload_size );
			if ( ! $upload_result['success'] ) {
				if ( ! $retried_expired_upload && $this->is_expired_upload_token_error( $upload_result['error'] ) ) {
					$this->log_debug( sprintf( 'Resetting expired Vimeo upload for attachment %d', $post_id ) );
					$this->reset_vimeo_upload_meta( $post_id );
					$this->maybe_upload_video_to_vimeo( $post_id, true, true );
					return;
				}

				$this->update_vimeo_status( $post_id, 'uploading', $upload_result['error'] );
				return;
			}

			if ( $upload_result['completed'] ) {
				$this->update_vimeo_status( $post_id, 'processing', '' );
			}

			$this->schedule_status_check( $post_id, $upload_result['completed'] ? 2 * MINUTE_IN_SECONDS : $this->get_upload_poll_delay() );
			return;
		}

		if ( $force || 'error' === $current_status ) {
			$this->reset_vimeo_upload_meta( $post_id );
		}

		update_post_meta( $post_id, '_vimeo_media_sync_upload_source', esc_url_raw( wp_get_attachment_url( $post_id ) ) );
		update_post_meta( $post_id, '_vimeo_media_sync_upload_size', (int) $size );
		$this->update_vimeo_status( $post_id, 'uploading', '' );

		$title = get_the_title( $post_id );
		$description = $post ? $post->post_content : '';
		$privacy = get_option( 'vimeo_media_sync_privacy', 'unlisted' );
		$privacy = $this->normalize_privacy_setting( $privacy );
		$response = $client->create_tus_video( $size, $title, $description, $privacy );

		if ( ! $response['success'] ) {
			if ( $privacy && $this->is_privacy_error( $response['body'] ) ) {
				$this->log_debug( sprintf( 'Retrying Vimeo upload without privacy for attachment %d', $post_id ) );
				$response = $client->create_tus_video( $size, $title, $description, '' );
			}
		}

		if ( ! $response['success'] ) {
			$this->update_vimeo_status( $post_id, 'error', $response['error'] );
			$this->log_debug( sprintf( 'Vimeo upload request failed for attachment %d: %s', $post_id, $response['error'] ) );
			return;
		}

		$body = $response['body'];
		$video_uri = isset( $body['uri'] ) ? $body['uri'] : '';
		$video_id = $this->extract_vimeo_id_from_uri( $video_uri );
		$upload_link = isset( $body['upload']['upload_link'] ) ? $body['upload']['upload_link'] : '';

		if ( '' === $video_uri || '' === $upload_link ) {
			$this->update_vimeo_status( $post_id, 'error', 'Vimeo response missing upload link.' );
			$this->log_debug( sprintf( 'Vimeo response missing upload link for attachment %d', $post_id ) );
			return;
		}

		update_post_meta( $post_id, '_vimeo_media_sync_uri', $video_uri );
		update_post_meta( $post_id, '_vimeo_media_sync_video_id', $video_id );
		update_post_meta( $post_id, '_vimeo_media_sync_link', isset( $body['link'] ) ? esc_url_raw( $body['link'] ) : '' );
		update_post_meta( $post_id, '_vimeo_media_sync_response', $body );
		update_post_meta( $post_id, '_vimeo_media_sync_upload_link', esc_url_raw( $upload_link ) );
		update_post_meta( $post_id, '_vimeo_media_sync_upload_offset', 0 );

		$add_to_project = $client->add_video_to_project( $project['uri'], $video_uri );
		if ( ! $add_to_project['success'] ) {
			$this->update_vimeo_status( $post_id, 'uploading', $add_to_project['error'] );
			$this->log_debug( sprintf( 'Failed adding video to project for attachment %d: %s', $post_id, $add_to_project['error'] ) );
			return;
		}

		$upload_result = $this->resume_tus_upload( $post_id, $upload_link, 0, $size );
		if ( ! $upload_result['success'] ) {
			if ( ! $retried_expired_upload && $this->is_expired_upload_token_error( $upload_result['error'] ) ) {
				$this->log_debug( sprintf( 'Resetting expired Vimeo upload for attachment %d', $post_id ) );
				$this->reset_vimeo_upload_meta( $post_id );
				$this->maybe_upload_video_to_vimeo( $post_id, true, true );
				return;
			}

			$this->update_vimeo_status( $post_id, 'uploading', $upload_result['error'] );
			return;
		}

		if ( $upload_result['completed'] ) {
			$this->update_vimeo_status( $post_id, 'processing', '' );
		}

		$this->log_debug( sprintf( 'Vimeo tus upload started for attachment %d', $post_id ) );
		$this->schedule_status_check( $post_id, $upload_result['completed'] ? 2 * MINUTE_IN_SECONDS : $this->get_upload_poll_delay() );
	}

	/**
	 * Delete Vimeo videos when attachments are removed.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 */
	public function maybe_delete_vimeo_video( $post_id ) {
		if ( '1' !== get_option( 'vimeo_media_sync_delete_on_remove', '0' ) ) {
			return;
		}

		$this->delete_vimeo_video_for_attachment( $post_id );
	}

	/**
	 * Delete a Vimeo video linked to an attachment.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 * @return   array { deleted: bool, skipped: bool, error: string }
	 */
	private function delete_vimeo_video_for_attachment( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $this->is_video_attachment( $post ) ) {
			return array(
				'deleted' => false,
				'skipped' => true,
				'error'   => '',
			);
		}

		$video_id = get_post_meta( $post_id, '_vimeo_media_sync_video_id', true );
		$video_uri = get_post_meta( $post_id, '_vimeo_media_sync_uri', true );
		if ( '' === $video_id && '' === $video_uri ) {
			return array(
				'deleted' => false,
				'skipped' => true,
				'error'   => '',
			);
		}

		$token = $this->get_access_token();
		if ( '' === $token ) {
			$this->log_debug( sprintf( 'Skipping Vimeo delete for attachment %d (missing token)', $post_id ) );
			return array(
				'deleted' => false,
				'skipped' => false,
				'error'   => 'Missing Vimeo access token.',
			);
		}

		$upload_source = get_post_meta( $post_id, '_vimeo_media_sync_upload_source', true );
		if ( '' === $upload_source ) {
			$this->log_debug( sprintf( 'Skipping Vimeo delete for attachment %d (missing upload source)', $post_id ) );
			return array(
				'deleted' => false,
				'skipped' => true,
				'error'   => '',
			);
		}

		$uri = $video_uri ? $video_uri : ( $video_id ? '/videos/' . $video_id : '' );
		if ( '' === $uri ) {
			return array(
				'deleted' => false,
				'skipped' => true,
				'error'   => '',
			);
		}

		$response = $this->get_vimeo_client()->delete_video( $uri );
		if ( ! $response['success'] ) {
			$this->log_debug( sprintf( 'Failed to delete Vimeo video for attachment %d: %s', $post_id, $response['error'] ) );
			return array(
				'deleted' => false,
				'skipped' => false,
				'error'   => $response['error'],
			);
		}

		$this->log_debug( sprintf( 'Deleted Vimeo video for attachment %d', $post_id ) );
		return array(
			'deleted' => true,
			'skipped' => false,
			'error'   => '',
		);
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
			$this->log_debug( 'Skipping Vimeo status check (missing token)' );
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
			$this->log_debug( 'No attachments pending Vimeo status checks' );
			return;
		}

		$client = $this->get_vimeo_client();

		foreach ( $attachments as $attachment_id ) {
			$attachment = get_post( $attachment_id );
			if ( ! $this->is_video_attachment( $attachment ) ) {
				continue;
			}

			$this->log_debug( sprintf( 'Checking Vimeo status for attachment %d', $attachment_id ) );
			$upload_link = get_post_meta( $attachment_id, '_vimeo_media_sync_upload_link', true );
			$upload_size = (int) get_post_meta( $attachment_id, '_vimeo_media_sync_upload_size', true );
			$upload_offset = (int) get_post_meta( $attachment_id, '_vimeo_media_sync_upload_offset', true );

			if ( $upload_link && $upload_size > 0 && $upload_offset < $upload_size ) {
				$upload_result = $this->resume_tus_upload( $attachment_id, $upload_link, $upload_offset, $upload_size );
				if ( ! $upload_result['success'] ) {
					if ( $this->is_expired_upload_token_error( $upload_result['error'] ) ) {
						$this->log_debug( sprintf( 'Resetting expired Vimeo upload for attachment %d', $attachment_id ) );
						$this->reset_vimeo_upload_meta( $attachment_id );
						$this->maybe_upload_video_to_vimeo( $attachment_id, true, true );
						continue;
					}

					$this->update_vimeo_status( $attachment_id, 'uploading', $upload_result['error'] );
					$this->log_debug( sprintf( 'Tus upload failed for attachment %d: %s', $attachment_id, $upload_result['error'] ) );
					continue;
				}

				if ( ! $upload_result['completed'] ) {
					$this->update_vimeo_status( $attachment_id, 'uploading', '' );
					$this->schedule_status_check( $attachment_id, $this->get_upload_poll_delay() );
					continue;
				}

				$this->update_vimeo_status( $attachment_id, 'processing', '' );
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
				$this->log_debug( sprintf( 'Vimeo status request failed for attachment %d: %s', $attachment_id, $response['error'] ) );
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
				$this->log_debug( sprintf( 'Scheduling next Vimeo poll for attachment %d in %d seconds', $attachment_id, $delay ) );
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
	 * Check whether WordPress cron is reachable over HTTP.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_wp_cron_health() {
		$cached = get_transient( 'vimeo_media_sync_wp_cron_health' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$cron_url = site_url( 'wp-cron.php?doing_wp_cron=' . rawurlencode( 'vimeo_media_sync_health_' . time() ) );
		$response = wp_remote_get(
			$cron_url,
			array(
				'timeout'     => 5,
				'redirection' => 2,
				'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
				'headers'     => array(
					'Cache-Control' => 'no-cache',
				),
			)
		);

		$health = array(
			'reachable' => false,
			'status'    => 0,
			'message'   => '',
			'disabled'  => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		);

		if ( is_wp_error( $response ) ) {
			$health['message'] = $response->get_error_message();
			set_transient( 'vimeo_media_sync_wp_cron_health', $health, 5 * MINUTE_IN_SECONDS );
			return $health;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$health['status'] = $status;
		$health['reachable'] = $status >= 200 && $status < 400;
		if ( ! $health['reachable'] ) {
			$health['message'] = sprintf( 'HTTP %d', $status );
		}

		set_transient( 'vimeo_media_sync_wp_cron_health', $health, 5 * MINUTE_IN_SECONDS );
		return $health;
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
	public function enqueue_scripts( $hook_suffix ) {

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

		$should_enqueue_media = in_array( $hook_suffix, array( 'upload.php', 'post.php', 'post-new.php' ), true );
		if ( $should_enqueue_media ) {
			wp_enqueue_media();
		}

		$script_deps = array( 'jquery' );
		if ( $should_enqueue_media ) {
			$script_deps[] = 'media-editor';
			$script_deps[] = 'media-views';
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vimeo-media-sync-admin.js', $script_deps, $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'VimeoMediaSync',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vimeo_media_sync_render_details' ),
				'syncNonce' => wp_create_nonce( 'vimeo_media_sync_sync_attachment' ),
				'syncMissingNonce' => wp_create_nonce( 'vimeo_media_sync_sync_missing' ),
				'clearMetaNonce' => wp_create_nonce( 'vimeo_media_sync_clear_all_metadata' ),
				'refreshMetaNonce' => wp_create_nonce( 'vimeo_media_sync_refresh_all_metadata' ),
				'deleteVideosNonce' => wp_create_nonce( 'vimeo_media_sync_delete_all_videos' ),
				'debug'   => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			)
		);

	}

	/**
	 * Register the Vimeo metabox on attachment edit screens.
	 *
	 * @since    1.0.0
	 */
	public function register_attachment_metabox() {
		add_meta_box(
			'vimeo-media-sync-details',
			__( 'Vimeo Media Sync', 'vimeo-media-sync' ),
			array( $this, 'render_attachment_metabox' ),
			'attachment',
			'side',
			'default'
		);
	}

	/**
	 * Render the Vimeo details metabox for video attachments.
	 *
	 * @since    1.0.0
	 * @param    WP_Post $post Attachment post.
	 */
	public function render_attachment_metabox( $post ) {
		if ( ! $this->is_video_attachment( $post ) ) {
			echo esc_html__( 'Vimeo details are available for video attachments only.', 'vimeo-media-sync' );
			return;
		}

		echo $this->render_vimeo_details_html( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Ajax handler to render the Vimeo details section.
	 *
	 * @since    1.0.0
	 */
	public function ajax_render_details() {
		check_ajax_referer( 'vimeo_media_sync_render_details', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$refresh = ! empty( $_POST['refresh'] );
		if ( $refresh ) {
			$this->check_vimeo_processing_status( $post_id );
		}

		wp_send_json_success(
			array(
				'html' => $this->render_vimeo_details_html( $post_id ),
			)
		);
	}

	/**
	 * Ajax handler to sync a single attachment.
	 *
	 * @since    1.0.0
	 */
	public function ajax_sync_attachment() {
		check_ajax_referer( 'vimeo_media_sync_sync_attachment', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$this->maybe_upload_video_to_vimeo( $post_id, true );

		wp_send_json_success(
			array(
				'info' => $this->get_vimeo_attachment_info( $post_id ),
			)
		);
	}

	/**
	 * Ajax handler to sync missing attachments.
	 *
	 * @since    1.0.0
	 */
	public function ajax_sync_missing() {
		check_ajax_referer( 'vimeo_media_sync_sync_missing', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$attachments = $this->get_missing_vimeo_attachments( 10 );
		$synced = 0;

		foreach ( $attachments as $attachment ) {
			$this->maybe_upload_video_to_vimeo( $attachment->ID, true );
			$synced++;
		}

		wp_send_json_success(
			array(
				'synced' => $synced,
			)
		);
	}

	/**
	 * Ajax handler to clear all Vimeo metadata from video attachments.
	 *
	 * @since    1.0.0
	 */
	public function ajax_clear_all_metadata() {
		check_ajax_referer( 'vimeo_media_sync_clear_all_metadata', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$cleared = 0;
		$page = 1;
		$per_page = 100;

		do {
			$attachments = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'video',
					'post_status'    => 'inherit',
					'posts_per_page' => $per_page,
					'paged'          => $page,
					'fields'         => 'ids',
				)
			);

			foreach ( $attachments as $attachment_id ) {
				$this->clear_vimeo_attachment_meta( $attachment_id );
				$cleared++;
			}

			$page++;
		} while ( ! empty( $attachments ) );

		wp_send_json_success(
			array(
				'cleared' => $cleared,
			)
		);
	}

	/**
	 * Ajax handler to refresh Vimeo metadata for all synced attachments.
	 *
	 * @since    1.0.0
	 */
	public function ajax_refresh_all_metadata() {
		check_ajax_referer( 'vimeo_media_sync_refresh_all_metadata', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$refreshed = 0;
		$page = 1;
		$per_page = 50;

		do {
			$attachments = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'video',
					'post_status'    => 'inherit',
					'posts_per_page' => $per_page,
					'paged'          => $page,
					'fields'         => 'ids',
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'     => '_vimeo_media_sync_uri',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => '_vimeo_media_sync_video_id',
							'compare' => 'EXISTS',
						),
					),
				)
			);

			foreach ( $attachments as $attachment_id ) {
				$this->check_vimeo_processing_status( $attachment_id );
				$refreshed++;
			}

			$page++;
		} while ( ! empty( $attachments ) );

		wp_send_json_success(
			array(
				'refreshed' => $refreshed,
			)
		);
	}

	/**
	 * Ajax handler to delete Vimeo videos for all synced attachments.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_all_videos() {
		check_ajax_referer( 'vimeo_media_sync_delete_all_videos', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		$deleted = 0;
		$failed = 0;
		$page = 1;
		$per_page = 50;

		do {
			$attachments = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'video',
					'post_status'    => 'inherit',
					'posts_per_page' => $per_page,
					'paged'          => $page,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'     => '_vimeo_media_sync_upload_source',
							'compare' => 'EXISTS',
						),
					),
				)
			);

			foreach ( $attachments as $attachment_id ) {
				$result = $this->delete_vimeo_video_for_attachment( $attachment_id );
				if ( $result['deleted'] ) {
					$deleted++;
					$this->clear_vimeo_attachment_meta( $attachment_id );
				} elseif ( ! $result['skipped'] ) {
					$failed++;
				}
			}

			$page++;
		} while ( ! empty( $attachments ) );

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				'failed'  => $failed,
			)
		);
	}

	/**
	 * Handle manual status refresh from attachment screens.
	 *
	 * @since    1.0.0
	 */
	public function handle_refresh_status() {
		check_admin_referer( 'vimeo_media_sync_refresh_status', 'vimeo_media_sync_nonce' );

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'vimeo-media-sync' ) );
		}

		$this->check_vimeo_processing_status( $post_id );

		$redirect = '';
		if ( isset( $_POST['redirect_to'] ) ) {
			$redirect = wp_validate_redirect( wp_unslash( $_POST['redirect_to'] ), '' );
		}
		if ( ! $redirect ) {
			$redirect = wp_get_referer();
		}
		if ( ! $redirect ) {
			$redirect = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle manual sync for attachments missing Vimeo data.
	 *
	 * @since    1.0.0
	 */
	public function handle_sync_missing() {
		check_admin_referer( 'vimeo_media_sync_sync_missing', 'vimeo_media_sync_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'vimeo-media-sync' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$attachments = array();
		if ( $post_id ) {
			$attachment = get_post( $post_id );
			if ( $attachment ) {
				$attachments = array( $attachment );
			}
		} else {
			$attachments = $this->get_missing_vimeo_attachments( 10 );
		}
		$synced = 0;

		foreach ( $attachments as $attachment ) {
			$this->maybe_upload_video_to_vimeo( $attachment->ID, true );
			$synced++;
		}

		$redirect = add_query_arg(
			array(
				'page'   => $this->plugin_name,
				'synced' => $synced,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
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
		$scheduled = wp_next_scheduled( 'vimeo_media_sync_check_status', $hook_args );

		// A pending check further out would otherwise swallow the short upload delay,
		// so clear it and let the sooner one take its place.
		if ( $scheduled && $scheduled > $timestamp ) {
			$this->log_debug( sprintf( 'Bringing forward Vimeo status check for attachment %d', $post_id ) );
			wp_unschedule_event( $scheduled, 'vimeo_media_sync_check_status', $hook_args );
			$scheduled = false;
		}

		if ( ! $scheduled ) {
			$this->log_debug( sprintf( 'Scheduling Vimeo status check for attachment %d', $post_id ) );
			wp_schedule_single_event( $timestamp, 'vimeo_media_sync_check_status', $hook_args );
		}
	}

	/**
	 * Collect Vimeo meta for display.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 * @return   array
	 */
	private function get_vimeo_attachment_info( $post_id ) {
		$data = array(
			'status'        => get_post_meta( $post_id, '_vimeo_media_sync_status', true ),
			'error'         => get_post_meta( $post_id, '_vimeo_media_sync_error', true ),
			'video_id'      => get_post_meta( $post_id, '_vimeo_media_sync_video_id', true ),
			'video_uri'     => get_post_meta( $post_id, '_vimeo_media_sync_uri', true ),
			'link'          => get_post_meta( $post_id, '_vimeo_media_sync_link', true ),
			'privacy'       => get_post_meta( $post_id, '_vimeo_media_sync_privacy', true ),
			'duration'      => get_post_meta( $post_id, '_vimeo_media_sync_duration', true ),
			'upload_offset' => (int) get_post_meta( $post_id, '_vimeo_media_sync_upload_offset', true ),
			'upload_size'   => (int) get_post_meta( $post_id, '_vimeo_media_sync_upload_size', true ),
		);

		return $data;
	}

	/**
	 * Fetch attachments missing Vimeo sync metadata.
	 *
	 * @since    1.0.0
	 * @param    int $limit Max results.
	 * @return   WP_Post[]
	 */
	private function get_missing_vimeo_attachments( $limit = 10 ) {
		return get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'video',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_vimeo_media_sync_uri',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_vimeo_media_sync_uri',
							'value'   => '',
							'compare' => '=',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_vimeo_media_sync_video_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_vimeo_media_sync_video_id',
							'value'   => '',
							'compare' => '=',
						),
					),
					array(
						'key'     => '_vimeo_media_sync_error',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);
	}

	/**
	 * Normalize privacy setting to Vimeo API value.
	 *
	 * @since    1.0.0
	 * @param    string $privacy Selected privacy.
	 * @return   string
	 */
	private function normalize_privacy_setting( $privacy ) {
		if ( 'default' === $privacy ) {
			return '';
		}

		return $privacy;
	}

	/**
	 * Detect privacy-related Vimeo API errors.
	 *
	 * @since    1.0.0
	 * @param    array $body Vimeo response body.
	 * @return   bool
	 */
	private function is_privacy_error( $body ) {
		if ( ! is_array( $body ) || empty( $body['invalid_parameters'] ) ) {
			return false;
		}

		foreach ( $body['invalid_parameters'] as $param ) {
			if ( isset( $param['field'] ) && 'privacy.view' === $param['field'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render Vimeo details HTML for attachment display.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 * @return   string
	 */
	private function render_vimeo_details_html( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $this->is_video_attachment( $post ) ) {
			return '';
		}

		$data = $this->get_vimeo_attachment_info( $post_id );
		$files = Vimeo_Media_Sync_Helpers::get_vimeo_direct_files( $post_id );
		$redirect_to = get_edit_post_link( $post_id, 'url' );
		if ( ! $redirect_to ) {
			$redirect_to = wp_get_referer();
		}
		$file_links = array();
		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				if ( empty( $file['link'] ) ) {
					continue;
				}
				$label_parts = array();
				if ( ! empty( $file['quality'] ) ) {
					$label_parts[] = $file['quality'];
				}
				if ( ! empty( $file['width'] ) && ! empty( $file['height'] ) ) {
					$label_parts[] = $file['width'] . 'x' . $file['height'];
				}
				if ( ! empty( $file['size'] ) ) {
					$label_parts[] = $this->format_bytes( (int) $file['size'] );
				}
				if ( ! empty( $file['type'] ) ) {
					$label_parts[] = $file['type'];
				}
				$file_links[] = array(
					'url'   => $file['link'],
					'label' => $label_parts ? implode( ' - ', $label_parts ) : esc_html__( 'Direct file', 'vimeo-media-sync' ),
				);
			}
		}

		ob_start();
		?>
		<div class="details vimeo-media-sync-details">
			<h2><?php echo esc_html__( 'Vimeo Media Sync', 'vimeo-media-sync' ); ?></h2>
			<div class="uploaded"><strong><?php echo esc_html__( 'Status:', 'vimeo-media-sync' ); ?></strong> <?php echo esc_html( $data['status'] ? $data['status'] : 'unknown' ); ?></div>
			<?php if ( $data['video_id'] ) : ?>
				<div class="uploaded"><strong><?php echo esc_html__( 'Vimeo ID:', 'vimeo-media-sync' ); ?></strong> <?php echo esc_html( $data['video_id'] ); ?></div>
			<?php endif; ?>
			<?php if ( $data['video_uri'] ) : ?>
				<div class="uploaded"><strong><?php echo esc_html__( 'Vimeo URI:', 'vimeo-media-sync' ); ?></strong> <?php echo esc_html( $data['video_uri'] ); ?></div>
			<?php endif; ?>
			<?php if ( $data['link'] ) : ?>
				<div class="uploaded">
					<strong><?php echo esc_html__( 'Vimeo Link:', 'vimeo-media-sync' ); ?></strong>
					<a href="<?php echo esc_url( $data['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $data['link'] ); ?></a>
				</div>
			<?php endif; ?>
			<?php if ( $data['privacy'] ) : ?>
				<div class="uploaded"><strong><?php echo esc_html__( 'Privacy:', 'vimeo-media-sync' ); ?></strong> <?php echo esc_html( $data['privacy'] ); ?></div>
			<?php endif; ?>
			<?php if ( $data['duration'] ) : ?>
				<div class="uploaded"><strong><?php echo esc_html__( 'Duration:', 'vimeo-media-sync' ); ?></strong> <?php echo esc_html( $data['duration'] ); ?>s</div>
			<?php endif; ?>
			<?php if ( $data['upload_size'] ) : ?>
				<div class="uploaded">
					<strong><?php echo esc_html__( 'Upload Progress:', 'vimeo-media-sync' ); ?></strong>
					<?php echo esc_html( $this->format_bytes( $data['upload_offset'] ) . ' / ' . $this->format_bytes( $data['upload_size'] ) ); ?>
				</div>
			<?php endif; ?>
			<?php if ( $data['error'] ) : ?>
				<div class="uploaded"><strong><?php echo esc_html__( 'Last Error:', 'vimeo-media-sync' ); ?></strong> <?php echo esc_html( $data['error'] ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $file_links ) ) : ?>
				<details class="uploaded vimeo-media-sync-files">
					<summary><?php echo esc_html__( 'Direct file links', 'vimeo-media-sync' ); ?></summary>
					<ul>
						<?php foreach ( $file_links as $file_link ) : ?>
							<li><a href="<?php echo esc_url( $file_link['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $file_link['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'vimeo_media_sync_refresh_status', 'vimeo_media_sync_nonce' ); ?>
				<input type="hidden" name="action" value="vimeo_media_sync_refresh_status" />
				<input type="hidden" name="post_id" value="<?php echo (int) $post_id; ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />
				<p>
					<button type="submit" class="button vimeo-media-sync-refresh" data-post-id="<?php echo (int) $post_id; ?>"><?php echo esc_html__( 'Refresh status', 'vimeo-media-sync' ); ?></button>
				</p>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Format bytes into a readable string.
	 *
	 * @since    1.0.0
	 * @param    int $bytes Byte count.
	 * @return   string
	 */
	private function format_bytes( $bytes ) {
		$bytes = (float) $bytes;
		if ( $bytes <= 0 ) {
			return '0 B';
		}

		$units = array( 'B', 'KB', 'MB', 'GB' );
		$unit_index = (int) floor( log( $bytes, 1024 ) );
		$unit_index = min( $unit_index, count( $units ) - 1 );
		$value = $bytes / pow( 1024, $unit_index );

		return number_format_i18n( $value, $unit_index === 0 ? 0 : 1 ) . ' ' . $units[ $unit_index ];
	}

	/**
	 * Clear Vimeo upload metadata to allow resync.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 */
	private function reset_vimeo_upload_meta( $post_id ) {
		$keys = array(
			'_vimeo_media_sync_uri',
			'_vimeo_media_sync_video_id',
			'_vimeo_media_sync_link',
			'_vimeo_media_sync_response',
			'_vimeo_media_sync_upload_link',
			'_vimeo_media_sync_upload_offset',
			'_vimeo_media_sync_upload_size',
			'_vimeo_media_sync_error',
			'_vimeo_media_sync_status',
		);

		foreach ( $keys as $key ) {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Clear all Vimeo metadata for an attachment.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Attachment ID.
	 */
	private function clear_vimeo_attachment_meta( $post_id ) {
		$keys = array(
			'_vimeo_media_sync_uri',
			'_vimeo_media_sync_video_id',
			'_vimeo_media_sync_link',
			'_vimeo_media_sync_status',
			'_vimeo_media_sync_synced_at',
			'_vimeo_media_sync_error',
			'_vimeo_media_sync_upload_source',
			'_vimeo_media_sync_upload_link',
			'_vimeo_media_sync_upload_offset',
			'_vimeo_media_sync_upload_size',
			'_vimeo_media_sync_duration',
			'_vimeo_media_sync_privacy',
			'_vimeo_media_sync_files',
			'_vimeo_media_sync_response',
		);

		foreach ( $keys as $key ) {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Detect expired Vimeo tus upload URL token errors.
	 *
	 * @since    1.0.0
	 * @param    string $error Error message.
	 * @return   bool
	 */
	private function is_expired_upload_token_error( $error ) {
		if ( '' === $error ) {
			return false;
		}

		$error = strtolower( $error );
		return false !== strpos( $error, 'status' )
			&& false !== strpos( $error, '401' )
			&& false !== strpos( $error, 'error parsing token' )
			&& false !== strpos( $error, 'invalid payload claim' )
			&& false !== strpos( $error, 'exp' )
			&& false !== strpos( $error, 'time validation failed' );
	}

	/**
	 * Determine how long a single invocation may spend sending tus chunks.
	 *
	 * Derived from PHP's max execution time so the loop stops well before the request
	 * is killed. A value of 0 means no limit (typically WP-CLI or cron), which gets a
	 * fixed ceiling instead of running unbounded.
	 *
	 * @since    1.5.0
	 * @return   float Seconds available for sending chunks.
	 */
	private function get_upload_time_budget() {
		$max_execution_time = (int) ini_get( 'max_execution_time' );

		if ( $max_execution_time > 0 ) {
			// Leave headroom for the rest of the request; never exceed the hard ceiling.
			$budget = min( $max_execution_time * 0.6, self::UPLOAD_TIME_BUDGET_CEILING );
		} else {
			$budget = self::UPLOAD_TIME_BUDGET_CEILING;
		}

		/**
		 * Filter the per-request time budget for sending tus chunks.
		 *
		 * @since 1.5.0
		 * @param float $budget Seconds available for sending chunks.
		 */
		return (float) apply_filters( 'vimeo_media_sync_upload_time_budget', $budget );
	}

	/**
	 * Delay before the next run of an upload that still has bytes to send.
	 *
	 * The upload leg is not waiting on Vimeo, it simply ran out of time budget, so it
	 * uses a short delay instead of the transcode polling backoff.
	 *
	 * @since    1.5.0
	 * @return   int Delay in seconds.
	 */
	private function get_upload_poll_delay() {
		/**
		 * Filter the delay before resuming an in-progress tus upload.
		 *
		 * @since 1.5.0
		 * @param int $delay Delay in seconds.
		 */
		return (int) apply_filters( 'vimeo_media_sync_upload_poll_delay', self::UPLOAD_POLL_DELAY );
	}

	/**
	 * Whether an attachment still has tus bytes waiting to be sent.
	 *
	 * @since    1.5.0
	 * @param    int $post_id Attachment ID.
	 * @return   bool
	 */
	private function has_pending_upload_bytes( $post_id ) {
		$upload_link = get_post_meta( $post_id, '_vimeo_media_sync_upload_link', true );
		$upload_size = (int) get_post_meta( $post_id, '_vimeo_media_sync_upload_size', true );
		$upload_offset = (int) get_post_meta( $post_id, '_vimeo_media_sync_upload_offset', true );

		return $upload_link && $upload_size > 0 && $upload_offset < $upload_size;
	}

	/**
	 * Resume a tus upload for an attachment.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id Attachment ID.
	 * @param    string $upload_link Tus upload URL.
	 * @param    int    $offset Current upload offset.
	 * @param    int    $size Total file size.
	 * @return   array { success: bool, completed: bool, offset: int, error: string }
	 */
	private function resume_tus_upload( $post_id, $upload_link, $offset, $size ) {
		$file_path = get_attached_file( $post_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return array(
				'success'   => false,
				'completed' => false,
				'offset'    => $offset,
				'error'     => 'Attachment file not found.',
			);
		}

		$client = $this->get_vimeo_client();
		$head = $client->tus_get_offset( $upload_link );
		if ( $head['success'] && (int) $head['offset'] > $offset ) {
			$offset = (int) $head['offset'];
			update_post_meta( $post_id, '_vimeo_media_sync_upload_offset', $offset );
		}

		$handle = fopen( $file_path, 'rb' );
		if ( ! $handle ) {
			return array(
				'success'   => false,
				'completed' => false,
				'offset'    => $offset,
				'error'     => 'Unable to read attachment file.',
			);
		}

		$chunk_size = 5 * MB_IN_BYTES;
		$time_budget = $this->get_upload_time_budget();

		if ( 0 !== fseek( $handle, $offset ) ) {
			fclose( $handle );
			return array(
				'success'   => false,
				'completed' => false,
				'offset'    => $offset,
				'error'     => 'Unable to seek attachment file.',
			);
		}

		$chunks_sent = 0;
		$started_at = microtime( true );
		$slowest_chunk = 0.0;

		// Keep sending chunks until this request's time budget is nearly spent rather
		// than stopping after a fixed chunk count. The first chunk always runs so every
		// invocation makes forward progress, and each later chunk only starts when there
		// is room for another one as slow as the slowest so far.
		while ( $offset < $size ) {
			if ( $chunks_sent > 0 && ( ( microtime( true ) - $started_at ) + $slowest_chunk ) > $time_budget ) {
				$this->log_debug( sprintf( 'Pausing Vimeo tus upload for attachment %d after %d chunk(s): time budget reached', $post_id, $chunks_sent ) );
				break;
			}

			$length = min( $chunk_size, $size - $offset );
			$chunk = fread( $handle, $length );
			if ( false === $chunk || '' === $chunk ) {
				fclose( $handle );
				return array(
					'success'   => false,
					'completed' => false,
					'offset'    => $offset,
					'error'     => 'Unable to read attachment chunk.',
				);
			}

			$chunk_started = microtime( true );
			$response = $client->tus_patch( $upload_link, $chunk, $offset );
			$slowest_chunk = max( $slowest_chunk, microtime( true ) - $chunk_started );
			if ( ! $response['success'] ) {
				fclose( $handle );
				return array(
					'success'   => false,
					'completed' => false,
					'offset'    => $offset,
					'error'     => $response['error'],
				);
			}

			$new_offset = (int) $response['offset'];
			if ( $new_offset <= $offset ) {
				fclose( $handle );
				return array(
					'success'   => false,
					'completed' => false,
					'offset'    => $offset,
					'error'     => 'Unexpected tus upload offset.',
				);
			}

			// Vimeo is authoritative on the offset. Re-seek whenever it disagrees with
			// where the read pointer landed, which matters now that a single invocation
			// can send many chunks back to back.
			$expected_offset = $offset + strlen( $chunk );
			$offset = $new_offset;

			if ( $new_offset !== $expected_offset && 0 !== fseek( $handle, $new_offset ) ) {
				fclose( $handle );
				update_post_meta( $post_id, '_vimeo_media_sync_upload_offset', $offset );
				return array(
					'success'   => false,
					'completed' => false,
					'offset'    => $offset,
					'error'     => 'Unable to seek attachment file.',
				);
			}

			update_post_meta( $post_id, '_vimeo_media_sync_upload_offset', $offset );
			$chunks_sent++;
		}

		fclose( $handle );

		return array(
			'success'   => true,
			'completed' => $offset >= $size,
			'offset'    => $offset,
			'error'     => '',
		);
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
		// An upload with bytes still queued locally is waiting on us, not on Vimeo, so
		// it resumes on the short upload delay rather than the transcode backoff.
		if ( $this->has_pending_upload_bytes( $attachment->ID ) ) {
			return $this->get_upload_poll_delay();
		}

		if ( 'in_progress' === $transcode_status || in_array( $status, array( 'queued', 'uploading', 'processing' ), true ) ) {
			return 2 * MINUTE_IN_SECONDS;
		}

		$created_at = strtotime( $attachment->post_date_gmt ? $attachment->post_date_gmt : $attachment->post_date );
		if ( ! $created_at ) {
			return 0;
		}

		if ( 'ready' === $status && 'in_progress' !== $transcode_status ) {
			$ready_at = get_post_meta( $attachment->ID, '_vimeo_media_sync_synced_at', true );
			$ready_at = $ready_at ? strtotime( $ready_at ) : $created_at;
			if ( ! $ready_at || ( time() - $ready_at ) > ( 6 * HOUR_IN_SECONDS ) ) {
				return 0;
			}
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
			'_vimeo_media_sync_upload_link',
			'_vimeo_media_sync_upload_offset',
			'_vimeo_media_sync_upload_size',
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
		} elseif ( '' !== $error ) {
			update_post_meta( $post_id, '_vimeo_media_sync_status', 'error' );
		}

		update_post_meta( $post_id, '_vimeo_media_sync_error', sanitize_text_field( $error ) );
		$this->log_debug(
			sprintf(
				'Updated Vimeo status for attachment %d: %s',
				$post_id,
				$status ? $status : 'unchanged'
			)
		);
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
	 * Log debug output when WP_DEBUG is enabled.
	 *
	 * @since    1.0.0
	 * @param    string $message Debug message.
	 */
	private function log_debug( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Vimeo Media Sync] ' . $message );
		}
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
