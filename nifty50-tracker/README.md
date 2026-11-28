# Yogesh Traderdomina

**Premium** Nifty 50 contribution tracker — subscription-only access (no public dashboard).  
Powered by **DhanHQ** live quotes and volume.

Deploy: **[DEPLOY-XAMPP-CPANEL.md](./DEPLOY-XAMPP-CPANEL.md)** (XAMPP + cPanel step-by-step).

## Stack

| Layer | Technology |
| --- | --- |
| Frontend | React + Vite + Tailwind CSS |
| Backend | Laravel 13 (PHP 8.3+) |
| Database | MySQL (production) / SQLite (local default) |
| Market data | [DhanHQ API v2](https://dhanhq.co/docs/v2/) |

## Phase 1 (this repo state)

- Laravel API: Dhan profile test, constituent import, OHLC refresh, contribution engine
- React dashboard UI (main dashboard, all-stocks table, admin status)
- Artisan: `dhan:test`, `nifty:import-constituents`

## Quick start (local)

### 1. Backend

```bash
cd backend
cp .env.example .env   # if fresh clone
php artisan migrate
php artisan nifty:import-constituents
```

Add Dhan credentials to `backend/.env`:

```env
DHAN_ACCESS_TOKEN=your_jwt_from_web_dhan
DHAN_CLIENT_ID=your_client_id
```

```bash
php artisan dhan:test
php artisan serve
```

### 2. Frontend

```bash
cd frontend
npm install
npm run dev
```

Open http://localhost:5173 — Vite proxies `/api` to http://127.0.0.1:8000.

### 3. Live refresh

Dashboard **Refresh quotes** calls `POST /api/market/refresh`, which pulls **NSE_EQ OHLC** for all mapped constituents and applies:

```text
points ≈ previous_index_value × (weightage% / 100) × (change% / 100)
```

Official Nifty free-float methodology + divisor reconciliation is planned for a later phase.

## MySQL (XAMPP / production)

In `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=nifty50_tracker
DB_USERNAME=root
DB_PASSWORD=
```

## API routes

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/api/dhan/connection-test` | Verify token / data plan |
| GET | `/api/market/dashboard` | Dashboard payload |
| POST | `/api/market/refresh` | Pull Dhan quotes + recalculate |
| POST | `/api/admin/import-constituents` | NSE list + Dhan security IDs |

## Development phases (roadmap)

1. ✅ Monorepo setup + Dhan test path + contribution engine  
2. Weightage import from Nifty Indices factsheet  
3. WebSocket live feed + TradingView charts  
4. Historical snapshots + CSV export  
5. AWS EC2 + Nginx deployment  

## Data requirements

- **Data API plan** on Dhan (`dataPlan: Active` on `/profile`) for live quotes  
- Constituents: [NSE Nifty 50 CSV](https://nsearchives.nseindia.com/content/indices/ind_nifty50list.csv)  
- Security IDs: [Dhan scrip master](https://images.dhan.co/api-data/api-scrip-master.csv) (updated ~08:30 IST daily)