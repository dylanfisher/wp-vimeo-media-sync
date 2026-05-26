# Changelog

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
