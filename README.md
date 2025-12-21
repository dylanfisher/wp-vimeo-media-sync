# Vimeo Media Sync

Synchronize WordPress video uploads to Vimeo with resumable (tus) uploads and status polling. This plugin creates Vimeo videos, adds them to a `Wordpress` folder, and tracks sync metadata on each attachment.

## Features
- Resumable Vimeo uploads via the tus protocol.
- Automatic status polling with backoff until processing completes.
- Attachment-level Vimeo metadata tracking.
- Optional privacy override (unlisted/public/private) with automatic retry if Vimeo rejects it.
- Dashboard tools to find and sync missing Vimeo uploads.
- Optional delete-on-remove to remove Vimeo videos when WP attachments are deleted.

## Requirements
- WordPress 6.x
- Vimeo personal access token with scopes: `public`, `private`, `create`, `edit`, `delete`, `upload`, `stats`, `video files`

## Installation
1. Copy this plugin into `wp-content/plugins/vimeo-media-sync`.
2. Activate it in the WordPress admin.

## Configuration
Set the Vimeo access token in `wp-config.php` or an environment variable:
```
define( 'VIMEO_MEDIA_SYNC_ACCESS_TOKEN', 'your_token' );
```
```
export VIMEO_MEDIA_SYNC_ACCESS_TOKEN=your_token
```

In the plugin dashboard (Vimeo Sync), choose a default privacy value and the Vimeo folder name to store uploads. If Vimeo does not allow privacy overrides, the plugin retries without the privacy parameter.
You can also enable automatic deletion of Vimeo videos when a WordPress attachment is deleted.

## Usage
1. Upload a video to the Media Library.
2. The plugin creates a Vimeo video via tus upload and adds it to the configured Vimeo folder.
3. Open the attachment details to see Vimeo status, IDs, and progress.
4. Use the “Refresh status” button to manually recheck processing.
5. Use the dashboard table to sync missing videos in bulk or per row; the table includes status, progress, and error context.

## Attachment Metadata
The plugin stores Vimeo metadata on attachments using post meta keys:
- `_vimeo_media_sync_video_id`
- `_vimeo_media_sync_uri`
- `_vimeo_media_sync_link`
- `_vimeo_media_sync_status`
- `_vimeo_media_sync_synced_at`
- `_vimeo_media_sync_error`
- `_vimeo_media_sync_upload_source`
- `_vimeo_media_sync_upload_link`
- `_vimeo_media_sync_upload_offset`
- `_vimeo_media_sync_upload_size`
- `_vimeo_media_sync_duration`
- `_vimeo_media_sync_privacy`
- `_vimeo_media_sync_files`
- `_vimeo_media_sync_response`

## Frontend Helpers
Use `Vimeo_Media_Sync_Helpers` to access synced metadata without API calls.

Available helpers:
- `get_vimeo_meta( $attachment )`
- `get_vimeo_video_id( $attachment )`
- `get_vimeo_uri( $attachment )`
- `get_vimeo_link( $attachment )`
- `get_vimeo_embed_html( $attachment, $args = [] )`
- `get_vimeo_embed_url( $attachment, $args = [] )`
- `get_vimeo_autoplay_embed_url( $attachment )`
- `get_vimeo_direct_files( $attachment )`
- `get_vimeo_hls_url( $attachment )`
- `get_vimeo_status_label( $attachment )`
- `is_vimeo_ready( $attachment )`
- `get_vimeo_error( $attachment )`

Examples:
```
$embed = Vimeo_Media_Sync_Helpers::get_vimeo_embed_html( $attachment_id );
$files = Vimeo_Media_Sync_Helpers::get_vimeo_direct_files( $attachment_id );
```

## Debugging
Set `WP_DEBUG` to `true` to log Vimeo sync progress and API calls to the PHP error log.

## Notes
- Vimeo ownership is tied to the access token owner. Use a team account token if you need uploads to land in a team account.
- The plugin relies on WordPress cron for status polling. Ensure WP-Cron is running on your site.
- Delete-on-remove only runs for video attachments that the plugin previously uploaded to Vimeo.
