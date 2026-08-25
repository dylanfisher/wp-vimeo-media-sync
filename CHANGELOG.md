# Changelog

## v1.5.0
- Send resumable upload chunks until the request's time budget is spent instead of stopping after a fixed 3 chunks, so large videos upload far faster.
- Resume in-progress uploads after a short delay rather than reusing the 2 minute transcode polling backoff.
- Bring forward an already-scheduled status check when a sooner one is requested.
- Re-seek the source file when Vimeo reports an unexpected upload offset.
- Skip a resumable upload run when another process already holds that attachment's upload lock, avoiding concurrent resumes that collided with a 412.
- Recover from a 412 offset mismatch by re-syncing with Vimeo and continuing the run instead of abandoning it.
- Add the `vimeo_media_sync_upload_time_budget` and `vimeo_media_sync_upload_poll_delay` filters.

## v1.3.0
- Automatically reset and retry a single attachment when its resumable Vimeo upload URL expires.
- Collapse dashboard configuration notes when the Vimeo token is already detected.
- Add a Vimeo Sync dashboard warning when `wp-cron.php` is unreachable.
- Document Basic Auth and `.htaccess` requirements for WP-Cron-backed uploads.
- Show all video attachments in the Sync Status table with pagination and an empty-state row.
- Match the Media Library pagination markup in the dashboard table.
- Only show the “Sync missing videos” button when missing attachments exist.
- Skip manual sync uploads when Vimeo metadata already exists to avoid duplicates.
- Update README details for the paginated dashboard table.
