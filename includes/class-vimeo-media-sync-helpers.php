<?php

/**
 * Frontend helper utilities for Vimeo Media Sync metadata.
 *
 * @link       https://https://www.dylanfisher.com/
 * @since      1.0.0
 *
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/includes
 */

class Vimeo_Media_Sync_Helpers {

	/**
	 * Retrieve Vimeo metadata for an attachment.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   array
	 */
	public static function get_vimeo_meta( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		if ( ! $attachment_id ) {
			return array();
		}

		$meta = array(
			'video_id'      => get_post_meta( $attachment_id, '_vimeo_media_sync_video_id', true ),
			'video_uri'     => get_post_meta( $attachment_id, '_vimeo_media_sync_uri', true ),
			'link'          => get_post_meta( $attachment_id, '_vimeo_media_sync_link', true ),
			'status'        => get_post_meta( $attachment_id, '_vimeo_media_sync_status', true ),
			'error'         => get_post_meta( $attachment_id, '_vimeo_media_sync_error', true ),
			'privacy'       => get_post_meta( $attachment_id, '_vimeo_media_sync_privacy', true ),
			'duration'      => get_post_meta( $attachment_id, '_vimeo_media_sync_duration', true ),
			'upload_offset' => (int) get_post_meta( $attachment_id, '_vimeo_media_sync_upload_offset', true ),
			'upload_size'   => (int) get_post_meta( $attachment_id, '_vimeo_media_sync_upload_size', true ),
			'response'      => get_post_meta( $attachment_id, '_vimeo_media_sync_response', true ),
		);

		if ( empty( $meta['video_uri'] ) && ! empty( $meta['video_id'] ) ) {
			$meta['video_uri'] = '/videos/' . $meta['video_id'];
		}

		return $meta;
	}

	/**
	 * Get the Vimeo video ID for an attachment.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   string
	 */
	public static function get_vimeo_video_id( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		$video_id = get_post_meta( $attachment_id, '_vimeo_media_sync_video_id', true );
		if ( $video_id ) {
			return $video_id;
		}

		$uri = get_post_meta( $attachment_id, '_vimeo_media_sync_uri', true );
		if ( preg_match( '/\/videos\/(\d+)/', (string) $uri, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Get the Vimeo URI for an attachment.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   string
	 */
	public static function get_vimeo_uri( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		$uri = get_post_meta( $attachment_id, '_vimeo_media_sync_uri', true );
		if ( $uri ) {
			return $uri;
		}

		$video_id = self::get_vimeo_video_id( $attachment_id );
		return $video_id ? '/videos/' . $video_id : '';
	}

	/**
	 * Get the Vimeo page link for an attachment.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   string
	 */
	public static function get_vimeo_link( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		return (string) get_post_meta( $attachment_id, '_vimeo_media_sync_link', true );
	}

	/**
	 * Build an iframe embed HTML string.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @param    array $args Optional embed args.
	 * @return   string
	 */
	public static function get_vimeo_embed_html( $attachment, $args = array() ) {
		$embed_url = self::get_vimeo_embed_url( $attachment, $args );
		if ( '' === $embed_url ) {
			return '';
		}

		$width = isset( $args['width'] ) ? (int) $args['width'] : 640;
		$height = isset( $args['height'] ) ? (int) $args['height'] : 360;
		$title = isset( $args['title'] ) ? (bool) $args['title'] : false;
		$byline = isset( $args['byline'] ) ? (bool) $args['byline'] : false;
		$portrait = isset( $args['portrait'] ) ? (bool) $args['portrait'] : false;

		return sprintf(
			'<iframe src="%s" width="%d" height="%d" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="%s"></iframe>',
			esc_url( $embed_url ),
			$width,
			$height,
			esc_attr( $title ? 'true' : 'false' )
		);
	}

	/**
	 * Get the Vimeo player embed URL.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @param    array $args Optional embed args.
	 * @return   string
	 */
	public static function get_vimeo_embed_url( $attachment, $args = array() ) {
		$video_id = self::get_vimeo_video_id( $attachment );
		if ( '' === $video_id ) {
			return '';
		}

		$params = array(
			'autoplay' => self::bool_to_int( $args, 'autoplay' ),
			'loop'     => self::bool_to_int( $args, 'loop' ),
			'muted'    => self::bool_to_int( $args, 'muted' ),
			'title'    => self::bool_to_int( $args, 'title' ),
			'byline'   => self::bool_to_int( $args, 'byline' ),
			'portrait' => self::bool_to_int( $args, 'portrait' ),
		);

		$params = array_filter(
			$params,
			function( $value ) {
				return null !== $value;
			}
		);

		$query = $params ? '?' . http_build_query( $params ) : '';
		return 'https://player.vimeo.com/video/' . $video_id . $query;
	}

	/**
	 * Get an autoplaying, muted, looping embed URL with minimal branding.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   string
	 */
	public static function get_vimeo_autoplay_embed_url( $attachment ) {
		return self::get_vimeo_embed_url(
			$attachment,
			array(
				'autoplay' => true,
				'muted'    => true,
				'loop'     => true,
				'title'    => false,
				'byline'   => false,
				'portrait' => false,
			)
		);
	}

	/**
	 * Return direct file links from the stored Vimeo response.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   array
	 */
	public static function get_vimeo_direct_files( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		$response = get_post_meta( $attachment_id, '_vimeo_media_sync_response', true );
		if ( ! is_array( $response ) || empty( $response['files'] ) ) {
			return array();
		}

		return $response['files'];
	}

	/**
	 * Get an HLS playlist URL from stored response files.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   string
	 */
	public static function get_vimeo_hls_url( $attachment ) {
		$files = self::get_vimeo_direct_files( $attachment );
		foreach ( $files as $file ) {
			if ( isset( $file['quality'] ) && 'hls' === $file['quality'] && ! empty( $file['link'] ) ) {
				return $file['link'];
			}
		}

		return '';
	}

	/**
	 * Get a human-friendly status label.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   string
	 */
	public static function get_vimeo_status_label( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		$status = get_post_meta( $attachment_id, '_vimeo_media_sync_status', true );
		switch ( $status ) {
			case 'queued':
				return 'Queued';
			case 'uploading':
				return 'Uploading';
			case 'processing':
				return 'Processing';
			case 'ready':
				return 'Ready';
			case 'error':
				return 'Error';
			default:
				return $status ? ucfirst( $status ) : 'Unknown';
		}
	}

	/**
	 * Check if the Vimeo sync is ready.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   bool
	 */
	public static function is_vimeo_ready( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		return 'ready' === get_post_meta( $attachment_id, '_vimeo_media_sync_status', true );
	}

	/**
	 * Return the last Vimeo error string.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   string
	 */
	public static function get_vimeo_error( $attachment ) {
		$attachment_id = self::resolve_attachment_id( $attachment );
		return (string) get_post_meta( $attachment_id, '_vimeo_media_sync_error', true );
	}

	/**
	 * Convert boolean args to Vimeo query values.
	 *
	 * @since    1.0.0
	 * @param    array  $args Input args.
	 * @param    string $key Key to check.
	 * @return   int|null
	 */
	private static function bool_to_int( $args, $key ) {
		if ( ! array_key_exists( $key, $args ) ) {
			return null;
		}

		return $args[ $key ] ? 1 : 0;
	}

	/**
	 * Resolve attachment ID from various inputs.
	 *
	 * @since    1.0.0
	 * @param    mixed $attachment Attachment object, array, or ID.
	 * @return   int
	 */
	private static function resolve_attachment_id( $attachment ) {
		if ( is_numeric( $attachment ) ) {
			return (int) $attachment;
		}

		if ( $attachment instanceof WP_Post ) {
			return (int) $attachment->ID;
		}

		if ( is_array( $attachment ) ) {
			if ( isset( $attachment['ID'] ) ) {
				return (int) $attachment['ID'];
			}
			if ( isset( $attachment['id'] ) ) {
				return (int) $attachment['id'];
			}
		}

		if ( is_object( $attachment ) ) {
			if ( isset( $attachment->ID ) ) {
				return (int) $attachment->ID;
			}
			if ( isset( $attachment->id ) ) {
				return (int) $attachment->id;
			}
		}

		return 0;
	}
}
