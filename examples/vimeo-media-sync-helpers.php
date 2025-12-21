<?php
/**
 * Example script to exercise Vimeo_Media_Sync_Helpers.
 *
 * Usage:
 * - Load within WordPress (e.g. via wp shell or a mu-plugin include).
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "This example must be run within WordPress.\n";
	exit( 1 );
}

if ( ! class_exists( 'Vimeo_Media_Sync_Helpers' ) ) {
	echo "Vimeo_Media_Sync_Helpers is not available.\n";
	exit( 1 );
}

$attachments = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'video',
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

if ( empty( $attachments ) ) {
	echo "No video attachments found.\n";
	exit( 0 );
}

$attachment_id = (int) $attachments[0]->ID;

echo "Attachment ID: {$attachment_id}\n";
echo "Meta:\n";
print_r( Vimeo_Media_Sync_Helpers::get_vimeo_meta( $attachment_id ) );

echo "\nVideo ID:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_video_id( $attachment_id ) );
echo "\n\nVideo URI:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_uri( $attachment_id ) );
echo "\n\nVimeo Link:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_link( $attachment_id ) );
echo "\n\nEmbed URL:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_embed_url( $attachment_id ) );
echo "\n\nEmbed HTML:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_embed_html( $attachment_id ) );
echo "\n\nDirect Files:\n";
print_r( Vimeo_Media_Sync_Helpers::get_vimeo_direct_files( $attachment_id ) );
echo "\n\nHLS URL:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_hls_url( $attachment_id ) );
echo "\n\nStatus Label:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_status_label( $attachment_id ) );
echo "\n\nIs Ready:\n";
var_export( Vimeo_Media_Sync_Helpers::is_vimeo_ready( $attachment_id ) );
echo "\n\nLast Error:\n";
var_export( Vimeo_Media_Sync_Helpers::get_vimeo_error( $attachment_id ) );
echo "\n";
