<?php

/**
 * Simple Vimeo REST client wrapper for WordPress.
 *
 * @link       https://www.dylanfisher.com/
 * @since      1.0.0
 *
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/includes
 */

class Vimeo_Media_Sync_Vimeo_Client {

	/**
	 * Vimeo API base URL.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	private $base_url = 'https://api.vimeo.com';

	/**
	 * OAuth access token.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	private $access_token;

	/**
	 * Initialize the client.
	 *
	 * @since    1.0.0
	 * @param    string $access_token Vimeo personal access token.
	 */
	public function __construct( $access_token ) {
		$this->access_token = $access_token;
	}

	/**
	 * Fetch a project by URI.
	 *
	 * @since    1.0.0
	 * @param    string $project_uri Project URI or full URL.
	 * @return   array|null Project data or null on failure.
	 */
	public function get_project( $project_uri ) {
		if ( '' === $project_uri ) {
			return null;
		}

		$response = $this->request( 'GET', $this->strip_base_url( $project_uri ) );
		return $response['success'] ? $response['body'] : null;
	}

	/**
	 * Look up a project (folder) by name, creating it if needed.
	 *
	 * @since    1.0.0
	 * @param    string $name Project name.
	 * @return   array|null Project data or null on failure.
	 */
	public function get_or_create_project( $name ) {
		$this->log_debug( 'Looking up Vimeo project: ' . $name );
		$normalized = strtolower( trim( $name ) );
		$path = '/me/projects?query=' . rawurlencode( $name ) . '&per_page=100';

		while ( $path ) {
			$response = $this->request( 'GET', $path );
			if ( $response['success'] && ! empty( $response['body']['data'] ) ) {
				foreach ( $response['body']['data'] as $project ) {
					if ( isset( $project['name'] ) && strtolower( trim( $project['name'] ) ) === $normalized ) {
						return $project;
					}
				}
			}

			$next = '';
			if ( $response['success'] && ! empty( $response['body']['paging']['next'] ) ) {
				$next = $response['body']['paging']['next'];
			}
			if ( '' === $next ) {
				break;
			}
			$path = $this->strip_base_url( $next );
		}

		$this->log_debug( 'Creating Vimeo project: ' . $name );
		$create = $this->request(
			'POST',
			'/me/projects',
			array(
				'name' => $name,
			)
		);

		return $create['success'] ? $create['body'] : null;
	}

	/**
	 * Strip the base URL from a full API URL.
	 *
	 * @since    1.0.0
	 * @param    string $url Full Vimeo API URL.
	 * @return   string API path.
	 */
	private function strip_base_url( $url ) {
		if ( 0 === strpos( $url, $this->base_url ) ) {
			return substr( $url, strlen( $this->base_url ) );
		}

		return $url;
	}

	/**
	 * Create a new video upload from a public URL.
	 *
	 * @since    1.0.0
	 * @param    string $video_url Public URL to the media.
	 * @param    string $name Video title.
	 * @param    string $description Video description.
	 * @return   array Response data.
	 */
	public function create_video_from_url( $video_url, $name, $description = '' ) {
		$this->log_debug( 'Creating Vimeo video from URL: ' . $video_url );
		return $this->request(
			'POST',
			'/me/videos',
			array(
				'name'        => $name,
				'description' => $description,
				'upload'      => array(
					'approach' => 'pull',
					'link'     => $video_url,
				),
			)
		);
	}

	/**
	 * Create a new Vimeo video with tus upload.
	 *
	 * @since    1.0.0
	 * @param    int    $size File size in bytes.
	 * @param    string $name Video title.
	 * @param    string $description Video description.
	 * @return   array Response data.
	 */
	public function create_tus_video( $size, $name, $description = '', $privacy = '' ) {
		$this->log_debug( 'Creating Vimeo tus upload (size: ' . (int) $size . ')' );
		$payload = array(
			'name'        => $name,
			'description' => $description,
			'upload'      => array(
				'approach' => 'tus',
				'size'     => (int) $size,
			),
		);

		if ( '' !== $privacy ) {
			$payload['privacy'] = array( 'view' => $privacy );
		}

		return $this->request(
			'POST',
			'/me/videos',
			$payload
		);
	}

	/**
	 * Add a video to a project.
	 *
	 * @since    1.0.0
	 * @param    string $project_uri Project URI (e.g. /projects/123).
	 * @param    string $video_uri Video URI (e.g. /videos/456).
	 * @return   array Response data.
	 */
	public function add_video_to_project( $project_uri, $video_uri ) {
		$this->log_debug( 'Adding video to project: ' . $project_uri . ' -> ' . $video_uri );
		return $this->request( 'PUT', $project_uri . $video_uri );
	}

	/**
	 * Fetch video details.
	 *
	 * @since    1.0.0
	 * @param    string $video_uri Video URI (e.g. /videos/456).
	 * @return   array Response data.
	 */
	public function get_video( $video_uri ) {
		$this->log_debug( 'Fetching Vimeo video: ' . $video_uri );
		return $this->request( 'GET', $video_uri );
	}

	/**
	 * Delete a Vimeo video.
	 *
	 * @since    1.0.0
	 * @param    string $video_uri Video URI (e.g. /videos/456).
	 * @return   array Response data.
	 */
	public function delete_video( $video_uri ) {
		$this->log_debug( 'Deleting Vimeo video: ' . $video_uri );
		return $this->request( 'DELETE', $video_uri );
	}

	/**
	 * Retrieve the current tus upload offset.
	 *
	 * @since    1.0.0
	 * @param    string $upload_link Tus upload URL.
	 * @return   array Response data with offset key.
	 */
	public function tus_get_offset( $upload_link ) {
		$response = $this->tus_request(
			'HEAD',
			$upload_link,
			array(
				'Tus-Resumable' => '1.0.0',
			)
		);

		$offset = 0;
		if ( $response['success'] ) {
			$offset = $this->extract_upload_offset( $response['headers'] );
		}

		$response['offset'] = $offset;
		return $response;
	}

	/**
	 * Send a tus PATCH chunk.
	 *
	 * @since    1.0.0
	 * @param    string $upload_link Tus upload URL.
	 * @param    string $chunk Binary data.
	 * @param    int    $offset Upload offset.
	 * @return   array Response data with offset key.
	 */
	public function tus_patch( $upload_link, $chunk, $offset ) {
		$response = $this->tus_request(
			'PATCH',
			$upload_link,
			array(
				'Tus-Resumable' => '1.0.0',
				'Upload-Offset' => (string) (int) $offset,
				'Content-Type'  => 'application/offset+octet-stream',
			),
			$chunk
		);

		$offset = $this->extract_upload_offset( $response['headers'] );
		$response['offset'] = $offset;
		return $response;
	}

	/**
	 * Perform an authenticated Vimeo API request.
	 *
	 * @since    1.0.0
	 * @param    string       $method HTTP method.
	 * @param    string       $path API path (leading slash).
	 * @param    array|string $body Request body.
	 * @param    array        $headers Additional headers.
	 * @return   array { success: bool, status: int, body: array, error: string }
	 */
	private function request( $method, $path, $body = null, $headers = array() ) {
		$this->log_debug( sprintf( 'Vimeo request %s %s', $method, $path ) );
		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array_merge(
				array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Accept'        => 'application/vnd.vimeo.*+json;version=3.4',
				),
				$headers
			),
		);

		if ( null !== $body ) {
			if ( is_string( $body ) ) {
				$args['body'] = $body;
			} else {
				$args['body'] = wp_json_encode( $body );
				$args['headers']['Content-Type'] = 'application/json';
			}
		}

		$response = wp_remote_request( $this->base_url . $path, $args );
		if ( is_wp_error( $response ) ) {
			$this->log_debug( 'Vimeo request error: ' . $response->get_error_message() );
			return array(
				'success' => false,
				'status'  => 0,
				'body'    => array(),
				'error'   => $response->get_error_message(),
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$this->log_debug( sprintf( 'Vimeo response status: %d', $status ) );
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			$decoded = array();
		}

		return array(
			'success' => $status >= 200 && $status < 300,
			'status'  => $status,
			'body'    => $decoded,
			'error'   => $status >= 200 && $status < 300 ? '' : wp_remote_retrieve_body( $response ),
		);
	}

	/**
	 * Perform a tus request against a full upload URL.
	 *
	 * @since    1.0.0
	 * @param    string       $method HTTP method.
	 * @param    string       $url Upload URL.
	 * @param    array        $headers Request headers.
	 * @param    string|null  $body Request body.
	 * @return   array { success: bool, status: int, body: string, headers: array, error: string }
	 */
	private function tus_request( $method, $url, $headers = array(), $body = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => $headers,
		);

		if ( null !== $body ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			$this->log_debug( 'Tus request error: ' . $response->get_error_message() );
			return array(
				'success' => false,
				'status'  => 0,
				'body'    => '',
				'headers' => array(),
				'error'   => $response->get_error_message(),
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		return array(
			'success' => $status >= 200 && $status < 300,
			'status'  => $status,
			'body'    => wp_remote_retrieve_body( $response ),
			'headers' => wp_remote_retrieve_headers( $response ),
			'error'   => $status >= 200 && $status < 300 ? '' : wp_remote_retrieve_body( $response ),
		);
	}

	/**
	 * Extract upload offset from response headers.
	 *
	 * @since    1.0.0
	 * @param    array $headers Response headers.
	 * @return   int
	 */
	private function extract_upload_offset( $headers ) {
		if ( isset( $headers['upload-offset'] ) ) {
			return (int) $headers['upload-offset'];
		}
		if ( isset( $headers['Upload-Offset'] ) ) {
			return (int) $headers['Upload-Offset'];
		}

		return 0;
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
}
