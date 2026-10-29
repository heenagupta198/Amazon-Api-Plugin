MMI Mobile Header (News) v1.4.0
================================

Fix: Works on HOME + all CATEGORIES + articles (not only single posts).

Problem was WP Rocket Used CSS on homepage left an EMPTY
<style id="mmi-news-mobile-header"></style> and removed mobile @media rules.

v1.4.0 injects CSS via rocket_buffer + RUCSS safelist + clears Used CSS on activate.

Install: delete old plugin → upload zip → activate → WP Rocket:
  - Clear cache
  - Clear Used CSS / Remove Unused CSS (if button exists)
  - Preload

Single file plugin — no mobile-header.css URL.
