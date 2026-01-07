# Repository Guidelines

## Project Structure & Module Organization
- `vimeo-media-sync.php` is the plugin bootstrap and registration entrypoint.
- `includes/` holds core classes (loader, i18n, activator/deactivator, main plugin class).
- `admin/` contains admin-only classes, JS, CSS, and partial templates.
- `public/` contains public-facing classes, JS, CSS, and partial templates.
- `languages/` stores translation templates (`.pot`).
- `uninstall.php` handles cleanup when the plugin is removed.

## Build, Test, and Development Commands
This plugin runs directly in WordPress; there is no build step in the repo.
- Activate the plugin in WordPress under `wp-content/plugins/vimeo-media-sync`.
- Optional syntax check for a file: `php -l vimeo-media-sync.php`.
- If adding assets, edit the source files in `admin/js`, `public/js`, `admin/css`, or `public/css`.

## Tracking changes in README
- Make sure any code changes are accurately reflected in the README.
- Update CHANGELOG.md for user-visible changes.

## Coding Style & Naming Conventions
- Follow WordPress PHP coding standards: tabs for indentation, braces on the next line, and spaces inside parentheses.
- PHP classes use `Studly_Case` names (example: `Vimeo_Media_Sync_Public`).
- Methods and functions are `snake_case`.
- JS uses the jQuery wrapper pattern `(function($){ ... })(jQuery);` and tabs for indentation.
- Keep CSS minimal and scoped to the plugin’s admin/public selectors.

## Testing Guidelines
There are no automated tests in this repository.
- Perform manual verification in a WordPress environment: activate the plugin, visit admin screens, and confirm public pages load without errors.
- If you introduce new behavior, include a short manual test checklist in your PR description.

## Commit & Pull Request Guidelines
No commit message conventions are established yet (no Git history). Use clear, imperative messages (e.g., `Add Vimeo upload hook`).
- PRs should include a short summary, testing notes, and any relevant screenshots for UI changes.
- Link related issues or tickets when applicable.

## Configuration & Security Notes
- Do not commit secrets or API keys; keep credentials in `wp-config.php` or environment-specific config.
- If adding new settings, expose them through WordPress options and sanitize inputs.
