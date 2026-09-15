# Laravel PoS (Point of Sale) Boilerplate

Boilerplate aplikasi **Point of Sale (PoS) serbaguna (General PoS)** berbasis Laravel dan Docker. Dirancang untuk dapat digunakan pada berbagai jenis bisnis (Retail, F&B/Cafe, Restoran, Toko Kelontong, hingga Jasa).

Boilerplate ini mengadopsi standar arsitektur **Domain-Driven Design (DDD)** dan **Action Pattern** terinspirasi dari buku terkemuka **_Laravel Beyond CRUD_** serta standar keamanan transaksi dari **_Securing Laravel_**.

---

## Fitur Unggulan

1. **Multi-Store / Multi-Outlet Ready**:
    - Struktur database siap cabang/outlet (`outlets`), kasir, dan sesi kasir (`cashier_shifts` buka/tutup kasir dengan saldo awal & akhir).
2. **Katalog Produk & Varian (General)**:
    - Mendukung produk fisik berstok, produk jasa (tanpa stok), barcode/SKU, dan multi-varian (misal: _Ukuran Regular/Large_, _Cold/Hot_, _250g_).
    - Pengurangan stok otomatis dan riwayat mutasi stok (`stock_movements`).
3. **Cetak Nota Kasir PDF (Thermal & Formal)**:
    - **Thermal Struk (58mm & 80mm)**: Didesain presisi untuk printer thermal kasir (menggunakan `barryvdh/laravel-dompdf` dengan font monospace).
    - **Faktur Penjualan Resmi (A4 PDF)**: Faktur lengkap dengan rincian PPN, biaya layanan (service charge), diskon, dan subtotal.
4. **Pembayaran Midtrans (Sandbox Ready & Graceful Halt)**:
    - Terintegrasi dengan **Midtrans Snap API (Sandbox Mode)** untuk pembayaran online (QRIS, GoPay, Bank Transfer, Kartu Kredit).
    - **Timing-Safe Webhook Handler**: Menggunakan `hash_equals()` untuk memverifikasi SHA-512 Signature key Midtrans.
    - **Feature Halt / Offline Friendly**: Jika belum memiliki API key Midtrans atau sedang offline, sistem otomatis beralih ke mode mock/bypass yang aman tanpa membuat transaksi error.
5. **Interactive POS Register Screen**:
    - Antarmuka layar kasir interaktif berbasis Tailwind CSS langsung tersedia di `http://localhost:8000` (split-screen: filter kategori, quick search barcode, keranjang belanja real-time, kalkulator uang kembalian, dan cetak PDF langsung).

---

## Referensi Buku & Best Practice Keamanan Kode

Boilerplate ini dibangun dengan mengacu pada literatur dan standar industri berikut:

### 1. **"Securing Laravel" & "Practical Laravel Security"** oleh _Stephen Rees-Carter_

- **Validasi Ketat Input**: Semua endpoint transaksi menggunakan `FormRequest` khusus (`CreateOrderRequest`) dengan validasi typed Enum.
- **Timing-Attack Prevention**: Verifikasi signature Midtrans Webhook menggunakan fungsi timing-safe `hash_equals()`.
- **Database Transaction Atomicity**: Seluruh mutasi pesanan, pembayaran, dan pemotongan stok dibungkus dalam `DB::transaction()` untuk mencegah inkonsistensi saldo/stok.
- **Rate Limiting**: Endpoint order dan webhook dilindungi oleh middleware `throttle` bawaan Laravel.

### 2. **"Laravel Beyond CRUD"** oleh _Brent Roose & Tim Spatie_

- **Pola Actions**: Logika transaksi diekstrak ke dalam Action class mandiri (`CreateOrderAction`).
- **PHP 8.3 Enums**: Menggunakan native Enums (`OrderStatus`, `PaymentStatus`, `PaymentMethod`, `ProductType`, `StockMovementType`) untuk mencegah penggunaan _magic strings_.
- **Thin Controllers**: Controller hanya bertugas memvalidasi request HTTP dan memanggil Action/Service.

---

## Rekomendasi Tampilan: Template Online vs Buat Sendiri?

Aplikasi Point of Sale memiliki **2 kebutuhan tampilan yang bertolak belakang**:

| Fitur                          | Pendekatan Terbaik                                                                                          | Alasan                                                                                                                                                                                                                                                |
| ------------------------------ | ----------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Layar Kasir (POS Register)** | **Buat Sendiri (Custom UI)** _(Sudah disediakan di boilerplate ini)_                                        | Layar kasir butuh UX khusus: **Single-Screen tanpa reload**, tombol touch-friendly besar, barcode scanner shortcut, keranjang real-time, kalkulator kembalian cepat, dan popup cetak struk. Template admin biasa terlalu kaku dan lambat untuk kasir. |
| **Admin Panel / Back-Office**  | **Gunakan Template Online Modern** (misal: [Tabler](https://tabler.io), [TailAdmin](https://tailadmin.com)) | Menghemat banyak waktu saat membuat tabel CRUD produk, kategori, rekap kasir per tanggal, dan grafik laporan penjualan.                                                                                                                               |

---

## Panduan Menjalankan dengan Docker

### 1. Menjalankan Container

Stack Docker terdiri dari **PHP 8.3-FPM (`app`)**, **Nginx (`web` port 8000)**, **MariaDB 10.11 (`db` port 3307)**, dan **Redis (`redis` port 6380)**:

```bash
# Menjalankan seluruh container di background
docker compose up -d

# Cek status container
docker compose ps
```

### 2. Migrasi & Seeder Demo Toko

Untuk mengisi database dengan data demo toko (Kategori, Produk ber-barcode, Varian, Outlet, Kasir, dan Shift aktif):

```bash
docker compose exec app php artisan migrate:fresh --seed
```

### 3. Akses Aplikasi

- **Layar Kasir Interaktif (Web POS)**: [http://localhost:8000](http://localhost:8000)
- **API Katalog Produk**: [http://localhost:8000/api/v1/products](http://localhost:8000/api/v1/products)
- **API Kategori**: [http://localhost:8000/api/v1/categories](http://localhost:8000/api/v1/categories)
- **API Riwayat Orders**: [http://localhost:8000/api/v1/orders](http://localhost:8000/api/v1/orders)

---

## Konfigurasi Midtrans Sandbox

Pengaturan Midtrans terdapat di `.env`:

```env
# Aktifkan (true) atau nonaktifkan/halt (false) fitur Midtrans
MIDTRANS_ENABLED=true

# Kunci API Sandbox dari dashboard Midtrans Anda
MIDTRANS_SERVER_KEY=SB-Mid-server-YOUR_KEY
MIDTRANS_CLIENT_KEY=SB-Mid-client-YOUR_KEY

# Set true jika siap live production
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true
```

> **Catatan Feature Halt**: Jika `MIDTRANS_ENABLED=false` atau server key dibiarkan default (`DEMO_TEST_KEY`), sistem tidak akan error. Transaksi akan tetap sukses tersimpan dengan status `pending` dan token mock simulasi.

---

## Endpoint Cetak PDF

- **Struk Kasir Thermal (58mm)**:
    ```http
    GET /api/v1/orders/{order_id}/receipt-pdf?width=58
    ```
- **Struk Kasir Thermal (80mm)**:
    ```http
    GET /api/v1/orders/{order_id}/receipt-pdf?width=80
    ```
- **Faktur Penjualan Resmi (A4)**:
    ```http
    GET /api/v1/orders/{order_id}/invoice-pdf
    ```
