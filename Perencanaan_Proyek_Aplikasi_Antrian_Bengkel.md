# Perencanaan Proyek: Aplikasi Antrian Bengkel

*Dokumen spesifikasi proyek. UAS Pemrograman Framework, revisi 24 November 2026: backend Laravel 12, frontend Vue 3 + Tailwind, khusus bengkel mobil, booking dengan DP, demo lokal.*

## 1. Petunjuk pengerjaan

Dokumen ini adalah satu-satunya sumber kebenaran proyek. Baca seluruhnya sebelum menulis kode, lalu kerjakan berurutan sesuai Bagian 12.

- Ikuti nama tabel, kolom, status, dan route persis seperti tertulis. Jangan mengganti nama.
- Jangan menambah fitur di luar Bagian 3. Jika ada hal yang tidak jelas, pilih opsi paling sederhana yang konsisten dengan dokumen ini dan catat keputusannya di `docs/ASUMSI.md`.
- Semua aturan bisnis berada di service class pada backend, bukan di controller dan bukan di komponen Vue.
- Setelah setiap fase jalankan `php artisan test`. Semua test harus lulus dan npm run build di frontend harus sukses sebelum lanjut ke fase berikutnya.
- Bahasa: antarmuka dan pesan error dalam Bahasa Indonesia; nama kode, tabel, dan kolom dalam Bahasa Inggris; nilai status dalam Bahasa Indonesia huruf kecil.
- Commit kecil dan sering, satu commit per langkah yang bermakna.

## 2. Konteks dan syarat dosen

Proyek ini adalah UAS mata kuliah Pemrograman Framework. Syarat dari dosen:

- Pilihan: melanjutkan Proyek Perpustakaan di repo baru, atau membuat aplikasi baru dengan bonus 5 poin. Proyek ini memilih aplikasi baru (bonus).
- Minimal 5 tabel.
- Harus ada transaksi.
- Transaksi tidak boleh hanya berupa tabel master yang berelasi dengan tabel dependent.
- Demo dilakukan pada minggu 12 sampai 15.

Cara dokumen ini memenuhinya:

- Aplikasi baru, backend Laravel dengan frontend Vue (kolaborasi dengan framework lain diizinkan dosen), dalam satu repo baru bernama `app-bengkel`.
- 13 tabel (Bagian 5).
- Transaksi: pendaftaran antrian, pemakaian sparepart, penyelesaian, pembayaran (termasuk pelunasan setelah DP), pembatalan, serta booking (validasi kapasitas slot, DP, dan check-in).
- Setiap transaksi menjalankan aturan bisnis nyata (state machine status, validasi stok dengan penguncian baris, batas beban mekanik, perhitungan total, rollback otomatis), semuanya di dalam `DB::transaction()`. Inilah yang membedakannya dari CRUD master-detail biasa.
- Skenario demo ada di Bagian 15.

## 3. Ringkasan aplikasi

Aplikasi web untuk mengelola antrian dan servis bengkel mobil. Kasir mendaftarkan kendaraan yang datang dan kendaraan mendapat nomor antrian harian. Kendaraan lalu dikerjakan mekanik dengan memakai jasa dan sparepart, kemudian dibayar.

Aktor:

- `admin`: mengelola data master (mekanik, jasa, sparepart), melihat laporan dan log notifikasi, dan dapat melakukan semua aksi kasir.
- `kasir`: mengelola pelanggan, kendaraan, booking, dan seluruh siklus work order.
- `customer` (publik, tanpa login): melacak kendaraan lewat kode pelacakan atau QR, membuat dan membatalkan booking, serta menerima notifikasi.

Alur utama: daftar antrian → mulai dikerjakan (pilih mekanik) → tambah jasa dan sparepart → selesai → bayar. Pembatalan diperbolehkan sebelum status selesai.

Alur customer: booking online (opsional) → dikonfirmasi staf → check-in saat datang (menjadi work order dengan nomor antrian dan kode pelacakan) → memantau status lewat kode atau QR → menerima notifikasi saat kendaraan selesai.

Dalam scope: autentikasi token dengan dua role, CRUD master, siklus work order, papan antrian (termasuk pilih tanggal dan order carry-over), dashboard, laporan harian, riwayat servis per kendaraan, struk dan tiket antrian ber-QR, landing publik, portal customer tanpa login (pelacakan lewat kode pelacakan atau QR), booking dari jauh hari beserta DP transfer/QRIS dengan unggah bukti dan verifikasi staf, halaman kelola booking untuk staf, notifikasi email dan browser beserta log notifikasi, tombol WhatsApp manual, REST API sebagai satu-satunya antarmuka backend, SPA Vue, seeder, test, dan demo lokal. Panduan deployment Vercel tetap disimpan, tetapi belum dijalankan.

Di luar scope: CRUD user (user dibuat lewat seeder), pembayaran cicilan (DP booking sudah termasuk), pelacakan lewat plat nomor dan 4 digit telepon (dibatalkan karena plat mudah ditebak), notifikasi WhatsApp otomatis, push notification saat browser tertutup, pengingat booking terjadwal (butuh scheduler), akun login customer, multi-cabang, upload foto kerusakan, soft delete, riwayat stok terpisah, TypeScript, dan SSR.

## 4. Arsitektur, stack, dan konvensi

Arsitektur:

- Repo `app-bengkel` berbentuk monorepo: `backend/` (Laravel 12, hanya API) dan `frontend/` (Vue 3 SPA). Keduanya hanya berkomunikasi lewat REST API berformat JSON. Framework utama yang dinilai tetap Laravel.
- Backend tidak merender halaman HTML: tanpa Blade untuk halaman, dan semua route ada di `routes/api.php`. Satu-satunya pengecualian adalah template email di `resources/views/emails/`.
- Frontend tidak memuat aturan bisnis. Validasi yang menentukan (stok, status, batas mekanik, pembayaran) hanya ada di backend. Frontend boleh memvalidasi ringan demi kenyamanan, tetapi tidak boleh bergantung padanya.

Backend:

- Laravel 12, PHP 8.2 ke atas. SQLite dipakai untuk pengembangan, pengujian, dan demo. MySQL atau PostgreSQL didukung dengan mengubah `DB_*` di `backend/.env`.
- Autentikasi token memakai Laravel Sanctum (`php artisan install:api`) dengan Bearer token. Token dipilih karena frontend dan backend berada di domain berbeda, sehingga autentikasi cookie sulit. Middleware `role:admin` berasal dari `RoleMiddleware` yang didaftarkan sebagai alias di `bootstrap/app.php`.
- Validasi di FormRequest. Respons memakai API Resource.
- Semua error di `api/*` berformat JSON: 401 belum login, 403 salah role, 404 tidak ditemukan, 422 validasi atau pelanggaran aturan bisnis, 429 terlalu banyak permintaan (throttle). Body error berbentuk `{ message, errors? }` (`errors` hanya untuk validasi). Atur lewat `withExceptions` di `bootstrap/app.php`.
- Pelanggaran aturan bisnis dilempar sebagai exception domain: `InvalidTransitionException`, `InsufficientStockException`, `MechanicUnavailableException`, `PaymentException`. Semuanya dirender menjadi 422. Fitur booking menambah SlotFullException yang juga dirender 422.
- CORS di `config/cors.php`: hanya mengizinkan origin dari `FRONTEND_URL` (boleh beberapa, dipisah koma; default `http://localhost:5173`). Nilai .env pengembangan memuat `http://localhost:5173,http://127.0.0.1:5173` karena frontend bisa dibuka dari salah satu alamat; `supports_credentials` bernilai false.
- Uang disimpan sebagai integer rupiah (unsigned), tidak pernah float. Zona waktu `Asia/Jakarta`, locale `id`, Faker `id_ID`.
- Status memakai PHP backed enum `WorkOrderStatus` dengan case `Menunggu = 'menunggu'`, `Dikerjakan = 'dikerjakan'`, `Selesai = 'selesai'`, `Dibayar = 'dibayar'`, `Batal = 'batal'`.
- Konfigurasi bisnis di `config/bengkel.php`: `max_active_orders_per_mechanic` bernilai 2 dan `booking.dp_amount` bernilai 50000 (dapat diubah lewat env `BENGKEL_BOOKING_DP`).

Frontend:

- Vue 3 dengan Composition API dan `<script setup>`, Vite, JavaScript (tanpa TypeScript), Vue Router mode history, Pinia, Axios, dan Tailwind CSS 4 lewat plugin `@tailwindcss/vite`. Tidak memakai Bootstrap. Token warna dan utilitas bersama ada di `src/assets/tailwind.css`.
- Satu instance Axios di `src/api/client.js`: `baseURL` dari `import.meta.env.VITE_API_URL`, header `Accept: application/json`, interceptor request yang menambahkan `Authorization: Bearer <token>`, dan interceptor response yang pada 401 menghapus token lalu mengarahkan ke `/login`.
- Token disimpan di `localStorage` (cukup untuk tugas kuliah).
- Angka uang dari API selalu integer rupiah; format tampilan lewat `src/utils/rupiah.js`. Normalisasi nomor telepon untuk tautan WhatsApp dilakukan langsung di `WorkOrderDetail.vue` (hapus karakter non-digit, awalan 0 diganti 62).
- Dev server dikunci ke port `5173` dengan `strictPort: true` agar tidak diam-diam pindah ke port lain yang belum terdaftar di CORS. Host tambahan (misalnya Tailscale) dibaca dari `DEV_ALLOWED_HOSTS`, tidak disimpan di `vite.config.js`.
- Antarmuka dan pesan dalam Bahasa Indonesia.

Fitur customer (pelacakan, booking, notifikasi):

- Endpoint publik berada di prefix `/api/public/*`, tanpa login, dan memakai `throttle` 30 permintaan per menit per IP. Pencarian lewat plat nomor tidak disediakan, sehingga tidak ada batas throttle terpisah. Respons publik hanya memuat data terbatas (Bagian 6) dan dibuat lewat API Resource khusus (`Public*Resource`), bukan resource staf.
- Email memakai Laravel Mail. `MAIL_MAILER=log` untuk pengembangan dan demo lokal, SMTP apa pun untuk produksi. Email dikirim setelah transaksi commit, dan kegagalan kirim tidak boleh membatalkan transaksi. Tautan di dalam email memakai URL pertama dari `FRONTEND_URL`.
- Parameter booking (jam buka, durasi slot, kapasitas, hari tutup, batas hari ke depan) ada di `config/bengkel.php`.
- QR dibuat di frontend dengan paket npm `qrcode` dan berisi URL halaman pelacakan. Customer memindainya dengan kamera bawaan HP yang langsung membuka URL itu, jadi tidak perlu pemindai di dalam aplikasi. Notifikasi browser memakai Notification API.

## 5. Skema database

Buat satu migration per tabel dengan urutan berikut (urutan penting karena foreign key). Semua tabel memiliki `id` dan `timestamps` kecuali dinyatakan lain.

1. `users` (ubah migration bawaan Laravel)
   - `name`, `email` unik, `password`, `role` string default `kasir` (nilai `admin` atau `kasir`)
2. `customers`
   - `name`, `phone` nullable, `email` nullable, `address` nullable
3. `vehicles`
   - `customer_id` FK ke customers (restrict)
   - `plate_number` string(15) unik, disimpan huruf kapital tanpa spasi
   - `type` string (selalu `mobil`; pilihan motor dihapus), `brand`, `model`, `year` nullable
4. `mechanics`
   - `name`, `phone` nullable, `is_active` boolean default true
5. `services`
   - `name`, `price` unsigned integer, `est_minutes` unsigned smallint
6. `spareparts`
   - `sku` unik, `name`, `price` unsigned integer, `stock` unsigned integer
   - `min_stock` unsigned integer default 5 (kolom tambahan untuk peringatan stok menipis)
7. `work_orders`
   - `queue_date` date, `queue_number` unsigned integer, dengan unique gabungan `(queue_date, queue_number)`
   - `tracking_code` string(8) unik (kode acak untuk pelacakan publik), `public_note` text nullable (catatan kondisi kendaraan yang boleh dilihat customer)
   - `vehicle_id` FK (restrict), `mechanic_id` nullable FK (restrict), `user_id` FK (restrict, kasir pencatat)
   - `status` string default `menunggu`, diberi index
   - `complaint` text nullable, `started_at` nullable, `finished_at` nullable
   - `discount` unsigned integer default 0, `total` unsigned integer default 0
8. `work_order_services`
   - `work_order_id` FK (cascade), `service_id` FK (restrict)
   - `price` unsigned integer (snapshot harga saat ditambahkan); unique gabungan `(work_order_id, service_id)`
9. `work_order_parts`
   - `work_order_id` FK (cascade), `sparepart_id` FK (restrict)
   - `qty` unsigned smallint, `price` unsigned integer (snapshot); unique gabungan `(work_order_id, sparepart_id)`
10. `payments`
    - `work_order_id` FK (restrict), `method` string (`tunai`, `transfer`, `qris`, atau `dp`)
    - `amount` unsigned integer (uang yang diterima), `paid_at` datetime
11. `bookings`
    - `booking_code` string(8) unik (kode acak)
    - `customer_name`, `phone`, `email` nullable
    - `plate_number` (huruf kapital tanpa spasi), `vehicle_type` (selalu `mobil`), `brand`, `model`
    - `service_id` nullable FK (restrict), `complaint` text nullable
    - `booking_date` date, `slot_time` string(5) berformat `HH:MM`, dengan index gabungan `(booking_date, slot_time)`
    - `dp_amount` unsigned integer default 0, `dp_paid_at` datetime nullable, `dp_method` string nullable (`transfer` atau `qris`)
    - `dp_proof_path` string nullable (berkas bukti di disk privat), `dp_proof_at` timestamp nullable (waktu unggah)
    - `status` string default `menunggu` (nilai: `menunggu`, `dikonfirmasi`, `ditolak`, `dibatalkan`, `check_in`, `tidak_hadir`), `staff_note` text nullable
    - `work_order_id` nullable FK unik (restrict), terisi saat check-in
12. `work_order_status_logs` (hanya `created_at`, tanpa `updated_at`)
    - `work_order_id` FK (cascade), `from_status` nullable, `to_status`, `user_id` nullable FK
13. `notification_logs`
    - `type` string (`order_selesai`, `booking_dibuat`, `booking_dikonfirmasi`, `booking_ditolak`, `booking_dibatalkan`), `channel` string (`email`), `recipient` string
    - `work_order_id` nullable FK, `booking_id` nullable FK
    - `status` string (`terkirim`, `gagal`, `dilewati`), `error` text nullable, `sent_at` datetime nullable

Relasi Eloquent:

- `Customer` hasMany `Vehicle`. `Vehicle` belongsTo `Customer` dan hasMany `WorkOrder`.
- `Mechanic` hasMany `WorkOrder`. `User` hasMany `WorkOrder`.
- `WorkOrder` belongsTo `Vehicle`, `Mechanic`, `User`; hasMany `serviceLines()` (model `WorkOrderServiceItem`), `partLines()` (model `WorkOrderPart`), `payments()`, dan `statusLogs()`; hasOne `booking()`. Model detail jasa diberi nama `WorkOrderServiceItem` agar tidak bentrok dengan class `WorkOrderService` di `app/Services`.
- `Service` dan `Sparepart` masing-masing hasMany ke tabel detailnya.
- `Booking` belongsTo `Service` (nullable) dan `WorkOrder` (nullable). `NotificationLog` belongsTo `WorkOrder` dan `Booking` (keduanya nullable).

Catatan desain:

- Harga disalin ke tabel detail agar perubahan harga di master tidak mengubah order lama.
- Stok hanya hidup di `spareparts.stock` dan hanya boleh diubah di dalam transaksi.
- `work_orders.total` adalah hasil hitung yang disimpan. Hitung ulang setiap kali item berubah.
- Satu work order dibayar sekali saat pelunasan, tetapi `payments` boleh berisi lebih dari satu baris: DP booking dicatat sebagai pembayaran `dp` saat check-in, lalu kasir menagih sisanya. `pay` membandingkan `amount` dengan sisa tagihan (`total` dikurangi jumlah pembayaran yang sudah ada), bukan dengan `total` penuh.
- Order tidak pernah dihapus. Pembatalan memakai status `batal`.
- URL publik memakai `tracking_code` atau `booking_code` yang acak, bukan id numerik atau plat nomor, agar tidak bisa ditebak.
- Data customer pada booking disimpan langsung di tabel `bookings`. Record `customers` dan `vehicles` baru dibuat atau dipakai ulang saat check-in.
- Setiap perubahan status work order dicatat di `work_order_status_logs` dan menjadi sumber linimasa di halaman pelacakan.

## 6. Aturan bisnis dan state machine

Transisi status yang sah. Transisi lain ditolak dengan `InvalidTransitionException`.

- `menunggu` → `dikerjakan` (aksi Mulai). Syarat: mekanik dipilih, aktif, dan jumlah order `dikerjakan` miliknya masih di bawah batas. Efek: isi `mechanic_id` dan `started_at`.
- `dikerjakan` → `selesai` (aksi Selesai). Syarat: minimal ada satu jasa atau sparepart, dan diskon tidak melebihi subtotal. Efek: isi `finished_at` dan `discount`, lalu hitung `total`.
- `selesai` → `dibayar` (aksi Bayar). Syarat: `amount` lebih besar atau sama dengan sisa tagihan (`total` dikurangi pembayaran yang sudah ada, termasuk DP). Efek: buat record `payments`.
- `menunggu` atau `dikerjakan` → `batal` (aksi Batal). Efek: semua sparepart di order dikembalikan ke stok.

Aturan lain:

- Nomor antrian urut per hari, mulai dari 1, ditampilkan tiga digit (`001`). Hari baru kembali ke 1.
- Jasa dan sparepart hanya boleh ditambah, diubah, atau dihapus saat status `dikerjakan`.
- Menambah sparepart: stok harus cukup, stok berkurang sebesar qty. Jika sparepart sudah ada di order, qty ditambahkan pada baris yang sama.
- Mengurangi qty atau menghapus sparepart dari order mengembalikan stok.
- `total` = jumlah harga jasa + jumlah (qty × harga sparepart) − diskon, minimal 0. Selalu hitung dari harga snapshot di tabel detail.
- Kembalian = `amount` − `total` (dihitung, tidak disimpan).
- Kendaraan yang masih punya order berstatus `menunggu` atau `dikerjakan` tidak boleh didaftarkan lagi.
- Setiap aksi harus atomik: jika satu langkah gagal, tidak ada data yang berubah sama sekali.

Status log dan kode pelacakan:

- Setiap perubahan status work order (termasuk pembuatan, dari `null` ke `menunggu`) menulis satu baris `work_order_status_logs` di dalam transaksi yang sama.
- `tracking_code` dibuat saat `register`: 8 karakter acak dari alfabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (tanpa karakter yang mirip seperti 0, O, 1, I). Ulangi pembuatan jika bentrok. Kode tetap bisa dibuka setelah order selesai atau dibayar sebagai riwayat.
- QR berisi URL `<origin frontend>/track/<tracking_code>`; frontend memakai `window.location.origin`, sehingga QR selalu mengarah ke domain tempat aplikasi dibuka. QR dibuat di frontend dan ditampilkan pada tiket antrian (dicetak setelah pendaftaran dan setelah check-in), struk, dan halaman detail order.
- Pelacakan tanpa kode lewat plat nomor ditambah 4 digit telepon tidak dipakai: plat nomor terlihat oleh siapa saja, sehingga pencarian itu membocorkan status servis. Satu-satunya jalur pelacakan publik adalah kode pelacakan atau QR. Endpoint `POST /api/public/track/lookup` tidak ada dan test memastikan route itu tetap 404.

Data yang boleh tampil di endpoint publik:

- Nomor antrian, status, linimasa (waktu tiap perubahan status), merek dan model kendaraan, plat nomor tersamar (2 karakter pertama dan 2 terakhir, sisanya `*`), `public_note`, nama jasa, serta nama dan qty sparepart.
- `estimated_finish_at` hanya saat status `dikerjakan`: `started_at` ditambah jumlah `est_minutes` semua jasa di order.
- `total` hanya saat status `selesai` atau `dibayar`.
- Tidak boleh tampil: nama, telepon, dan email customer, nama mekanik, id numerik, dan harga satuan.

Data yang boleh tampil di cek booking (`GET /api/public/bookings/{code}`):

- `booking_code`, status, tanggal, jam slot, merek, model, plat nomor tersamar, dan nama jasa.
- `staff_note` hanya saat status `ditolak`. `tracking_code` dan `queue_number` hanya saat status `check_in`.
- Status DP: `dp_amount`, `dp_paid` (boolean), `dp_proof` (boolean, sudah ada bukti), `dp_method`, dan `dp_proof_at`. Nominal dan status ini perlu agar pelanggan tahu berapa yang harus ditransfer dan apakah buktinya sudah diterima.
- Tidak boleh tampil: nama, telepon, dan email customer, serta id numerik.

Aturan booking:

- Parameter di `config/bengkel.php`: `booking.open_time` = `08:00`, `booking.close_time` = `16:00`, `booking.slot_minutes` = 60 (slot mulai 08:00 sampai 15:00), `booking.slot_capacity` = 3, `booking.closed_weekdays` = Minggu, `booking.max_days_ahead` = 30, dan `booking.dp_amount` = 50000.
- Customer boleh booking mulai besok sampai `max_days_ahead` hari ke depan, bukan pada hari tutup, dan pada slot yang valid. Hari ini tidak tersedia (datang langsung sebagai walk-in).
- Kapasitas: jumlah booking berstatus `menunggu` atau `dikonfirmasi` pada tanggal dan slot yang sama harus kurang dari `slot_capacity`. Pengecekan dan pembuatan booking terjadi dalam satu transaksi dengan `lockForUpdate()`.
- Satu plat nomor hanya boleh punya satu booking aktif (`menunggu` atau `dikonfirmasi`) per tanggal.
- Setiap booking baru menyimpan `dp_amount` dari konfigurasi. DP hanya boleh transfer atau QRIS, bukan tunai. Pelanggan mengunggah bukti (JPG/PNG/WEBP/PDF maksimal 4 MB) lewat `POST /api/public/bookings/{code}/dp`; berkas disimpan di disk privat.
- Staf memverifikasi DP lewat `POST /api/bookings/{id}/dp` (opsional multipart `proof` bila staf perlu mengunggah bukti pengganti). Verifikasi ditolak selama belum ada bukti, DP sudah tercatat, atau booking tidak memakai DP.
- Konfirmasi booking ditolak selama DP belum diverifikasi. Setelah DP tercatat, pelanggan tidak bisa membatalkan lewat halaman status (422); pembatalan dilakukan lewat bengkel agar DP dapat dikembalikan.
- Saat check-in, DP dicatat sebagai pembayaran `method=dp` pada work order dengan `paid_at` = waktu DP diterima, sehingga sisa tagihan berkurang otomatis.

Transisi status booking. Transisi lain ditolak dengan `InvalidTransitionException`.

- `menunggu` → `dikonfirmasi` (staf, hanya jika DP sudah diverifikasi), atau `menunggu` → `ditolak` (staf, `staff_note` wajib).
- `menunggu` atau `dikonfirmasi` → `dibatalkan` (customer lewat kode booking selama DP belum dibayar, atau staf kapan saja).
- `dikonfirmasi` → `check_in` (staf, hanya pada `booking_date`): cari atau buat customer (berdasarkan telepon) dan kendaraan (berdasarkan plat), panggil `WorkOrderService::register`, lalu isi `work_order_id`. Seluruh langkah berada dalam satu transaksi: jika kendaraan masih punya order aktif, check-in ditolak dan booking tetap `dikonfirmasi`. Customer baru diisi dari `customer_name`, `phone`, dan `email` booking; jika customer sudah ada dan `email`-nya kosong, isi dari booking agar notifikasi selesai bisa terkirim. Jika plat sudah terdaftar, kendaraan itu dipakai ulang tanpa mengubah pemiliknya. Kode pelacakan order muncul di respons cek booking.
- `dikonfirmasi` → `tidak_hadir` (staf, hanya setelah `booking_date` lewat).

Notifikasi:

- `order_selesai`: dikirim saat status work order menjadi `selesai`; berisi nomor antrian, total, dan tautan pelacakan.
- `booking_dibuat`, `booking_dikonfirmasi`, `booking_ditolak`, `booking_dibatalkan`: berisi kode booking, tanggal, jam, dan tautan cek booking.
- Kanal utama adalah email ke `customers.email` atau `bookings.email`. Jika alamat kosong, catat `dilewati`.
- Email dikirim setelah transaksi commit. Kegagalan kirim dicatat `gagal` di `notification_logs` dan tidak membatalkan perubahan status. Setiap percobaan dicatat.
- Browser: halaman pelacakan memeriksa status tiap 15 detik. Saat status yang dilihat berubah menjadi `selesai` (bukan jika halaman baru dibuka dan statusnya sudah `selesai`), tampilkan banner dan, jika customer sudah menekan tombol Aktifkan notifikasi, notifikasi browser (Notification API). Izin notifikasi hanya diminta lewat klik tombol, bukan otomatis saat halaman dibuka. Hanya berfungsi selama tab terbuka.
- WhatsApp: pada detail order berstatus `selesai` atau `dibayar`, tombol Kirim WhatsApp membuka `https://wa.me/<nomor>?text=<pesan>`. Nomor dinormalisasi (hapus karakter non-digit, awalan 0 diganti 62). Staf yang menekan kirim; tidak ada pengiriman otomatis.

## 7. Service class dan transaksi

Letakkan di `app/Services/WorkOrderService.php`. Controller hanya memanggil method berikut:

- `register(Vehicle $vehicle, string $complaint, User $user): WorkOrder`
- `registerWalkIn(array $customerData, array $vehicleData, string $complaint, User $user): WorkOrder` (satu transaksi: pakai ulang pelanggan berdasarkan telepon atau buat baru, buat kendaraan bertipe `mobil`, lalu panggil `register`)
- `start(WorkOrder $wo, Mechanic $mechanic): WorkOrder`
- `addService(WorkOrder $wo, Service $service): WorkOrder`
- `removeService(WorkOrder $wo, Service $service): WorkOrder`
- `addPart(WorkOrder $wo, Sparepart $part, int $qty): WorkOrder`
- `updatePartQty(WorkOrder $wo, Sparepart $part, int $qty): WorkOrder`
- `removePart(WorkOrder $wo, Sparepart $part): WorkOrder`
- `finish(WorkOrder $wo, int $discount): WorkOrder`
- `pay(WorkOrder $wo, string $method, int $amount): Payment`
- `cancel(WorkOrder $wo): WorkOrder`
- `updatePublicNote(WorkOrder $wo, ?string $note): WorkOrder` (ditolak jika status `batal`; tidak mengubah status sehingga tidak masuk status log)
- `recalculateTotal(WorkOrder $wo): void`

Setiap method selain `recalculateTotal` membuka `DB::transaction()`, mengambil ulang order dengan `lockForUpdate()`, memvalidasi status, lalu mengubah data.

Setiap perubahan status memanggil `logStatus($wo, $from, $to, $user)` di dalam transaksi yang sama. `$user` boleh `null` (misalnya saat seeder); method selain `register` mengambilnya dari `auth()->user()`. `register` juga membuat `tracking_code` unik (8 karakter acak dari alfabet Bagian 6, ulangi jika bentrok). `WorkOrderService` menerima `NotificationService` lewat constructor, dan notifikasi dikirim setelah transaksi commit (lihat pola `finish` di bawah).

`BookingService` (`app/Services/BookingService.php`):

- `availableSlots(string $date): array` (daftar slot beserta sisa kapasitas)
- `create(array $data): Booking` (mengisi `dp_amount` dari konfigurasi)
- `submitDpProof(Booking $b, string $method, string $proofPath): Booking` (unggahan pelanggan)
- `receiveDp(Booking $b, string $method, ?string $proofPath = null): Booking` (verifikasi staf)
- `confirm(Booking $b, User $u): Booking` (ditolak jika `dp_amount > 0` dan `dp_paid_at` kosong)
- `reject(Booking $b, User $u, string $note): Booking`
- `cancel(Booking $b, ?User $u = null): Booking` (customer memakai kode booking, jadi `$u` kosong; endpoint publik menolak jika DP sudah dibayar)
- `checkIn(Booking $b, User $u): WorkOrder` (mencatat DP sebagai pembayaran `dp`)
- `markNoShow(Booking $b, User $u): Booking`

`NotificationService` (`app/Services/NotificationService.php`):

- `orderFinished(WorkOrder $wo): void`
- `bookingEvent(Booking $b, string $type): void`

Keduanya mengirim Mailable (`WorkOrderFinishedMail`, `BookingStatusMail`), menangkap exception, dan menulis `notification_logs`. Pola pembuatan booking:

```php
public function create(array $data): Booking
{
    $booking = DB::transaction(function () use ($data) {
        $this->assertBookable($data['booking_date'], $data['slot_time']);
        $taken = Booking::where('booking_date', $data['booking_date'])
            ->where('slot_time', $data['slot_time'])
            ->whereIn('status', ['menunggu', 'dikonfirmasi'])
            ->lockForUpdate()->count();
        if ($taken >= config('bengkel.booking.slot_capacity')) {
            throw new SlotFullException($data['slot_time']);
        }
        // tolak juga jika plat yang sama sudah punya booking aktif pada tanggal itu
        return Booking::create($data + [
            'booking_code' => $this->uniqueCode(),
            'status' => 'menunggu',
            'dp_amount' => (int) config('bengkel.booking.dp_amount'),
        ]);
    });
    $this->notifications->bookingEvent($booking, 'booking_dibuat');
    return $booking;
}
```

Pola pendaftaran dan nomor antrian:

```php
public function register(Vehicle $vehicle, string $complaint, User $user): WorkOrder
{
    return DB::transaction(function () use ($vehicle, $complaint, $user) {
        $active = WorkOrder::where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['menunggu', 'dikerjakan'])->exists();
        if ($active) {
            throw new InvalidTransitionException('Kendaraan masih punya order aktif.');
        }
        $today = today()->toDateString();
        $last = WorkOrder::where('queue_date', $today)->lockForUpdate()->max('queue_number');
        $wo = WorkOrder::create([
            'queue_date' => $today,
            'queue_number' => ($last ?? 0) + 1,
            'tracking_code' => $this->uniqueTrackingCode(),
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'complaint' => $complaint,
            'status' => 'menunggu',
        ]);
        $this->logStatus($wo, null, WorkOrderStatus::Menunggu, $user);
        return $wo;
    });
}
```

Sebagai cadangan, tangkap `UniqueConstraintViolationException` dan ulangi paling banyak 3 kali.

Pola penyelesaian dan notifikasi (`start`, `pay`, dan `cancel` mengikuti pola yang sama untuk ubah status dan `logStatus`, tetapi hanya `finish` yang mengirim notifikasi order):

```php
public function finish(WorkOrder $wo, int $discount): WorkOrder
{
    $wo = DB::transaction(function () use ($wo, $discount) {
        $wo = WorkOrder::lockForUpdate()->findOrFail($wo->id);
        $this->assertStatus($wo, WorkOrderStatus::Dikerjakan);
        // validasi: minimal ada satu jasa atau sparepart, diskon tidak melebihi subtotal
        $wo->update([
            'status' => WorkOrderStatus::Selesai,
            'finished_at' => now(),
            'discount' => $discount,
        ]);
        $this->recalculateTotal($wo);
        $this->logStatus($wo, WorkOrderStatus::Dikerjakan, WorkOrderStatus::Selesai, auth()->user());
        return $wo->refresh();
    });
    // setelah commit; NotificationService menangkap exception dan mencatat notification_logs
    $this->notifications->orderFinished($wo);
    return $wo;
}
```

Pola menambah sparepart (pola yang sama dipakai untuk `updatePartQty`, `removePart`, dan `cancel`):

```php
public function addPart(WorkOrder $wo, Sparepart $part, int $qty): WorkOrder
{
    return DB::transaction(function () use ($wo, $part, $qty) {
        $wo = WorkOrder::lockForUpdate()->findOrFail($wo->id);
        $this->assertStatus($wo, WorkOrderStatus::Dikerjakan);
        $part = Sparepart::lockForUpdate()->findOrFail($part->id);
        if ($part->stock < $qty) {
            throw new InsufficientStockException($part, $qty);
        }
        $part->decrement('stock', $qty);
        $line = $wo->partLines()->where('sparepart_id', $part->id)->first();
        if ($line) {
            $line->increment('qty', $qty);
        } else {
            $wo->partLines()->create([
                'sparepart_id' => $part->id, 'qty' => $qty, 'price' => $part->price,
            ]);
        }
        $this->recalculateTotal($wo);
        return $wo->refresh();
    });
}
```

Catatan: `lockForUpdate()` tidak berpengaruh di SQLite, tetapi transaksi dan rollback tetap bekerja. Uji perilaku penguncian hanya relevan di MySQL.

Pola pelunasan setelah DP:

```php
public function pay(WorkOrder $wo, string $method, int $amount): Payment
{
    return DB::transaction(function () use ($wo, $method, $amount) {
        $wo = $this->locked($wo, S::Selesai);
        $remaining = max(0, (int) $wo->total - (int) $wo->payments()->sum('amount'));
        if ($remaining === 0) {
            throw new PaymentException('Order sudah dibayar.');
        }
        if ($amount < $remaining) {
            throw new PaymentException('Jumlah pembayaran kurang.');
        }
        $payment = $wo->payments()->create(['method' => $method, 'amount' => $amount, 'paid_at' => now()]);
        $this->change($wo, S::Dibayar);

        return $payment;
    });
}
```

## 8. Kontrak REST API

Prefix `/api`. Semua endpoint kecuali login dan endpoint publik (`/api/public/*`, lihat akhir bagian ini) membutuhkan header `Authorization: Bearer <token>`. Endpoint yang disebut khusus admin hanya untuk role `admin`; selebihnya untuk admin dan kasir. Respons sukses berbentuk `{ data: ... }` (API Resource). Daftar berpaginasi memakai format paginasi Laravel dan menerima `page`, `per_page`, serta `search` bila relevan.

Auth:

- `POST /api/auth/login`: body `email`, `password`; respons `token` dan `user`
- `POST /api/auth/logout`
- `GET /api/auth/me`

Master data:

- `customers` dan `vehicles`: `apiResource` penuh untuk admin dan kasir
- `GET /api/vehicles/{id}/history`: riwayat servis satu kendaraan untuk staf (nomor antrian, status, tanggal antrian, keluhan, jasa, sparepart, tanggal selesai), terbaru lebih dulu
- `mechanics`, `services`, `spareparts`: `GET` untuk semua role (dipakai pada form), sedangkan `POST`, `PUT`, `DELETE` khusus admin
- `GET /api/spareparts` mendukung filter `low_stock=1`

Work order (daftarkan `/api/work-orders/board` sebelum `/api/work-orders/{id}`):

- `GET /api/work-orders/board`: antrian satu tanggal (default hari ini) dikelompokkan per status (menunggu, dikerjakan, selesai, dibayar, batal), menerima parameter `date`, dan menyertakan `meta.carry_over` berisi order dari tanggal sebelumnya yang masih `menunggu`, `dikerjakan`, atau `selesai`
- `GET /api/work-orders`: paginasi, filter `status` dan `date`
- `POST /api/work-orders`: body `vehicle_id`, `complaint` untuk pelanggan lama, atau `customer` (`name`, `phone`, `email`, `address`) dan `vehicle` (`plate_number`, `brand`, `model`, `year`) sekaligus untuk walk-in pelanggan baru (kasir diambil dari token, bukan dari body)
- `GET /api/work-orders/{id}`: detail beserta kendaraan, pelanggan, mekanik, jasa, sparepart, dan pembayaran
- `POST /api/work-orders/{id}/start`: body `mechanic_id`
- `POST /api/work-orders/{id}/services`: body `service_id`
- `DELETE /api/work-orders/{id}/services/{service}`
- `POST /api/work-orders/{id}/parts`: body `sparepart_id`, `qty`
- `PATCH /api/work-orders/{id}/parts/{part}`: body `qty`
- `DELETE /api/work-orders/{id}/parts/{part}`
- `POST /api/work-orders/{id}/finish`: body `discount`
- `POST /api/work-orders/{id}/pay`: body `method`, `amount`
- `POST /api/work-orders/{id}/cancel`
- `PATCH /api/work-orders/{id}/public-note`: body `public_note` (nullable, maksimal 500 karakter), catatan kondisi yang tampil di halaman pelacakan customer; 422 jika status `batal`

`{service}` dan `{part}` adalah id master (`services.id` dan `spareparts.id`), karena pasangan order dan master bersifat unik. Setiap aksi mengembalikan order terbaru (`WorkOrderResource`) agar frontend langsung memperbarui tampilan; `pay` juga menyertakan kembalian dan membandingkan `amount` dengan sisa tagihan. `WorkOrderResource` memuat `tracking_code`, `public_note`, `status_logs` (pada detail), kode booking asal jika ada, serta `services_total` dan `parts_total` untuk menampilkan subtotal terpisah.

Ringkasan:

- `GET /api/dashboard`: `counts` (jumlah per status), `revenue` (kas diterima hari ini), `low_stock`, `action_items` (`pending_bookings`, `unassigned_orders`, `unpaid_orders`), `payments` (`total`, `count`, `by_method` termasuk `dp`, `recent`), dan `unpaid_orders`
- `GET /api/reports/daily?date=`: jumlah order per status dan total pendapatan pada tanggal itu (khusus admin)

Booking (staf, admin dan kasir):

- `GET /api/bookings`: paginasi 20 per halaman, filter `status` dan `date`, terbaru lebih dulu
- `GET /api/bookings/{id}`
- `POST /api/bookings/{id}/confirm`
- `POST /api/bookings/{id}/reject`: body `staff_note` (wajib)
- `POST /api/bookings/{id}/cancel`
- `POST /api/bookings/{id}/check-in`: respons booking terbaru beserta `work_order` (`id`, `queue_number`, `tracking_code`) agar frontend langsung membuka tiket antriannya; DP ikut dicatat sebagai pembayaran
- `POST /api/bookings/{id}/no-show`
- `POST /api/bookings/{id}/dp`: mencatat DP setelah staf mencocokkan bukti. Body `method` (`transfer` atau `qris`), opsional multipart `proof` untuk bukti pengganti. Ditolak selama belum ada bukti.
- `GET /api/bookings/{id}/dp-proof`: membuka berkas bukti dari disk privat (404 jika tidak ada)

Booking hanya dibuat oleh customer lewat endpoint publik. Transisi tidak sah, konfirmasi sebelum DP diverifikasi, check-in di luar `booking_date`, atau kendaraan yang masih punya order aktif mengembalikan 422.

Log notifikasi (khusus admin):

- `GET /api/notification-logs`: paginasi 20 per halaman, terbaru lebih dulu

Endpoint publik (tanpa token, prefix `/api/public`, `throttle` 30 permintaan per menit per IP, respons `{ data: ... }`; pelacakan dan booking memakai `Public*Resource`, daftar jasa memakai `ServiceResource` yang hanya memuat field aman):

- `GET /api/public/track/{code}`: kode dinormalisasi (huruf kapital, tanpa spasi). Respons hanya memuat field yang diizinkan di Bagian 6: `queue_number`, `status`, `timeline` (daftar `{ status, at }` dari status log), `vehicle` (`brand`, `model`, `plate_masked`), `public_note`, `services` (nama), `parts` (`name`, `qty`), `estimated_finish_at`, dan `total`. Kode tidak ada: 404.
- `GET /api/public/booking-slots?date=YYYY-MM-DD`: daftar slot `{ time, remaining, available }` dengan `meta.dp_amount`. Tanggal yang melanggar aturan booking: 422.
- `GET /api/public/services`: daftar jasa untuk form booking dan landing (`id`, `name`, `price`, `est_minutes`).
- `POST /api/public/bookings`: body `customer_name`, `phone`, `email` (opsional), `plate_number`, `vehicle_type` (harus `mobil`), `brand`, `model`, `service_id` (opsional), `complaint` (opsional), `booking_date`, `slot_time`. Respons 201 berisi `booking_code`. Kapasitas penuh, tanggal tidak sah, dan plat ganda: 422.
- `GET /api/public/bookings/{code}`: cek booking, field sesuai Bagian 6 termasuk status DP. Kode tidak ada: 404.
- `POST /api/public/bookings/{code}/dp`: multipart `method` (`transfer` atau `qris`) dan `proof` (JPG/PNG/WEBP/PDF maksimal 4 MB). Respons booking terbaru dengan status bukti.
- `POST /api/public/bookings/{code}/cancel`: membatalkan booking milik kode itu (status `menunggu` atau `dikonfirmasi`) selama DP belum dibayar; selain itu 422.

Endpoint `POST /api/public/track/lookup` tidak disediakan; pencarian lewat plat nomor dibatalkan (Bagian 6).

Dokumentasikan semua endpoint di `API.md` lengkap dengan contoh request dan response.

## 9. Frontend Vue

Halaman staf memakai guard `meta.requiresAuth`. Halaman admin memakai `meta.roles = ['admin']`; kasir yang membukanya diarahkan ke `/dashboard`. Halaman `/login`, landing `/`, dan halaman publik customer (`/track`, `/track/:code`, `/booking`, `/booking/:code`) memakai `meta.public = true`: tanpa guard, dan halaman customer memakai layout `PublicLayout` (header sederhana dengan tautan Lacak servis dan Booking, tanpa `AppNavbar`).

- `/login`
- `/` landing publik: dua tombol utama (Booking servis dan Lacak servis), daftar layanan beserta tarif, dan sisa slot besok. Klik slot membawa `?date=&slot=` ke `/booking`. Tanpa tautan staf.
- `/dashboard`: kartu kas hari ini (total diterima, rincian tunai/transfer/QRIS/DP, pembayaran terakhir), antrian per status, perlu tindakan, tagihan menunggu bayar, dan stok menipis
- `/customers` dan `/vehicles`: daftar dengan pencarian dan paginasi, form tambah dan ubah, hapus dengan konfirmasi
- `/vehicles/:id/history`: riwayat servis satu kendaraan untuk staf
- `/mechanics`, `/services`, `/spareparts`: sama seperti di atas, khusus admin
- `/work-orders`: papan antrian dengan lima kolom status (menunggu, dikerjakan, selesai, dibayar, batal), pemilih tanggal, dan daftar order carry-over dari tanggal sebelumnya
- `/work-orders/new`: form pendaftaran satu layar; pilih kendaraan lama atau isi pelanggan dan kendaraan baru, lalu keluhan
- `/work-orders/:id`: detail order dengan tombol aksi sesuai status, rincian pembayaran (termasuk DP), dan sisa tagihan
- `/work-orders/:id/ticket`: tiket antrian siap cetak (nomor antrian besar, plat, kode pelacakan, dan QR), dibuka otomatis setelah pendaftaran dan setelah check-in
- `/work-orders/:id/receipt`: struk siap cetak lewat `window.print()`, memuat QR kode pelacakan, dengan CSS `@media print` yang menyembunyikan navbar
- `/bookings`: daftar booking untuk staf dengan filter status dan tanggal, kolom DP (nominal, metode, bukti), serta aksi catat/verifikasi DP, lihat bukti, konfirmasi, tolak (dengan catatan), batal, check-in, dan tidak hadir
- `/notifications`: log notifikasi (khusus admin)
- `/reports/daily`: laporan harian (khusus admin)
- `/track`: form kode pelacakan saja (publik)
- `/track/:code`: status kendaraan, tujuan QR (publik)
- `/booking`: form booking dari jauh hari (publik)
- `/booking/:code`: cek booking, unggah bukti DP, dan batalkan booking (publik)
- Halaman 404 untuk path lain

Perilaku:

- Papan antrian memanggil `GET /api/work-orders/board` tiap 10 detik dengan `setInterval`, dan menghentikannya di `onUnmounted`. Kartu menampilkan nomor antrian tiga digit, plat nomor, mekanik, dan status.
- Tombol aksi hanya tampil jika transisinya sah, tetapi backend tetap penentu. Setelah tiap aksi, ganti data order dengan respons API.
- Error 422 ditampilkan sebagai alert dengan `message` dari backend. Tombol aksi dinonaktifkan selama request berjalan agar tidak terkirim dua kali.
- Gunakan komponen bersama: `AppNavbar`, `MasterCrud`, `DataTable`, `ConfirmModal`, `StatusBadge` (untuk status order dan booking), `AlertMessage`, `PublicLayout`, `QrCode` (membungkus paket `qrcode`, props `value` dan `size`), dan `StatusTimeline`.
- Detail order menampilkan kode pelacakan, QR, tombol salin tautan pelacakan, linimasa status, form `public_note` (disimpan lewat `PATCH /api/work-orders/{id}/public-note`), dan, saat status `selesai` atau `dibayar`, tombol Kirim WhatsApp. Nomor dinormalisasi langsung di komponen (hapus karakter non-digit, awalan 0 diganti 62) lalu dibuka lewat `wa.me` di tab baru.
- Tiket antrian dan struk memakai `window.print()` dengan CSS `@media print` yang sama.
- `/bookings`: filter bawaan status `menunggu`. Konfirmasi dinonaktifkan sampai DP tercatat. Aksi DP memakai modal berisi metode dan unggahan bukti pengganti bila perlu; tombol Lihat bukti mengambil berkas lewat `GET /api/bookings/{id}/dp-proof`. Check-in hanya pada hari `booking_date`, tidak hadir hanya setelah tanggalnya lewat. Tolak memakai modal dengan isian catatan wajib. Setelah check-in berhasil, arahkan ke `/work-orders/:id/ticket`.

Perilaku halaman customer (Bahasa Indonesia, tata letak mobile-first karena sering dibuka dari HP):

- `/track`: input kode (otomatis huruf kapital) lalu menuju `/track/:code`. Tidak ada pencarian lewat plat nomor.
- `/track/:code`: tampilkan nomor antrian tiga digit, status (`StatusBadge`), linimasa, merek dan model, plat tersamar, `public_note`, jasa, sparepart, estimasi selesai (saat `dikerjakan`), dan total (saat `selesai` atau `dibayar`). Kode tidak ditemukan menampilkan pesan ramah dengan tautan kembali ke `/track`.
- Halaman pelacakan memanggil API tiap 15 detik dengan `setInterval`, menghentikannya di `onUnmounted` dan saat status `dibayar` atau `batal`. Tombol Aktifkan notifikasi memanggil `Notification.requestPermission()`. Saat status berubah menjadi `selesai`, tampilkan banner dan, jika izin diberikan, `new Notification(...)`; tidak ada notifikasi jika halaman baru dibuka dan statusnya sudah `selesai`. Tampilkan catatan bahwa notifikasi browser hanya berfungsi selama tab terbuka.
- `/booking`: pilih tanggal (mulai besok sampai batas hari ke depan, hari tutup dinonaktifkan), lalu muat slot dari `GET /api/public/booking-slots`; slot dengan sisa 0 dinonaktifkan. Slot yang diklik dari landing dipilih otomatis. Isi data diri, plat, merek, model (jenis selalu mobil), jasa (opsional), dan keluhan (opsional). Setelah berhasil, tampilkan kode booking, nominal DP, dan tautan ke `/booking/:code`, serta ingatkan customer untuk menyimpannya. Error 422 (misalnya slot baru saja penuh) tampil sebagai alert dan slot dimuat ulang.
- `/booking/:code`: tampilkan status booking, tanggal, jam, dan status DP; saat DP belum dibayar, sediakan pilihan transfer/QRIS dan unggahan bukti (maksimal 4 MB); tombol Batalkan (dengan `ConfirmModal`) hanya saat `menunggu` atau `dikonfirmasi` dan DP belum dibayar; saat `check_in`, tampilkan tautan ke `/track/<tracking_code>`.

## 10. Data seeder

`php artisan migrate:fresh --seed` harus menghasilkan data demo yang konsisten.

- Users: `admin@bengkel.test` (admin), `kasir1@bengkel.test` dan `kasir2@bengkel.test` (kasir). Password semuanya `password`.
- 5 mekanik (1 nonaktif). Tiga mekanik aktif masing-masing memegang satu order `dikerjakan`, dan batas beban 2 diuji lewat feature test.
- 10 jasa mobil yang realistis (ganti oli, servis ringan, tune up, ganti kampas rem, servis AC, balancing, spooring, dan sebagainya) dengan harga masuk akal.
- 15 sparepart, tiga di antaranya sudah di bawah `min_stock` (satu bahkan 0) agar peringatan stok bisa didemokan.
- 10 pelanggan dengan 12 kendaraan bermerek mobil. Semua pelanggan punya nomor telepon dan 7 di antaranya punya email.
- 9 work order untuk hari ini yang tersebar di semua status: 2 `menunggu`, 3 `dikerjakan`, 1 `selesai`, 2 `dibayar`, dan 1 `batal`. Ditambah 5 order lampau: 3 `dibayar` untuk laporan, 1 order menginap yang masih `dikerjakan` sebagai carry-over, dan 1 `batal`; tanggal antriannya dimundurkan tanpa mengubah `created_at`.
- Buat order lewat `WorkOrderService`, bukan insert langsung, agar total dan stok konsisten.
- Karena order dibuat lewat service, `tracking_code` dan status log terbentuk otomatis. Pengecualian demi demo: ubah `tracking_code` satu order `dikerjakan` menjadi `TRACK234` (sesuai alfabet kode pelacakan).
- Booking dibuat lewat `BookingService` untuk dua hari buka berikutnya (besok dan lusa). Hari pertama: slot 09:00 sengaja terisi tiga booking sehingga penuh (salah satunya memakai `booking_code` `BKNG2345`), slot 10:00 satu `menunggu`, slot 11:00 dua `dikonfirmasi` setelah DP diverifikasi, slot 12:00 satu `dibatalkan`, dan slot 13:00 satu `menunggu`. Hari kedua memakai kode `LUSA2345` dan `LUSA02` sampai `LUSA07` dengan pola serupa, termasuk satu booking yang bukti DP-nya sudah diunggah tetapi belum diverifikasi. Setiap booking memakai plat berbeda.
- Tiga booking bertanggal hari ini ditambahkan langsung agar demo check-in dan verifikasi DP bisa dilakukan: `HRINI01` sudah `dikonfirmasi` dengan DP transfer, `HRINI03` masih `menunggu` dengan bukti QRIS belum diverifikasi, dan `HRINI02` sudah check-in sehingga ordernya memakai DP sebagai pembayaran awal. Tiga booking lampau (`LEWAT01` tidak hadir, `LEWAT02` dibatalkan, `LEWAT03` dikonfirmasi tetapi tanggalnya sudah lewat) melengkapi filter.
- Seeder menulis satu bukti transfer contoh (`dp-proofs/demo-transfer.svg`) ke disk privat agar tombol Lihat bukti langsung berfungsi, memanggil `config(['mail.default' => 'log', 'queue.default' => 'sync'])` dan `Mail::fake()` agar tidak ada email nyata yang terkirim, lalu tetap menghasilkan baris `notification_logs`.
- Setelah seed, database SQLite bisa dibekukan menjadi dump di `database/dumps/` (`sqlite3 database/database.sqlite .dump`). Dump hanya berlaku untuk tanggal pembuatannya; bila demo mundur, jalankan `migrate:fresh --seed` lagi.

## 11. Pengujian

PHPUnit feature test dengan `RefreshDatabase` (SQLite in-memory). Saat ini 49 test (233 assertion) lulus lewat `php artisan test`. Cakupan wajib:

1. Nomor antrian bertambah per hari dan kembali ke 1 di hari baru.
2. Kendaraan dengan order aktif ditolak.
3. `start` ditolak jika mekanik nonaktif atau sudah memegang batas order.
4. `addPart` mengurangi stok. Jika stok kurang, terjadi exception dan stok serta order tidak berubah.
5. `updatePartQty` dan `removePart` mengembalikan stok.
6. `finish` menghitung total dengan diskon; diskon melebihi subtotal ditolak.
7. `pay` menolak amount kurang dari sisa tagihan. Jika berhasil, status menjadi `dibayar` dan ada record payment.
8. `cancel` mengembalikan seluruh stok; order `selesai` tidak bisa dibatalkan.
9. Transisi tidak sah (misalnya bayar saat `menunggu`) ditolak.
10. Akses API: tanpa token mengembalikan 401, kasir yang memanggil endpoint khusus admin mendapat 403, dan login dengan kredensial salah mengembalikan 422.
11. `register` membuat `tracking_code` 8 karakter dari alfabet yang diizinkan dan unik; setiap perubahan status (dari `register` sampai `bayar` atau `batal`) menulis satu baris `work_order_status_logs` dengan `from_status` dan `to_status` yang benar.
12. `GET /api/public/track/{code}` tanpa token: kode valid hanya mengembalikan field yang diizinkan (tidak ada nama, telepon, email, mekanik, id numerik, dan harga satuan), plat tersamar, `estimated_finish_at` hanya saat `dikerjakan`, `total` hanya saat `selesai` atau `dibayar`; kode tidak ada mengembalikan 404.
13. Booking publik: pembuatan sah menghasilkan `booking_code`, `dp_amount`, dan notifikasi `booking_dibuat`; ditolak (422) untuk tanggal hari ini, tanggal lampau, melewati `max_days_ahead`, hari tutup, slot tidak valid, kapasitas penuh (`SlotFullException`), dan plat yang sama sudah punya booking aktif pada tanggal itu. `GET /public/services` memuat `price` dan `est_minutes`, dan `GET /public/booking-slots` memuat `meta.dp_amount`.
14. DP booking: unggah bukti oleh pelanggan, verifikasi staf ditolak tanpa bukti, konfirmasi ditolak sebelum DP tercatat, pembatalan publik ditolak setelah DP dibayar, dan saat check-in DP masuk sebagai pembayaran sehingga `pay` hanya menagih sisanya.
15. Transisi booking: `confirm`, `reject` (catatan wajib), `cancel` oleh customer lewat kode, `check-in` hanya pada `booking_date` (membuat customer dan kendaraan, membuat work order, mengisi `work_order_id`), `check-in` ditolak jika kendaraan masih punya order aktif dan booking tetap `dikonfirmasi`, `tidak_hadir` hanya setelah tanggal lewat; transisi lain ditolak.
16. Endpoint pendukung: walk-in satu request membuat pelanggan, kendaraan, dan order; board menerima `date` dan mengirim `meta.carry_over`; `public-note` dan pembatalan ditolak pada status yang salah; error aturan bisnis menjadi JSON 422; dashboard memuat stok menipis, action items, pembayaran hari ini, dan tagihan belum dibayar; laporan harian khusus admin; riwayat kendaraan khusus staf; log notifikasi khusus admin; route `track/lookup` tetap 404.
17. Master data dan kesehatan aplikasi: CRUD pelanggan dengan pencarian/paginasi, pesan validasi Bahasa Indonesia, normalisasi plat, penghapusan data terpakai 422, filter `low_stock`, endpoint master butuh token, dan `GET /` mengembalikan status JSON.

Frontend: `npm run build` harus sukses, dan alur utama diuji manual di browser memakai skenario demo (Bagian 15). Unit test frontend bersifat opsional.

## 12. Rencana implementasi bertahap

Fase 0 sampai 4 adalah inti (MVP, memenuhi syarat dosen). Fase 5 dan 6 menambah fitur customer. Fase 7 sampai 9 menyelesaikan proyek.

- Fase 0, persiapan: buat repo `app-bengkel` dengan dua folder. Backend: `composer create-project laravel/laravel backend`, lalu `php artisan install:api` (Sanctum). Frontend: `npm create vue@latest frontend` (pilih Router dan Pinia, tanpa TypeScript), lalu pasang `axios`, `qrcode`, `tailwindcss`, dan `@tailwindcss/vite`. Atur `.env` dan `.env.example` di keduanya (termasuk `MAIL_MAILER=log` dan `FRONTEND_URL`), timezone, locale, `config/bengkel.php` (termasuk blok `booking` dan `dp_amount`), dan CORS. Selesai jika `php artisan serve` dan `npm run dev` berjalan di port 8000 dan 5173.
- Fase 1, auth: migration users, endpoint login, logout, dan me dengan Sanctum, `RoleMiddleware`, serta penanganan error JSON untuk `api/*`. Frontend: klien Axios, store `auth`, halaman login, router guard, dan layout navbar per role. Selesai jika login dan logout bekerja dari browser dan admin serta kasir melihat menu yang berbeda.
- Fase 2, master data: migration, model, `apiResource` dengan FormRequest dan Resource untuk customers, vehicles, mechanics, services, spareparts, plus `GET /vehicles/{id}/history`. Frontend: halaman daftar dan form untuk tiap master serta halaman riwayat kendaraan. Penghapusan data yang masih dipakai mengembalikan 422 dengan pesan ramah. Selesai jika semua CRUD berjalan dari UI.
- Fase 3, work order dasar: migration tabel 7 sampai 10 dan 12, model, enum, exception, method `register` dan `registerWalkIn` (termasuk `tracking_code` dan status log), serta endpoint board (dengan `date` dan `carry_over`), list, show, dan store. Frontend: papan antrian, form pendaftaran satu layar, dan halaman detail. Selesai jika pendaftaran menghasilkan nomor antrian yang benar, kode pelacakan unik, dan satu baris status log.
- Fase 4, transaksi inti: seluruh method `WorkOrderService` (setiap perubahan status memanggil `logStatus`), endpoint aksi, dan test nomor 1 sampai 9 serta 11. Frontend: tombol aksi dan penampilan pesan error 422. Selesai jika semua test lulus dan alur daftar sampai bayar berjalan dari UI.
- Fase 5, pelacakan dan notifikasi order: migration tabel 11 dan 13 (tabel `bookings` dibuat sekarang karena `notification_logs` punya FK ke sana; logikanya baru dikerjakan di Fase 6), `NotificationService::orderFinished`, `WorkOrderFinishedMail` beserta template emailnya, `updatePublicNote`, endpoint `public-note`, `notification-logs`, dan `track` dengan `PublicTrackingResource` dan `throttle` (tanpa pencarian plat). Frontend: landing publik `/`, komponen `PublicLayout`, `QrCode`, `StatusTimeline`; halaman `/track` dan `/track/:code` (polling 15 detik, banner, dan Notification API); tiket antrian `/work-orders/:id/ticket` yang dibuka setelah pendaftaran; kode pelacakan, QR, `public_note`, linimasa, dan tombol WhatsApp di detail order; serta `/notifications`. Test nomor 12. Selesai jika order baru bisa dilacak dari QR di tab lain, status di tab itu berubah tanpa refresh, dan menyelesaikan order menghasilkan satu baris `notification_logs`.
- Fase 6, booking dan DP: `BookingService`, `SlotFullException`, `BookingStatusMail`, `NotificationService::bookingEvent`, kolom DP, endpoint booking staf dan publik (termasuk `booking-slots`, `public/services`, unggah bukti DP, verifikasi DP, dan `dp-proof`). Frontend: `/booking`, `/booking/:code`, dan `/bookings` dengan aksi DP. Test nomor 13 sampai 15. Selesai jika customer bisa booking dan mengunggah bukti DP, staf bisa memverifikasi lalu mengonfirmasi, check-in pada hari H menghasilkan order dengan pembayaran DP, dan slot penuh ditolak.
- Fase 7, dashboard, laporan, struk: endpoint dashboard (kas, pembayaran, action items, tagihan belum dibayar, stok menipis) dan laporan harian, papan antrian dengan pemilih tanggal dan polling 10 detik, serta halaman struk dengan `window.print()` beserta QR kode pelacakan.
- Fase 8, seeder, test akses, dan dokumentasi API: seeder (Bagian 10), test nomor 10, 16, dan 17, dan `API.md`.
- Fase 9, penutup: README (cara menjalankan, akun demo, kode demo `TRACK234` dan `BKNG2345`, struktur), `DEMO.md`, uji `migrate:fresh --seed` dari nol, latihan demo, dan pemeriksaan build/test. Deployment Vercel disiapkan sebagai panduan (Bagian 14), tetapi demo dijalankan lokal. Selesai jika checklist di Bagian 16 terpenuhi.

Jika waktu mepet, korbankan berurutan: laporan harian, lalu struk, lalu deployment Vercel. Deployment memang belum dijalankan; demo lokal adalah jalur utama (lihat Bagian 14). Fase 0 sampai 5 dan seeder tidak boleh dikorbankan; Fase 6 (booking) dikorbankan paling akhir.

## 13. Struktur folder yang diharapkan

```
app-bengkel/
  README.md  API.md  DEMO.md  Perencanaan_Proyek_Aplikasi_Antrian_Bengkel.md
  docs/ASUMSI.md
  backend/                       Laravel 12 (hanya API)
    app/
      Enums/WorkOrderStatus.php
      Exceptions/                5 exception domain (termasuk SlotFullException)
      Http/Controllers/Api/      Auth, Dashboard, Master, Customer, Vehicle, Mechanic,
                                 Service, Sparepart, WorkOrder, Report, Booking,
                                 NotificationLog, PublicTracking
      Http/Middleware/RoleMiddleware.php
      Http/Requests/
      Http/Resources/            termasuk Public*Resource untuk endpoint publik
      Mail/                      WorkOrderFinishedMail, BookingStatusMail
      Models/                    termasuk WorkOrderServiceItem
      Services/                  WorkOrderService, BookingService, NotificationService
    bootstrap/app.php            alias middleware, penanganan error JSON
    config/                      bengkel.php, cors.php
    database/                    migrations, factories, seeders, dumps (dump demo)
    resources/views/emails/      template email (satu-satunya Blade)
    routes/api.php
    tests/Feature/
  frontend/                      Vue 3 + Vite + Tailwind (JavaScript)
    src/
      api/client.js
      stores/auth.js
      router/index.js
      views/                     Login, Dashboard, Customers, Vehicles, VehicleHistory,
                                 Mechanics, Services, Spareparts, WorkOrderBoard,
                                 WorkOrderCreate, WorkOrderDetail, Ticket, Receipt,
                                 Bookings, Notifications, ReportDaily, NotFound
      views/public/              Landing, TrackLookup, TrackDetail, BookingCreate,
                                 BookingStatus
      components/                AppNavbar, PublicLayout, MasterCrud, DataTable,
                                 ConfirmModal, StatusBadge, StatusTimeline, QrCode,
                                 AlertMessage
      utils/                     rupiah.js
      assets/tailwind.css        token warna dan utilitas bersama
    index.html  vite.config.js  vercel.json  .env.example
```

Backend Vercel (`Dockerfile.vercel`, `Caddyfile`, `vercel.json`) belum dibuat karena deployment belum dijalankan; panduan di Bagian 14 tetap menjadi acuan bila nanti diperlukan.

## 14. Deployment ke Vercel

Bagian ini adalah panduan, bukan status. Saat ini aplikasi dijalankan dan didemokan secara lokal (`php artisan serve` di port 8000 dan `npm run dev` di port 5173); deployment Vercel belum dijalankan. `frontend/vercel.json` sudah ada, sedangkan berkas backend Vercel belum dibuat. Bila nanti dideploy, frontend dan backend menjadi dua project Vercel dari repo yang sama, masing-masing dengan Root Directory sendiri. Database harus berada di luar Vercel karena runtime backend bersifat stateless.

Frontend (project Vercel pertama):

- Root Directory `frontend`, preset Vite, perintah build `npm run build`, output `dist`.
- Variabel lingkungan `VITE_API_URL` berisi URL backend produksi berakhiran `/api`. Vite menanam nilainya saat build, jadi setiap kali nilainya berubah, deploy ulang.
- `frontend/vercel.json` mengarahkan semua path ke `/index.html` agar Vue Router mode history tidak 404 saat halaman di-refresh:

```json
{
  "rewrites": [{ "source": "/(.*)", "destination": "/index.html" }]
}
```

Backend (project Vercel kedua, belum disiapkan):

- Vercel punya panduan resmi untuk menjalankan Laravel di function berbasis container dengan Docker dan FrankenPHP: https://vercel.com/kb/guide/laravel-php-with-docker. Ikuti panduan itu di folder `backend/` (file `Dockerfile.vercel`, `Caddyfile`, dan `vercel.json` dengan service `runtime: container`), lalu atur Root Directory project ke `backend`. Fitur ini tergolong baru (panduannya terbit September 2026), jadi baca dokumentasi terbarunya sebelum mulai.
- Runtime stateless, tanpa disk yang tahan lama. Pakai `LOG_CHANNEL=stderr`, `SESSION_DRIVER=array`, `CACHE_STORE=array`, dan `QUEUE_CONNECTION=sync`. Token Sanctum tersimpan di database, jadi tidak terpengaruh.
- Jangan menjalankan `php artisan config:cache` atau `php artisan optimize` saat build image. `APP_KEY` dan kredensial database dibaca saat runtime dari environment variable Vercel.
- Environment variable backend: `APP_KEY` (dari `php artisan key:generate --show`), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `DB_*`, dan `FRONTEND_URL` (URL frontend di Vercel, dipakai untuk CORS dan tautan di email), serta `MAIL_MAILER=log` (email tercatat di log Vercel) atau kredensial SMTP jika tersedia.
- Database: MySQL atau PostgreSQL terkelola di luar Vercel. Pilih penyedia yang punya paket gratis dan cek ketentuan terbarunya. Karena tidak ada shell di Vercel, jalankan `php artisan migrate --force --seed` dari komputer lokal dengan kredensial database produksi.
- Setelah deploy, uji: buka URL frontend, login dengan akun seeder, buka papan antrian, lalu daftarkan satu order. Buka `/track/TRACK234` di jendela tanpa login dan pastikan statusnya tampil, serta `/booking` memuat daftar slot. Pastikan tab Network di browser tidak menampilkan error CORS.

Cadangan jika deployment backend bermasalah: jalankan keduanya secara lokal saat demo (`php artisan serve` dan `npm run dev`), atau pasang backend di layanan lain yang mendukung PHP atau Docker dan arahkan `VITE_API_URL` ke sana. Backend wajib memakai HTTPS jika frontend dibuka dari URL Vercel.

## 15. Skenario demo (isi untuk DEMO.md)

Durasi sekitar 10 menit. Demo dijalankan lokal: backend `http://127.0.0.1:8000`, frontend `http://localhost:5173`. Setiap langkah menunjukkan salah satu syarat dosen atau fitur customer.

1. Buka `http://localhost:5173`. Tunjukkan landing publik: daftar layanan, tarif, dan sisa slot besok. Masuk lewat tombol staf dan login sebagai `kasir1@bengkel.test` dengan kata sandi `password`.
2. Buka papan antrian. Tunjukkan order hari ini beserta nomor antrian dan lima kolom status, lalu bagian carry-over "Masih di bengkel dari tanggal sebelumnya".
3. Buka `/track` di jendela tanpa login, masukkan `TRACK234`. Tunjukkan status, linimasa, dan plat yang disamarkan. Tekan Aktifkan notifikasi dan biarkan tab terbuka.
4. Dari panel staf, mulai satu order `menunggu` dengan mekanik aktif. Tambahkan jasa dan sparepart, isi catatan kondisi kendaraan, lalu selesaikan dan bayar. Tunjukkan total, kembalian, dan struk.
5. Masuk sebagai admin. Tampilkan dashboard (kas hari ini, pembayaran, tagihan menunggu bayar, stok menipis), laporan harian, dan log notifikasi beserta statusnya; jelaskan bahwa email tidak keluar sungguhan karena `MAIL_MAILER=log`/`Mail::fake()`. Buka detail order `selesai` dan tunjukkan tombol Kirim WhatsApp.
6. Buka `/booking`, pilih tanggal besok dan slot `10:00`, isi data, lalu kirim. Simpan kode booking yang muncul.
7. Di halaman status booking, unggah bukti DP (contoh gambar di bawah 4 MB). Status berubah menjadi "bukti terkirim".
8. Login staf, buka menu Booking, pilih status `Menunggu` dan tanggal besok, lalu temukan kode tadi. Klik Lihat bukti, lalu Verifikasi DP. Status DP menjadi "Transfer · Lunas", kemudian klik Konfirmasi.
9. Tunjukkan penolakan sebelum DP: pada booking `HRINI03` (hari ini, status `Menunggu`, bukti QRIS belum diverifikasi), klik Konfirmasi. Muncul pesan bahwa DP belum diterima. Verifikasi DP-nya, lalu konfirmasi.
10. Buka booking `HRINI01` (hari ini, sudah dikonfirmasi) dan klik Check-in. Order baru mendapat nomor antrian dan kode pelacakan, DP tercatat sebagai pembayaran awal, dan detail order menunjukkan sisa tagihan.
11. Tunjukkan penolakan slot penuh: buat booking ke slot `09:00` besok. Tiga slot itu sudah terisi di data demo, jadi permintaan ditolak dengan pesan yang jelas.
12. Jelaskan arsitektur singkat: Vue, Vue Router, Pinia, Axios, dan Tailwind di depan; Laravel dengan Sanctum, service bisnis, dan transaksi database di belakang; SQLite untuk pengembangan; endpoint publik tanpa login memakai resource terbatas.

Hindari:

- Mengubah stok atau status di luar `WorkOrderService` atau di luar transaksi.
- Menaruh aturan bisnis di controller atau di komponen Vue.
- Memakai float untuk uang, atau menghitung total dari harga master (selalu dari snapshot).
- Meng-commit `.env`, atau membuka endpoint tanpa autentikasi selain login dan `/api/public/*`.
- Menulis URL backend langsung di kode frontend (selalu lewat `VITE_API_URL`).
- Memakai resource staf di endpoint publik, atau menampilkan nama, telepon, email, mekanik, atau id numerik ke customer (selalu lewat `Public*Resource`).
- Mengirim email di dalam transaksi, atau membiarkan kegagalan email membatalkan perubahan status.

Checklist sebelum demo:

- Repo baru berisi `backend/` dan `frontend/`, riwayat commit rapi, dan `.env.example` lengkap di keduanya.
- 13 tabel dengan foreign key, dan `migrate:fresh --seed` sukses dari nol.
- Semua aturan Bagian 6 berjalan, `php artisan test` hijau, dan `npm run build` sukses.
- Pelacakan lewat kode dan QR, notifikasi selesai (email tercatat, banner, notifikasi browser), serta booking dari jauh hari dengan DP berjalan dari UI; test nomor 12 membuktikan endpoint publik tidak membocorkan data pribadi.
- Demo lokal siap: `php artisan serve` dan `npm run dev` berjalan di port 8000 dan 5173, dan login berhasil dari `http://localhost:5173`.
- README, `API.md`, `DEMO.md`, dan `docs/ASUMSI.md` tersedia serta sesuai perilaku aplikasi.
- Skenario demo sudah dilatih minimal dua kali.

## 17. Ringkasan instruksi pengerjaan

Acuan singkat saat mengerjakan proyek:

```
Baca dokumen perencanaan sepenuhnya. Bangun monorepo app-bengkel: backend Laravel 12 (hanya API, autentikasi Sanctum) di folder backend/ dan frontend Vue 3 (Vite, Pinia, Vue Router, Axios, Tailwind, qrcode) di folder frontend/, khusus bengkel mobil, termasuk portal customer publik (pelacakan lewat kode atau QR, notifikasi selesai, dan booking dari jauh hari dengan DP transfer/QRIS). Ikuti Bagian 12 fase demi fase, mulai dari Fase 0. Aturan bisnis hanya boleh ada di backend. Setelah tiap fase, jalankan test dan build, ringkas apa yang selesai, lalu lanjut ke fase berikutnya. Catat setiap asumsi di docs/ASUMSI.md. Jangan menambah fitur di luar scope.
```
