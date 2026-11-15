=== MMI Mobile List ===
Contributors: mymobile
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.3.0
License: GPLv2 or later

91mobiles-style mobile spec cards for MyMobileIndia Devices (WooCommerce + ReHub).

== Description ==

* **PAF pages:** shortcode `[mmi_mobile_list]` (same as under-₹20,000 pages).
* **Archives:** Best Mobiles, Latest Mobiles, Upcoming Mobiles, and brand (`product_brand`) listings.
* Specs read from WooCommerce attributes: `pa_processor`, `pa_ram`, `pa_internal-memory`, cameras, battery, display, etc.

== Installation ==

1. Upload zip via **Plugins → Add New → Upload**.
2. Activate on the **/devices/** site.
3. Open **Settings → MMI Mobile List** — enable archives and use **Download ZIP**.

== Shortcode examples ==

`[mmi_mobile_list price_range="rs-10000-rs-20000" per_page="5" paginate="1"]`

`[mmi_mobile_list category="upcoming-mobiles"]`

== Changelog ==

= 1.3.0 =
* Archive spec layout for best-mobiles, latest-mobiles, upcoming-mobiles, and product_brand.
* Admin download ZIP button + GitHub link.
* Settings: per page, release date toggle.

= 1.2.1 =
* Live site PAF card layout (baseline).