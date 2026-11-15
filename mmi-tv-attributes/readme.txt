=== MMI TV Attributes ===
Contributors: mmi
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0

Creates a separate WooCommerce attribute set for TVs. Existing mobile attributes are not changed.

== Install ==

1. Upload the `mmi-tv-attributes` folder to `wp-content/plugins/` on the Devices (WooCommerce) site.
2. Activate **MMI TV Attributes**.
3. Open **WooCommerce → TV Attributes** and click **Create / update TV attributes** (safe to run again).
4. Confirm attributes under **Products → Attributes**. Names look like Screen Size / Resolution; taxonomies are `pa_tv-screen-size`, `pa_tv-resolution`, etc.

== Use on a TV product ==

1. Set product category to **TVs**.
2. Product data → Attributes → add TV attributes (do not reuse mobile RAM / camera attributes).
3. Choose values, tick **Visible on the product page**, Save attributes, Update.

The product Additional information tab then uses the same grouped spec table markup as mobiles (General, Display, Picture, Sound, Smart Features, Connectivity, Design, Power).

== Notes ==

* Mobile attributes stay untouched.
* TV slugs all start with `tv-` so filters never mix 6.5" phones with 43" TVs.
* Deactivating the plugin does not delete attributes or terms (products keep their specs).
