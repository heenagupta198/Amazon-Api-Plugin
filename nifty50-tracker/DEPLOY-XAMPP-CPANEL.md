# Yogesh Traderdomina — XAMPP (localhost) + cPanel (live server)

Premium app: **public access off** — only admin + paid subscribers can log in.

---

## Direct code links (GitHub)

Branch: `cursor/nifty50-tracker-b53b` · Repo: `heenagupta198/Amazon-Api-Plugin`

| Item | Link |
| --- | --- |
| Main README | [nifty50-tracker/README.md](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/README.md) |
| Dashboard (Top 5 + volume UI) | [frontend/src/pages/Dashboard.jsx](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/frontend/src/pages/Dashboard.jsx) |
| Top 5 Nifty block | [frontend/src/components/TopFivePremium.jsx](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/frontend/src/components/TopFivePremium.jsx) |
| Login (premium gate) | [frontend/src/pages/Login.jsx](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/frontend/src/pages/Login.jsx) |
| Auth API | [backend/routes/api.php](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/backend/routes/api.php) |
| Volume + quotes | [backend/app/Services/MarketFeedService.php](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/backend/app/Services/MarketFeedService.php) |

---

## A. XAMPP on Windows (localhost)

### 1. Copy project

1. Clone or download folder `nifty50-tracker` into e.g. `C:\xampp\htdocs\yogesh-traderdomina\`
2. Backend path: `C:\xampp\htdocs\yogesh-traderdomina\backend\`
3. Frontend path: `C:\xampp\htdocs\yogesh-traderdomina\frontend\`

### 2. MySQL database

1. Open **phpMyAdmin** → Create database: `traderdomina`
2. Edit `backend\.env`:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_BRAND_NAME="Yogesh Traderdomina"

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=traderdomina
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173

DHAN_ACCESS_TOKEN=your_token
DHAN_CLIENT_ID=your_client_id
```

3. In `backend` folder (PowerShell / CMD):

```bat
composer install
php artisan key:generate
php artisan migrate
php artisan nifty:import-constituents
php artisan traderdomina:create-admin --email=yogesh@yourdomain.com --password=YourStr0ngPass12!
php artisan serve
```

### 3. Frontend (Vite)

```bat
cd frontend
npm install
npm run dev
```

Open: **http://localhost:5173** → Login page (no public dashboard).

### 4. Create paying users (only you as admin)

Login as admin → **Admin** tab → create subscriber with subscription days.

---

## B. cPanel shared hosting (live)

Recommended split:

| URL | What to host |
| --- | --- |
| `https://traderdomina.yourdomain.com` | React build (`frontend/dist`) |
| `https://api.yourdomain.com` | Laravel `backend/public` only |

### 1. Upload backend

1. Zip `backend` (without `vendor/` — upload smaller, run composer on server if SSH exists)
2. cPanel → **Subdomain** `api` → document root: `.../api/public`
3. Put Laravel files **one level above** `public` (standard Laravel layout)
4. Point subdomain document root to `backend/public`

### 2. MySQL on cPanel

1. Create DB + user in cPanel MySQL
2. `.env` production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=cpanel_dbname
DB_USERNAME=cpanel_user
DB_PASSWORD=***
FRONTEND_URL=https://traderdomina.yourdomain.com
```

3. SSH (if available):

```bash
cd ~/api
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan traderdomina:create-admin
```

No SSH? Use **Terminal** in cPanel or run migrations locally and import SQL (advanced).

### 3. Build & upload frontend

On your PC:

```bash
cd frontend
echo VITE_API_BASE=https://api.yourdomain.com/api > .env.production
npm run build
```

Upload contents of `frontend/dist/` to main domain `public_html/` (or subdomain folder).

**SPA routing** — create `public_html/.htaccess`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

### 4. HTTPS (required for security)

cPanel → **SSL/TLS** → AutoSSL / Let’s Encrypt for both domains.

### 5. Lock down public access

- No register route — only admin creates subscribers
- All `/api/market/*` behind `auth:sanctum` + subscription
- Set `APP_DEBUG=false` on production
- Strong admin password (12+ chars, mixed case, numbers)

---

## Security checklist (hacker-resistant basics)

| Control | Status in code |
| --- | --- |
| No public signup | `allow_public_registration` false |
| Bearer token auth (Sanctum) | Login → token; logout revokes |
| Subscription expiry | Subscribers blocked after date |
| Admin full access | `is_admin` bypasses expiry |
| Login rate limit | 5/min per IP + email |
| API throttle | Laravel default + refresh 10/min |
| Security headers | X-Frame-Options, nosniff, CSP (API) |
| Dhan keys only in `.env` | Never in frontend |

**Also do on server:** firewall, fail2ban (VPS), cPanel 2FA on your account, regular backups, rotate Dhan token daily.

---

## Daily operations

1. Refresh Dhan token at web.dhan.co (24h)
2. Login as admin → **Refresh quotes** on dashboard
3. Extend subscriber days from Admin when they pay

Questions / next phases: official Nifty weightage CSV, Razorpay subscription payments, WebSocket live feed.