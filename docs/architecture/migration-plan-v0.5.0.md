# ODP Migration Plan — Account Foundation

## Status

DRAFT

## Versi

v0.5.0 — Asas Akaun dan Pengesahan

## Tujuan

Dokumen ini mentakrifkan urutan, struktur dan peraturan migration database untuk Account Foundation v0.5.0.

Migration mesti dilaksanakan berdasarkan:

1. Account Foundation Blueprint
2. Database Specification v0.5.0
3. Migration Plan ini

Migration tidak boleh memperkenalkan fungsi atau jadual di luar scope Account Foundation v0.5.0.

---

# 1. Prinsip Migration

Migration Account Foundation mesti:

- menggunakan MySQL 8.0+
- menggunakan `CHAR(36)` untuk UUID v7
- UUID dijana oleh application layer
- tidak menggunakan database auto-increment sebagai Account ID
- mematuhi foreign key dependency
- mematuhi global uniqueness phone/email ACTIVE
- tidak menggunakan cascade delete terhadap Account
- tidak menyimpan plaintext password
- tidak menyimpan plaintext OTP
- tidak menyimpan plaintext Recovery Token
- mengekalkan Account yang berstatus DELETED
- tidak memperkenalkan business operational tables
- boleh dijalankan secara berurutan dari database kosong
- mempunyai `down()` yang sepadan dengan `up()`

---

# 2. Migration Scope

Migration v0.5.0 hanya merangkumi:

1. `accounts`
2. `account_contacts`
3. `account_profiles`
4. `account_passwords`
5. `account_password_history`
6. `account_devices`
7. `app_sessions`
8. `account_security`
9. `otp_challenges`
10. `account_recovery_requests`

Security Activity dan Audit Log tidak dibuat sebagai database table.

Kedua-duanya menggunakan JSONL pada filesystem server seperti yang ditetapkan dalam Database Specification.

Recovery Token disimpan sebagai hash di dalam `account_recovery_requests`.

Tiada jadual tambahan `account_recovery_tokens` untuk v0.5.0.

---

# 3. Migration Order

Urutan rasmi migration ialah:

```text
01 accounts
│
├── 02 account_contacts
├── 03 account_profiles
├── 04 account_passwords
├── 05 account_password_history
├── 06 account_devices
│   │
│   └── 07 app_sessions
│
├── 08 account_security
│
├── 09 otp_challenges
│
└── 10 account_recovery_requests
```

Dependency utama:

```text
accounts
├── account_contacts
│   └── otp_challenges
├── account_profiles
├── account_passwords
├── account_password_history
├── account_devices
│   └── app_sessions
├── account_security
└── account_recovery_requests
```

Migration mesti menggunakan timestamp yang memastikan urutan dependency di atas.

---

# 4. Migration Naming

Nama migration mesti menggunakan Laravel migration convention.

Format:

```text
YYYY_MM_DD_HHMMSS_create_accounts_table.php
```

Cadangan urutan nama:

```text
2026_10_02_000001_create_accounts_table.php
2026_10_02_000002_create_account_contacts_table.php
2026_10_02_000003_create_account_profiles_table.php
2026_10_02_000004_create_account_passwords_table.php
2026_10_02_000005_create_account_password_history_table.php
2026_10_02_000006_create_account_devices_table.php
2026_10_02_000007_create_app_sessions_table.php
2026_10_02_000008_create_account_security_table.php
2026_10_02_000009_create_otp_challenges_table.php
2026_10_02_000010_create_account_recovery_requests_table.php
```

Timestamp sebenar boleh disesuaikan dengan keadaan repository ketika implementation.

Yang penting ialah dependency order mesti dikekalkan.

---

# 5. UUID Rule

Semua primary key UUID v7 dalam Account Foundation menggunakan:

```text
CHAR(36)
```

UUID dijana oleh application layer sebelum insert.

Migration tidak boleh:

- menggunakan `$table->id()`
- menggunakan auto increment sebagai identity Account
- menggunakan database-generated identity sebagai pengganti UUID v7
- menjadikan device identifier sebagai Account ID

Contoh column:

```php
$table->char('id', 36)->primary();
```

Untuk foreign key UUID:

```php
$table->char('account_id', 36);
```

Application layer bertanggungjawab memastikan UUID yang diberikan ialah UUID v7.

---

# 6. Migration 01 — accounts

## Table

```text
accounts
```

## Tujuan

Menyimpan identity utama Account.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| id | CHAR(36) | NO | — |
| status | VARCHAR | NO | ACTIVE |
| created_at | DATETIME | NO | — |
| updated_at | DATETIME | NO | — |
| deactivated_at | DATETIME | YES | NULL |
| deleted_at | DATETIME | YES | NULL |

## Primary Key

```text
id
```

## Status Values

```text
ACTIVE
SUSPENDED
DEACTIVATED
DELETED
```

Status validation utama dilakukan pada application layer.

Database implementation menggunakan `VARCHAR` dan tidak menggunakan ENUM database supaya architecture tidak terikat kepada enum database.

## Index

Minimum:

```text
PRIMARY KEY (id)
INDEX (status)
```

## Account Lifecycle

Account yang berstatus `DELETED` tidak boleh dipadam secara fizikal sebagai sebahagian daripada lifecycle normal.

`DELETED` adalah status kekal.

Account ID tidak boleh digunakan semula.

---

# 7. Migration 02 — account_contacts

## Table

```text
account_contacts
```

## Tujuan

Menyimpan phone dan email yang berkaitan dengan Account.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| id | CHAR(36) | NO | — |
| account_id | CHAR(36) | NO | — |
| type | VARCHAR | NO | — |
| value | VARCHAR | NO | — |
| status | VARCHAR | NO | ACTIVE |
| is_verified | BOOLEAN | NO | FALSE |
| verified_at | DATETIME | YES | NULL |
| released_at | DATETIME | YES | NULL |
| created_at | DATETIME | NO | — |
| updated_at | DATETIME | NO | — |

## Primary Key

```text
id
```

## Foreign Key

```text
account_id → accounts.id
```

## Foreign Key Behaviour

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

Account tidak boleh dihapus melalui cascade.

## Type Values

```text
PHONE
EMAIL
```

## Status Values

```text
ACTIVE
RELEASED
```

## Index

Minimum:

```text
PRIMARY KEY (id)
INDEX (account_id)
INDEX (type)
INDEX (status)
INDEX (account_id, type, status)
```

## Active Contact Uniqueness

Phone dan email yang berstatus `ACTIVE` mesti unik secara global.

Business rules:

```text
ACTIVE PHONE → unique seluruh sistem
ACTIVE EMAIL → unique seluruh sistem
```

Satu Account juga tidak boleh mempunyai lebih daripada:

```text
1 ACTIVE PHONE
1 ACTIVE EMAIL
```

Contact `RELEASED` boleh digunakan semula selepas verification berjaya.

## Database Implementation

MySQL 8.0 tidak menyediakan partial unique index seperti sesetengah database lain.

Oleh itu implementation mesti menggunakan mekanisme MySQL yang selamat untuk memastikan:

```text
ACTIVE phone → global unique
ACTIVE email → global unique
```

Business rule tidak boleh diubah hanya kerana limitation implementation index.

Mekanisme sebenar hendaklah diputuskan semasa implementation dan mesti diuji.

---

# 8. Migration 03 — account_profiles

## Table

```text
account_profiles
```

## Tujuan

Menyimpan profil asas Account.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| account_id | CHAR(36) | NO | — |
| display_name | VARCHAR(255) | YES | NULL |
| display_name_changed_at | DATETIME | YES | NULL |
| profile_photo | VARCHAR(255) | YES | NULL |
| created_at | DATETIME | NO | — |
| updated_at | DATETIME | NO | — |

## Primary Key

```text
account_id
```

## Foreign Key

```text
account_id → accounts.id
```

## Foreign Key Behaviour

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## Constraint

Satu Account hanya mempunyai satu profile.

`display_name` tidak unique.

`profile_complete` tidak boleh menjadi database column.

## Application Rules

Application layer mengawal:

- Unicode letters + spaces
- maksimum panjang nama
- 30-day display name change rule
- profile completeness

Migration tidak menyimpan:

```text
profile_complete
```

---

# 9. Migration 04 — account_passwords

## Table

```text
account_passwords
```

## Tujuan

Menyimpan password semasa Account dalam bentuk hash.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| account_id | CHAR(36) | NO | — |
| password_hash | VARCHAR(255) | NO | — |
| created_at | DATETIME | NO | — |
| updated_at | DATETIME | NO | — |

## Primary Key

```text
account_id
```

## Foreign Key

```text
account_id → accounts.id
```

## Foreign Key Behaviour

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## Rules

Migration tidak menyimpan:

- plaintext password
- confirmation password
- password requirement state

Password policy dikawal application layer.

## Password Policy

Application layer mesti memastikan:

- panjang 8–12 aksara
- mempunyai huruf besar
- mempunyai huruf kecil
- mempunyai nombor
- simbol tidak diwajibkan

---

# 10. Migration 05 — account_password_history

## Table

```text
account_password_history
```

## Tujuan

Menyimpan satu password terdahulu untuk menghalang password reuse.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| id | CHAR(36) | NO | — |
| account_id | CHAR(36) | NO | — |
| password_hash | VARCHAR(255) | NO | — |
| created_at | DATETIME | NO | — |

## Primary Key

```text
id
```

## Foreign Key

```text
account_id → accounts.id
```

## Foreign Key Behaviour

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## Index

```text
PRIMARY KEY (id)
INDEX (account_id)
```

## Password History Rule

Sistem hanya mengekalkan:

```text
1 password terdahulu
```

Password history disimpan sebagai hash sahaja.

Application/service layer bertanggungjawab memastikan hanya satu previous password dikekalkan.

Migration tidak perlu memaksa rule satu rekod melalui database constraint yang boleh menyukarkan rotation.

---

# 11. Migration 06 — account_devices

## Table

```text
account_devices
```

## Tujuan

Menyimpan konteks device yang digunakan oleh Account.

Device bukan identity.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| id | CHAR(36) | NO | — |
| account_id | CHAR(36) | NO | — |
| device_identifier | VARCHAR(255) | NO | — |
| platform | VARCHAR(50) | YES | NULL |
| device_name | VARCHAR(255) | YES | NULL |
| last_seen_at | DATETIME | YES | NULL |
| created_at | DATETIME | NO | — |
| updated_at | DATETIME | NO | — |

## Primary Key

```text
id
```

## Foreign Key

```text
account_id → accounts.id
```

## Foreign Key Behaviour

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## Index

Minimum:

```text
PRIMARY KEY (id)
INDEX (account_id)
INDEX (device_identifier)
```

## Important Rule

Field berikut TIDAK boleh dimasukkan:

```text
app
```

App context hanya berada dalam:

```text
app_sessions.app
```

Device identifier bukan permanent identity Account.

Device boleh digunakan untuk lebih daripada satu App context melalui session.

---

# 12. Migration 07 — app_sessions

## Table

```text
app_sessions
```

## Tujuan

Menyimpan authentication session mengikut aplikasi.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| id | CHAR(36) | NO | — |
| account_id | CHAR(36) | NO | — |
| device_id | CHAR(36) | YES | NULL |
| app | VARCHAR | NO | — |
| token_hash | VARCHAR(255) | NO | — |
| status | VARCHAR | NO | ACTIVE |
| created_at | DATETIME | NO | — |
| last_used_at | DATETIME | YES | NULL |
| revoked_at | DATETIME | YES | NULL |
| expires_at | DATETIME | YES | NULL |

## Primary Key

```text
id
```

## Foreign Keys

```text
account_id → accounts.id
device_id → account_devices.id
```

## Foreign Key Behaviour

Untuk `account_id`:

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

Untuk `device_id`:

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## App Values

```text
USER
RUNNER
ADMIN
```

## Status Values

```text
ACTIVE
REVOKED
EXPIRED
```

## Index

Minimum:

```text
PRIMARY KEY (id)
INDEX (account_id)
INDEX (device_id)
INDEX (app)
INDEX (status)
INDEX (account_id, app, status)
```

## Session Uniqueness

Business rule:

```text
maximum 1 ACTIVE session per Account + App
```

Contoh yang sah:

```text
Account A + USER   = ACTIVE
Account A + RUNNER = ACTIVE
```

Contoh yang tidak sah:

```text
Account A + USER = ACTIVE
Account A + USER = ACTIVE
```

Application layer mesti memastikan login berjaya pada App yang sama akan revoke session lama.

Database implementation mesti menyokong integrity rule tersebut.

## Session Lifetime

ODP tidak menggunakan inactivity timeout.

ODP juga tidak menggunakan fixed session lifetime untuk session biasa.

`expires_at` kekal nullable untuk keadaan yang secara khusus memerlukan expiry, tetapi session biasa tidak diberikan fixed expiry.

Session biasa kekal `ACTIVE` sehingga:

- logout
- revoke
- security event
- successful recovery
- tindakan lain yang ditetapkan architecture

Tidak aktif untuk tempoh yang panjang tidak menyebabkan session tamat.

---

# 13. Migration 08 — account_security

## Table

```text
account_security
```

## Tujuan

Menyimpan state login security dan escalation.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| account_id | CHAR(36) | NO | — |
| failed_attempts | INTEGER | NO | 0 |
| security_level | INTEGER | NO | 0 |
| locked_until | DATETIME | YES | NULL |
| admin_review_required | BOOLEAN | NO | FALSE |
| admin_reviewed_at | DATETIME | YES | NULL |
| updated_at | DATETIME | NO | — |

## Primary Key

```text
account_id
```

## Foreign Key

```text
account_id → accounts.id
```

## Foreign Key Behaviour

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## Validation

Application layer mesti memastikan:

```text
security_level = 0, 1, 2 atau 3
failed_attempts >= 0
```

## Login Security Escalation

### Level 0

Normal.

### Level 1

Selepas 3 percubaan login gagal:

```text
security_level = 1
locked_until = +30 minit
```

### Level 2

Selepas 3 percubaan gagal seterusnya:

```text
security_level = 2
locked_until = +1 jam
```

### Level 3

Selepas 3 percubaan gagal seterusnya:

```text
security_level = 3
admin_review_required = TRUE
```

Level 3 tidak mempunyai fixed 24-hour timer.

Successful login pada Level 1 atau Level 2 reset escalation mengikut authentication rules.

Level 3 memerlukan Admin Review.

Security lock adalah berasingan daripada:

```text
accounts.status
```

---

# 14. Migration 09 — otp_challenges

## Table

```text
otp_challenges
```

## Tujuan

Menyimpan state OTP tanpa menyimpan OTP plaintext.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| id | CHAR(36) | NO | — |
| account_id | CHAR(36) | YES | NULL |
| contact_id | CHAR(36) | NO | — |
| purpose | VARCHAR | NO | — |
| code_hash | VARCHAR(255) | NO | — |
| attempts | INTEGER | NO | 0 |
| resend_count | INTEGER | NO | 0 |
| expires_at | DATETIME | NO | — |
| last_sent_at | DATETIME | NO | — |
| consumed_at | DATETIME | YES | NULL |
| invalidated_at | DATETIME | YES | NULL |
| created_at | DATETIME | NO | — |

## Primary Key

```text
id
```

## Foreign Keys

```text
account_id → accounts.id
contact_id → account_contacts.id
```

## Foreign Key Behaviour

Untuk `account_id`:

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

Untuk `contact_id`:

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## Index

Minimum:

```text
PRIMARY KEY (id)
INDEX (account_id)
INDEX (contact_id)
INDEX (purpose)
INDEX (expires_at)
INDEX (contact_id, purpose)
```

## Purpose Values

```text
REGISTER
VERIFY_PHONE
VERIFY_EMAIL
CHANGE_PHONE
CHANGE_EMAIL
ACCOUNT_RECOVERY
```

## OTP Rules

Application layer mesti menguatkuasakan:

- OTP 6 digit
- validity 5 minit
- maksimum 3 verification attempts
- resend cooldown 5 minit
- maksimum 3 resend
- OTP baharu invalidate OTP lama
- OTP terikat kepada contact yang tepat
- OTP plaintext tidak disimpan

## Resend Rule

Selepas maksimum 3 resend:

```text
resend disekat
↓
tunggu 24 jam dari resend terakhir
↓
resend boleh digunakan semula
```

Tempoh 24 jam adalah rolling period.

Bukan reset pada 00:00.

Timezone tidak digunakan untuk menentukan tempoh 24 jam tersebut.

---

# 15. Migration 10 — account_recovery_requests

## Table

```text
account_recovery_requests
```

## Tujuan

Menyimpan Recovery Request untuk Account Recovery Model C.

## Columns

| Column | Type | Null | Default |
|---|---|---:|---|
| id | CHAR(36) | NO | — |
| account_id | CHAR(36) | NO | — |
| reason | VARCHAR | NO | — |
| status | VARCHAR | NO | PENDING |
| requested_at | DATETIME | NO | — |
| reviewed_at | DATETIME | YES | NULL |
| reviewed_by | CHAR(36) | YES | NULL |
| recovery_token_hash | VARCHAR(255) | YES | NULL |
| recovery_token_expires_at | DATETIME | YES | NULL |
| recovery_token_used_at | DATETIME | YES | NULL |
| recovery_expires_at | DATETIME | YES | NULL |
| completed_at | DATETIME | YES | NULL |

## Primary Key

```text
id
```

## Foreign Key

```text
account_id → accounts.id
```

## Foreign Key Behaviour

```text
ON DELETE RESTRICT
ON UPDATE CASCADE
```

## Index

Minimum:

```text
PRIMARY KEY (id)
INDEX (account_id)
INDEX (status)
INDEX (requested_at)
INDEX (account_id, status)
```

## Reason Values

```text
EMAIL_INACCESSIBLE
PHONE_AND_EMAIL_INACCESSIBLE
OTHER
```

## Status Values

```text
PENDING
APPROVED
REJECTED
CANCELLED
```

## Recovery Rules

Recovery workflow:

```text
PENDING
↓
Admin Review
↓
APPROVED
↓
Recovery Mode 30 minit
↓
Password Recovery
```

Admin:

- boleh approve
- boleh reject
- tidak melihat password
- tidak menetapkan password

Account status tidak berubah semasa recovery.

Account `DELETED` tidak boleh recover.

Successful recovery mesti revoke semua ACTIVE sessions.

---

# 16. Recovery Token

Recovery Token disimpan dalam table:

```text
account_recovery_requests
```

Field:

```text
recovery_token_hash
recovery_token_expires_at
recovery_token_used_at
```

## Token Rules

Recovery Token mesti:

- dijana secara random
- disimpan sebagai hash sahaja
- tidak menyimpan plaintext
- terikat kepada `account_id`
- terikat kepada `account_recovery_requests.id`
- sah selama 30 minit
- hanya boleh digunakan sekali
- token yang telah digunakan tidak boleh digunakan semula
- token yang telah tamat tempoh tidak boleh digunakan
- successful recovery menyebabkan token dianggap digunakan
- successful recovery revoke semua ACTIVE sessions

Tiada jadual `account_recovery_tokens` digunakan dalam v0.5.0.

---

# 17. Security Activity Storage

Security Activity tidak disimpan dalam relational database.

Storage:

```text
Server Filesystem
```

Format:

```text
JSONL
```

Retention:

```text
3 bulan
```

Keperluan:

- private
- bukan public web directory
- access melalui application layer
- user/owner boleh melihat Security Activity sendiri mengikut authorization
- secara fizikal berasingan daripada Audit Log

Struktur folder dan nama fail akan ditetapkan semasa implementation.

---

# 18. Audit Log Storage

Audit Log tidak disimpan dalam relational database.

Storage:

```text
Server Filesystem
```

Format:

```text
JSONL
```

Retention:

```text
minimum 7 tahun
```

Keperluan:

- append-only
- monthly files
- hash chain
- monthly SHA-256 fingerprint
- secara fizikal berasingan daripada Security Activity
- Root Admin / Kapten sahaja melalui application layer

Struktur folder, nama fail, record format dan mekanisme hash chain akan ditetapkan semasa implementation.

---

# 19. Foreign Key Strategy

Semua foreign key Account Foundation mesti menggunakan:

```text
ON UPDATE CASCADE
ON DELETE RESTRICT
```

kecuali terdapat keputusan architecture baharu yang diluluskan.

Tiada foreign key kepada `accounts` boleh menggunakan:

```text
ON DELETE CASCADE
```

Sebab:

- Account DELETED mesti kekal
- Account ID tidak boleh digunakan semula
- sejarah Account mesti dikekalkan
- auditability mesti dikekalkan

---

# 20. Default Laravel Migrations

Repository semasa masih mempunyai migration Laravel default:

```text
2014_10_12_000000_create_users_table.php
2014_10_12_100000_create_password_reset_tokens_table.php
2019_12_14_000001_create_personal_access_tokens_table.php
```

Migration Account Foundation v0.5.0 tidak boleh terus memadam atau mengubah migration tersebut berdasarkan andaian.

Status sementara:

```text
EXISTING LARAVEL SKELETON
```

Keputusan terhadap migration tersebut hendaklah dibuat secara berasingan.

Pilihan yang mungkin pada masa akan datang:

- kekalkan
- hentikan penggunaan
- gantikan
- migrate data
- buang selepas architecture migration selesai

Tiada pilihan di atas dianggap LOCKED dalam Migration Plan ini.

Jangan mencampurkan migration legacy tersebut dengan Account Foundation tanpa keputusan architecture baharu.

---

# 21. Laravel Sanctum

Repository semasa mempunyai dependency:

```text
laravel/sanctum
```

Sanctum tidak boleh dianggap sebagai pengganti automatik kepada:

```text
app_sessions
```

Account Foundation menggunakan session architecture yang telah LOCKED.

Jika Sanctum digunakan sebagai implementation detail, implementation tersebut mesti mematuhi:

- Account identity
- App context
- session status
- one ACTIVE session per Account + App
- revoke rules
- recovery rules
- security rules

Framework dependency tidak boleh menentukan architecture Account Foundation.

---

# 22. Migration Dependency Check

Sebelum migration dijalankan, dependency mesti berada dalam urutan berikut:

```text
accounts
    ↓
account_contacts
account_profiles
account_passwords
account_password_history
account_devices
account_security
    ↓
app_sessions
otp_challenges
account_recovery_requests
```

Secara khusus:

```text
accounts
↓
account_contacts
↓
otp_challenges
```

dan:

```text
accounts
↓
account_devices
↓
app_sessions
```

Migration yang mempunyai foreign key kepada table yang belum wujud tidak boleh dijalankan.

---

# 23. Fresh Database Test

Migration mesti diuji menggunakan database kosong.

Command:

```bash
php artisan migrate:fresh
```

Expected:

- semua migration berjaya
- tiada foreign key error
- tiada duplicate index error
- tiada duplicate constraint error
- semua table Account Foundation wujud
- semua foreign key wujud
- semua required index wujud

---

# 24. Migration Rollback Test

Migration mesti diuji dengan:

```bash
php artisan migrate:rollback
```

Kemudian:

```bash
php artisan migrate
```

Expected:

- rollback berjaya
- migration boleh dijalankan semula
- schema kembali kepada keadaan yang dijangka
- tiada table orphan
- tiada foreign key orphan
- tiada dependency order error

---

# 25. Schema Validation

Selepas migration, schema mesti diperiksa.

## Tables

Semua table berikut mesti wujud:

```text
accounts
account_contacts
account_profiles
account_passwords
account_password_history
account_devices
app_sessions
account_security
otp_challenges
account_recovery_requests
```

## Primary Keys

Setiap table mesti mempunyai primary key seperti yang ditetapkan.

## Foreign Keys

Semua relationship mesti wujud dan menggunakan behaviour yang betul.

## UUID

Semua UUID field mesti menggunakan:

```text
CHAR(36)
```

## Account Identity

```text
accounts.id
```

mesti bukan auto increment.

## Security

Tiada plaintext untuk:

```text
password
OTP
Recovery Token
session secret
```

boleh disimpan dalam database.

---

# 26. Constraint Validation

Migration implementation mesti diuji untuk memastikan:

## Account

- Account ID unik
- Account ID bukan auto increment
- Account status mempunyai default `ACTIVE`

## Contacts

- ACTIVE phone tidak boleh duplicate
- ACTIVE email tidak boleh duplicate
- satu Account tidak boleh mempunyai lebih daripada satu ACTIVE PHONE
- satu Account tidak boleh mempunyai lebih daripada satu ACTIVE EMAIL
- RELEASED contact boleh digunakan semula

## Profile

- satu profile untuk satu Account
- display_name tidak unique
- tiada `profile_complete`

## Password

- satu current password bagi Account
- password hash sahaja

## Password History

- history berkaitan Account
- satu previous password sahaja dikekalkan oleh application logic

## Devices

- device berkaitan Account
- device bukan identity
- `app` tidak wujud dalam `account_devices`

## Sessions

- session berkaitan Account
- session boleh berkaitan device
- App context berada pada session
- maksimum satu ACTIVE session per Account + App

## Security

- satu security state per Account
- security level 0–3

## OTP

- OTP berkaitan contact
- OTP boleh berkaitan Account atau belum mempunyai Account
- OTP hash sahaja

## Recovery

- recovery request berkaitan Account
- reason mengikut specification
- status mengikut specification
- recovery token hash sahaja

---

# 27. Data Integrity Rules

Migration tidak boleh menghasilkan keadaan berikut:

```text
account_profiles.account_id
```

tanpa Account yang sepadan.

```text
account_passwords.account_id
```

tanpa Account yang sepadan.

```text
account_password_history.account_id
```

tanpa Account yang sepadan.

```text
account_devices.account_id
```

tanpa Account yang sepadan.

```text
app_sessions.account_id
```

tanpa Account yang sepadan.

```text
app_sessions.device_id
```

tanpa device yang sepadan apabila `device_id` digunakan.

```text
account_security.account_id
```

tanpa Account yang sepadan.

```text
otp_challenges.contact_id
```

tanpa contact yang sepadan.

```text
account_recovery_requests.account_id
```

tanpa Account yang sepadan.

---

# 28. Database Layer vs Application Layer

Tidak semua security/business rules perlu dipaksa melalui database.

## Database bertanggungjawab untuk

- primary key
- foreign key
- basic uniqueness
- basic nullability
- data type
- relational integrity
- basic defaults
- indexes

## Application Layer bertanggungjawab untuk

- UUID v7 generation
- password policy
- password history rotation
- display name validation
- 30-day display name rule
- profile completeness
- OTP generation
- OTP hashing
- OTP verification
- OTP expiry
- OTP attempt limit
- OTP resend cooldown
- OTP resend rolling 24 hours
- login escalation
- session lifecycle
- session revocation
- recovery workflow
- Recovery Token generation
- Recovery Token validation
- Security Activity
- Audit Log
- authorization

Migration tidak boleh memasukkan business logic yang sepatutnya berada dalam application/service layer.

---

# 29. Migration Tidak Boleh Mengandungi

Migration Account Foundation v0.5.0 tidak boleh menambah jadual untuk:

```text
vendors
orders
jobs
deliveries
dispatches
matching
vehicles
runner_onboarding
payments
wallets
fares
personal_shopper_operations
business_modules
```

Perkara tersebut ialah FUTURE.

Ia tidak termasuk dalam Account Foundation v0.5.0.

---

# 30. Implementation Sequence

Selepas Migration Plan ini LOCKED:

```text
Migration Plan
↓
Create migrations
↓
Run migration
↓
Schema inspection
↓
Migration tests
↓
Fix migration issues
↓
Commit
↓
Update VERSION
↓
Update CHANGELOG.md
↓
Update DEVELOPMENT_LOG.md
↓
Final validation
```

Jangan membina authentication service penuh sebelum migration foundation disahkan.

---

# 31. Testing Requirement

Migration implementation mesti mempunyai test yang mengesahkan sekurang-kurangnya:

1. semua table boleh dibuat
2. semua table boleh dirollback
3. migration boleh dijalankan semula
4. semua foreign key berfungsi
5. UUID column menggunakan `CHAR(36)`
6. Account ID bukan auto increment
7. active phone uniqueness berfungsi
8. active email uniqueness berfungsi
9. satu ACTIVE PHONE sahaja bagi setiap Account
10. satu ACTIVE EMAIL sahaja bagi setiap Account
11. session structure betul
12. maksimum satu ACTIVE session per Account + App
13. User dan Runner boleh mempunyai ACTIVE session serentak
14. account security structure betul
15. OTP structure betul
16. OTP resend structure betul
17. recovery structure betul
18. Recovery Token disimpan sebagai hash
19. tiada plaintext password disimpan
20. tiada plaintext OTP disimpan
21. tiada plaintext Recovery Token disimpan
22. tiada business table di luar scope
23. test lulus

---

# 32. Version Control

Migration Plan ini adalah sebahagian daripada Account Foundation v0.5.0.

Selepas LOCKED, sebarang perubahan kepada perkara berikut mesti direkodkan sebagai perubahan rasmi:

- table
- column
- datatype
- primary key
- foreign key
- index
- unique constraint
- default
- nullable state
- migration order
- session schema
- OTP schema
- recovery schema
- Recovery Token storage

Jangan edit Migration Plan secara senyap selepas ia LOCKED.

---

# 33. Definition of Done

Migration Account Foundation v0.5.0 dianggap selesai hanya apabila:

- [ ] Migration Plan LOCKED
- [ ] semua migration dibuat mengikut plan
- [ ] migration berjaya pada database kosong
- [ ] rollback berjaya
- [ ] migration boleh dijalankan semula
- [ ] semua table wujud
- [ ] semua primary key betul
- [ ] semua foreign key betul
- [ ] semua index betul
- [ ] active phone uniqueness diuji
- [ ] active email uniqueness diuji
- [ ] satu active phone per Account diuji
- [ ] satu active email per Account diuji
- [ ] session constraint diuji
- [ ] UUID representation diuji
- [ ] security schema diuji
- [ ] OTP schema diuji
- [ ] recovery schema diuji
- [ ] Recovery Token schema diuji
- [ ] tiada plaintext security data disimpan
- [ ] tiada business table di luar scope
- [ ] test lulus
- [ ] VERSION dikemas kini
- [ ] CHANGELOG.md dikemas kini
- [ ] DEVELOPMENT_LOG.md dikemas kini
- [ ] validation akhir selesai

---

# 34. Status Dokumen

Status semasa:

**DRAFT**

Dokumen ini menjadi rujukan implementation selepas Kapten meluluskan dan menetapkannya sebagai:

**LOCKED**

Selepas diluluskan:

```text
DRAFT
↓
REVIEW
↓
LOCKED
↓
IMPLEMENTATION
```

---

# Final Principle

```text
Blueprint
↓
Database Specification
↓
Migration Plan
↓
Migration
↓
Implementation
↓
Test
↓
Validation
```

**Jangan melangkau urutan ini.**
