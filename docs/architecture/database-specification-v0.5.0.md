# ODP Database Specification — Account Foundation

## Status

LOCKED

## Versi

v0.5.0 — Asas Akaun dan Pengesahan

## Tujuan

Dokumen ini menterjemahkan Blueprint Account Foundation kepada spesifikasi database yang digunakan sebagai rujukan rasmi untuk migration dan implementation Account Foundation v0.5.0.

Semua keputusan database yang dinyatakan sebagai LOCKED dalam dokumen ini telah diputuskan.

Migration Account Foundation v0.5.0 mesti mematuhi spesifikasi ini.

---

# 1. Prinsip Database

Database Account Foundation mesti:

- mengekalkan satu identity Account berpusat
- menggunakan UUID v7 untuk Account ID
- menggunakan `CHAR(36)` sebagai representasi UUID v7 dalam database
- UUID v7 dijana oleh application layer sebelum insert
- tidak menjadikan device sebagai identity
- memisahkan authentication daripada authorization
- memisahkan Security Activity daripada Audit Log
- tidak menyimpan password plaintext
- tidak menyimpan OTP plaintext
- mengekalkan rekod Account yang telah DELETED
- tidak menggunakan semula Account ID
- menggunakan MySQL 8.0+
- mengekalkan data yang diperlukan untuk security, OTP dan recovery

---

# 2. Database Engine

## DB-001 — Database Engine

Database production ODP menggunakan:

`MySQL 8.0+`

### Status

**LOCKED**

### Keputusan

- Database engine rasmi: MySQL
- Minimum version: 8.0+
- Semua migration Account Foundation mesti serasi dengan MySQL 8.0+
- Database-specific implementation tidak boleh mengubah architecture Account Foundation yang telah LOCKED

---

# 3. UUID Representation

## DB-002 — UUID v7 Representation

Account ID menggunakan UUID v7.

Database representation:

`CHAR(36)`

### Status

**LOCKED**

### Keputusan

- UUID version: UUID v7
- Representation database: `CHAR(36)`
- UUID dijana oleh application layer
- UUID dijana sebelum record dimasukkan ke database
- UUID tidak dijana oleh database sebagai identity Account
- Account ID tidak boleh digunakan semula

Contoh format UUID v7:

`0199xxxx-xxxx-7xxx-xxxx-xxxxxxxxxxxx`

---

# 4. Jadual Identity Foundation

Jadual teras Account Foundation:

1. `accounts`
2. `account_contacts`
3. `account_profiles`
4. `account_passwords`
5. `account_password_history`
6. `account_devices`
7. `app_sessions`

Jadual sokongan:

8. `account_security`
9. `otp_challenges`
10. `account_recovery_requests`

Security Activity dan Audit Log tidak disimpan sebagai relational database table.

Kedua-duanya menggunakan JSONL pada filesystem server dan disimpan secara fizikal berasingan.

---

# 5. accounts

## Tujuan

Menyimpan identity utama Account.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| id | CHAR(36) | NO | Primary Key; UUID v7 |
| status | VARCHAR / ENUM | NO | ACTIVE, SUSPENDED, DEACTIVATED, DELETED |
| created_at | DATETIME | NO | Masa Account dicipta |
| updated_at | DATETIME | NO | Masa rekod dikemas kini |
| deactivated_at | DATETIME | YES | Masa Account DEACTIVATED |
| deleted_at | DATETIME | YES | Masa Account DELETED |

## Constraint

- `id` PRIMARY KEY
- `id` mesti UUID v7
- Account ID tidak boleh digunakan semula
- `DELETED` adalah kekal
- Account DELETED tidak dipadam secara fizikal
- Account DELETED tidak boleh dipulihkan
- `deactivated_at` hanya digunakan apabila status `DEACTIVATED`
- `deleted_at` hanya digunakan apabila status `DELETED`

---

# 6. account_contacts

## Tujuan

Menyimpan phone dan email yang berkaitan dengan Account.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| id | CHAR(36) | NO | Primary Key; UUID v7 |
| account_id | CHAR(36) | NO | FK ke accounts |
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
- Phone ACTIVE mesti unik seluruh sistem

## Email

- Dinormalisasi secara konsisten
- Tidak menggunakan provider-specific aliasing
- Email ACTIVE mesti unik seluruh sistem

## Constraint

- maksimum satu ACTIVE PHONE bagi setiap Account
- maksimum satu ACTIVE EMAIL bagi setiap Account
- satu ACTIVE PHONE hanya boleh dimiliki oleh satu Account
- satu ACTIVE EMAIL hanya boleh dimiliki oleh satu Account
- contact RELEASED boleh digunakan semula selepas verification berjaya
- contact RELEASED tidak dianggap sebagai active identity contact

## DB-003 — Global Active Contact Uniqueness

**LOCKED**

Phone dan email yang berstatus `ACTIVE` mesti unik merentas semua Account.

---

# 7. account_profiles

## Tujuan

Menyimpan maklumat profil asas Account.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| account_id | CHAR(36) | NO | Primary Key + FK |
| display_name | VARCHAR | YES | Nama paparan |
| display_name_changed_at | DATETIME | YES | Masa terakhir perubahan |
| profile_photo | VARCHAR | YES | Rujukan/path media |
| created_at | DATETIME | NO | Masa rekod dicipta |
| updated_at | DATETIME | NO | Masa rekod dikemas kini |

## Constraint

- satu profile untuk satu Account
- `display_name` tidak unik
- `profile_complete` tidak disimpan
- profile completeness dikira berdasarkan keadaan data sebenar
- validasi Unicode letters + spaces dibuat pada application layer
- had perubahan 30 hari dikawal application layer berdasarkan `display_name_changed_at`

---

# 8. account_passwords

## Tujuan

Menyimpan password semasa Account dalam bentuk hash.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| account_id | CHAR(36) | NO | Primary Key + FK |
| password_hash | VARCHAR | NO | Hash sahaja |
| created_at | DATETIME | NO | Masa password ditetapkan |
| updated_at | DATETIME | NO | Masa password dikemas kini |

## Constraint

- satu password semasa bagi satu Account
- plaintext tidak boleh disimpan
- confirmation password tidak disimpan
- password hash sahaja disimpan

## Password Policy

- panjang 8–12 aksara
- mesti mempunyai huruf besar
- mesti mempunyai huruf kecil
- mesti mempunyai nombor
- simbol tidak diwajibkan

Password policy divalidasi pada application layer.

---

# 9. account_password_history

## Tujuan

Menyimpan sejarah password terdahulu untuk menghalang penggunaan semula password.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| id | CHAR(36) | NO | Primary Key; UUID v7 |
| account_id | CHAR(36) | NO | FK ke accounts |
| password_hash | VARCHAR | NO | Hash sahaja |
| created_at | DATETIME | NO | Masa hash direkodkan |

## DB-012 — Password History

**LOCKED**

Sistem hanya perlu mengekalkan **1 password terdahulu**.

### Rules

- password baharu tidak boleh sama dengan password terdahulu
- hanya satu rekod history terdahulu diperlukan
- password disimpan dalam bentuk hash sahaja
- plaintext tidak pernah disimpan
- history lebih lama daripada satu password tidak diperlukan untuk polisi reuse ODP

---

# 10. account_devices

## Tujuan

Menyimpan konteks device yang berkaitan dengan Account.

Device bukan identity.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| id | CHAR(36) | NO | Primary Key; UUID v7 |
| account_id | CHAR(36) | NO | FK ke accounts |
| device_identifier | VARCHAR | NO | Identifier device |
| platform | VARCHAR | YES | Android, iOS atau platform lain |
| device_name | VARCHAR | YES | Nama device |
| last_seen_at | DATETIME | YES | Masa terakhir dilihat |
| created_at | DATETIME | NO | Masa device direkodkan |
| updated_at | DATETIME | NO | Masa rekod dikemas kini |

## DB-013 — Device App Context

**LOCKED**

Field `app` **tidak disimpan** dalam `account_devices`.

App context hanya disimpan pada `app_sessions`.

## Constraint

- device bukan identity
- device identifier bukan Account ID
- device identifier tidak digunakan sebagai permanent identity
- uniqueness device identifier belum digunakan sebagai identity rule
- satu device boleh digunakan untuk lebih daripada satu App context melalui session

---

# 11. app_sessions

## Tujuan

Menyimpan session authentication bagi setiap aplikasi.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| id | CHAR(36) | NO | Primary Key; UUID v7 |
| account_id | CHAR(36) | NO | FK ke accounts |
| device_id | CHAR(36) | YES | FK ke account_devices |
| app | VARCHAR / ENUM | NO | USER, RUNNER, ADMIN |
| token_hash | VARCHAR | NO | Hash token/session secret |
| status | VARCHAR / ENUM | NO | ACTIVE, REVOKED, EXPIRED |
| created_at | DATETIME | NO | Masa session dicipta |
| last_used_at | DATETIME | YES | Masa terakhir digunakan |
| revoked_at | DATETIME | YES | Masa session direvoke |
| expires_at | DATETIME | YES | Tiada fixed expiry biasa |

## DB-004 — Session Status

**LOCKED**

Status session:

- `ACTIVE`
- `REVOKED`
- `EXPIRED`

## DB-005 — Session Lifetime

**LOCKED**

ODP tidak menggunakan inactivity timeout atau fixed session expiry.

### Rules

- session kekal `ACTIVE` selagi belum logout atau direvoke
- tempoh tidak aktif tidak menyebabkan session tamat
- membuka semula app selepas tempoh lama tidak memerlukan login semula selagi session masih sah
- session boleh kekal aktif walaupun user tidak menggunakan app untuk tempoh yang panjang
- `expires_at` tidak digunakan sebagai fixed lifetime biasa
- session boleh direvoke oleh security event yang telah ditetapkan

### Session Behaviour

- maksimum satu ACTIVE session bagi Account + App
- User App dan Runner App boleh ACTIVE serentak
- login berjaya pada App yang sama revoke session lama
- login gagal tidak revoke session lama
- logout → current session `REVOKED`
- recovery berjaya → semua ACTIVE sessions `REVOKED`
- security event yang ditetapkan boleh revoke session
- session yang telah `REVOKED` tidak boleh diaktifkan semula

---

# 12. account_security

## Tujuan

Menyimpan state keselamatan login yang diperlukan untuk escalation.

## DB-006 — Account Security Structure

**LOCKED**

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| account_id | CHAR(36) | NO | Primary Key + FK |
| failed_attempts | INTEGER | NO | Bilangan percubaan gagal |
| security_level | INTEGER | NO | 0, 1, 2 atau 3 |
| locked_until | DATETIME | YES | Untuk Level 1/2 |
| admin_review_required | BOOLEAN | NO | TRUE bagi Level 3 |
| admin_reviewed_at | DATETIME | YES | Masa review |
| updated_at | DATETIME | NO | Masa state dikemas kini |

## Login Security Escalation

### Level 0

Normal.

### Level 1

Selepas 3 percubaan login gagal:

- Security Level = 1
- lock selama 30 minit

### Level 2

Selepas 3 percubaan gagal seterusnya:

- Security Level = 2
- lock selama 1 jam

### Level 3

Selepas 3 percubaan gagal seterusnya:

- Security Level = 3
- Admin Review diperlukan
- tiada timer automatik 24 jam

### Successful Login

- successful login pada Level 1 atau Level 2 reset escalation
- Level 3 tidak boleh bypass melalui login biasa
- Level 3 memerlukan Admin Review

Security lock adalah berasingan daripada `accounts.status`.

---

# 13. otp_challenges

## Tujuan

Menyimpan state OTP tanpa menyimpan OTP plaintext.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| id | CHAR(36) | NO | Primary Key; UUID v7 |
| account_id | CHAR(36) | YES | FK jika Account telah wujud |
| contact_id | CHAR(36) | NO | FK ke account_contacts |
| purpose | VARCHAR / ENUM | NO | REGISTER, VERIFY_PHONE, VERIFY_EMAIL, CHANGE_PHONE, CHANGE_EMAIL, ACCOUNT_RECOVERY |
| code_hash | VARCHAR | NO | Hash OTP |
| attempts | INTEGER | NO | Bilangan cubaan |
| resend_count | INTEGER | NO | Bilangan resend |
| expires_at | DATETIME | NO | Masa tamat |
| last_sent_at | DATETIME | NO | Masa OTP terakhir dihantar |
| consumed_at | DATETIME | YES | Masa OTP berjaya digunakan |
| invalidated_at | DATETIME | YES | Masa OTP dibatalkan |
| created_at | DATETIME | NO | Masa OTP dicipta |

## OTP Rules

- OTP 6 digit
- sah selama 5 minit
- maksimum 3 entry attempts
- resend cooldown 5 minit
- maksimum 3 resend
- OTP baharu invalidate OTP lama untuk purpose/contact berkaitan
- OTP plaintext tidak disimpan
- OTP mesti terikat kepada contact yang tepat

## DB-007 — OTP Resend Limit

**LOCKED**

Selepas user mencapai maksimum 3 resend:

- resend disekat
- user mesti menunggu 24 jam
- 24 jam dikira dari `last_sent_at` resend terakhir
- reset bukan berdasarkan 00:00
- perubahan tarikh tidak mereset counter
- user boleh menghubungi Admin untuk bantuan
- counter hanya boleh membenarkan resend semula selepas tempoh 24 jam tamat atau proses bantuan Admin yang dibenarkan selesai

---

# 14. account_recovery_requests

## Tujuan

Menyimpan Recovery Request untuk Account Recovery Model C.

## Field

| Field | Type | Null | Catatan |
|---|---|---:|---|
| id | CHAR(36) | NO | Primary Key; UUID v7 |
| account_id | CHAR(36) | NO | FK ke accounts |
| reason | VARCHAR / ENUM | NO | EMAIL_INACCESSIBLE, PHONE_AND_EMAIL_INACCESSIBLE, OTHER |
| status | VARCHAR / ENUM | NO | PENDING, APPROVED, REJECTED, CANCELLED |
| requested_at | DATETIME | NO | Masa request |
| reviewed_at | DATETIME | YES | Masa Admin review |
| reviewed_by | CHAR(36) | YES | Identifier Admin |
| recovery_expires_at | DATETIME | YES | 30 minit selepas approval |
| completed_at | DATETIME | YES | Masa recovery selesai |

## Recovery Rules

- Admin sahaja approve/reject
- Admin tidak melihat password
- Admin tidak menetapkan password
- Recovery Mode sah 30 minit selepas approval
- successful recovery revoke semua ACTIVE sessions
- Account DELETED tidak boleh recover
- recovery tidak mengubah Account Status

---

# 15. Recovery Mode Token

## DB-009 — Recovery Token

**LOCKED**

Selepas Recovery Request diluluskan:

- server menjana Recovery Token rawak
- token disimpan sebagai hash sahaja
- plaintext token tidak disimpan
- token terikat kepada `account_id`
- token terikat kepada `recovery_request_id`
- token sah selama 30 minit
- token hanya boleh digunakan sekali
- token tamat tempoh tidak boleh digunakan
- selepas recovery berjaya, token dianggap digunakan
- successful recovery revoke semua ACTIVE sessions
- Admin tidak pernah melihat atau menetapkan password

Mekanisme token mesti memastikan token tidak boleh digunakan untuk Account lain.

---

# 16. Security Activity

Security Activity tidak disimpan dalam relational database.

## DB-010 — Security Activity Storage

**LOCKED**

### Format

`JSONL`

### Storage

Filesystem server ODP.

### Keperluan

- private
- bukan public web directory
- access melalui application layer
- retention 3 bulan
- disimpan secara fizikal berasingan daripada Audit Log
- user/owner boleh melihat Security Activity sendiri mengikut authorization
- tidak menggunakan relational table sebagai storage utama

Struktur folder dan nama fail akan ditetapkan semasa implementation.

---

# 17. Audit Log

Audit Log tidak disimpan dalam relational database.

## DB-011 — Audit Log Storage

**LOCKED**

### Format

`JSONL`

### Storage

Filesystem server ODP.

Audit Log mesti disimpan dalam storage/directory yang berasingan secara fizikal daripada Security Activity.

### Keperluan

- append-only
- retention minimum 7 tahun
- fail bulanan
- hash chain
- fingerprint SHA-256 bulanan
- Root Admin / Kapten sahaja boleh mengakses melalui application layer
- aktiviti export dan logging berkaitan turut diaudit

Struktur folder, nama fail, record format dan mekanisme hash chain akan ditetapkan semasa implementation.

---

# 18. Foreign Key

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

Hubungan tambahan:

`account_contacts`

→ `otp_challenges.contact_id`

`account_devices`

→ `app_sessions.device_id`

Recovery Token yang disimpan secara relational mesti mempunyai hubungan kepada Recovery Request dan Account.

## Foreign Key Delete Behaviour

Foreign key tidak boleh membenarkan penghapusan Account secara cascade.

Sebab:

- Account DELETED mesti kekal
- auditability mesti dikekalkan
- Account ID tidak boleh digunakan semula
- rekod sejarah Account mesti kekal

---

# 19. Keputusan Database Yang Telah LOCKED

Semua keputusan berikut telah diputuskan untuk v0.5.0:

1. **Database Engine**
   - MySQL 8.0+

2. **UUID Representation**
   - UUID v7
   - Database representation: `CHAR(36)`
   - Application-generated

3. **Active Contact Uniqueness**
   - ACTIVE phone global unique
   - ACTIVE email global unique

4. **Session Status**
   - ACTIVE
   - REVOKED
   - EXPIRED

5. **Session Lifetime**
   - Tiada inactivity timeout
   - Tiada fixed session expiry
   - Session kekal aktif sehingga logout atau revoke

6. **Account Security**
   - `account_security`
   - Level 0, 1, 2, 3
   - Level 1 = 30 minit
   - Level 2 = 1 jam
   - Level 3 = Admin Review

7. **OTP Resend**
   - maksimum 3 resend
   - selepas itu tunggu 24 jam dari resend terakhir
   - alternatif hubungi Admin

8. **OTP Timezone**
   - tidak digunakan untuk rule 24 jam
   - rule menggunakan rolling 24 jam

9. **Recovery Mode**
   - Recovery Token
   - random
   - hash sahaja
   - sekali guna
   - sah 30 minit

10. **Security Activity**
    - JSONL
    - private server filesystem
    - retention 3 bulan
    - berasingan daripada Audit Log

11. **Audit Log**
    - JSONL
    - server filesystem berasingan
    - append-only
    - minimum 7 tahun
    - monthly files
    - hash chain
    - monthly SHA-256 fingerprint

12. **Password History**
    - simpan 1 password terdahulu
    - hash sahaja

13. **Device App Context**
    - `account_devices.app` tidak digunakan
    - App context berada pada `app_sessions`

---

# 20. Scope

Database Specification ini hanya meliputi:

**Account Foundation v0.5.0**

Termasuk:

- Account identity
- Account status
- Phone
- Email
- Profile
- Password
- Password history
- Device
- Application session
- Login security
- OTP
- Account recovery
- Security Activity
- Audit Log

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

Perkara di atas adalah FUTURE dan tidak boleh dimasukkan ke migration Account Foundation v0.5.0.

---

# 21. Migration Rule

Migration hanya boleh dibuat berdasarkan dokumen ini.

Migration mesti:

- menggunakan MySQL 8.0+
- menggunakan `CHAR(36)` untuk UUID v7
- mematuhi foreign key rules
- mengekalkan Account DELETED
- tidak menggunakan cascade delete terhadap Account
- tidak menyimpan plaintext password
- tidak menyimpan plaintext OTP
- mematuhi global uniqueness phone/email ACTIVE
- mematuhi session rules
- mematuhi OTP rules
- mematuhi Recovery rules
- tidak memperkenalkan business tables yang berada di luar scope v0.5.0

---

# 22. Status Dokumen

**LOCKED**

Dokumen ini merupakan Database Specification rasmi untuk:

`ODP Account Foundation v0.5.0`

Semua keputusan database yang dinyatakan sebagai LOCKED adalah keputusan rasmi untuk scope v0.5.0.

Sebarang perubahan selepas dokumen ini LOCKED memerlukan keputusan baharu daripada Kapten dan mesti direkodkan sebagai perubahan versi/decision yang berkaitan.

---

## Final Principle

Blueprint
↓
Database Specification
↓
Migration
↓
Implementation
↓
Test
↓
Validation

**Jangan melangkau urutan ini.**
