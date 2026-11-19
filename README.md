# App Bengkel

Aplikasi antrian dan servis bengkel. Backend Laravel 12 menyediakan REST API Sanctum; frontend Vue 3 + Vite menyediakan panel staf dan portal customer publik.

## Setup

```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Atur `DB_*`, `FRONTEND_URL`, dan `MAIL_MAILER=log` pada `backend/.env`. Untuk demo lokal, SQLite tersedia melalui `database/database.sqlite`.

```bash
cd frontend
cp .env.example .env
/opt/homebrew/bin/npm install
/opt/homebrew/bin/npm run dev
```

Set `VITE_API_URL=http://127.0.0.1:8000/api` pada `frontend/.env`. Frontend produksi dibangun dengan `/opt/homebrew/bin/npm run build` dan diarahkan ke `frontend/dist`.

## Akun demo

| Role | Email | Password |
|---|---|---|
| Admin | `admin@bengkel.test` | `password` |
| Kasir | `kasir1@bengkel.test` | `password` |
| Kasir | `kasir2@bengkel.test` | `password` |

Kode publik: `TRACK234`, `BKNG2345`.

## Arsitektur

Vue memanggil API Laravel melalui Axios. Sanctum menangani token dan middleware role membatasi endpoint staf. Controller menerima request dan mendelegasikan aturan bisnis ke `WorkOrderService` serta `BookingService`. Model Eloquent mengakses database. Endpoint `api/public/*` memakai resource publik dan tidak memerlukan login. Email demo dicatat melalui mail log dan queue sinkron.

## Pengujian

```bash
cd backend && php artisan test --no-coverage
cd frontend && /opt/homebrew/bin/npm run build
```

## Deployment

Frontend adalah project Vercel dengan Root Directory `frontend`, build `npm run build`, output `dist`, dan `VITE_API_URL` berisi URL backend `/api`. Backend memerlukan runtime PHP/Docker dan database eksternal. Jangan menaruh kredensial database di repository. Detail endpoint ada di [API.md](API.md), skenario ada di [DEMO.md](DEMO.md), dan asumsi ada di [docs/ASUMSI.md](docs/ASUMSI.md).
