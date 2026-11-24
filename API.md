# API

Base URL lokal: `http://127.0.0.1:8000/api`. Respons JSON. Endpoint publik tidak memerlukan token; endpoint lain memakai `Authorization: Bearer <token>`.

## Auth

```http
POST /auth/login
{"email":"kasir1@bengkel.test","password":"password"}
GET /auth/me
POST /auth/logout
```

## Publik

```http
GET /public/services                  (id, name, price, est_minutes)
GET /public/booking-slots?date=2026-11-20   (data slot + meta.dp_amount)
GET /public/track/TRACK234
POST /public/bookings
GET /public/bookings/BKNG2345         (termasuk status DP)
POST /public/bookings/BKNG2345/dp   (multipart: method=transfer|qris, proof)
POST /public/bookings/BKNG2345/cancel   (ditolak 422 bila DP sudah dibayar)
```

Tidak ada endpoint pencarian lewat plat nomor. Pelacakan hanya memakai kode atau QR.

## Dashboard dan laporan

`GET /dashboard` untuk admin/kasir. `GET /reports/daily?date=2026-11-19` untuk admin. `GET /notification-logs` untuk admin.

## Master data

`customers`, `vehicles`, `mechanics`, `services`, dan `spareparts` memakai `GET /{resource}`, `GET /{resource}/{id}`, serta endpoint CRUD yang tersedia pada route resource. Contoh: `POST /customers` dengan `{"name":"Budi","phone":"0812345678"}`. Riwayat servis satu kendaraan: `GET /vehicles/{id}/history`.

## Work order

```http
GET /work-orders
GET /work-orders/board?date=2026-11-24   (meta.carry_over berisi order tanggal sebelumnya)
POST /work-orders
{"vehicle_id":1,"complaint":"Ganti oli"}
POST /work-orders   (walk-in pelanggan baru)
{"customer":{"name":"Budi","phone":"0812345678"},"vehicle":{"plate_number":"B1234XY","brand":"Toyota","model":"Avanza"},"complaint":"Ganti oli"}
GET /work-orders/1
POST /work-orders/1/start
{"mechanic_id":1}
POST /work-orders/1/services
{"service_id":1}
POST /work-orders/1/parts
{"sparepart_id":1,"qty":1}
POST /work-orders/1/finish
{"discount":0}
POST /work-orders/1/pay
{"method":"tunai","amount":75000}
POST /work-orders/1/cancel
PATCH /work-orders/1/public-note
{"public_note":"Silakan ambil sore ini"}
```

## Booking staf

`GET /bookings`, `GET /bookings/{id}`, `POST /bookings/{id}/confirm`, `POST /bookings/{id}/reject` dengan `staff_note`, `POST /bookings/{id}/cancel`, `POST /bookings/{id}/check-in`, `POST /bookings/{id}/no-show`, dan `POST /bookings/{id}/dp` dengan `{"method":"transfer|qris"}` (opsional multipart `proof` bila staf perlu mengunggah pengganti). Bukti pelanggan dikirim lewat `POST /public/bookings/{code}/dp` multipart `method=transfer|qris` + `proof` (JPG/PNG/WEBP/PDF maks 4 MB); staf memverifikasi setelah bukti ada. Bukti dibuka lewat `GET /bookings/{id}/dp-proof`. Konfirmasi booking ditolak (422) selama DP belum diverifikasi; DP masuk sebagai pembayaran `dp` saat check-in.

`GET /work-orders/{id}` mengembalikan `services_total` dan `parts_total` selain `total`, sehingga harga jasa dan sparepart dapat ditampilkan terpisah.

Aturan transisi, validasi, status HTTP, dan pembatasan field publik mengikuti `Perencanaan_Proyek_Aplikasi_Antrian_Bengkel.md`.
