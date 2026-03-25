# Matture

Gate any content behind a tap-to-reveal overlay.

Matture is a WordPress Gutenberg block that wraps any content — images, text, video, or nested blocks — behind a customisable reveal layer. Visitors must actively choose to view gated content by clicking or tapping the overlay. This makes it ideal for adult content warnings, plot spoilers, sensitive topic alerts, or age-gated material.

- No shortcodes — it's a native Gutenberg block
- No jQuery — frontend JavaScript is lightweight and vanilla
- No configuration required — drop the block in, pick a mode, and you're done

Made as part of PluginJam Blockathon — Theme: Reveal.

## Features

### Four reveal modes

Matture ships with four carefully designed reveal modes, each with its own visual style and default labels:

| Mode | Description | Default Warning | Default Button |
|------|-------------|-----------------|----------------|
| **NSFW** | Heavy blur with a bold warning badge. Designed for adult or explicit content that should not be visible by default. | `NSFW Content — Tap to reveal` | `Reveal` |
| **Mature** | Full dark overlay with a confirmation button, similar to the Facebook-style age gate. Best for content that requires an explicit "I am 18+" confirmation. | `Mature Content — Click to confirm you're 18+` | `I confirm I am 18+` |
| **Spoiler** | Light blur overlay with a Discord/Reddit-inspired aesthetic. Perfect for hiding plot twists, game endings, or any content someone might not want to see yet. | `Spoiler — Click to reveal` | `Show Spoiler` |
| **Trigger Warning** | Soft grey overlay with fully customisable warning text. Ideal for content that discusses sensitive or potentially distressing topics. | `Trigger Warning — Click to continue` | `Continue` |

All warning labels and button text can be overridden per block in the editor, or globally via PHP filters.

### Block controls

Every Content Gate block provides the following settings in the block inspector sidebar:

- **Mode selector** — choose from NSFW, Mature, Spoiler, or Trigger Warning
- **Warning label** — customise the warning text shown on the overlay (leave blank to use the mode default)
- **Reveal button label** — customise the text on the reveal button (leave blank to use the mode default)
- **Sub-label** — optional secondary text displayed below the main warning, useful for providing extra context
- **Blur intensity** — adjust the blur level for NSFW and Spoiler modes (0–40px range)
- **Allow re-hide** — when enabled, a "Re-hide" button appears after the content is revealed, letting visitors re-apply the gate
- **Remember reveal** — when enabled, the visitor's reveal state is saved in `localStorage` so the content stays revealed on future page loads
- **Show mode icon** — toggle an SVG icon alongside the warning label that visually represents the current mode

### CSS custom properties

All visual styling is driven by CSS custom properties, making it straightforward for themes to override Matture's appearance without modifying the plugin:

| Property | Description | Default |
|----------|-------------|---------|
| `--matture-overlay-bg` | Background colour of the overlay | Varies per mode |
| `--matture-blur-intensity` | Blur filter value applied to gated content | Varies per mode |
| `--matture-border-color` | Border colour (used by Trigger Warning mode) | `#ccc` |
| `--matture-button-bg` | Reveal button background colour | `transparent` |
| `--matture-button-color` | Reveal button text and border colour | `currentColor` |
| `--matture-warning-color` | Warning label text colour | Varies per mode |

**Example: overriding in your theme CSS or Additional CSS:**

```css
/* Make all overlays use a custom dark blue background */
.matture-gate {
	--matture-overlay-bg: rgba(10, 20, 60, 0.9);
	--matture-warning-color: #f0f0f0;
	--matture-button-color: #f0f0f0;
}

/* Override just the NSFW mode */
.matture-gate--nsfw {
	--matture-overlay-bg: rgba(200, 0, 0, 0.95);
}
```

### Data attributes

Every rendered gate element exposes the following HTML data attributes, making it easy to target or interact with Matture blocks from your own JavaScript:

| Attribute | Description |
|-----------|-------------|
| `data-mode` | The active mode slug (`nsfw`, `mature`, `spoiler`, or `trigger`) |
| `data-matture-state` | Current visibility state: `hidden` or `revealed` |
| `data-allow-rehide` | `1` if re-hide is enabled, `0` otherwise |
| `data-blur` | Blur intensity in pixels |
| `data-remember` | `1` if localStorage persistence is enabled, `0` otherwise |
| `data-button-label` | The reveal button label text |
| `data-sub-label` | The sub-label text (may be empty) |

## Hooks and Filters

Matture provides a comprehensive set of PHP hooks and filters for developers who want to extend or customise its behaviour. You can use these to modify attributes, change default text, inject custom HTML, register new modes, or hook into the render lifecycle.

### PHP Filters

#### `matture_block_attributes`

Modify the block attributes array before the block is rendered. This is useful for enforcing a specific mode across all blocks, overriding attributes based on user roles, or adding custom logic.

```php
add_filter( 'matture_block_attributes', function( $attributes ) {
    // Force all content gates to use Mature mode
    $attributes['mode'] = 'mature';
    return $attributes;
} );
```

**Parameters:**
- `$attributes` *(array)* — The block attributes array.

**Returns:** *(array)* — The modified attributes array.

---

#### `matture_default_warning_text`

Filter the default warning labels and button text for each mode. This is called before rendering and receives the full defaults array along with the current mode slug. Use this to change the default text globally without editing each block individually.

```php
add_filter( 'matture_default_warning_text', function( $defaults, $mode ) {
    // Customise the NSFW defaults
    $defaults['nsfw']['label']  = 'Adult content — tap to continue';
    $defaults['nsfw']['button'] = 'I understand';
    return $defaults;
}, 10, 2 );
```

**Parameters:**
- `$defaults` *(array)* — Associative array keyed by mode slug, each containing `label` and `button` keys.
- `$mode` *(string)* — The current mode slug being rendered.

**Returns:** *(array)* — The modified defaults array.

---

#### `matture_overlay_html`

Filter the fully built overlay HTML string before it is output. This gives you complete control over the overlay markup. You can append extra elements, wrap it in a container, or replace it entirely.

```php
add_filter( 'matture_overlay_html', function( $html, $attributes ) {
    // Append a custom disclaimer to every overlay
    return $html . '<p class="my-disclaimer">Viewer discretion is advised.</p>';
}, 10, 2 );
```

**Parameters:**
- `$html` *(string)* — The complete overlay HTML string.
- `$attributes` *(array)* — The block attributes array.

**Returns:** *(string)* — The modified overlay HTML string.

---

#### `matture_modes`

Filter the list of valid mode slugs. Use this to register custom modes alongside the built-in ones, or to remove modes you don't want available. If a block uses a mode that isn't in this list, it will fall back to `mature`.

```php
add_filter( 'matture_modes', function( $modes ) {
    // Add a custom mode
    $modes[] = 'age-verify';
    return $modes;
} );
```

**Parameters:**
- `$modes` *(array)* — Indexed array of mode slug strings.

**Returns:** *(array)* — The modified modes array.

> **Note:** When adding a custom mode, you will also need to provide your own CSS for the `.matture-gate--your-mode` class and handle any custom overlay styling.

---

#### `matture_ai_classify_content`

Filter the REST API response payload for a block status request. This hook exists primarily for AI-agent integrations that need to classify or enrich block metadata server-side.

```php
add_filter( 'matture_ai_classify_content', function( $data, $request ) {
    $data['mode']     = 'nsfw';
    $data['severity'] = 'high';
    return $data;
}, 10, 2 );
```

**Parameters:**
- `$data` *(array)* — The response data array containing `block_id`, `mode`, and `state`.
- `$request` *(WP_REST_Request)* — The REST request object.

**Returns:** *(array)* — The modified response data array.

---

### PHP Actions

#### `matture_before_overlay`

Fires immediately before the overlay HTML is output during rendering. Use this to inject custom markup before the overlay, such as a wrapping element.

```php
add_action( 'matture_before_overlay', function( $attributes ) {
    echo '<div class="my-custom-wrapper">';
} );
```

**Parameters:**
- `$attributes` *(array)* — The block attributes array.

---

#### `matture_after_overlay`

Fires immediately after the overlay HTML is output during rendering.

```php
add_action( 'matture_after_overlay', function( $attributes ) {
    echo '</div><!-- .my-custom-wrapper -->';
} );
```

**Parameters:**
- `$attributes` *(array)* — The block attributes array.

---

#### `matture_before_content`

Fires immediately before the gated content wrapper (`<div class="matture-gate__content">`) is output.

```php
add_action( 'matture_before_content', function( $attributes ) {
    // Inject analytics tracking pixel
    echo '<img src="https://example.com/track?mode=' . esc_attr( $attributes['mode'] ) . '" alt="" />';
} );
```

**Parameters:**
- `$attributes` *(array)* — The block attributes array.

---

#### `matture_after_content`

Fires immediately after the gated content wrapper is closed.

```php
add_action( 'matture_after_content', function( $attributes ) {
    // Clean up or close custom wrappers
} );
```

**Parameters:**
- `$attributes` *(array)* — The block attributes array.

---

#### `matture_register_abilities`

Fires on `init` after blocks are registered. This is the recommended hook point for third-party integrations that depend on Matture being fully loaded — for example, registering abilities with an AI integration layer.

```php
add_action( 'matture_register_abilities', function() {
    // Register your integration here
} );
```

---

### JavaScript Hooks

Matture fires client-side hooks via `@wordpress/hooks` (`wp.hooks`) when a gate is revealed. These hooks are only available when `wp.hooks` is loaded on the page, which is standard on any WordPress site using block editor scripts.

#### `matture.beforeReveal`

Fires just before a gate transitions to the revealed state. Use this to run custom logic (such as pausing videos, firing analytics events, or checking conditions) before the overlay fades out.

```js
wp.hooks.addAction( 'matture.beforeReveal', 'my-plugin', function( gateElement, mode ) {
    console.log( 'About to reveal:', mode, gateElement );
} );
```

**Parameters:**
- `gateElement` *(HTMLElement)* — The `.matture-gate` DOM element being revealed.
- `mode` *(string)* — The mode slug of the gate (`nsfw`, `mature`, `spoiler`, or `trigger`).

---

#### `matture.afterReveal`

Fires after a gate has been fully revealed. Use this for post-reveal actions such as tracking analytics events, lazy-loading media, or triggering animations on the now-visible content.

```js
wp.hooks.addAction( 'matture.afterReveal', 'my-plugin', function( gateElement, mode ) {
    // Example: Track the reveal event in Google Analytics
    if ( typeof gtag !== 'undefined' ) {
        gtag( 'event', 'matture_reveal', { mode: mode } );
    }
} );
```

**Parameters:**
- `gateElement` *(HTMLElement)* — The `.matture-gate` DOM element that was revealed.
- `mode` *(string)* — The mode slug of the gate.

---

### REST API

Matture registers a public, read-only REST API endpoint:

```
GET /wp-json/matture/v1/status/{block_id}
```

This returns the server-side status of a content gate block. The `state` field is always `hidden` on the server (since reveal state is a client-side concept managed by JavaScript). Use the `matture_ai_classify_content` filter to populate the `mode` field or add additional data.

**Example response:**

```json
{
    "block_id": "my-block-id",
    "mode": "",
    "state": "hidden",
    "timestamp": "2025-01-01T00:00:00+00:00"
}
```

The `block_id` parameter accepts alphanumeric characters, hyphens, and underscores.

## Installation

1. Upload the `matture` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen in WordPress
3. Open the block editor on any post or page and search for **"Matture"** or **"Content Gate"**
4. Add the block, choose your preferred reveal mode, and drop any content inside the block

That's it — no additional setup, settings pages, or database tables are required.

## FAQ

### Which reveal mode should I use?

It depends on your content:

- Use **NSFW** for adult or explicit content that needs the strongest visual gate — it applies a heavy blur and desaturation so the underlying content is completely unrecognisable.
- Use **Mature** for age-gated content where you want a clear confirmation step, similar to the "Are you 18+?" overlays on social media platforms.
- Use **Spoiler** for plot reveals, game endings, puzzle solutions, or any content that a reader might prefer to discover on their own — the lighter blur keeps the experience playful.
- Use **Trigger Warning** for sensitive topics (e.g. discussions of trauma, violence, or distressing imagery) where you want a soft, respectful overlay with fully customisable warning text and a sub-label for additional context.

### Can I customise the overlay colours?

Absolutely. All overlay colours are set through CSS custom properties, so you can override them in your theme's stylesheet or WordPress's **Additional CSS** panel without touching the plugin code. For example:

```css
.matture-gate--nsfw {
    --matture-overlay-bg: rgba(100, 0, 0, 0.95);
    --matture-warning-color: #ffe0e0;
}
```

See the [CSS custom properties](#css-custom-properties) section above for the full list of available properties.

### Can I add my own custom reveal mode?

Yes. Use the `matture_modes` PHP filter to register your custom mode slug, and then add corresponding CSS for `.matture-gate--your-mode` in your theme. You will also want to hook into `matture_default_warning_text` to define default labels for your new mode. See the [Hooks and Filters](#hooks-and-filters) section for full details and code examples.

### Does Matture remember the visitor's reveal choice?

Optionally. Each block has a **"Remember reveal"** toggle in the inspector sidebar. When enabled, Matture stores the revealed state in the visitor's browser using `localStorage`. The storage key is based on the block's anchor ID (if set) or its position on the page, so each gate instance is tracked independently. Clearing browser data will reset the state.

### What about performance?

Matture is designed to be as lightweight as possible. It loads a small CSS file and a small vanilla JavaScript file on the frontend — and only on pages where the Content Gate block is actually used. There are no external API requests, no tracking scripts, and no jQuery dependency.

### Does Matture work with any theme?

Yes. Matture has no theme dependencies at all. It uses standard WordPress block markup and CSS custom properties, so it integrates cleanly with any block-based theme (such as Twenty Twenty-Five) or any classic theme. The overlay will adapt to the block's container width automatically.

### Does Matture use jQuery?

No. The frontend JavaScript is entirely vanilla — no jQuery, no external libraries. The only optional dependency is `wp.hooks` (part of WordPress core) for the JavaScript hook system.

### Can I use Matture with other blocks inside?

Yes. The Content Gate block acts as a wrapper (a "parent" block). You can nest any other blocks inside it — images, paragraphs, videos, galleries, columns, or even other plugin blocks. Everything inside the gate will be hidden until the visitor reveals it.

## Plugin Development

Contributions and pull requests are very welcome! The project uses a Docker-based local development environment via `wp-env`.

### Prerequisites

- **Node.js** — see `.tool-versions` for the required version (currently 20.16.0)
- **Docker** — required for `wp-env`
- **`@wordpress/env`** installed globally: `npm install -g @wordpress/env`

### Getting started

```bash
# 1. Clone the repository
git clone https://github.com/thetwopct/matture.git
cd matture

# 2. Install Node dependencies
npm install

# 3. Start the wp-env Docker environment
wp-env start

# 4. Install Composer dependencies (MUST use wp-env wrapper)
wp-env run cli --env-cwd=wp-content/plugins/matture composer install
```

The local dev site runs at `http://localhost:8926` and the test site at `http://localhost:8927`.

### Build commands

```bash
# Build the Gutenberg block JS/CSS (run from repo root, NOT through wp-env)
npm run build

# Watch mode for development — rebuilds on file changes
npm run start
```

Build output goes to `blocks/content-gate/build/` and is committed to the repository.

### Linting and static analysis

All PHP tools must be run through `wp-env`:

```bash
# PHP CodeSniffer (WordPress coding standards)
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script sniff

# Auto-fix PHPCS violations
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script fix

# PHPStan static analysis (level 5)
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script analyse

# PHP version compatibility checks
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script version-checks

# Run all PHP checks at once (sniff + analyse)
wp-env run cli --env-cwd=wp-content/plugins/matture composer run-script checks
```

JavaScript and CSS linting runs directly on the host:

```bash
npm run lint:js
npm run lint:css
```

### Running tests

PHP tests run through `wp-env` using the `tests-cli` environment:

```bash
wp-env run tests-cli --env-cwd=wp-content/plugins/matture vendor/bin/phpunit
```

JavaScript tests run on the host via Jest:

```bash
npm run test:unit
```

> **Important:** Do not run PHPUnit or Composer directly on your host machine — the WordPress test scaffolding and PHP stubs are only available inside the `wp-env` container.
