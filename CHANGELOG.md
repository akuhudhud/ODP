# Log Perubahan

Semua perubahan penting dalam projek ini direkodkan di sini.

## [0.5.0] - 2026-10-03

### Ditambah

- Asas Account & Authentication ODP.
- Struktur Account sebagai identity utama ODP.
- Account ID menggunakan UUID v7 dengan representasi `CHAR(36)`.
- Struktur `account_contacts` untuk phone dan email.
- Struktur `account_profiles`.
- Struktur `account_passwords` dan `account_password_history`.
- Struktur `account_devices` dan `app_sessions`.
- Struktur `account_security` untuk kawalan cubaan login gagal dan security level.
- Struktur `otp_challenges` untuk OTP phone dan email.
- Struktur `account_recovery_requests` dan `account_recovery_tokens`.
- Asas account lifecycle: ACTIVE, SUSPENDED, DEACTIVATED dan DELETED.
- Asas login security dan pengasingan session mengikut app.
- Peraturan keselamatan OTP termasuk resend lock dan Admin override.
- Asas Account Recovery dengan Recovery Mode.
- 11 migration foundation untuk Account & Authentication.

### Diubah

- Menggunakan struktur Account ODP menggantikan auth foundation Laravel yang tidak diperlukan.
- Menghapuskan migration Laravel auth default:
  - `users`
  - `password_reset_tokens`
  - `personal_access_tokens`
- Mengekalkan `failed_jobs` sebagai migration Laravel yang masih diperlukan.
- Menetapkan Security Activity dan Audit Log sebagai struktur JSONL berasingan.

### Pengesahan

- MySQL 8.0 fresh migration: LULUS.
- Migration rollback: LULUS.
- Re-migration: LULUS.
- Generated-column unique constraints: LULUS.
- Foreign key constraints: LULUS.
- Laravel authentication test suite awal: 13 tests passed, 56 assertions.
- Lifecycle authentication validation: 19 tests passed, 78 assertions.
- Working tree selepas validation: BERSIH.

### Skop

- v0.5.0 meliputi foundation Account & Authentication sahaja.
- Business logic belum dilaksanakan.
- Runner, Vendor, Order, Delivery, Personal Shopper, Payment dan Wallet kekal sebagai FUTURE.

## [0.4.0] - 2026-10-01

### Ditambah

- Struktur standard response API v1.
- Format response berjaya dengan `status`, `message` dan `data`.
- Format response error dengan `status`, `message` dan `data`.
- Dokumentasi response API v1 di `docs/api/v1.md`.

### Diubah

- Mengemas kini `HealthController` supaya menggunakan standard response API v1.
- Mengemas kini feature test API health untuk mengesahkan struktur response baharu.

### Pengesahan

- Keseluruhan Laravel test suite: 3 tests passed.

### Skop

- Infrastruktur API sahaja.
- Tiada business logic dilaksanakan.

## [0.3.0] - 2026-10-01

### Ditambah

- Asas API v1.
- Endpoint kesihatan API di `GET /api/v1/health`.
- `HealthController` di bawah namespace API v1.
- Automated feature test untuk endpoint API health.

### Pengesahan

- API health test lulus.
- Keseluruhan Laravel test suite: 3 tests passed.

### Skop

- Infrastruktur API sahaja.
- Tiada business logic dilaksanakan.
- Tiada workflow authentication.
- Tiada order engine.
- Tiada fare/price engine.
- Tiada sistem payment atau wallet.
- Tiada sistem vendor.
- Tiada workflow delivery atau personal shopper.

## [0.2.0] - 2026-09-30

### Ditambah

- Asas Laravel 10 backend di bawah `backend/`.
- Struktur aplikasi Laravel.
- Pengurusan dependency melalui Composer.
- Konfigurasi environment Laravel.
- Penjanaan application key.
- Laravel test suite awal.

### Pengesahan

- Laravel Framework 10.50.3 disahkan.
- PHP 8.4.15 disahkan.
- Composer 2.10.3 disahkan.
- PHPUnit tests: 2 passed.

### Skop

- Asas backend sahaja.
- Tiada business logic dilaksanakan.
- Tiada order engine.
- Tiada fare engine.
- Tiada sistem payment atau wallet.
- Tiada modul vendor.
- Tiada logic delivery atau personal shopper.

## [0.1.0] - 2026-09-30

### Ditambah

- Struktur awal repository ODP.
- Direktori backend, user app, runner app dan admin.
- Direktori database, dokumentasi dan tests.
- README.
- Development log.
- Sistem versioning.
- Root `.gitignore`.

### Skop

- Struktur foundation dan dokumentasi sahaja.
- Business logic sengaja tidak dimasukkan.
