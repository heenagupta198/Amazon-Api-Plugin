=== MMI Content Slider ===
Contributors: mymobileindia
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Link news articles on the main site to device products and show a Latest News carousel on WooCommerce product pages (multisite /devices/).

== Installation ==

1. Upload the `mmi-content-slider` folder to `wp-content/plugins/` on the network (or both sites).
2. Network-activate the plugin (recommended) or activate on the main news site and the Devices subsite.
3. Edit a news post on the main site — use the **Linked Device Products** box in the sidebar (below other meta boxes such as AMP).
4. Search by product name or numeric ID, add one or more devices, and publish.
5. Open that product on `https://www.mymobileindia.com/devices/` — **Latest News** appears after specifications and before **Related Products**.

== Notes ==

* Product IDs are from the Devices subsite WooCommerce catalog (`post_type` product).
* Blog IDs auto-detect: main site = news, path `/devices/` = products. Override with network options `mmi_csl_devices_blog_id` and `mmi_csl_news_blog_id` if needed.
