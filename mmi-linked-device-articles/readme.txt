=== MMI Linked Device Articles ===
Contributors: mymobileindia
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Link English news articles to device products. Show a Related Articles carousel on the Devices product page, just above ReHub Related Products.

== Installation (BOTH WordPress sites) ==

News and Devices are separate WordPress installs. Upload this zip on **both**. Do not replace MMI Content Slider — this plugin has a different name.

1. English news site: https://www.mymobileindia.com/
   - Plugins → Add New → Upload Plugin → mmi-linked-device-articles-v1.0.0.zip → Activate.
   - This is where **Linked Device Products** is saved on articles, and where the articles API lives.

2. Devices site: https://www.mymobileindia.com/devices/
   - Plugins → Add New → Upload Plugin → same zip → Activate.
   - Keep your existing **MMI Content Slider** plugin. Do not delete it.
   - This is where the Related Articles carousel prints above Related Products.

3. You do not need to re-link products. Existing Linked Device Products data is reused.

4. Test: open a phone product page (example iPhone 14 256GB). Related Articles should appear just above Related Products.

== Notes ==

* Plugin folder: `mmi-linked-device-articles` (will not overwrite `mmi-content-slider`).
* Product IDs are from the Devices WooCommerce catalog.
* Default URLs: news = https://www.mymobileindia.com , devices = https://www.mymobileindia.com/devices
* Settings → MMI Linked Device Articles if you need to change those URLs.
