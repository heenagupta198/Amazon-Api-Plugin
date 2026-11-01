# ReHub `/devices/` — v1.2.0

## Download

**[mmi-feature-news-v1.2.0.zip](https://github.com/heenagupta198/Amazon-Api-Plugin/raw/cursor/feature-news-static-grid-3fd9/mmi-feature-news-v1.2.0.zip)**

## What changed in v1.2.0

| Item | Fix |
|------|-----|
| Content | **Tech News** from [mymobileindia.com/tech-news/](https://www.mymobileindia.com/tech-news/) (category ID **10**) |
| Hero heading | **No “Featured” title** and **no View All** on top block (default) |
| Grid heading | **“Feature”** only — not “Feature News” |
| Desktop layout | Left + right **equal height**, less white gap |
| Mobile | Side posts show as **horizontal swipe slider** under main story |

## Elementor shortcodes (devices homepage)

1. **Tech news block (top):** `[mmi_feature_hero]` or `[mmi_tech_news_hero]`  
   — Do **not** add `title="Featured"` unless you want a heading.

2. **Feature grid (below):** `[mmi_feature_news]`  
   — Title comes from settings (**Feature**). View All → tech-news archive.

After upload: **Plugins → deactivate → activate** (runs v1.2 settings upgrade) → **Settings → MMI Feature News → Save** → clear WP Rocket cache.

## If old titles still show

Open **Settings → MMI Feature News** and set:

- Category slug: `tech-news`  
- Remote category ID: `10`  
- View All URL: `https://www.mymobileindia.com/tech-news/`  
- Grid title: `Feature`  
- Hero title: *(empty)*  
- Uncheck hero header / View All  
