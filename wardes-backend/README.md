# WARDES Backend — Laravel 11

Backend REST API pusat untuk ekosistem WARDES (Warung Desa).

## Persyaratan
- PHP 8.2+
- Composer 2
- MySQL 8+
- Ekstensi PHP: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, curl, fileinfo
- Firebase service account JSON (untuk push notification; opsional saat pengembangan)

## Instalasi
Paket ini adalah source overlay aplikasi Laravel 11. Buat kerangka Laravel terlebih dahulu:

```bash
composer create-project laravel/laravel:^11.0 wardes-backend
cd wardes-backend
```

Ekstrak isi ZIP ini ke dalam folder `wardes-backend`, gabungkan folder `app`, `bootstrap`, `config`, `database`, `routes` dan file `.env.example` (jangan menimpa `.env` yang sudah berisi data Anda).

```bash
composer require laravel/sanctum
cp .env.example .env
php artisan key:generate
```

Atur koneksi MySQL dan kredensial gateway/FCM di `.env`, lalu:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

API tersedia di `http://127.0.0.1:8000/api/v1`.

## Akun admin awal
Seeder membuat akun:
- Phone: `081234567890`
- Password: `WardesAdmin!2026`

Segera ubah password setelah login pertama. Akun ini hanya untuk instalasi lokal awal.

## Autentikasi
`POST /api/v1/auth/register` dan `POST /api/v1/auth/login` menghasilkan Bearer token.
Kirim header `Authorization: Bearer TOKEN` untuk endpoint terlindungi.

## Catatan gateway
- Midtrans: menggunakan Snap API melalui HTTP Laravel; isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_IS_PRODUCTION`.
- Xendit: menggunakan Invoice API; isi `XENDIT_SECRET_KEY`.
- Untuk `payment_gateway` digital, pilih provider melalui `PAYMENT_PROVIDER=midtrans|xendit`.
- Webhook URL: `POST /api/v1/webhooks/payment-gateway`.
- Atur callback URL pada dashboard provider. Verifikasi Midtrans menggunakan signature key; Xendit menggunakan `XENDIT_CALLBACK_TOKEN`.
- Gateway tidak dapat benar-benar memproses transaksi sebelum kredensial merchant yang valid dikonfigurasi.

## Desain transaksi
- Satu checkout = satu warung.
- Stok dikunci dengan `lockForUpdate()` dan dikurangi dalam transaksi database.
- Pembayaran COD tetap `unpaid` sampai pesanan ditandai `completed`.
- Status pembayaran digital hanya berubah menjadi `paid` setelah webhook tervalidasi.
- Webhook idempotent: event pembayaran berulang tidak menggandakan perubahan.

## Endpoint utama
Lihat `routes/api.php`. Semua endpoint memakai prefix `/api/v1`.
