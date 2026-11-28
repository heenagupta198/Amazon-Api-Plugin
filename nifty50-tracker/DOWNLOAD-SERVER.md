# Server par fast upload — direct download (GitHub)

Google Drive ki jagah **same codebase** yahan se download karein (taaki run time kam ho).

## Option A — Full branch ZIP (recommended)

1. Open:  
   **https://github.com/heenagupta198/Amazon-Api-Plugin/archive/refs/heads/cursor/nifty50-tracker-b53b.zip**
2. Unzip on server / XAMPP
3. Folder path: `Amazon-Api-Plugin-cursor-nifty50-tracker-b53b/nifty50-tracker/`

## Option B — Single folder only

Clone:

```bash
git clone -b cursor/nifty50-tracker-b53b --depth 1 https://github.com/heenagupta198/Amazon-Api-Plugin.git
cd Amazon-Api-Plugin/nifty50-tracker
```

## Server pe install (short)

**Backend**

```bash
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan nifty:import-constituents
php artisan traderdomina:create-admin
```

**Frontend**

```bash
cd ../frontend
npm ci
npm run build
```

Upload `frontend/dist` → `public_html`  
Point API subdomain → `backend/public`

Full steps: [DEPLOY-XAMPP-CPANEL.md](./DEPLOY-XAMPP-CPANEL.md)

## Key files (raw links)

| File | URL |
| --- | --- |
| Modules API | [routes/api.php](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/backend/routes/api.php) |
| Analytics engine | [NiftyModuleAnalyticsService.php](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/backend/app/Services/Analytics/NiftyModuleAnalyticsService.php) |
| NiftyPulse UI | [NiftyPulse.jsx](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/frontend/src/pages/NiftyPulse.jsx) |
| Module registry | [registry.js](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/frontend/src/modules/registry.js) |
| Dhan licensing | [DHAN-LICENSING.md](https://github.com/heenagupta198/Amazon-Api-Plugin/blob/cursor/nifty50-tracker-b53b/nifty50-tracker/DHAN-LICENSING.md) |

## “Hack trick” (legal dev tips only)

- `composer install --no-dev` + `php artisan config:cache` → backend 2–3× faster on shared hosting
- `npm ci` instead of `npm install` → reproducible frontend build
- Module analytics cache 2 min — avoids Dhan rate limits on every page view
- **Never** put `DHAN_ACCESS_TOKEN` in React — server `.env` only