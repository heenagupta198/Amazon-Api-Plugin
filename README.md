# Amazon-Api-Plugin

## MMI Content Slider (`mmi-content-slider/`)

WordPress multisite plugin for [MyMobileIndia](https://www.mymobileindia.com/):

- **Admin (main site):** search and multi-select device products when editing news posts.
- **Frontend (Devices site):** show a **Related Articles** carousel on linked product pages, immediately above ReHub **Related Products**.

v1.7.0: news REST now matches integer product IDs stored in `_mmi_linked_product_ids` (previously the lookup required quoted strings, so `/news-by-product/{id}` returned empty). The devices product page always mounts the carousel and loads articles from the news REST API if server-side fetch is empty.

Activate **MMI Content Slider 1.7.0** on both WordPress installs (English news + `/devices/`). Download: `mmi-content-slider-v1.7.0.zip`.

See `mmi-content-slider/readme.txt` for installation.
