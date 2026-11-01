# ReHub `/devices/` — Feature sections from English site

## Why it was blank

`/devices/` is a **separate WordPress shop** (ReHub + Elementor). It does **not** have the English **Feature** posts.  
Version **1.0.0** only queried local posts → **empty shortcode** (`[...]` in Elementor).

**v1.1.0** loads posts from **https://www.mymobileindia.com** (category **Feature**, ID **9**) via REST API.

---

## Install v1.1.0

1. Download: https://github.com/heenagupta198/Amazon-Api-Plugin/raw/cursor/feature-news-static-grid-3fd9/mmi-feature-news-v1.1.0.zip  
   (or replace plugin folder after pull)
2. **Plugins → deactivate → delete old MMI Feature News → Upload new zip → Activate**
3. **Settings → MMI Feature News**:
   - **Fetch from English site** ✓
   - **News source URL:** `https://www.mymobileindia.com`
   - **Remote category ID:** `9`
   - **View All URL:** `https://www.mymobileindia.com/feature/`
4. **Elementor** on https://www.mymobileindia.com/devices/ homepage:

| Block (top → bottom) | Shortcode widget content |
|----------------------|---------------------------|
| **Featured** (big + 3 small) | `[mmi_feature_hero]` |
| **Featured News** (4 cards) | `[mmi_feature_news title="Featured News"]` |

5. **Update** page → clear **WP Rocket** cache.

---

## Layout rules (your mockups)

| Section | Desktop | Mobile |
|---------|---------|--------|
| `[mmi_feature_hero]` | 1 large + 3 side posts, **no slider arrows** | **Only latest 1** + View All |
| `[mmi_feature_news]` | **4** cards + View All | **1** card + View All |

Links open on **English site** (correct for Feature articles).

Optional: avoid duplicate stories — use `[mmi_feature_news offset="4"]` to show the next 4 after the hero.

---

## Elementor still empty?

- Confirm plugin **v1.1.0** (Plugins list)
- Settings saved with **Use remote news** enabled
- Server can reach `https://www.mymobileindia.com/wp-json/` (host firewall)
- Logged-in admin may see a red dashed notice if API fails
