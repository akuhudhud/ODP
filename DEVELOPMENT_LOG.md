# Log Pembangunan

## 2026-10-03 — Asas Account & Authentication

### Versi

`0.5.0`

### Selesai

- Menetapkan Account sebagai identity utama ODP.
- Menetapkan Account ID menggunakan UUID v7 dengan representasi `CHAR(36)`.
- Menetapkan satu Account sebagai satu identity dalam ODP.
- Menetapkan phone dan email sebagai contact/credential Account.
- Menambah migration `accounts`.
- Menambah migration `account_contacts`.
- Menambah migration `account_profiles`.
- Menambah migration `account_passwords`.
- Menambah migration `account_password_history`.
- Menambah migration `account_devices`.
- Menambah migration `account_security`.
- Menambah migration `app_sessions`.
- Menambah migration `otp_challenges`.
- Menambah migration `account_recovery_requests`.
- Menambah migration `account_recovery_tokens`.
- Menetapkan account lifecycle: ACTIVE, SUSPENDED, DEACTIVATED dan DELETED.
- Menetapkan login security dengan security level 0 hingga 3.
- Menetapkan session isolation mengikut app.
- Menetapkan maksimum satu active session bagi setiap Account + App.
- Menetapkan OTP menggunakan phone melalui WhatsApp dan email melalui Email.
- Menetapkan OTP 6 digit dengan tempoh sah 5 minit.
- Menetapkan had percubaan OTP, resend cooldown dan resend lock.
- Menetapkan Admin boleh melakukan `RESET_OTP_RESEND_LOCK`.
- Menetapkan Admin boleh melakukan `BYPASS_OTP_VERIFICATION`.
- Menetapkan kedua-dua Admin override OTP sebagai tindakan berisiko tinggi yang wajib direkodkan dalam Audit Log bersama reason.
- Menetapkan Account Recovery dengan Recovery Mode.
- Menetapkan recovery token unik bagi setiap recovery request.
- Menghapuskan migration Laravel auth default yang tidak digunakan:
  - `users`
  - `password_reset_tokens`
  - `personal_access_tokens`
- Mengekalkan `failed_jobs`.
- Menyediakan migration foundation sebanyak 11 table ODP.
- Menetapkan Security Activity dan Audit Log sebagai JSONL berasingan.
- Mengekalkan business tables di luar scope v0.5.0.

### Keputusan Architecture

Account menjadi identity utama ODP.

USER, Runner dan Admin bukan identity berasingan. Ia merupakan capability/authorization yang akan digunakan mengikut keperluan sistem.

USER ialah capability asas dan tidak memerlukan capability row.

Runner capability dan Runner eligibility kekal sebagai dua perkara berasingan.

Identity Verification tidak disamakan dengan authentication, capability, authorization atau service access.

### Database Foundation

Migration disusun mengikut dependency:

1. `accounts`
2. `account_contacts`
3. `account_profiles`
4. `account_passwords`
5. `account_password_history`
6. `account_devices`
7. `account_security`
8. `app_sessions`
9. `otp_challenges`
10. `account_recovery_requests`
11. `account_recovery_tokens`

Database menggunakan MySQL 8.0+.

UUID v7 digunakan untuk identifier yang berkaitan dan direpresentasikan sebagai `CHAR(36)`.

Active contact uniqueness menggunakan generated column dan unique constraint.

Active session uniqueness menggunakan generated column dan unique constraint.

Tiada cascade delete digunakan.

### Security Foundation

Password hanya disimpan dalam bentuk hash.

OTP hanya disimpan dalam bentuk hash.

Security Activity adalah owner-facing sahaja.

Audit Log adalah rekod dalaman untuk Root Admin/Kapten.

Audit Log menggunakan JSONL append-only dengan hash chain dan fingerprint bulanan selepas bulan ditutup.

Password, OTP, token dan data sensitif tidak direkodkan dalam Audit Log.

### Validation

Environment validation:

- PHP `8.4.15`
- MySQL `8.0`
- `pdo_mysql` aktif dalam PHP 8.4 Docker environment.

Migration validation:

- Fresh migration: LULUS.
- Semua 11 migration ODP: LULUS.
- `failed_jobs`: LULUS.
- Foreign key constraints: LULUS.
- Generated-column unique constraints: LULUS.
- Migration rollback: LULUS.
- Re-migration: LULUS.

Automated test validation:

- Laravel test suite: `3 tests passed`.
- Assertions: `4`.
- Working tree selepas validation: BERSIH.

### Scope Control

v0.5.0 hanya meliputi foundation Account & Authentication.

Perkara berikut belum dilaksanakan:

- Identity Verification
- Runner eligibility
- Vehicle system
- Runner documents
- Operation rules
- Order engine
- Delivery workflow
- Personal Shopper workflow
- Vendor system
- Payment integration
- Wallet
- Business-specific workflows

Semua perkara tersebut kekal sebagai FUTURE.

### Next Stage

Teruskan foundation Account & Authentication kepada implementation layer secara terkawal sebelum memperkenalkan business workflow.

---

## 2026-10-01 — Standard Response API v1

### Versi

`0.4.0`

### Selesai

- Menetapkan struktur standard response API v1.
- Menyeragamkan success response menggunakan `status`, `message` dan `data`.
- Menyeragamkan error response menggunakan `status`, `message` dan `data`.
- Mengemas kini `HealthController` supaya menggunakan struktur response standard.
- Mengemas kini `ApiHealthTest` untuk mengesahkan response standard.
- Menambah dokumentasi response API v1 di `docs/api/v1.md`.
- Keseluruhan Laravel test suite lulus: `3 tests passed`.

### Keputusan Architecture

API ODP akan menggunakan struktur response yang konsisten bagi endpoint API v1.

### Kawalan Skop

Peringkat ini kekal sebagai infrastructure sahaja.

Tiada business workflow diperkenalkan.

### Peringkat Seterusnya

Terus mengukuhkan backend foundation sebelum melaksanakan workflow khusus business.

---

## 2026-10-01 — Asas API v1

### Versi

`0.3.0`

### Selesai

- Menetapkan struktur route API v1.
- Menambah `GET /api/v1/health`.
- Menambah `HealthController` di bawah `Api\V1`.
- Menambah automated API health feature test.
- Menyelaraskan Codespace dengan commit GitHub terkini.
- Keseluruhan Laravel test suite lulus: `3 tests passed`.

### Keputusan Architecture

Versioning API bermula pada `/api/v1`.

API foundation diasingkan daripada workflow khusus business.

### Kawalan Skop

Perkara berikut sengaja tidak dimasukkan:

- Workflow authentication
- Business logic profile
- Order engine
- Fare/price engine
- Payment integration
- Wallet
- Runner task logic
- Vendor system
- Delivery workflow
- Personal Shopper workflow

### Peringkat Seterusnya

Teruskan backend foundation tanpa memperkenalkan business logic terlalu awal.

---

## 2026-09-30 — Asas Backend Laravel 10

### Versi

`0.2.0`

### Selesai

- Menetapkan backend Laravel 10 di bawah `backend/`.
- Laravel Framework `10.50.3` disahkan.
- PHP `8.4.15` disahkan.
- Composer `2.10.3` disahkan.
- Environment aplikasi disediakan.
- Application key berjaya dijana.
- Laravel test suite awal berjaya dijalankan.
- Keputusan PHPUnit: `2 passed`.
- Backend di-commit dan dihantar ke GitHub.

### Keputusan Architecture

Projek menggunakan Laravel 10 pada peringkat awal bagi keserasian dengan persekitaran shared hosting yang dirancang.

Laravel akan kekal pada versi 10 sepanjang fasa pembangunan awal. Sebarang upgrade Laravel pada masa hadapan akan dianggap sebagai migration terkawal selepas keperluan deployment dan keserasian hosting disahkan.

### Kawalan Skop

Backend ketika ini hanya mengandungi technical foundation Laravel.

Perkara berikut sengaja belum dilaksanakan:

- Design authentication
- Business logic profile
- Order engine
- Fare/price engine
- Payment integration
- Wallet
- Runner task logic
- Vendor system
- Delivery module
- Personal Shopper module

### Peringkat Seterusnya

Membina backend API foundation ODP tanpa memperkenalkan logic khusus business.
