# AMP for WP + WP Rocket (mymobileindia.com)

## WP Rocket (non-AMP stays as-is)

WP Rocket **does not** run minify/delay/lazyload on AMP HTML (by design). Page cache **can** still serve `/amp/` URLs when compatibility is on (AMP for WP ships this).

Check in WP Rocket:

1. **Advanced rules → Never cache URL(s)** — `/amp` must **not** be listed.
2. **Clear cache** after any AMP change.
3. View one `/amp/` page source — you should see `Performance optimized by WP Rocket` in HTML comments (already present on your site).

If AMP TTFB is still ~2–3s, use **server/page cache for `/amp/`** (LiteSpeed/Cloudflare) — zero extra MariaDB load when cached.

## AMP for WP settings (biggest LCP win, no custom code)

Your live AMP head loads **6+ extension scripts before content**:

- `amp-form`, `amp-image-lightbox`, `amp-addthis`, `amp-bind`, `amp-web-push`, `amp-user-notification`

Each script delays hero image work. Turn these **off** in AMP for WP unless you truly use them:

| Feature | Typical menu |
|--------|----------------|
| Web Push | AMP → Options → Push Notifications |
| AddThis | AMP → Social / Sharing |
| GDPR / cookie bar | AMP → GDPR / User notification |
| Image lightbox | AMP → Design / Single / Lightbox |
| Contact form AMP | AMP → Contact Form (if unused) |

**Design:** Swift is fine; avoid extra modules on **Single Post** template.

## Schema plugin on AMP

Your AMP HTML includes a **very large** Schema & Structured Data JSON block (full `articleBody`). That increases HTML size and generation time.

In **Schema & Structured Data for WP** (or Yoast), if there is an option to **disable schema on AMP** or shorten output, enable it for AMP only. Non-AMP pages keep full schema.

## Install `mmi-amp-performance`

- Zero cron, zero options table, **one** thumbnail read per AMP post view (same as theme).
- Activates only on `/amp/` requests.
