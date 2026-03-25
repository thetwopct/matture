# Copilot Agent Instructions — Matture

## What This Plugin Does

Matture is a WordPress Gutenberg block plugin that gates content behind a tap-to-reveal overlay. It provides five modes: NSFW, Mature, Spoiler, Trigger Warning. The plugin is small (~1000 lines of PHP, 4 JS files, 2 SCSS files) and has a comprehensive test suite.

## Tech Stack & Requirements

- **PHP 8.0+**, WordPress 6.6+, tested up to 6.8
- **Node.js 20.16.0** (see `.tool-versions`)
- **`@wordpress/scripts` ^30** for JS/SCSS build tooling
- **Composer** for PHP dev dependencies (PHPCS, PHPStan, PHPUnit)
- **wp-env** (Docker-based local WordPress environment) — **all PHP/composer commands MUST run through wp-env**

## Environment Setup (ALWAYS follow this order)

```bash
# 1. Install Node dependencies (run from repo root directly)
npm install

# 2. Start the wp-env Docker environment
wp-env start

# 3. Install Composer dependencies (MUST use wp-env wrapper)
wp-env run cli --env-cwd=wp-content/plugins/matture composer install
```

> **CRITICAL:** Never run `composer install`, `vendor/bin/phpunit`, `vendor/bin/phpcs`, or any PHP tool directly on the host. Always use the `wp-env run` wrapper. Running composer directly on the host will hang or fail because PHP stubs and WordPress test libraries are only available inside the wp-env container.

## Build Commands

```bash
# Build Gutenberg block JS/CSS (run from repo root directly — NOT through wp-env)
npm run build

# Watch mode for development
npm run start
```

The build outputs to `blocks/content-gate/build/`. The build artifacts (`index.js`, `view.js`, `index.css`, `style-index.css`, and `.asset.php` files) are committed to the repo.

## Validation & Linting (ALL through wp-env)

```bash
# PHP CodeSniffer (WordPress coding standards)
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script sniff

# Auto-fix PHPCS violations
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script fix

# PHPStan static analysis (level 5)
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script analyse

# PHP version compatibility checks (5.6–8.5)
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script version-checks

# Run all PHP checks (sniff + analyse)
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script checks

# JavaScript linting
npm run lint:js

# CSS/SCSS linting
npm run lint:css
```

## Running Tests

### PHP Tests (PHPUnit — MUST use wp-env `tests-cli`)

```bash
wp-env run tests-cli --env-cwd=wp-content/plugins/matture vendor/bin/phpunit
```

- Tests require the WordPress testing environment provided by wp-env (`tests-cli`, not `cli`).
- PHPUnit config: `phpunit.xml.dist` — bootstrap is `tests/bootstrap.php`.
- The bootstrap loads WordPress test scaffolding via `WP_TESTS_DIR` and the Yoast PHPUnit Polyfills.
- **Do not attempt to run PHPUnit outside wp-env** — the bootstrap will fail without `WP_TESTS_DIR`.

### JavaScript Tests (Jest via wp-scripts)

```bash
npm run test:unit
```

## CI Workflow

`.github/workflows/package-plugin.yml` runs on push to `main` and all PRs. It:
1. Sparse-checks out `blocks/`, `includes/`, `languages/`, `matture.php`, `readme.txt`
2. Lints `readme.txt` via `wporg-plugin-readme-linter`
3. Extracts the plugin version from `matture.php`
4. Packages the plugin as a build artifact

The workflow **skips** `**.md`, `**.dist`, `**.neon`, `tests/**`, `utils/**`, and `.github/copilot-instructions.md`.

## Repository Layout

```
matture.php                  # Main plugin entry — defines constants, requires classes, calls init
includes/
  class-matture-init.php     # Plugin initialization, block registration, text domain loading
  class-matture-hooks.php    # Block render callback, overlay HTML builder, all PHP hooks/filters
  class-matture-rest.php     # REST API: GET /matture/v1/status/{block_id}
blocks/content-gate/
  block.json                 # Block metadata (attributes, supports, assets)
  index.js                   # Block registration entry point
  edit.js                    # Block editor component (InspectorControls, mode selector)
  save.js                    # Deprecated save function (block uses server-side render)
  view.js                    # Frontend reveal/hide logic (vanilla JS, wp.hooks)
  editor.scss                # Editor-only styles
  style.scss                 # Frontend styles (5 mode themes, CSS custom properties)
  build/                     # Compiled output (committed to repo)
tests/
  bootstrap.php              # PHPUnit bootstrap (loads WP test env + plugin)
  Test_Block_Registration.php
  Test_Hooks.php             # Largest test file — 38 tests covering render, filters, actions
  Test_Rest.php              # 15 tests covering REST endpoint responses and filters
  js/view.test.js            # 47 Jest tests for frontend reveal/hide/localStorage
languages/                   # i18n — matture.pot translation template
```

### Config Files

| File | Purpose |
|------|---------|
| `.phpcs.xml.dist` | PHPCS ruleset — WordPress standard, excludes `vendor/`, `node_modules/`, `build/`, `tests/` (relaxed comment/filename rules) |
| `phpunit.xml.dist` | PHPUnit 10.5 config — 3 test files in `main` suite |
| `phpstan.dist.neon` | PHPStan level 5 — includes WP + WooCommerce stubs |
| `.wp-env.json` | wp-env config — PHP 8.4 (local dev runtime; plugin minimum is 8.0), ports 8926 (dev) / 8927 (tests), WP_DEBUG enabled |
| `.tool-versions` | Node.js 20.16.0 |
| `.distignore` | Files excluded from plugin distribution ZIP |

## Coding Standards (WPCS)

- **Indentation:** TABS only, never spaces
- **Line length:** 100 characters max
- **Braces:** Always required for control structures
- **PHPDoc:** Required on all classes and methods (relaxed in `tests/`)
- **Security:** Always sanitize inputs (`sanitize_text_field()`, etc.), always escape outputs (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`), always verify nonces, always check capabilities with `current_user_can()`
- **Database:** Use `$wpdb->prepare()` for all dynamic queries
- **Namespacing:** All PHP classes use `namespace Matture;`
- **Constants:** Prefixed `MATTURE_` (e.g., `MATTURE_PLUGIN_PATH`)
- **Hooks:** Prefixed `matture_` (e.g., `matture_block_attributes`)
- **Test files:** Named `Test_ClassName.php` (PascalCase, not hyphenated — PHPCS filename rules are excluded for `tests/`)

## Key Architecture Notes

- The block uses **server-side rendering** (`render_callback` in PHP) — `save.js` is a deprecated migration stub.
- All 5 PHP filters and 5 PHP actions are defined in `class-matture-hooks.php`.
- The REST endpoint in `class-matture-rest.php` is public (`__return_true` permission) and read-only.
- Frontend JS uses vanilla JavaScript with `wp.hooks` for extensibility — no jQuery.
- Block styles use CSS custom properties (`--matture-blur`, `--matture-overlay-bg`, etc.).
- The `vendor/` directory is git-ignored — never modify or commit it.

## Common Pitfalls to Avoid

1. **Never run composer or PHP tools directly** — always use `wp-env run cli --env-cwd=wp-content/plugins/matture`.
2. **Use `tests-cli` (not `cli`) for PHPUnit** — the tests environment has `WP_TESTS_DIR` set.
3. **Do not use spaces for indentation** — PHPCS enforces tabs.
4. **Do not forget output escaping** — every `echo`/`printf` needs `esc_*` functions.
5. **Do not skip nonce verification** on form handlers or AJAX endpoints.
6. **Do not modify `vendor/` or `node_modules/`** — both are git-ignored.
7. **Always rebuild blocks after JS/SCSS changes** — run `npm run build` and commit the `build/` output.
8. **New test files** must be added to the `<testsuite>` in `phpunit.xml.dist`.

> **Trust these instructions.** Only search the codebase if something here is incomplete or produces an error.
