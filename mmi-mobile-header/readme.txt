MMI Mobile Header (News) v1.2.1
================================

English news site ONLY (www.mymobileindia.com). Not for /devices/.

WHY ADMIN SEES FIX BUT PHONE (LOGGED OUT) DOES NOT
-------------------------------------------------
WP Rocket (and similar) do not cache pages for logged-in admins.
Guests and your phone (logged out) get OLD cached HTML until you purge cache.

AFTER INSTALL OR UPDATE
-----------------------
1. WP Rocket → Clear cache → "Clear and preload" (include MOBILE cache).
2. If Cloudflare is on: Purge Everything once.
3. Test in Chrome Incognito on phone (not logged in to WordPress).

Install: Upload mmi-mobile-header.zip → Activate.

Lightweight: one small CSS file, no JavaScript, no database.
