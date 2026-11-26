# Asumsi dan keputusan

Hal yang tidak diatur eksplisit di `Perencanaan_Proyek_Aplikasi_Antrian_Bengkel.md` beserta alasan pemilihannya.

## Teknis

- Laravel dipin ke `^12.0`. Saat pengerjaan, `composer create-project` menghasilkan Laravel 13, sedangkan Bagian 4 mensyaratkan 12.
- Pengembangan dan demo memakai MySQL XAMPP (MariaDB 10.4, database `bengkel`, user `root` tanpa kata sandi) karena itu yang tersedia di mesin ini. Pengujian otomatis tetap SQLite `:memory:` lewat `phpunit.xml` (`DB_CONNECTION=sqlite`) agar cepat dan tidak menyentuh data demo.
- `booking.closed_weekdays` berisi nama hari Bahasa Indonesia (`['Minggu']`) karena `APP_LOCALE=id`, dan `BookingService` membandingkannya lewat `Carbon::translatedFormat('l')`.
- Zona waktu ditulis langsung `Asia/Jakarta` di `config/app.php` karena Laravel 12 tidak membaca timezone dari env.
- `frontend/.env` tidak ikut git. Nilai awalnya ada di `.env.example` (`VITE_API_URL=http://localhost:8000/api`).
- Format kode memakai Pint untuk PHP (`backend/vendor/bin/pint`) dan Prettier untuk Vue/JS (`npm run format` di `frontend/`). Aturannya ada di `.prettierrc.json`.
- Backend hanya dipakai sebagai REST API, jadi scaffolding aset Vite bawaan Laravel (`vite.config.js`, `resources/css`, `resources/js`) dihapus. `GET /` mengembalikan status JSON sederhana dan diuji di `HealthTest`.

## Perubahan lingkup

- Pencarian publik lewat plat nomor dan 4 digit telepon (`POST /public/track/lookup`) dibatalkan karena plat bisa dilihat siapa saja. Pelacakan publik sekarang hanya melalui kode pelacakan atau QR, dan halaman `/track` berisi form kode saja.
- Riwayat servis per kendaraan ditambahkan sebagai `GET /vehicles/{id}/history` dan halaman `/vehicles/:id/history` untuk staf. Isinya nomor antrian, status, tanggal antrian, keluhan, jasa, sparepart, dan tanggal selesai.
- Jenis kendaraan dibatasi `mobil`. Pilihan motor dihapus dari validasi backend, booking publik, dan form kendaraan. Spesifikasi sudah disesuaikan pada revisi 24 November 2026, sehingga tidak ada lagi penyebutan motor.
- Halaman landing publik di `/` berisi dua tombol, Booking servis (`/booking`) dan Lacak servis (`/track`), tanpa tautan staf. Dashboard staf dipindah ke `/dashboard`.
- Landing juga menampilkan daftar layanan beserta tarif dan sisa slot besok dari `GET /public/services` dan `GET /public/booking-slots`. Keduanya hanya membaca data. Klik slot membawa `?date=&slot=` ke `/booking` dan form booking memilih slot tersebut otomatis. `GET /public/services` sekarang mengembalikan `price` dan `est_minutes`, sedangkan header/footer publik memakai lebar `max-w-5xl` untuk landing (`meta.wide`) dan `max-w-3xl` untuk halaman lain.
- Pendaftaran datang langsung (walk-in) dibuat satu langkah: `POST /api/work-orders` menerima `vehicle_id` untuk pelanggan lama atau `customer` + `vehicle` untuk pelanggan baru. Backend memakai ulang pelanggan berdasarkan nomor telepon dan menolak plat yang sudah terdaftar, sehingga kasir tidak perlu berpindah menu. Walk-in tetap tanpa batas kapasitas slot karena tidak memakai booking (Bagian 3).
- Dashboard disusun ulang dengan kartu ringkas: kas hari ini (total diterima, rincian tunai/transfer/QRIS, pembayaran terakhir), antrian per status, perlu tindakan, tagihan menunggu bayar, dan stok menipis. `GET /api/dashboard` menambah `payments` (dihitung dari `paid_at` hari ini) dan `unpaid_orders`. `revenue` berarti kas yang diterima hari ini, sedangkan laporan harian tetap menghitung pendapatan per `queue_date`.
- Harga jasa dan sparepart ditampilkan terpisah. `WorkOrderResource` menambah `services_total` dan `parts_total` untuk subtotal di detail order dan struk, sedangkan `total` tetap jumlah keduanya dikurangi diskon.

## DP booking

DP booking Rp 50.000 (bisa diubah lewat env `BENGKEL_BOOKING_DP`) dipakai untuk mengunci slot. Aturannya:

- Booking menyimpan `dp_amount`, `dp_paid_at`, `dp_method`, `dp_proof_path`, dan `dp_proof_at`.
- DP hanya boleh dibayar melalui transfer atau QRIS, tidak menerima tunai.
- Pelanggan mengunggah bukti (JPG/PNG/WEBP/PDF maksimal 4 MB) sendiri lewat `POST /api/public/bookings/{code}/dp`.
- Staf memverifikasi lewat `POST /api/bookings/{id}/dp`. Verifikasi ditolak selama pelanggan belum mengirim bukti, dan staf tetap bisa mengunggah bukti pengganti bila pelanggan mengirimnya lewat jalur lain.
- Bukti disimpan di disk privat dan dibuka staf lewat `GET /api/bookings/{id}/dp-proof`.
- Konfirmasi booking ditolak selama DP belum diverifikasi.
- Setelah DP diverifikasi, pelanggan tidak bisa membatalkan sendiri lewat halaman status. Pembatalan dilakukan lewat bengkel agar DP dapat dikembalikan.
- Saat check-in, DP masuk sebagai pembayaran `method=dp` pada order sehingga sisa tagihan berkurang otomatis.
- `GET /api/public/booking-slots` mengirim `meta.dp_amount` dan `PublicBookingResource` mengirim `dp_amount`, `dp_paid`, `dp_proof`, `dp_method`, serta `dp_proof_at` untuk ditampilkan ke pelanggan.
