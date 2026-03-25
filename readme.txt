=== Matture ===
Contributors: bonkerz
Tags: content gate, nsfw, spoiler, overlay, block
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gate any content behind a tap-to-reveal overlay. Four modes: NSFW, mature, spoiler, and trigger warning.

== Description ==

Matture is a wrapper block that gates any content — images, text, video, or nested blocks — behind a customisable tap-to-reveal overlay.

Pick a reveal mode, drop your content inside, and Matture handles the rest. No shortcodes. No jQuery. No configuration required.

**Four reveal modes:**

* **NSFW** — heavy blur with a warning badge. Click to reveal.
* **Mature** — full dark overlay with a confirm button, Facebook-style.
* **Spoiler** — light blur with a Discord/Reddit aesthetic.
* **Trigger Warning** — soft grey overlay with fully customisable warning text.

**Block controls:**

* Mode selector
* Custom warning label and sub-label text
* Re-hideable toggle (can the visitor re-hide after revealing?)
* Blur intensity slider (NSFW and Spoiler modes)
* Show/hide SVG icon toggle

**Styling:**

All colours and overlay styles are set via CSS custom properties — easily overrideable in your theme or Additional CSS without touching the plugin.

Available properties: `--matture-overlay-bg`, `--matture-blur-intensity`, `--matture-border-color`, `--matture-button-bg`, `--matture-button-color`, `--matture-warning-color`.

**Developer-friendly:**

Full PHP hook and filter layer. Add or modify modes, filter overlay HTML, hook into render, and listen to JS reveal events via `@wordpress/hooks`.

REST status endpoint: `GET /wp-json/matture/v1/status/{block_id}`.

Semantic `data-matture-mode` and `data-matture-state` attributes on every overlay for easy JS targeting.

== Hooks & Filters ==

Matture exposes a full extensibility layer for developers.

**PHP Filters**

`matture_block_attributes`

Modify block attributes before the block is rendered.

    add_filter( 'matture_block_attributes', function( $attributes ) {
        // Force all blocks to the 'mature' mode.
        $attributes['mode'] = 'mature';
        return $attributes;
    } );

`matture_default_warning_text`

Filter the default warning label and button text for each mode. Receives the full defaults array and the current mode slug.

    add_filter( 'matture_default_warning_text', function( $defaults, $mode ) {
        $defaults['nsfw']['label']  = 'Adult content — tap to continue';
        $defaults['nsfw']['button'] = 'I understand';
        return $defaults;
    }, 10, 2 );

`matture_overlay_html`

Filter the fully built overlay HTML string before output. Receives the HTML and the full attributes array.

    add_filter( 'matture_overlay_html', function( $html, $attributes ) {
        // Append a custom disclaimer to every overlay.
        return $html . '<p class="my-disclaimer">Viewer discretion advised.</p>';
    }, 10, 2 );

`matture_modes`

Filter the list of valid mode slugs. Use this to register a custom mode alongside the built-in ones.

    add_filter( 'matture_modes', function( $modes ) {
        $modes[] = 'custom-mode';
        return $modes;
    } );

`matture_ai_classify_content`

Filter the REST API response payload for a block status request. Use this to populate the `mode` field or add extra data for AI agents.

    add_filter( 'matture_ai_classify_content', function( $data, $request ) {
        $data['mode']     = 'nsfw';
        $data['severity'] = 'high';
        return $data;
    }, 10, 2 );

**PHP Actions**

`matture_before_overlay`

Fires immediately before the overlay HTML is output. Receives the block attributes array.

    add_action( 'matture_before_overlay', function( $attributes ) {
        echo '<div class="my-prefix-wrapper">';
    } );

`matture_after_overlay`

Fires immediately after the overlay HTML is output. Receives the block attributes array.

    add_action( 'matture_after_overlay', function( $attributes ) {
        echo '</div>';
    } );

`matture_before_content`

Fires immediately before the gated content wrapper is output. Receives the block attributes array.

    add_action( 'matture_before_content', function( $attributes ) {
        // Inject analytics event.
    } );

`matture_after_content`

Fires immediately after the gated content wrapper is output. Receives the block attributes array.

    add_action( 'matture_after_content', function( $attributes ) {
        // Clean up.
    } );

`matture_register_abilities`

Fires on `init` at priority 20, after blocks are registered. Use this hook to register third-party abilities or integrations that depend on Matture being fully loaded.

    add_action( 'matture_register_abilities', function() {
        // Register your integration here.
    } );

**JavaScript Hooks**

Matture fires JavaScript hooks via `@wordpress/hooks` (`wp.hooks`) when a gate is revealed. These hooks are only available if `wp.hooks` is present on the page (standard on any WordPress site using block editor scripts).

`matture.beforeReveal`

Fires before a gate transitions to the revealed state. Receives the gate DOM element and the mode string.

    wp.hooks.addAction( 'matture.beforeReveal', 'my-plugin', function( gateElement, mode ) {
        console.log( 'About to reveal:', mode, gateElement );
    } );

`matture.afterReveal`

Fires after a gate has been revealed. Receives the gate DOM element and the mode string.

    wp.hooks.addAction( 'matture.afterReveal', 'my-plugin', function( gateElement, mode ) {
        // Track the reveal event.
        if ( typeof gtag !== 'undefined' ) {
            gtag( 'event', 'matture_reveal', { mode: mode } );
        }
    } );

**REST API**

`GET /wp-json/matture/v1/status/{block_id}`

Returns the server-side status of a content gate block. The `state` field is always `hidden` server-side; use the `matture_ai_classify_content` filter to enrich the response.

Example response:

    {
        "block_id": "my-block-id",
        "mode": "",
        "state": "hidden",
        "timestamp": "2026-01-01T00:00:00+00:00"
    }

**Data Attributes**

Every rendered gate element exposes the following data attributes for JavaScript targeting:

* `data-matture-mode` — the active mode slug (e.g. `nsfw`, `mature`)
* `data-matture-state` — current visibility state (`hidden` or `revealed`)
* `data-mode` — alias for `data-matture-mode`
* `data-allow-rehide` — `1` if re-hide is enabled, `0` otherwise
* `data-blur` — blur intensity in pixels (used by NSFW and Spoiler modes)
* `data-remember` — `1` if localStorage persistence is enabled

== Installation ==

1. Upload the `matture` folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress under Plugins.
3. In the block editor, search for "Matture" and add the block.
4. Choose a reveal mode and drop any content inside.

== Frequently Asked Questions ==

= Which reveal mode should I use? =

Use **NSFW** for adult content requiring the strongest gate, **Spoiler** for plot reveals or game content, **Trigger Warning** for sensitive topics with custom messaging, and **Mature** for a Facebook-style confirm flow.

= Can I customise the overlay colours? =

Yes. All colours are CSS custom properties. Override `--matture-overlay-bg`, `--matture-button-bg`, etc. in your theme stylesheet or Additional CSS.

= Can I add my own reveal mode? =

Yes, via the `matture_modes` PHP filter. See the plugin source for the mode definition format.

= Does it use jQuery? =

No. Frontend is vanilla JavaScript only.

= Does it remember the visitor's reveal choice? =

Optionally. Enable the "Remember reveal" toggle on the block and it will use localStorage to remember the state per visitor per block.

= Does it work with any theme? =

Yes. Matture has no theme dependencies. It uses standard block markup and CSS custom properties, so it integrates cleanly with any block-based or classic theme.

= Will it slow my site down? =

Matture loads a small CSS file and a small vanilla JS file on the frontend — only on pages where the block is used. No external requests, no trackers, no jQuery.

== Screenshots ==

1. NSFW mode — heavy blur with warning badge
2. Mature mode — dark overlay with confirm button
3. Spoiler mode — light blur, Discord aesthetic
4. Trigger Warning mode — soft grey overlay with custom text
5. Block inspector controls in the editor

== Changelog ==

= 1.0.0 =
* Initial release — four reveal modes, CSS custom properties, PHP hooks/filters, REST status endpoint.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
