# Log Pembangunan

## 2026-10-04 — OTP Contact Pending & Policy Foundation

### Versi

`0.5.0`

### Selesai

- Menambah status contact `PENDING` untuk proses verification sebelum contact menjadi `ACTIVE`.
- Menetapkan contact `PENDING` bukan active identity contact.
- Menetapkan satu contact `PENDING` bagi setiap Account + type.
- Menetapkan nilai contact `PENDING` kekal unik secara global sepanjang proses verification.
- Menetapkan contact baharu menjadi `ACTIVE` hanya selepas OTP verification berjaya.
- Menetapkan contact lama bagi type yang sama menjadi `RELEASED` secara atomik selepas verification berjaya.
- Menambah `OtpChallengePolicy` untuk memisahkan authorization context daripada OTP challenge service.
- Menambah unit test untuk OTP policy.
- Validation: `18 tests passed`, `18 assertions`.

### Keputusan Architecture

Lifecycle contact kini menggunakan `ACTIVE`, `PENDING` dan `RELEASED`.

`PENDING` digunakan untuk contact baharu yang sedang melalui proses verification. Contact lama kekal `ACTIVE` sehingga verification contact baharu berjaya.

OTP policy kekal berasingan daripada OTP challenge persistence dan delivery layer.

### Kawalan Skop

Belum diperkenalkan:

- OTP API endpoint.
- OTP delivery integration.
- WhatsApp API integration.
- Email delivery integration.
- Registration workflow.
- Phone change workflow.
- Email change workflow.
- Account Recovery API workflow.
- Admin OTP override API.

---

## 2026-10-04 — OTP Challenge Foundation

### Versi

`0.5.0`

### Selesai

- Menambah `OtpChallenge` sebagai model rasmi OTP challenge.
- Menambah migration `otp_challenges`.
- Menambah `OtpChallengeService` sebagai domain service OTP.
- Menetapkan OTP menggunakan kod 6 digit.
- Menetapkan OTP hanya disimpan dalam bentuk hash.
- Menetapkan tempoh sah OTP selama 5 minit.
- Menetapkan maksimum 3 percubaan verification bagi setiap OTP.
- Menetapkan resend cooldown selama 5 minit.
- Menetapkan maksimum 3 resend.
- Menetapkan resend lock selama 24 jam selepas had resend dicapai.
- Menetapkan resend count reset selepas resend lock 24 jam tamat.
- Menetapkan OTP baharu membatalkan OTP terdahulu bagi contact dan purpose yang sama.
- Menetapkan OTP challenge terikat kepada contact yang tepat.
- Menetapkan OTP purpose:
  - `REGISTER`
  - `VERIFY_PHONE`
  - `VERIFY_EMAIL`
  - `CHANGE_PHONE`
  - `CHANGE_EMAIL`
  - `ACCOUNT_RECOVERY`
- Menambah unit test untuk `OtpChallenge`.
- Menambah unit test untuk `OtpChallengeService`.
- Menambah test untuk OTP resend lock dan reset selepas 24 jam.

### Keputusan Architecture

`OtpChallengeService` kekal sebagai domain challenge layer.

OTP challenge, authorization/policy, API endpoint dan delivery channel akan kekal sebagai concern yang berasingan.

Plaintext OTP hanya wujud pada proses issuance dan tidak disimpan dalam database.

Delivery OTP melalui WhatsApp atau Email tidak menjadi sebahagian daripada challenge persistence layer.

### Kawalan Skop

Milestone ini hanya menyediakan OTP challenge foundation.

Belum diperkenalkan:

- OTP API endpoint.
- OTP delivery integration.
- WhatsApp API integration.
- Email delivery integration.
- Registration workflow.
- Phone change workflow.
- Email change workflow.
- Account Recovery API workflow.
- Admin OTP override API.

## 2026-10-04 — Account & Profile API Foundation

### Versi

`0.5.0`

### Selesai

- Menambah `AccountController`.
- Menambah `ProfileController`.
- Menambah `ProfileUpdateController`.
- Menambah relationship `Account` → `AccountProfile`.
- Menambah route `GET /api/v1/account`.
- Menambah route `GET /api/v1/account/profile`.
- Menambah route `PUT /api/v1/account/profile`.
- Menetapkan Account endpoint hanya boleh dicapai melalui authenticated API session.
- Menetapkan Profile GET boleh digunakan oleh Account `ACTIVE` dan `SUSPENDED`.
- Menetapkan Profile UPDATE hanya boleh digunakan oleh Account `ACTIVE`.
- Menetapkan Profile UPDATE ditolak dengan HTTP `403` bagi Account `SUSPENDED`.
- Menetapkan display name hanya menerima huruf Unicode dan ruang.
- Menetapkan display name hanya boleh ditukar sekali setiap 30 hari.
- Menambah automated feature test untuk Account endpoint.
- Menambah automated feature test untuk Profile GET endpoint.
- Menambah automated feature test untuk Profile UPDATE endpoint.
- Menambah unit test untuk relationship `Account` → `AccountProfile`.

### Validation

- Account/Profile test suite: `13 tests passed`.
- Assertions: `31`.
- Account GET endpoint: LULUS.
- Profile GET endpoint: LULUS.
- Profile UPDATE endpoint: LULUS.
- SUSPENDED profile update restriction: LULUS.
- Display name validation: LULUS.
- Display name 30-day change restriction: LULUS.
- Account model relationship: LULUS.
- Validation dijalankan menggunakan PHP 8.4 Docker environment dengan `pdo_mysql`.

### Keputusan Architecture

Account API dan Profile API kekal sebagai foundation layer.

Account Status, Authentication, Authorization dan Business Service Access kekal sebagai concern yang berasingan.

`SUSPENDED` masih boleh authenticated bagi tujuan restricted access dan komunikasi dengan sistem/Admin, tetapi tidak dibenarkan mengubah profile.

### Kawalan Skop

Peringkat ini hanya memperkenalkan Account/Profile API minimum.

Belum diperkenalkan:

- Contact management.
- Password management.
- OTP workflow.
- Account Recovery workflow.
- Identity Verification.
- Business service authorization.
- Runner workflow.
- Vendor workflow.
- Order workflow.
- Payment atau Wallet.

### Peringkat Seterusnya

Audit keseluruhan Account & Authentication Foundation v0.5.0 sebelum menentukan implementation layer seterusnya.

---

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
- Menetapkan `SUSPENDED` sebagai restricted access dan bukan authentication termination.
- Menetapkan Account `SUSPENDED` masih boleh login tetapi tidak boleh menggunakan business service.
- Menetapkan Account `DEACTIVATED` tidak boleh login melalui authentication biasa.
- Menetapkan Account `DELETED` sebagai terminal Account Status.
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
- Mengemas kini `LoginController` untuk membenarkan login bagi Account `ACTIVE` dan `SUSPENDED`.
- Mengemas kini `LoginController` untuk menolak login bagi Account `DEACTIVATED` dan `DELETED`.
- Mengemas kini `AuthenticateApiSession` untuk membenarkan authenticated session bagi Account `ACTIVE` dan `SUSPENDED`.
- Mengemas kini `AuthenticateApiSession` untuk menolak existing session bagi Account `DEACTIVATED` dan `DELETED`.
- Menambah feature test bagi lifecycle authentication.
- Menambah feature test bagi lifecycle API session authentication.
- Menambah feature test untuk logout.

### Keputusan Architecture

Account menjadi identity utama ODP.

USER, Runner dan Admin bukan identity berasingan. Ia merupakan capability/authorization yang akan digunakan mengikut keperluan sistem.

USER ialah capability asas dan tidak memerlukan capability row.

Runner capability dan Runner eligibility kekal sebagai dua perkara berasingan.

Identity Verification tidak disamakan dengan authentication, capability, authorization atau service access.

Account Status, Authentication, Authorization dan Business Service Access kekal sebagai concern yang berasingan.

`SUSPENDED` ialah restricted access dan bukan authentication termination.

`DEACTIVATED` bukan status terminal dan boleh melalui Reactivation / Account Recovery Flow yang sah.

`DELETED` ialah terminal Account Status.

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

- Authentication lifecycle test suite: `19 tests passed`.
- Assertions: `78`.
- Login tests: LULUS.
- Logout tests: LULUS.
- API session authentication tests: LULUS.
- ACTIVE account authentication: LULUS.
- SUSPENDED account authentication: LULUS.
- DEACTIVATED account authentication rejection: LULUS.
- DELETED account authentication rejection: LULUS.
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
- Business service authorization untuk lifecycle `SUSPENDED`

Semua perkara tersebut kekal sebagai FUTURE atau scope implementation seterusnya.

### Next Stage

Teruskan foundation Account & Authentication kepada implementation layer secara terkawal sebelum memperkenalkan business workflow.

Authorization khusus profile, contact, credential dan business service akan dibina secara berasingan mengikut endpoint dan capability yang ditetapkan.

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
- Menambah `HealthController` di bawah `Api\\V1`.
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

Backend ketika ini hanya mengandungi technical foundation.

Tiada business logic diperkenalkan pada peringkat ini.

---

## 2026-09-30 — Repository Foundation

### Versi

`0.1.0`

### Selesai

- Menetapkan struktur awal repository ODP.
- Menetapkan direktori `backend/`.
- Menetapkan direktori `user-app/`.
- Menetapkan direktori `runner-app/`.
- Menetapkan direktori `admin/`.
- Menetapkan direktori `database/`.
- Menetapkan direktori `docs/`.
- Menetapkan direktori `tests/`.
- Menambah `README.md`.
- Menambah `DEVELOPMENT_LOG.md`.
- Menambah sistem versioning melalui `VERSION`.
- Menambah root `.gitignore`.
- Menetapkan GitHub sebagai Source of Truth.

### Keputusan Architecture

Repository foundation menjadi asas kepada pembangunan ODP secara berperingkat.

Semua perubahan utama mesti melalui GitHub dan direkodkan mengikut versioning projek.

### Kawalan Skop

Peringkat ini hanya meliputi repository foundation dan dokumentasi.

Business logic belum diperkenalkan.
