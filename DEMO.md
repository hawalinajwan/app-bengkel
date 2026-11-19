# Demo 10 Menit

1. Jalankan backend dan frontend, lalu login sebagai `kasir1@bengkel.test` dengan password `password`.
2. Buka papan antrian. Tampilkan order `TRACK234` dari portal publik tanpa login.
3. Pada `/track`, masukkan `TRACK234`. Tunjukkan status, linimasa, dan plat tersamar.
4. Dari panel staf, mulai order menunggu dengan mekanik aktif. Tambahkan jasa dan sparepart, lalu selesaikan dan bayar.
5. Tampilkan dashboard dan laporan harian admin. Buka log notifikasi untuk order selesai.
6. Buka halaman booking publik. Pilih tanggal besok dan slot `09:00`. Tampilkan booking `BKNG2345` dan statusnya.
7. Login staf, konfirmasi booking, lalu check-in pada tanggal booking. Order baru memperoleh nomor antrean dan kode tracking.
8. Tampilkan penolakan slot penuh dengan membuat booking ke slot `09:00` setelah kapasitas tercapai.
9. Gunakan order lain untuk demonstrasi pembatalan dan pengembalian stok.
10. Jelaskan arsitektur: Vue, Axios, API Laravel, Sanctum, service bisnis, model database, dan endpoint publik.

## Checklist

- [ ] Login admin dan kasir berhasil.
- [ ] `TRACK234` dapat dilacak tanpa token.
- [ ] Booking `BKNG2345` tampil.
- [ ] Papan antrian dan dashboard tampil.
- [ ] Tambah jasa, sparepart, selesai, dan bayar berhasil.
- [ ] Notifikasi order selesai tercatat, email tidak dikirim keluar.
- [ ] Slot penuh mengembalikan error validasi bisnis.
- [ ] QR mengarah ke halaman tracking.
