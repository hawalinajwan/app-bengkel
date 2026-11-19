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
GET /public/services
GET /public/booking-slots?date=2026-11-20
GET /public/track/TRACK234
POST /public/track/lookup
{"plate_number":"B001ABC","phone_suffix":"1234"}
POST /public/bookings
GET /public/bookings/BKNG2345
POST /public/bookings/BKNG2345/cancel
```

## Dashboard dan laporan

`GET /dashboard` untuk admin/kasir. `GET /reports/daily?date=2026-11-19` untuk admin. `GET /notification-logs` untuk admin.

## Master data

`customers`, `vehicles`, `mechanics`, `services`, dan `spareparts` memakai `GET /{resource}`, `GET /{resource}/{id}`, serta endpoint CRUD yang tersedia pada route resource. Contoh: `POST /customers` с `{"name":"Budi","phone":"0812345678"}`.

## Work order

```http
GET /work-orders
GET /work-orders/board
POST /work-orders
{"vehicle_id":1,"complaint":"Ganti oli"}
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

`GET /bookings`, `GET /bookings/{id}`, `POST /bookings/{id}/confirm`, `POST /bookings/{id}/reject` dengan `staff_note`, `POST /bookings/{id}/cancel`, `POST /bookings/{id}/check-in`, dan `POST /bookings/{id}/no-show`.

Aturan transisi, validasi, status HTTP, dan pembatasan field publik mengikuti `Perencanaan_Proyek_Aplikasi_Antrian_Bengkel.md`.
