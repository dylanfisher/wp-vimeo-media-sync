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

## Versioning
The `Version` header in `vimeo-media-sync.php` is the single source of truth. On every push to `main` a GitHub Actions workflow reads that header and cuts a release if no matching tag exists yet, so leaving the header on an already-tagged version means the change never ships.

- Run `git tag -l` before choosing a version. Never reuse a version that is already tagged.
- If the current header is already tagged, the change belongs in the next version: bump the header rather than adding to the shipped one.
- Bump the header in the same commit as the change it ships. The workflow compares against tags rather than the previous commit, so a bump is still picked up when it is not the last commit in a push.
- Add a matching `## vX.Y.Z` section to CHANGELOG.md. Every released version should have one; do not append to a section that has already shipped.
- Use the version being released in `@since` docblock tags on new functions, constants, and filters. This is the bumped version, not the header's previous value.
- Follow semver: patch for fixes, minor for new behavior or filters, major for breaking changes.
- See the release checklist in README.md for the full flow.

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
Use clear, imperative subject lines describing the change (e.g., `Add Vimeo upload hook`), matching the existing history.

- Do not commit, push, or tag unless asked to.
- Keep a commit to one logical change, and include its README, CHANGELOG, and `Version` header updates in that same commit so a release is never missing its notes.
- Run `php -l` on every changed PHP file before committing.
- Note in the commit or PR whether the change was manually verified in WordPress, since this repo has no automated tests.
- PRs should include a short summary, testing notes, and any relevant screenshots for UI changes.
- Link related issues or tickets when applicable.

## Configuration & Security Notes
- Do not commit secrets or API keys; keep credentials in `wp-config.php` or environment-specific config.
- If adding new settings, expose them through WordPress options and sanitize inputs.
