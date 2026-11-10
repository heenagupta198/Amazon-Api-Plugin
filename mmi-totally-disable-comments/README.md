# MMI Totally Disable Comments (v1.0.0)

Lightweight WordPress plugin — **single PHP file**, no settings screen, no cron, no front-end requests to external APIs.

## Why spam still arrives after “Discussion → uncheck comments”

WordPress only applies that checkbox to **new** posts. Your older news articles (iPhone, 5G phones, etc.) often still have **Comments: Open** per post. Bots do not use the theme form only; they POST directly to:

| Entry point | File / URL |
|-------------|------------|
| Classic form handler | `wp-comments-post.php` (root of WordPress) |
| REST API | `/wp-json/wp/v2/comments` |
| XML-RPC (older bots) | `xmlrpc.php` → `wp.newComment` |

Newspaper / tagDiv themes can still show comment UI unless comment support is removed from post types — this plugin does that.

## Install

1. Download `mmi-totally-disable-comments-v1.0.0.zip` from this repo.
2. WordPress admin → **Plugins → Add New → Upload Plugin** → activate.
3. On activation, all posts/pages are set to **closed** for comments and pingbacks.

## Extra CPU savings (recommended)

Plugin blocks spam in PHP, but each bot hit may still **boot WordPress**. For high traffic, add a server rule **above** WordPress rules:

**Apache / LiteSpeed (.htaccess in WordPress root):**

```apache
<Files "wp-comments-post.php">
  Require all denied
</Files>
```

**Nginx:**

```nginx
location = /wp-comments-post.php {
  return 403;
}
```

Optional: block `xmlrpc.php` if you do not use the mobile app or Jetpack XML-RPC:

```apache
<Files "xmlrpc.php">
  Require all denied
</Files>
```

## After install

1. **Settings → Discussion**: keep “Allow comments on new posts” unchecked (plugin enforces anyway).
2. Delete existing spam: **Comments** screen may be hidden; use **Tools** or temporarily deactivate plugin, bulk-delete spam, reactivate.
3. Consider **Cloudflare** (or host firewall) rate-limit on `wp-comments-post.php` and `xmlrpc.php`.

## Uninstall

Deactivate and delete plugin. Comment status in the database is not reverted (posts stay closed).
