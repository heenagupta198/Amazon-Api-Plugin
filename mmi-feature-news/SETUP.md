# Feature News — static layout (no scrolling)

## Install from ZIP

1. Download **`mmi-feature-news-v1.0.0.zip`** (from this Cursor agent run artifacts, or from GitHub — see below).
2. WordPress admin → **Plugins → Add New → Upload Plugin**.
3. Choose the zip → **Install Now** → **Activate**.
4. **Settings → MMI Feature News** — confirm category `feature`, desktop `4`, mobile `1`.
5. TagDiv Composer on homepage → add **Shortcode** block: `[mmi_feature_news]`
6. Enable **Hide legacy block** (class `tdi_59`) or remove the old Feature module.
7. Clear WP Rocket + Tagdiv cache.

## Shortcode

`[mmi_feature_news]`

## GitHub (next time)

Repository: https://github.com/heenagupta198/Amazon-Api-Plugin

After the plugin is merged, download:

- **Whole plugin folder:** https://github.com/heenagupta198/Amazon-Api-Plugin/tree/main/mmi-feature-news  
- **ZIP of main branch:** https://github.com/heenagupta198/Amazon-Api-Plugin/archive/refs/heads/main.zip  

Then upload only the `mmi-feature-news` folder via FTP to `wp-content/plugins/`, or zip that folder and use **Upload Plugin**.
