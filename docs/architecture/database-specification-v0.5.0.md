# ODP Database Specification — Account Foundation

## Status

DRAFT

## Versi

v0.5.0 — Asas Akaun dan Pengesahan

## Tujuan

Dokumen ini menterjemahkan blueprint Account Foundation kepada spesifikasi database yang boleh digunakan sebagai rujukan sebelum migration dan implementation.

Dokumen ini belum mengunci keputusan database yang belum dinyatakan secara jelas dalam blueprint.

Tiada migration boleh dianggap muktamad berdasarkan dokumen ini sehingga spesifikasi ini disemak dan diluluskan.

---

# 1. Prinsip Database

Database Account Foundation mesti:

- mengekalkan satu identity Account berpusat
- menggunakan UUID v7 untuk Account ID
- menerima UUID v7 yang dijana oleh application layer
- tidak menjadikan device sebagai identity
- memisahkan authentication daripada authorization
- memisahkan Security Activity daripada Audit Log
- tidak menyimpan password plaintext
- tidak menyimpan OTP plaintext
- mengekalkan rekod Account yang telah DELETED
- tidak menggunakan semula Account ID

---

# 2. Jadual Identity Foundation

Jadual teras yang dikenal pasti:

1. `accounts`
2. `account_contacts`
3. `account_profiles`
4. `account_passwords`
5. `account_password_history`
6. `account_devices`
7. `app_sessions`

Blueprint juga memerlukan persistence untuk Login Security, OTP dan Account Recovery.

Oleh itu, jadual sokongan berikut dikenal pasti untuk semakan:

8. `account_security`
9. `otp_challenges`
10. `account_recovery_requests`

Security Activity dan Audit Log tidak dicadangkan sebagai jadual database dalam spesifikasi ini kerana blueprint menetapkan kedua-duanya sebagai JSONL dan mesti disimpan secara fizikal berasingan.

---

# 3. accounts

## Tujuan

Menyimpan identity utama Account.

## Field

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| id | UUID v7 / CHAR(36) | NO | Primary Key; dijana application |
| status | VARCHAR / ENUM | NO | ACTIVE, SUSPENDED, DEACTIVATED, DELETED |
| created_at | DATETIME | NO | Masa Account dicipta |
| updated_at | DATETIME | NO | Masa rekod dikemas kini |
| deactivated_at | DATETIME | YES | Diisi apabila Account DEACTIVATED |
| deleted_at | DATETIME | YES | Diisi apabila Account DELETED |

## Constraint

- `id` PRIMARY KEY
- Account ID tidak boleh digunakan semula
- `DELETED` adalah kekal
- Account DELETED tidak dipadam secara fizikal
- `deactivated_at` berkaitan dengan status DEACTIVATED
- `deleted_at` berkaitan dengan status DELETED

Nota: jenis SQL sebenar UUID masih perlu diputuskan berdasarkan database production yang akan digunakan.

---

# 4. account_contacts

## Tujuan

Menyimpan phone dan email yang berkaitan dengan Account.

## Field Minimum

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| id | UUID | NO | Primary Key |
| account_id | UUID | NO | FK ke accounts |
| type | VARCHAR / ENUM | NO | PHONE atau EMAIL |
| value | VARCHAR | NO | Nilai contact yang dinormalisasi |
| status | VARCHAR / ENUM | NO | ACTIVE atau RELEASED |
| is_verified | BOOLEAN | NO | Status verification |
| verified_at | DATETIME | YES | Masa verification |
| released_at | DATETIME | YES | Masa contact RELEASED |
| created_at | DATETIME | NO | Masa rekod dicipta |
| updated_at | DATETIME | NO | Masa rekod dikemas kini |

## Phone

- Format E.164
- Verification melalui WhatsApp OTP

## Email

- Dinormalisasi secara konsisten
- Tidak menggunakan provider-specific aliasing

## Constraint

- maksimum satu ACTIVE PHONE bagi setiap Account
- maksimum satu ACTIVE EMAIL bagi setiap Account
- contact RELEASED boleh digunakan oleh Account lain selepas verification berjaya
- contact RELEASED tidak dianggap sebagai active identity contact

## Perkara yang perlu dikunci sebelum migration

Unique constraint global terhadap contact aktif perlu ditentukan:

- Phone aktif mesti unik merentas semua Account
- Email aktif mesti unik merentas semua Account

Cadangan teknikal: contact aktif yang sama tidak boleh dimiliki oleh dua Account.

---

# 5. account_profiles

## Tujuan

Menyimpan maklumat profil asas Account.

## Field

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| account_id | UUID | NO | Primary Key + FK ke accounts |
| display_name | VARCHAR | YES | Nama paparan |
| display_name_changed_at | DATETIME | YES | Masa terakhir perubahan |
| profile_photo | VARCHAR | YES | Rujukan/path media |
| created_at | DATETIME | NO | Masa rekod dicipta |
| updated_at | DATETIME | NO | Masa rekod dikemas kini |

## Constraint

- satu profile untuk satu Account
- `display_name` tidak unik
- `profile_complete` tidak disimpan
- validasi Unicode letters + spaces dibuat pada application layer
- had perubahan 30 hari dikawal application layer berdasarkan `display_name_changed_at`

---

# 6. account_passwords

## Tujuan

Menyimpan password semasa Account dalam bentuk hash.

## Field

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| account_id | UUID | NO | Primary Key + FK |
| password_hash | VARCHAR | NO | Hash sahaja |
| created_at | DATETIME | NO | Masa password ditetapkan |
| updated_at | DATETIME | NO | Masa password dikemas kini |

## Constraint

- satu password semasa bagi satu Account
- plaintext tidak boleh disimpan
- confirmation password tidak disimpan

Polisi panjang dan komposisi password dikawal application layer.

---

# 7. account_password_history

## Tujuan

Menyimpan sejarah hash password untuk menghalang penggunaan semula password.

## Field

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| id | UUID | NO | Primary Key |
| account_id | UUID | NO | FK ke accounts |
| password_hash | VARCHAR | NO | Hash sahaja |
| created_at | DATETIME | NO | Masa hash direkodkan |

## Constraint

- plaintext tidak boleh disimpan
- history tidak boleh digunakan sebagai password aktif secara terus
- retention history perlu dikekalkan selagi diperlukan oleh polisi password reuse

Nota: bilangan password history yang perlu diperiksa belum ditetapkan dalam blueprint dan tidak boleh diandaikan dalam migration.

---

# 8. account_devices

## Tujuan

Menyimpan konteks device yang berkaitan dengan Account.

## Field Minimum

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| id | UUID | NO | Primary Key |
| account_id | UUID | NO | FK ke accounts |
| device_identifier | VARCHAR | NO | Identifier device |
| platform | VARCHAR | YES | Android, iOS atau platform lain |
| device_name | VARCHAR | YES | Nama device |
| app | VARCHAR | YES | USER, RUNNER atau ADMIN apabila diperlukan |
| last_seen_at | DATETIME | YES | Masa terakhir dilihat |
| created_at | DATETIME | NO | Masa device direkodkan |
| updated_at | DATETIME | NO | Masa rekod dikemas kini |

## Constraint

- device bukan identity
- device identifier bukan Account ID
- jangan gunakan device identifier sebagai permanent identity
- uniqueness device identifier belum dikunci oleh blueprint

---

# 9. app_sessions

## Tujuan

Menyimpan session authentication bagi setiap aplikasi.

## Field Minimum

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| id | UUID | NO | Primary Key |
| account_id | UUID | NO | FK ke accounts |
| device_id | UUID | YES | FK ke account_devices |
| app | VARCHAR / ENUM | NO | USER, RUNNER, ADMIN |
| token_hash | VARCHAR | NO | Hash token/session secret |
| status | VARCHAR / ENUM | NO | ACTIVE atau status session lain yang akan ditetapkan |
| created_at | DATETIME | NO | Masa session dicipta |
| last_used_at | DATETIME | YES | Masa terakhir digunakan |
| revoked_at | DATETIME | YES | Masa session direvoke |
| expires_at | DATETIME | YES | Masa tamat jika digunakan |

## Constraint

Business rule:

- maksimum satu ACTIVE session bagi Account + App
- User App dan Runner App boleh ACTIVE serentak
- login berjaya pada App yang sama revoke session lama
- login gagal tidak revoke session lama
- recovery berjaya revoke semua active sessions

Nota: jenis token, expiry policy dan status tambahan perlu dikunci sebelum implementation authentication.

---

# 10. account_security

## Tujuan

Menyimpan state keselamatan login yang diperlukan untuk escalation.

Jadual ini dicadangkan kerana Login Security memerlukan state yang berterusan dan tidak sesuai bergantung kepada session.

## Field Cadangan

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| account_id | UUID | NO | Primary Key + FK |
| failed_attempts | INTEGER | NO | Bilangan percubaan gagal dalam escalation |
| security_level | INTEGER | NO | 0, 1, 2 atau 3 |
| locked_until | DATETIME | YES | Untuk Level 1/2 |
| admin_review_required | BOOLEAN | NO | TRUE bagi Level 3 |
| admin_reviewed_at | DATETIME | YES | Masa review |
| updated_at | DATETIME | NO | Masa state dikemas kini |

## Constraint

- security lock berasingan daripada `accounts.status`
- Level 1 lock 30 minit
- Level 2 lock 1 jam
- Level 3 memerlukan Admin Review
- Level 3 tiada timer tetap 24 jam
- successful login Level 1/2 reset escalation
- Level 3 tidak boleh login sebelum review selesai

Status dan field akhir jadual ini mesti disahkan sebelum migration.

---

# 11. otp_challenges

## Tujuan

Menyimpan state OTP tanpa menyimpan OTP plaintext.

## Field Cadangan

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| id | UUID | NO | Primary Key |
| account_id | UUID | YES | FK jika Account telah wujud |
| contact_id | UUID | NO | FK ke account_contacts |
| purpose | VARCHAR / ENUM | NO | REGISTER, VERIFY_PHONE, VERIFY_EMAIL, CHANGE_PHONE, CHANGE_EMAIL, ACCOUNT_RECOVERY |
| code_hash | VARCHAR | NO | Hash OTP |
| attempts | INTEGER | NO | Bilangan cubaan |
| resend_count | INTEGER | NO | Bilangan resend |
| expires_at | DATETIME | NO | 5 minit |
| last_sent_at | DATETIME | NO | Untuk cooldown |
| consumed_at | DATETIME | YES | Masa OTP berjaya digunakan |
| invalidated_at | DATETIME | YES | Masa OTP dibatalkan |
| created_at | DATETIME | NO | Masa OTP dicipta |

## Constraint

- OTP 6 digit
- sah 5 minit
- maksimum 3 entry attempts
- resend cooldown 5 minit
- maksimum 3 resend
- OTP baharu invalidate OTP lama untuk purpose/contact berkaitan
- OTP plaintext tidak disimpan
- OTP mesti terikat kepada contact yang tepat

Nota: rule "tunggu sehingga hari berikutnya" memerlukan definisi timezone dan counter reset sebelum implementation.

---

# 12. account_recovery_requests

## Tujuan

Menyimpan Recovery Request untuk Model C.

## Field Minimum

| Field | Type Cadangan | Null | Catatan |
|---|---|---:|---|
| id | UUID | NO | Primary Key |
| account_id | UUID | NO | FK ke accounts |
| reason | VARCHAR / ENUM | NO | EMAIL_INACCESSIBLE, PHONE_AND_EMAIL_INACCESSIBLE, OTHER |
| status | VARCHAR / ENUM | NO | PENDING, APPROVED, REJECTED, CANCELLED |
| requested_at | DATETIME | NO | Masa request |
| reviewed_at | DATETIME | YES | Masa Admin review |
| reviewed_by | UUID | YES | Identifier Admin |
| recovery_expires_at | DATETIME | YES | 30 minit selepas approval |
| completed_at | DATETIME | YES | Masa recovery selesai |

## Constraint

- Admin sahaja approve/reject
- Admin tidak melihat atau menetapkan password
- Recovery Mode sah 30 minit selepas approval
- successful recovery revoke semua active sessions
- Account DELETED tidak boleh recover
- recovery tidak mengubah Account Status

Nota: mekanisme Recovery Mode token/secret belum ditetapkan dan perlu dikunci sebelum implementation.

---

# 13. Security Activity

Security Activity tidak disimpan dalam database relational berdasarkan blueprint semasa.

Format:

`JSONL`

Retention:

3 bulan

Keperluan:

- boleh dibaca oleh pemilik Account mengikut authorization
- disimpan secara fizikal berasingan daripada Audit Log

Lokasi storage dan struktur JSONL belum ditetapkan dalam blueprint dan perlu ditentukan sebelum implementation production.

---

# 14. Audit Log

Audit Log tidak disimpan sebagai jadual relational berdasarkan blueprint semasa.

Format:

`JSONL`

Keperluan:

- Root Admin / Kapten sahaja
- append-only
- retention minimum 7 tahun
- fail bulanan
- hash chain
- fingerprint SHA-256 bulanan
- aktiviti export dan logging berkaitan diaudit

Lokasi storage, format record dan mekanisme key/hash chain perlu ditentukan sebelum implementation.

---

# 15. Foreign Key

Hubungan utama:

`accounts`

→ `account_contacts.account_id`

→ `account_profiles.account_id`

→ `account_passwords.account_id`

→ `account_password_history.account_id`

→ `account_devices.account_id`

→ `app_sessions.account_id`

→ `account_security.account_id`

→ `account_recovery_requests.account_id`

`account_contacts`

→ `otp_challenges.contact_id`

`account_devices`

→ `app_sessions.device_id`

Foreign key tidak boleh membenarkan penghapusan Account secara cascade kerana Account DELETED mesti kekal.

---

# 16. Perkara Yang Belum Dikunci

Sebelum migration v0.5.0, perkara berikut mesti diputuskan:

1. Database engine production.
2. Representasi UUID v7 dalam database.
3. Unique constraint global untuk phone/email ACTIVE.
4. Status penuh `app_sessions`.
5. Token/session expiry policy.
6. Struktur akhir `account_security`.
7. Cara reset counter resend OTP pada hari berikutnya.
8. Timezone rasmi untuk rule "hari berikutnya".
9. Mekanisme Recovery Mode token.
10. Struktur storage JSONL Security Activity.
11. Struktur storage JSONL Audit Log.
12. Polisi retention sebenar `account_password_history`.
13. Sama ada `account_devices.app` diperlukan atau app hanya berada pada session.

Tiada perkara di atas boleh diandaikan sebagai keputusan LOCKED.

---

# 17. Scope

Database Specification ini hanya meliputi Account Foundation v0.5.0.

Tidak termasuk:

- Identity Verification
- Runner onboarding
- Vehicle
- Order
- Job
- Matching
- Dispatch
- Delivery operation
- Personal Shopper
- Vendor workflow
- Fare/price engine
- Payment
- Wallet
- business operational workflow

---

# 18. Status

**DRAFT — MENUNGGU SEMAKAN DAN KEPUTUSAN KAPTEN**

Dokumen ini belum menjadi LOCKED architecture.

Migration hanya boleh dimulakan selepas Database Specification diluluskan.
