# Asumsi

Keputusan yang tidak diatur eksplisit di `Perencanaan_Proyek_Aplikasi_Antrian_Bengkel.md`.

- Laravel dipin ke `^12.0` (composer default saat ini 13) agar sesuai Bagian 4.
- Dev dan test memakai SQLite (`DB_CONNECTION=sqlite`); MySQL/PostgreSQL hanya untuk demo sesuai Bagian 4.
- `booking.closed_weekdays` berisi nama hari Bahasa Indonesia (`['Minggu']`) karena `APP_LOCALE=id`; `BookingService` membandingkan dengan `Carbon::translatedFormat('l')`.
- Zona waktu ditulis langsung `Asia/Jakarta` di `config/app.php` (Laravel 12 tidak membaca env timezone).
- `.env` frontend diabaikan git; nilai awal `VITE_API_URL=http://localhost:8000/api` ada di `.env.example`.
