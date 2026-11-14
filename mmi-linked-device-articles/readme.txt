=== MMI Linked Device Articles ===
Contributors: mymobileindia
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Link English news articles to device products. Show Related Articles on the Devices product page, just above ReHub Related Products.

== Installation (BOTH WordPress sites) ==

Upload the same zip on both sites. Do not replace MMI Content Slider on Devices.

1. English news https://www.mymobileindia.com/ — Upload + Activate MMI Linked Device Articles 1.1.0.
   If MMI Content Slider is also active here, deactivate it on the news site only (it can erase linked products on Update). Keep Content Slider on Devices.
2. Devices https://www.mymobileindia.com/devices/ — Upload + Activate the same zip. Keep MMI Content Slider.
3. Open an article, add phones in Linked Device Products. Links save immediately (you do not have to rely on Update).
4. Open that phone on Devices. Related Articles appears just above Related Products.

If a previous update emptied the linked list, add the phones again once. After 1.1.0 they will stay saved.

== Changelog ==

= 1.1.0 =
* Stop Update from wiping Linked Device Products (Gutenberg / empty POST / old Content Slider).
* Save links immediately via AJAX when you add, remove, or reorder.
* Keep showing product IDs even if Devices REST labels fail.
* Own meta key plus legacy `_mmi_linked_product_ids` so existing links still count.
