=== MMI Related Articles ===
Contributors: mymobile
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later

Manual related-article picker with AJAX search, date priority (7/15 days), and frontend carousel for Newspaper (TagDiv) themes.

== Installation ==

1. Upload the `mmi-related-articles` folder to `/wp-content/plugins/`.
2. Activate **MMI Related Articles** in WordPress admin → Plugins.
3. Edit any post — use the **Related Articles** box in the sidebar (near Publish).
4. Search (e.g. "OnePlus"), pick articles, drag to reorder, click **Update**.

== Frontend ==

Selected articles appear automatically after the article on single posts (TagDiv `td_global_after_content`, with fallbacks). No shortcode or theme file edits required.

If Newspaper also shows its own "Related Articles" block, disable that module in Theme Panel → Single Post to avoid duplicates, or use the `mmi_ra_hide_theme_related_posts` filter.

== Performance ==

- Admin search uses AJAX with max 20 results and `no_found_rows`.
- Search matches **post title only** (not post content), so brand searches stay accurate.
- Frontend loads only saved post IDs (no full-site search on page view).
