=== MMI AMP Performance (Core Web Vitals) ===
Contributors: mmi
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Improves LCP on legacy article /amp/ URLs and 301-redirects home, archive, search, and paged AMP URLs to the canonical non-AMP page in one hop.

== Description ==

Use when Search Console shows "LCP longer than 4s" mainly on `/amp/` URLs while canonical mobile pages score well.

* Preloads the featured image with `fetchpriority=high` (AMP head only)
* Serves smaller responsive srcset for hero images on AMP
* Adds `data-hero` for AMP optimizer
* Optional: disables AMP auto-lightbox sanitizer (can delay hero paint)
* Sends `Cache-Control` on AMP HTML for logged-out visitors
* **301** home, category, search, and paged AMP URLs (`/tech-news/amp/`, `?amp=1`, `/section/page/2/amp/`) to the matching non-AMP URL (article AMP documents are unchanged)

Does **not** enqueue scripts, change theme templates, or alter non-AMP HTML (except the redirect responses above).

== Installation ==

1. Upload `mmi-amp-performance` to `wp-content/plugins/`
2. Activate after your AMP plugin (Official AMP or AMP for WP)
3. Purge all caches (LiteSpeed / Cloudflare / object cache)
4. Re-test one `/amp/` URL in PageSpeed Insights (mobile)

== Server checklist (required for ~1.9k URLs) ==

* Full-page cache **only** for `/amp/*` or AMP endpoint
* CDN cache for `wp-content/uploads` (WebP if possible)
* In AMP plugin settings: disable unused components (forms, anim, lightbox gallery) on posts
* Keep canonical URLs as your fast non-AMP mobile pages (already good)

== Changelog ==

= 1.1.0 =
* One-hop 301 for non-article AMP URLs (home, archives, search, paged `/page/N/amp/`)
* Filter `mmi_amp_perf_redirect_non_singular` to disable redirects; `mmi_amp_perf_redirect_status` for status code

= 1.0.1 =
* AMP for WP: use amp_post_template_head only; prevent double hook registration
* LCP preload prefers large/medium_large (not full 1200px when smaller exists)

= 1.0.0 =
* Initial release
