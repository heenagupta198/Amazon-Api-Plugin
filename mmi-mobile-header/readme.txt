MMI Mobile Header (News) v1.2.2
================================

English news site ONLY (www.mymobileindia.com). Not for /devices/.

HOMEPAGE STILL WRONG AFTER CLEAR CACHE?
---------------------------------------
WP Rocket was saving an EMPTY <style id="mmi-news-mobile-header"></style> on the
cached homepage. v1.2.2 uses only mobile-header.css (no inline CSS).

Also: homepage TagDiv columns use width 64%/18% — v1.2.2 overrides those on mobile.

AFTER INSTALL OR UPDATE
-----------------------
1. WP Rocket → Clear cache → "Clear and preload" (include MOBILE cache).
2. If Cloudflare is on: Purge Everything once.
3. Test in Chrome Incognito on phone (not logged in to WordPress).

Install: Upload mmi-mobile-header.zip → Activate.

Lightweight: one small CSS file, no JavaScript, no database.
