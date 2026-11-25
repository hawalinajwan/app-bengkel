# Demo 10 Menit

Persiapan: backend di `http://127.0.0.1:8000` dan frontend di `http://localhost:5173`. Data demo bisa dibuat dengan `php artisan migrate:fresh --seed` atau dipulihkan dari dump `backend/database/dumps/demo-2hari-2026-11-25.sql` (`sqlite3 database/database.sqlite < database/dumps/demo-2hari-2026-11-25.sql`). Seeder mengisi booking untuk hari ini, besok, dan lusa, jadi dua tanggal ke depan sudah punya isi. Login staf memakai `kasir1@bengkel.test` / `password` (admin: `admin@bengkel.test` / `password`).

1. Buka `http://localhost:5173`. Tunjukkan landing publik: daftar layanan, tarif, dan sisa slot besok. Klik **Lacak servis** sebentar, lalu masuk lewat tombol staf dan login sebagai kasir.
2. Buka papan antrian. Tunjukkan order hari ini beserta nomor antrian dan lima kolom status, lalu bagian carry-over "Masih di bengkel dari tanggal sebelumnya".
3. Buka `/track` di jendela tanpa login, masukkan `TRACK234`. Tunjukkan status, linimasa, dan plat yang disamarkan. Tekan **Aktifkan notifikasi** dan biarkan tab terbuka.
4. Dari panel staf, mulai satu order `menunggu` dengan mekanik aktif. Tambahkan jasa dan sparepart, isi catatan kondisi kendaraan, lalu selesaikan dan bayar. Tunjukkan total, kembalian, dan struk.
5. Masuk sebagai admin. Tampilkan dashboard (kas hari ini, pembayaran, tagihan menunggu bayar, stok menipis), laporan harian, dan log notifikasi beserta statusnya. Jelaskan bahwa email tidak keluar sungguhan karena `MAIL_MAILER=log`/`Mail::fake()`. Buka detail order `selesai` dan tunjukkan tombol **Kirim WhatsApp**.
6. Buka `/booking`, pilih tanggal besok dan slot `10:00`, isi data, lalu kirim. Simpan kode booking yang muncul.
7. Di halaman status booking, unggah bukti DP (contoh gambar apa saja di bawah 4 MB). Status berubah menjadi "bukti terkirim".
8. Login staf, buka menu Booking, pilih status `Menunggu` dan tanggal besok, lalu temukan kode tadi. Klik **Lihat bukti** untuk mencocokkan, lalu **Verifikasi DP**. Status DP menjadi "Transfer · Lunas", kemudian klik **Konfirmasi**.
9. Tunjukkan penolakan sebelum DP: pada booking `HRINI03` (hari ini, status `Menunggu`, bukti QRIS belum diverifikasi), klik **Konfirmasi**. Muncul pesan bahwa DP belum diterima. Verifikasi DP-nya, lalu konfirmasi.
10. Buka booking `HRINI01` (hari ini, sudah dikonfirmasi) dan klik **Check-in**. Order baru mendapat nomor antrian dan kode pelacakan, DP tercatat sebagai pembayaran awal, dan detail order menunjukkan sisa tagihan.
11. Tunjukkan penolakan slot penuh: buat booking ke slot `09:00` besok. Tiga slot itu sudah terisi di data demo, jadi permintaan ditolak dengan pesan yang jelas. Ulangi dengan tanggal lusa (`LUSA2345` dan dua booking lain mengisi `09:00`) untuk menunjukkan aturan kapasitas berlaku di tanggal mana pun.
12. Buka menu Booking dengan filter tanggal lusa. Tunjukkan `LUSA05` (sudah dikonfirmasi dengan DP) dan `LUSA06` (bukti QRIS sudah diunggah, belum diverifikasi) sebagai bahan latihan verifikasi staf.
13. Jelaskan arsitektur singkat: Vue, Vue Router, Pinia, Axios, dan Tailwind di depan; Laravel dengan Sanctum, service bisnis, dan transaksi database di belakang; SQLite untuk pengembangan; endpoint publik tanpa login memakai resource terbatas.

## Checklist

- [ ] Landing publik, login admin, dan login kasir berhasil.
- [ ] `TRACK234` bisa dilacak tanpa token dan hanya menampilkan data terbatas.
- [ ] Papan antrian menampilkan order hari ini dan carry-over.
- [ ] Tambah jasa, sparepart, selesai, dan bayar berhasil.
- [ ] Dashboard, laporan harian, log notifikasi, dan tombol WhatsApp tampil.
- [ ] Booking baru tampil di halaman status dengan kode sendiri.
- [ ] Bukti DP bisa diunggah pelanggan dan diverifikasi staf.
- [ ] Konfirmasi ditolak sebelum DP diverifikasi.
- [ ] Check-in membuat order dengan pembayaran DP dan sisa tagihan yang benar.
- [ ] Slot penuh mengembalikan pesan kesalahan yang jelas, baik untuk besok maupun lusa.
- [ ] Filter Booking menampilkan data besok dan lusa (`BKNG2345`, `LUSA2345`).
- [ ] Dump `demo-2hari-2026-11-25.sql` bisa dipulihkan ke database kosong.
- [ ] QR pada tiket mengarah ke halaman pelacakan.
