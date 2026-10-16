# MMI Mobile Price List

WooCommerce shortcode for 91mobiles-style price band pages.

## Price rules

| Page | Shortcode | Phones shown (active WooCommerce price) |
|------|-----------|----------------------------------------|
| Under ₹10,000 | `[mmi_mobile_list max_price="10000"]` or `bucket="under-10000"` | ₹1 – ₹9,999 |
| ₹10k – ₹15k | `[mmi_mobile_list min_price="10000" max_price="15000"]` or `bucket="under-15000"` | ₹10,000 – ₹14,999 |
| ₹15k – ₹20k | `min_price="15000" max_price="20000"` or `bucket="under-20000"` | ₹15,000 – ₹19,999 |
| ₹20k – ₹25k | `min_price="20000" max_price="25000"` or `bucket="under-25000"` | ₹20,000 – ₹24,999 |
| ₹25k – ₹30k | `min_price="25000" max_price="30000"` or `bucket="under-30000"` | ₹25,000 – ₹29,999 |

`max_price` values are **bucket ceilings** (15000 means up to ₹14,999). Use `exact="yes"` for literal min/max.

If you only pass `min_price="10000"` (no max), the plugin assumes `max_price="15000"` for the 10k–15k band.

## Category & brand

```
[mmi_mobile_list max_price="10000" category="best-mobiles"]
[mmi_mobile_list max_price="20000" brand="vivo"]
[mmi_mobile_list min_price="10000" max_price="11000" brand="vivo"]
```

`brand` uses `product_brand`, `pa_brand`, or other common brand taxonomies if present.

## Layout (91mobiles-style)

- Title + release date above the card
- Image left, specs right, **⋮** menu with **All Details** → product page
- **View All Specs** → same product URL (use `details_anchor="specifications"` if your product template has that ID)

## Performance (low CPU / MariaDB)

- **Full HTML cache** per shortcode + page (default **6 hours**) — repeat visitors do **not** re-run product queries
- Product listing data cached separately in transients + object cache group
- Cache clears on product save (debounced 60s during bulk updates)
- **Settings → MMI Mobile List** — change TTL or purge cache manually
- Uses `wc_get_products()` (price lookup table) only on cache miss

## Backend settings

**Settings → MMI Mobile List**

- **Products per page** — default **10** (91mobiles-style lists)
- Cache duration + manual purge

## Upcoming mobiles (no regular price)

```
[mmi_mobile_list list="upcoming"]
[mmi_mobile_list bucket="upcoming" category="upcoming-mobiles"]
```

## Download

Use `mmi-mobile-list.zip` in the repository root.
