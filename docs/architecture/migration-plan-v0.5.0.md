# ODP Migration Plan — Account Foundation

## Status

LOCKED

## Versi

v0.5.0 — Asas Akaun dan Pengesahan

## Tujuan

Dokumen ini menetapkan urutan migration database untuk Account Foundation v0.5.0.

Migration mesti dibuat berdasarkan:

1. `docs/DEVELOPMENT_RULES.md`
2. `docs/architecture/account-foundation.md`
3. `docs/architecture/database-specification-v0.5.0.md`
4. `docs/decisions/authentication-foundation.md`
5. dokumen ini

Migration tidak boleh memperkenalkan business logic atau database table di luar scope v0.5.0.

---

# 1. Prinsip Migration

Migration mesti:

- menggunakan MySQL 8.0+
- menggunakan `CHAR(36)` untuk UUID v7
- menggunakan UUID v7 yang dijana application layer
- mengekalkan Account DELETED
- tidak menggunakan Account ID semula
- tidak menggunakan cascade delete terhadap Account
- tidak menyimpan password plaintext
- tidak menyimpan OTP plaintext
- mematuhi active contact uniqueness
- mematuhi one-active-session-per-Account-per-App
- mematuhi Account Security escalation
- mematuhi OTP rules
- mematuhi Recovery rules
- tidak memasukkan business tables yang belum diperlukan

---

# 2. Migration Tables

Account Foundation v0.5.0 mempunyai 11 relational tables:

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
11. `account_recovery_tokens`

Security Activity dan Audit Log tidak dibuat sebagai relational tables.

Kedua-duanya menggunakan JSONL filesystem.

---

# 3. Migration Order

Migration mesti dibuat mengikut dependency order berikut:

accounts
↓
account_contacts
↓
account_profiles
account_passwords
account_password_history
account_devices
account_security
↓
app_sessions
otp_challenges
↓
account_recovery_requests
↓
account_recovery_tokens

Urutan sebenar timestamp migration mesti memastikan parent table diwujudkan sebelum foreign key child table.

---

# 4. Migration 01 — accounts

Table:

`accounts`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `status`
- `created_at`
- `updated_at`
- `deactivated_at`, nullable
- `deleted_at`, nullable

## Status

Allowed:

- `ACTIVE`
- `SUSPENDED`
- `DEACTIVATED`
- `DELETED`

## Rules

- `id` menggunakan UUID v7
- UUID dijana application layer
- Account ID tidak boleh digunakan semula
- Account DELETED tidak dipadam secara fizikal
- Account DELETED tidak boleh dipulihkan
- `deactivated_at` hanya untuk DEACTIVATED
- `deleted_at` hanya untuk DELETED

---

# 5. Migration 02 — account_contacts

Table:

`account_contacts`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `account_id` — CHAR(36), FK
- `type`
- `value`
- `status`
- `is_verified`
- `verified_at`, nullable
- `released_at`, nullable
- `created_at`
- `updated_at`

## Type

- `PHONE`
- `EMAIL`

## Status

- `ACTIVE`
- `RELEASED`

## Rules

Phone:

- E.164
- verification melalui WhatsApp OTP

Email:

- normalized
- tiada provider-specific aliasing

Active uniqueness:

- satu ACTIVE PHONE bagi satu Account
- satu ACTIVE EMAIL bagi satu Account
- ACTIVE PHONE global unique
- ACTIVE EMAIL global unique

Database mesti menguatkuasakan uniqueness untuk contact yang berstatus ACTIVE sahaja.

Implementation boleh menggunakan generated column + unique index atau mekanisme MySQL 8.0+ yang setara dan telah diuji.

---

# 6. Migration 03 — account_profiles

Table:

`account_profiles`

## Fields

- `account_id` — CHAR(36), PRIMARY KEY + FK
- `display_name`, nullable
- `display_name_changed_at`, nullable
- `profile_photo`, nullable
- `created_at`
- `updated_at`

## Rules

- satu profile bagi satu Account
- `account_id` ialah primary key
- display name tidak unik
- tiada `profile_complete`
- profile completeness dikira application layer
- display name hanya huruf Unicode dan ruang
- display name change cooldown 30 hari
- `display_name_changed_at` menjadi sumber rujukan cooldown

---

# 7. Migration 04 — account_passwords

Table:

`account_passwords`

## Fields

- `account_id` — CHAR(36), PRIMARY KEY + FK
- `password_hash`
- `created_at`
- `updated_at`

## Rules

- satu password semasa bagi satu Account
- password hash sahaja
- plaintext tidak disimpan
- password confirmation tidak disimpan

Password policy application layer:

- 8–12 aksara
- huruf besar
- huruf kecil
- nombor
- simbol tidak diwajibkan

---

# 8. Migration 05 — account_password_history

Table:

`account_password_history`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `account_id` — CHAR(36), FK
- `password_hash`
- `created_at`

## Rules

- maksimum satu password terdahulu diperlukan
- password hash sahaja
- plaintext tidak disimpan
- password baharu tidak boleh sama dengan password terdahulu

Implementation application layer mesti memastikan hanya satu history record aktif dikekalkan mengikut polisi ODP.

---

# 9. Migration 06 — account_devices

Table:

`account_devices`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `account_id` — CHAR(36), FK
- `device_identifier`
- `platform`, nullable
- `device_name`, nullable
- `last_seen_at`, nullable
- `created_at`
- `updated_at`

## Rules

- device bukan identity
- device identifier bukan Account ID
- `app` tidak disimpan dalam table ini
- App context disimpan dalam `app_sessions`

Tiada uniqueness global terhadap `device_identifier` sebagai identity rule.

---

# 10. Migration 07 — account_security

Table:

`account_security`

## Fields

- `account_id` — CHAR(36), PRIMARY KEY + FK
- `failed_attempts`
- `security_level`
- `locked_until`, nullable
- `admin_review_required`
- `admin_reviewed_at`, nullable
- `updated_at`

## Defaults

`failed_attempts`:

`0`

`security_level`:

`0`

`admin_review_required`:

`false`

## Security Level

Level 0:

- normal

Level 1:

- selepas 3 failed attempts
- lock 30 minit

Level 2:

- selepas 3 failed attempts seterusnya
- lock 1 jam

Level 3:

- selepas 3 failed attempts seterusnya
- Admin Review diperlukan
- tiada fixed 24-hour timer

Security lock adalah berasingan daripada Account Status.

---

# 11. Migration 08 — app_sessions

Table:

`app_sessions`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `account_id` — CHAR(36), FK
- `device_id` — CHAR(36), nullable, FK
- `app`
- `token_hash`
- `status`
- `created_at`
- `last_used_at`, nullable
- `revoked_at`, nullable
- `expires_at`, nullable

## App

- `USER`
- `RUNNER`
- `ADMIN`

## Status

- `ACTIVE`
- `REVOKED`
- `EXPIRED`

## Rules

- maksimum satu ACTIVE session bagi Account + App
- User App dan Runner App boleh ACTIVE serentak
- login berjaya pada App yang sama revoke session lama
- login gagal tidak revoke session lama
- logout revoke current session
- recovery berjaya revoke semua ACTIVE sessions
- revoked session tidak boleh diaktifkan semula

## Session Lifetime

ODP tidak menggunakan:

- inactivity timeout
- fixed normal session expiry

`expires_at` tidak digunakan sebagai fixed lifetime biasa.

Database mesti menguatkuasakan maksimum satu ACTIVE session bagi Account + App.

Implementation boleh menggunakan generated column + unique index atau mekanisme MySQL 8.0+ yang setara dan telah diuji.

---

# 12. Migration 09 — otp_challenges

Table:

`otp_challenges`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `account_id` — CHAR(36), nullable, FK
- `contact_id` — CHAR(36), FK
- `purpose`
- `code_hash`
- `attempts`
- `resend_count`
- `expires_at`
- `last_sent_at`
- `consumed_at`, nullable
- `invalidated_at`, nullable
- `created_at`

## Purpose

- `REGISTER`
- `VERIFY_PHONE`
- `VERIFY_EMAIL`
- `CHANGE_PHONE`
- `CHANGE_EMAIL`
- `ACCOUNT_RECOVERY`

## OTP Rules

- 6 digit
- sah 5 minit
- maksimum 3 entry attempts
- resend cooldown 5 minit
- maksimum 3 resend
- OTP baharu invalidate OTP lama
- OTP plaintext tidak disimpan
- code hash sahaja

## Resend Lock

Selepas maksimum 3 resend:

- resend disekat
- tunggu 24 jam
- 24 jam dikira dari `last_sent_at` resend terakhir
- menggunakan rolling 24 jam
- bukan berdasarkan 00:00 atau pertukaran tarikh

Admin boleh:

`RESET_OTP_RESEND_LOCK`

Admin juga boleh:

`BYPASS_OTP_VERIFICATION`

Kedua-dua tindakan:

- memerlukan authorization sesuai
- wajib mempunyai `reason`
- wajib direkodkan dalam Audit Log
- Admin tidak melihat OTP
- Admin tidak menetapkan OTP

---

# 13. Migration 10 — account_recovery_requests

Table:

`account_recovery_requests`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `account_id` — CHAR(36), FK
- `reason`
- `status`
- `requested_at`
- `reviewed_at`, nullable
- `reviewed_by`, nullable
- `recovery_expires_at`, nullable
- `completed_at`, nullable

## Reason

- `EMAIL_INACCESSIBLE`
- `PHONE_AND_EMAIL_INACCESSIBLE`
- `OTHER`

## Status

- `PENDING`
- `APPROVED`
- `REJECTED`
- `CANCELLED`

## Rules

- Admin sahaja approve/reject
- Admin tidak melihat password
- Admin tidak menetapkan password
- Recovery Mode sah 30 minit selepas approval
- successful recovery revoke semua ACTIVE sessions
- recovery tidak mengubah Account Status
- DELETED tidak boleh recover

---

# 14. Migration 11 — account_recovery_tokens

Table:

`account_recovery_tokens`

## Fields

- `id` — CHAR(36), PRIMARY KEY
- `account_id` — CHAR(36), FK
- `recovery_request_id` — CHAR(36), FK + UNIQUE
- `token_hash`
- `expires_at`
- `used_at`, nullable
- `created_at`

## Rules

- token random
- token hash sahaja
- plaintext token tidak disimpan
- token terikat kepada Account
- token terikat kepada Recovery Request
- satu Recovery Request hanya mempunyai satu Recovery Token
- token sah 30 minit
- token sekali guna
- token tamat tidak boleh digunakan
- token yang telah digunakan tidak boleh digunakan semula
- Recovery Request baharu diperlukan untuk token baharu
- Account DELETED tidak boleh menggunakan Recovery Token

---

# 15. Foreign Key Rules

Hubungan utama:

accounts
├── account_contacts
├── account_profiles
├── account_passwords
├── account_password_history
├── account_devices
├── account_security
├── app_sessions
├── otp_challenges
├── account_recovery_requests
└── account_recovery_tokens

Hubungan tambahan:

account_devices
└── app_sessions

account_contacts
└── otp_challenges

account_recovery_requests
└── account_recovery_tokens

## Delete Behaviour

Account tidak boleh dipadam melalui cascade.

Tidak boleh menggunakan:

`ON DELETE CASCADE`

terhadap hubungan yang berpunca daripada Account.

Tujuan:

- Account DELETED mesti kekal
- history mesti kekal
- auditability mesti dikekalkan
- Account ID tidak boleh digunakan semula

`ON UPDATE CASCADE` bukan global rule.

---

# 16. Security Activity

Security Activity bukan relational table.

Storage:

`JSONL`

Retention:

3 bulan

Keperluan:

- private
- bukan public web directory
- access melalui application layer
- fizikal berasingan daripada Audit Log
- hanya pemilik Account boleh melihat Security Activity sendiri

Event termasuk:

- `LOGIN_SUCCESS`
- `LOGIN_FAILED`
- `LOGOUT`
- `SESSION_REVOKED`
- `NEW_DEVICE`
- `ACCOUNT_LOCKED`
- `PASSWORD_CHANGED`
- `PASSWORD_RESET`
- `PHONE_CHANGED`
- `EMAIL_CHANGED`
- `ACCOUNT_DEACTIVATED`
- `ACCOUNT_REACTIVATED`
- `ACCOUNT_RECOVERY`

---

# 17. Audit Log

Audit Log bukan relational table.

Storage:

`JSONL`

Retention:

minimum 7 tahun

Keperluan:

- append-only
- monthly files
- hash chain
- monthly SHA-256 fingerprint
- fizikal berasingan daripada Security Activity
- Root Admin / Kapten sahaja melalui application layer

High-risk event termasuk:

- `OTP_RESEND_LOCK_RESET`
- `OTP_VERIFICATION_BYPASSED`

High-risk action mesti mempunyai:

- actor
- target
- reason
- timestamp
- result

Audit Log tidak boleh menyimpan:

- password
- password hash
- OTP
- OTP hash
- authentication token
- session token
- dokumen sensitif

---

# 18. Migration Naming

Laravel migration mesti menggunakan timestamp standard Laravel.

Nama migration mesti menerangkan table yang diwujudkan.

Contoh:

- `create_accounts_table`
- `create_account_contacts_table`
- `create_account_profiles_table`
- `create_account_passwords_table`
- `create_account_password_history_table`
- `create_account_devices_table`
- `create_account_security_table`
- `create_app_sessions_table`
- `create_otp_challenges_table`
- `create_account_recovery_requests_table`
- `create_account_recovery_tokens_table`

Migration tidak boleh mengandungi business logic.

Migration hanya bertanggungjawab kepada database schema, constraint, index dan foreign key.

---

# 19. Index Requirement

Migration mesti menyediakan index yang diperlukan untuk operasi Account Foundation.

Minimum:

`account_contacts`

- index `account_id`
- index `type`
- index `status`
- uniqueness untuk ACTIVE contact

`account_profiles`

- primary key `account_id`

`account_passwords`

- primary key `account_id`

`account_password_history`

- index `account_id`

`account_devices`

- index `account_id`
- index `device_identifier`

`account_security`

- primary key `account_id`

`app_sessions`

- index `account_id`
- index `device_id`
- index `app`
- index `status`
- uniqueness ACTIVE Account + App

`otp_challenges`

- index `account_id`
- index `contact_id`
- index `purpose`
- index `expires_at`

`account_recovery_requests`

- index `account_id`
- index `status`

`account_recovery_tokens`

- index `account_id`
- unique `recovery_request_id`
- index `expires_at`

Index tambahan boleh ditambah hanya jika diperlukan oleh implementation dan tidak mengubah architecture.

---

# 20. Migration Safety

Migration mesti boleh:

1. migrate dari database kosong
2. rollback
3. migrate semula
4. migrate tanpa melanggar foreign key dependency
5. mencipta semua required indexes
6. mencipta semua required constraints

Migration tidak boleh bergantung kepada data production yang telah wujud untuk berjaya.

---

# 21. Rollback

Rollback mesti menghormati dependency order.

Jika rollback penuh dilakukan, child tables mesti dibuang sebelum parent tables.

Urutan rollback adalah reverse daripada migration order:

account_recovery_tokens
↓
account_recovery_requests
↓
otp_challenges
app_sessions
account_security
account_devices
account_password_history
account_passwords
account_profiles
account_contacts
↓
accounts

Rollback tidak boleh digunakan sebagai mekanisme untuk memadam Account production secara operasi.

---

# 22. Scope Control

Migration v0.5.0 hanya boleh mengandungi Account Foundation.

Tidak boleh ditambah:

- vendor
- vehicle
- runner eligibility
- runner documents
- order
- job
- dispatch
- matching
- delivery
- personal shopper
- fare engine
- payment
- wallet
- business module
- operational workflow

Semua perkara tersebut ialah `FUTURE`.

---

# 23. Definition of Done

Migration Account Foundation v0.5.0 dianggap selesai apabila:

- migration plan ini LOCKED
- semua 11 migration table telah diwujudkan
- migration boleh dijalankan dari database kosong
- migration boleh rollback
- migration boleh dijalankan semula selepas rollback
- foreign key rules lulus
- active contact uniqueness lulus
- active session uniqueness lulus
- Recovery Token uniqueness lulus
- Account Security schema lulus
- OTP schema lulus
- Recovery schema lulus
- tiada plaintext password
- tiada plaintext OTP
- tiada cascade delete terhadap Account
- Security Activity kekal di luar relational database
- Audit Log kekal di luar relational database
- semua test berkaitan lulus
- `git diff --check` lulus
- VERSION / CHANGELOG / DEVELOPMENT_LOG dikemas kini pada milestone yang sesuai
- tiada regression terhadap test sedia ada

---

# 24. Validation Sequence

Selepas migration implementation:

Migration
↓
Fresh Database
↓
Run Migrations
↓
Schema Validation
↓
Foreign Key Validation
↓
Index Validation
↓
Constraint Validation
↓
Rollback
↓
Re-Migration
↓
Automated Tests
↓
git diff --check
↓
Final Review

---

# 25. Authority

Jika berlaku konflik:

1. `docs/DEVELOPMENT_RULES.md`
2. `docs/architecture/account-foundation.md`
3. `docs/architecture/database-specification-v0.5.0.md`
4. `docs/decisions/authentication-foundation.md`
5. dokumen migration plan ini

Dokumen yang lebih tinggi dalam senarai menjadi rujukan utama.

Sebarang konflik mesti dihentikan dan diputuskan sebelum implementation diteruskan.

---

# 26. Status Dokumen

**LOCKED**

Dokumen ini ialah migration plan rasmi untuk:

`ODP Account Foundation v0.5.0`

Migration implementation hanya boleh dimulakan selepas semua dokumen architecture dan database yang berkaitan telah disemak.

---

## Final Principle

Blueprint
↓
Architecture
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

**Jangan melangkau urutan ini.**
