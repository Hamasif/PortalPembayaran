# Portal Pembayaran Mahasiswa

Aplikasi web pengelolaan profil mahasiswa, tagihan UKT, dan pembayaran pendidikan dengan **QRIS**. Dibangun dengan Laravel, Blade + Tailwind CSS, dan MySQL.

> **Status pembayaran**: Integrasi QRIS menggunakan **mock provider** untuk pengembangan lokal. Pembayaran sungguhan **belum aktif** sampai kredensial merchant resmi tersedia dan adapter provider resmi diimplementasikan.

---

## Daftar Isi

1. [Persyaratan Sistem](#1-persyaratan-sistem)
2. [Instalasi Dependencies](#2-instalasi-dependencies)
3. [Pembuatan Database MySQL](#3-pembuatan-database-mysql)
4. [Konfigurasi .env](#4-konfigurasi-env)
5. [Konfigurasi Google Cloud OAuth](#5-konfigurasi-google-cloud-oauth)
6. [Pengaturan Domain Email yang Diizinkan](#6-pengaturan-domain-email-yang-diizinkan)
7. [Migrasi Database & Data Awal](#7-migrasi-database--data-awal)
8. [Import Data Mahasiswa](#8-import-data-mahasiswa)
9. [Pembuatan Akun Admin Awal](#9-pembuatan-akun-admin-awal)
10. [Menjalankan Aplikasi](#10-menjalankan-aplikasi)
11. [Menjalankan Test](#11-menjalankan-test)
12. [Konfigurasi Payment Gateway Sandbox](#12-konfigurasi-payment-gateway-sandbox)
13. [Struktur Data & Aturan Bisnis](#13-struktur-data--aturan-bisnis)
14. [Prosedur Deployment HTTPS](#14-prosedur-deployment-https)
15. [Backup dan Pemeliharaan](#15-backup-dan-pemeliharaan)
16. [Catatan Keamanan](#16-catatan-keamanan)

---

## 1. Persyaratan Sistem

| Komponen | Versi minimum |
|---|---|
| PHP | 8.3 (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `intl`, `fileinfo`, `bcmath`, `curl`) |
| Composer | 2.x |
| Node.js + npm | 20+ |
| MySQL | 8.0+ |

> Aplikasi ini dikembangkan dan diuji dengan PHP 8.3 + MySQL 8.0 (Laragon/XAMPP juga dapat digunakan).

## 2. Instalasi Dependencies

```bash
# Dependencies PHP
composer install

# Dependencies frontend (Tailwind CSS 4, Vite, Chart.js, qrcode)
npm install

# Build aset frontend produksi
npm run build
```

## 3. Pembuatan Database MySQL

Buat dua database: satu untuk aplikasi, satu untuk test otomatis.

```sql
CREATE DATABASE portal_pembayaran_mahasiswa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE portal_pembayaran_mahasiswa_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Atau via CLI:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS portal_pembayaran_mahasiswa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS portal_pembayaran_mahasiswa_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 4. Konfigurasi .env

Salin contoh konfigurasi lalu sesuaikan:

```bash
cp .env.example .env
php artisan key:generate
```

Nilai penting yang **wajib** disesuaikan sebelum dipakai:

| Kunci | Keterangan |
|---|---|
| `DB_*` | Kredensial MySQL Anda |
| `APP_URL` | URL publik aplikasi (harus konsisten dengan redirect OAuth) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Kredensial Google OAuth (bagian 5) |
| `PAYMENT_GATEWAY_WEBHOOK_SECRET` | Secret verifikasi webhook — **wajib**, tanpa ini webhook ditolak (HTTP 503) |

> Jangan pernah meng-commit file `.env`. File tersebut sudah masuk `.gitignore`.

## 5. Konfigurasi Google Cloud OAuth

1. Buka [Google Cloud Console](https://console.cloud.google.com/) → buat/buka project.
2. Menu **APIs & Services → OAuth consent screen**:
   - User type: **Internal** (jika organisasi memakai Google Workspace) atau External.
   - Tambahkan domain aplikasi ke **Authorized domains**.
3. Menu **Credentials → Create Credentials → OAuth client ID**:
   - Application type: **Web application**.
   - **Authorized redirect URIs**: `https://domain-anda.ac.id/auth/google/callback` (di lokal: `http://localhost:8000/auth/google/callback`).
4. Salin *Client ID* dan *Client secret* ke `.env`:

```env
GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxx
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Cara kerja login:
- Identitas akun disimpan sebagai `users.google_id` (ID stabil), bukan hanya email.
- Email Google wajib berstatus **verified** — email belum terverifikasi ditolak.
- Domain email divalidasi di **backend** setelah identitas Google terverifikasi.
- OAuth state tervalidasi otomatis oleh Socialite; session diregenerasi setelah login/logout.

## 6. Pengaturan Domain Email yang Diizinkan

Domain email resmi kampus dikontrol lewat **tabel `allowed_email_domains`** (dikelola admin Super Admin pada menu *Pengaturan Domain*).

- Seed awal mengisi domain contoh dari `config/portal.php`:
  ```php
  'allowed_email_domains' => [
      'student.universitas.ac.id',
      'universitas.ac.id',
  ],
  ```
- **WAJIB diganti** dengan domain resmi institusi Anda sebelum produksi.
- Jika tabel kosong, sistem memakai fallback dari `config/portal.php`.
- Perubahan domain hanya dapat dilakukan oleh `super_admin` dan tercatat di audit log.

## 7. Migrasi Database & Data Awal

```bash
# Migrasi semua tabel
php artisan migrate

# Data awal: domain email contoh, tahun akademik, dan data dummy (fiktif)
php artisan db:seed --class=DatabaseSeeder
```

> **Perhatian**: seeder hanya mengisi data **dummy/fiktif** untuk pengembangan. Data mahasiswa resmi diinput lewat fitur import admin.

Perintah `migrate:fresh` **menghapus seluruh data** — jangan dijalankan di server produksi.

## 8. Import Data Mahasiswa

Admin (`super_admin` / `finance_admin`) mengimpor data induk mahasiswa dari **Excel/CSV** melalui menu *Data Mahasiswa → Import*.

Format kolom (header wajib):

```
nim;nama_lengkap;program_studi;angkatan;email;status
20230001;Contoh Nama;Teknik Informatika;2023;20230001@student.universitas.ac.id;active
```

- Delimiter otomatis dikenali (`,` atau `;`). Maksimal 5 MB.
- `status` (opsional): `active` | `inactive` | `graduated` — default `active`.
- **Preview dulu, konfirmasi kemudian**: seluruh baris divalidasi (NIM kosong/duplikat, format angkatan, email) sebelum tersimpan.
- Baris dengan NIM yang sudah ada akan **diperbarui** (bukan diduplikasi).
- Import berjalan dalam **database transaction**; baris gagal dilaporkan dan dapat diunduh sebagai CSV.

### Import tagihan massal

Menu *Tagihan UKT → Import Tagihan* — format kolom:

```
nim;tahun_akademik;semester;jenis;nominal;jatuh_tempo;keterangan
20230001;2025/2026;ganjil;ukt;5000000;2025-10-31;UKT Semester Ganjil
```

Aturan penting:
- Duplikat jenis tagihan pada periode yang sama **ditolak** (tidak diubah diam-diam).
- Tagihan yang sudah lunas **tidak pernah** disentuh.
- Nominal selalu berasal dari data admin, tidak pernah dari input mahasiswa/frontend.

## 9. Pembuatan Akun Admin Awal

Tidak ada kredensial default yang diketahui publik — admin dibuat lewat artisan dan login menggunakan **Google**:

```bash
# Super Admin
php artisan portal:create-admin --email=admin@universitas.ac.id --name="Nama Admin" --role=super_admin

# Admin Keuangan
php artisan portal:create-admin --email=keuangan@universitas.ac.id --name="Nama Admin" --role=finance_admin
```

Pada login Google pertama dengan email tersebut (email terverifikasi + domain diizinkan), identitas Google **terhubung otomatis** ke akun yang sudah dibuat.

Role:

| Role | Hak akses |
|---|---|
| `super_admin` | Semua fitur admin + pengaturan domain email |
| `finance_admin` | Mahasiswa, verifikasi, tagihan, transaksi, laporan, audit log |
| `student` | Dashboard, tagihan, pembayaran, profil sendiri |

Otorisasi diperiksa di **backend** melalui middleware (`admin`, `super_admin`, `verified_student`) — menyembunyikan tombol di frontend bukan mekanisme keamanan.

## 10. Menjalankan Aplikasi

```bash
# Mode pengembangan (Vite dev server + Laravel)
composer run dev
# atau terpisah:
php artisan serve
npm run dev
```

Buka `http://localhost:8000`.

**Alur pengguna:**

1. **Mahasiswa**: Landing → *Login dengan Google* → (login pertama) lengkapi NIM + angkatan → sistem mencocokkan dengan data induk → jika cocok, akun aktif → dashboard tagihan → *Bayar dengan QRIS*.
2. **Verifikasi manual**: bila NIM/angkatan tidak cocok atau email kampus di data induk berbeda, akun masuk antrean *Verifikasi Akun* admin.
3. **Admin**: login Google dengan email yang terdaftar sebagai admin → dashboard ringkasan, kelola mahasiswa/tagihan/transaksi/laporan.

### Scheduler (produksi)

Tambahkan cron berikut agar transaksi QRIS kedaluwarsa otomatis diproses:

```
* * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

## 11. Menjalankan Test

Test menggunakan database **terpisah** (`portal_pembayaran_mahasiswa_test`) — data development tidak tersentuh.

```bash
php artisan test
# atau
vendor/bin/phpunit
```

Cakupan test (64 test): domain Google diizinkan/ditolak, email belum terverifikasi, registrasi pertama, NIM tidak cocok, NIM duplikat & account linking, akses tagihan mahasiswa lain, role admin berbeda, import valid/tidak valid, pembuatan tagihan, transaksi pending/sukses/gagal/kedaluwarsa, webhook palsu/duplikat, ketidaksesuaian nominal, penghitungan sisa tagihan, dan akses endpoint tanpa autentikasi.

Kredensial Google **tidak diperlukan** — OAuth di-mock pada test (lihat `tests/Concerns/FakesGoogleUser.php`).

## 12. Konfigurasi Payment Gateway Sandbox

Arsitektur pembayaran memakai **service abstraction**:

```
app/Services/Payment/
├── PaymentGatewayInterface.php   # kontrak: createTransaction, getStatus
├── PaymentGatewayManager.php     # pemilihan driver dari config
├── MockQrisGateway.php           # mock QRIS (khusus dev/testing)
└── PaymentService.php            # orkestrasi + idempotensi + transaksi DB
```

### Mock (default)

```env
PAYMENT_GATEWAY=mock
PAYMENT_GATEWAY_WEBHOOK_SECRET=secret-lokal-anda
```

- QR yang ditampilkan adalah **payload simulasi** (tidak bisa dibayar sungguhan).
- Tombol *Simulasikan Pembayaran* tersedia hanya pada env `local`/`testing`.
- Webhook dapat diuji dengan mengirim POST bertanda tangan ke `/webhooks/payment`.

### Provider resmi

1. Dapatkan kredensial merchant dari provider resmi yang mendukung QRIS.
2. Implementasikan adapter baru `implements PaymentGatewayInterface` **berdasarkan dokumentasi resmi provider** (jangan mengarang endpoint/signature).
3. Daftarkan adapter pada `PaymentGatewayManager::driver()`.
4. Set `PAYMENT_GATEWAY=<nama-driver>`, `PAYMENT_GATEWAY_ENV=production`, kredensial di `.env`.
5. Daftarkan URL webhook provider ke `https://domain-anda/webhooks/payment`.

Mekanisme keamanan yang sudah ada:
- Nominal transaksi **selalu dari database** (sisa tagihan), bukan input frontend.
- Verifikasi webhook: HMAC-SHA256 raw body (`X-Webhook-Signature`, `hash_equals`).
- Pencocokan ID transaksi, nominal, dan mata uang sebelum perubahan status.
- Idempotensi: `provider_event_id` unik + transaksi pending dipakai ulang; status final tidak ditimpa.
- Semua perubahan status dalam **database transaction**.
- Status tidak pernah dianggap sukses hanya dari redirect browser.
- Penyedia status check manual bila webhook terlambat (admin: *Transaksi → Sinkronkan Status*).

**MVP tidak mendukung pembayaran parsial**: nominal pembayaran harus sama dengan sisa tagihan (konfigurasi `portal.allow_partial_payment = false`).

## 13. Struktur Data & Aturan Bisnis

Tabel utama: `users`, `students`, `academic_terms`, `bills`, `payments`, `payment_events`, `audit_logs`, `allowed_email_domains`, `student_import_batches` (+ tabel bawaan Laravel).

Poin penting desain:
- **Uang disimpan sebagai integer rupiah** (`unsignedBigInteger`) — aman dari kesalahan pembulatan.
- `students.user_id` nullable: profil resmi ada dulu, akun Google menyusul setelah verifikasi.
- Satu NIM ↔ satu akun Google; satu akun Google ↔ satu mahasiswa (diproteksi di layanan + transaction lock).
- Status **tagihan** (`unpaid/partially_paid/paid/cancelled`) terpisah dari status **pembayaran** (`pending/success/failed/expired`).
- Transaksi keuangan **tidak dihapus** — koreksi melalui aksi admin yang tercatat di `audit_logs`.
- `payment_events` menyimpan metadata aman (tanpa secret); `payload_hash` untuk ketertelusuran.
- Webhook tanpa secret terkonfigurasi ditolak (503) — sistem menolak memproses pembayaran dari sumber tak terverifikasi.

## 14. Prosedur Deployment HTTPS

1. **Server**: siapkan server dengan PHP 8.3-fpm + Nginx/Apache + MySQL 8.0.
2. **Kode**: `git clone` ke direktori release, lalu:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```
3. **Environment**: salin `.env.example` → `.env`, isi semua kredensial produksi, set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`.
4. **HTTPS**: gunakan sertifikat TLS (mis. Let's Encrypt). Contoh Nginx — paksa redirect 80→443, `ssl_protocols TLSv1.2 TLSv1.3`. Konfigurasi proxy agar `TrustProxies` mendeteksi HTTPS (`X-Forwarded-*`).
5. **Migrasi**: `php artisan migrate --force`.
6. **Cache**: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
7. **Storage**: `php artisan storage:link`; pastikan `storage/` dan `bootstrap/cache/` writable.
8. **Scheduler**: aktifkan cron `schedule:run` (bagian 10).
9. **Webhook**: beritahukan URL `https://.../webhooks/payment` ke provider; pastikan secret terpasang di kedua sisi.
10. **Google OAuth**: tambahkan domain produksi ke Authorized redirect URIs & Authorized domains.

## 15. Backup dan Pemeliharaan

### Backup database (harian disarankan)

```bash
mysqldump -u USER -p --single-transaction --routines --triggers portal_pembayaran_mahasiswa | gzip > backup-$(date +%F).sql.gz
```

- Simpan backup di penyimpanan terpisah/eksternal dengan enkripsi.
- Uji restore berkala:
  ```bash
  gunzip < backup-2026-10-12.sql.gz | mysql -u USER -p portal_pembayaran_mahasiswa
  ```
- Backup juga `.env` (aman & terenkripsi) dan direktori `storage/app`.

### Pemeliharaan rutin

| Aktivitas | Frekuensi |
|---|---|
| Backup database & verifikasi restore | Harian |
| Review `audit_logs` untuk aktivitas mencurigakan | Mingguan |
| `composer audit` untuk cek kerentanan dependency | Mingguan |
| Update dependency (`composer update`, `npm update`) + jalankan test | Bulanan |
| Rotasi `PAYMENT_GATEWAY_WEBHOOK_SECRET` & kredensial OAuth | Sesuai kebijakan (mis. tiap 6–12 bulan) |
| Bersihkan berkas import lama di `storage/app/imports` | Bulanan |

## 16. Catatan Keamanan

Ringkasan lapisan keamanan yang terimplementasi:

- **CSRF protection** pada seluruh form (kecuali `/webhooks/*` yang diverifikasi via HMAC signature).
- **Validasi input di backend** pada semua endpoint; proteksi mass-assignment (`$fillable` ketat; atribut sensitif seperti `role`/`status` hanya via `forceFill` dari kode internal).
- **Rate limiting**: callback Google (10/menit/IP), pencarian admin (30/menit), pembuatan transaksi (10/menit), webhook (120/menit).
- **Otorisasi berbasis role & kepemilikan data** di middleware/controller — mengubah ID di URL tidak membocorkan data mahasiswa lain (teruji).
- **Session**: regenerasi setelah login/logout, invalidation saat logout.
- **Upload**: batas ekstensi (csv/xls/xlsx) & ukuran (5 MB); ekspor CSV dilindungi dari **formula injection**.
- **Audit log** untuk tindakan admin dan event pembayaran (tanpa secret).
- **Error handling** yang tidak membocorkan stack trace di produksi (`APP_DEBUG=false`).
- Secret hanya di `.env` (tidak pernah dikirim ke frontend / di-commit).

Jika menemukan kerentanan, laporkan ke tim TI institusi — jangan diungkap publik.

---

## Ringkasan Perintah Cepat

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan portal:create-admin --email=admin@kampus.ac.id --name="Admin" --role=super_admin
php artisan test
php artisan serve
```
