# Demo 10 Menit

1. Jalankan backend dan frontend, lalu login sebagai `kasir1@bengkel.test` dengan kata sandi `password`.
2. Buka papan antrian untuk memperlihatkan order hari ini beserta nomor antrian dan statusnya.
3. Buka `/track` di jendela tanpa login, masukkan `TRACK234`. Tunjukkan status, linimasa, dan plat yang disamarkan.
4. Dari panel staf, mulai satu order menunggu dengan mekanik aktif. Tambahkan jasa dan sparepart, lalu selesaikan dan bayar.
5. Tampilkan dashboard dan laporan harian sebagai admin. Buka log notifikasi untuk melihat catatan email yang tidak benar-benar terkirim.
6. Buka `/booking`, pilih tanggal besok dan slot `10:00`, isi data, lalu kirim. Simpan kode booking yang muncul.
7. Di halaman status booking, unggah bukti DP (contoh gambar apa saja di bawah 4 MB). Status berubah menjadi "bukti terkirim".
8. Login staf, buka menu Booking, cari kode tersebut. Klik **Lihat bukti** untuk mencocokkan, lalu **Verifikasi DP**. Status DP menjadi "Transfer · Lunas".
9. Klik **Konfirmasi**, lalu **Check-in** pada tanggal booking. Order baru mendapat nomor antrian dan kode pelacakan, dan DP tercatat sebagai pembayaran awal. Tunjukkan sisa tagihan di detail order.
10. Tunjukkan penolakan slot penuh: buat booking ke slot `09:00` setelah tiga booking aktif di slot itu. Jelaskan arsitektur singkat: Vue dan Axios di depan, Laravel dengan Sanctum dan service bisnis di belakang, SQLite untuk pengembangan.

## Checklist

- [ ] Login admin dan kasir berhasil.
- [ ] `TRACK234` bisa dilacak tanpa token.
- [ ] Booking baru tampil di halaman status dengan kode sendiri.
- [ ] Bukti DP bisa diunggah pelanggan dan diverifikasi staf.
- [ ] Konfirmasi ditolak sebelum DP diverifikasi.
- [ ] Check-in membuat order dengan pembayaran DP.
- [ ] Papan antrian, dashboard, dan laporan harian tampil.
- [ ] Tambah jasa, sparepart, selesai, dan bayar berhasil.
- [ ] Slot penuh mengembalikan pesan kesalahan yang jelas.
- [ ] QR pada tiket mengarah ke halaman pelacakan.
