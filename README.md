# Antrian Bengkel

Aplikasi antrian dan servis bengkel mobil. Kasir mendaftarkan kendaraan yang datang, mekanik mengerjakan order dengan jasa dan sparepart, lalu kasir menagih pembayaran. Pelanggan bisa booking slot dari rumah, mengunggah bukti DP, dan melacak progres servis lewat kode pelacakan.

Backend Laravel 12 menyediakan REST API dengan Sanctum. Frontend Vue 3 + Vite berisi panel staf dan portal pelanggan.

## Menjalankan

Backend:

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Default pengembangan memakai SQLite di `backend/database/database.sqlite`. Isi `DB_*` di `backend/.env` bila ingin memakai MySQL atau PostgreSQL. Email tidak dikirim keluar selama `MAIL_MAILER=log`; isinya tercatat di `storage/logs` dan tabel `notification_logs`.

Frontend:

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

`frontend/.env` berisi `VITE_API_URL=http://localhost:8000/api`. Untuk produksi jalankan `npm run build`, hasilnya di `frontend/dist`.

Backend berjalan di `http://127.0.0.1:8000`, frontend di `http://localhost:5173`. Halaman `/` adalah landing publik; panel staf ada di `/dashboard` setelah login. Dev server frontend dikunci ke port 5173 agar origin-nya selalu cocok dengan daftar CORS di backend.

Pengujian:

```bash
cd backend && php artisan test --no-coverage
cd frontend && npm run build
```

## Akun demo

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@bengkel.test` | `password` |
| Kasir | `kasir1@bengkel.test` | `password` |
| Kasir | `kasir2@bengkel.test` | `password` |

Kode untuk demo: `TRACK234` (pelacakan order) dan `BKNG2345` (status booking).

## Struktur

```
backend/
  app/Http/Controllers/Api/   endpoint staf dan publik
  app/Services/               aturan bisnis work order dan booking
  app/Models/                 model Eloquent
  database/seeders/           data demo
  tests/Feature/              pengujian alur API
frontend/
  src/views/                  halaman staf
  src/views/public/           landing, booking, dan pelacakan
  src/components/             komponen bersama
  src/assets/tailwind.css     token warna dan utilitas
```
## Cara kerja singkat

Kasir membuat order lewat `WorkOrderService`, yang menentukan nomor antrian harian, kode pelacakan, dan log status. Mekanik menambah jasa dan sparepart; stok dikurangi dalam transaksi database dengan penguncian baris. Status order bergerak `menunggu` → `dikerjakan` → `selesai` → `dibayar`, dan setiap perpindahan dicatat di `work_order_status_logs`.

Booking publik memakai kapasitas slot per jam. Booking wajib DP transfer/QRIS, dan pelanggan mengunggah bukti sendiri; staf memverifikasi sebelum booking dikonfirmasi. Saat check-in, DP masuk sebagai pembayaran awal sehingga kasir hanya menagih sisanya.

Endpoint lengkap ada di [API.md](API.md), skenario demo di [DEMO.md](DEMO.md), dan keputusan yang tidak diatur spesifikasi di [docs/ASUMSI.md](docs/ASUMSI.md).
