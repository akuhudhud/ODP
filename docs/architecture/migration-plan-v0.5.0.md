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
7. `account_security`
8. `app_sessions`
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

`
