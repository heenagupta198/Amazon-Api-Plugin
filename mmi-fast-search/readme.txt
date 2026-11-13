=== MMI Fast Frontend Search ===
Contributors: mymobileindia
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Instant, lightweight search overlay for tagDiv / Newspaper theme headers.

== Why ==
tagDiv live AJAX search hits PHP + MySQL on every keystroke — slow and heavy on CPU/RAM.

This plugin builds **one JSON file** (static index) and searches **in the browser**. Typing does not load the server.

== Install ==
1. Upload the `mmi-fast-search` folder to `wp-content/plugins/` (or install the zip).
2. Activate **MMI Fast Frontend Search**.
3. Go to **Settings → MMI Fast Search** and click **Rebuild index now** once.
4. Clear WP Rocket / CDN cache for CSS/JS if you use a cache plugin.

== Replace tagDiv search ==
Enabled by default (`Replace tagDiv search`):
* Hides the default tagDiv dropdown.
* Clicks on the header magnifying glass open the MMI overlay instead.
* Heavy tagDiv search scripts are dequeued when possible.

Manual open: `MmiFastSearchOpen()` in browser console, or **Ctrl/Cmd + K**.

== Tuning ==
* **Max posts** — default 4000 newest articles (adjust if the JSON is too large).
* **Last N days** — limit index to recent posts only (lighter JSON).
* **Preload index** — faster first open; slightly more bandwidth on each page load.

== Index updates ==
* Rebuild on publish/update (once per request).
* Daily WP-Cron rebuild.
* Manual rebuild from settings.

Index file: `wp-content/uploads/mmi-fast-search/search-*.json` (public titles/URLs only).
