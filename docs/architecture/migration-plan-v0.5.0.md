# ODP — Migration Plan v0.5.0

**Status:** DRAFT  
**Version:** v0.5.0  
**Scope:** Account Foundation / Database Foundation  
**Sumber keputusan:** `account-foundation.md`, `database-specification-v0.5.0.md`, `authentication-foundation.md`, `DEVELOPMENT_RULES.md`

---

## 1. Tujuan

Dokumen ini menetapkan pelan pelaksanaan database migration untuk Account Foundation v0.5.0.

Migration hanya melaksanakan struktur database yang telah ditetapkan dalam Database Specification v0.5.0.

Dokumen ini tidak boleh memperkenalkan keputusan architecture baharu.

---

## 2. Prinsip Utama

Pelaksanaan migration mesti:

1. Mengikut dokumen yang telah LOCKED.
2. Tidak mengubah keputusan architecture yang telah LOCKED.
3. Tidak menambah feature di luar scope v0.5.0.
4. Tidak menghapuskan Laravel default migrations secara andaian.
5. Tidak menggunakan migration sebagai tempat menetapkan business logic.
6. Memastikan foreign key dan constraint diuji.
7. Memastikan fresh migration boleh menghasilkan database yang lengkap.
8. Memastikan rollback boleh dilakukan dengan betul.
9. Tidak menggunakan data database sebagai pengganti security policy application layer.

---

## 3. Scope v0.5.0

Migration v0.5.0 melibatkan 11 table berikut:

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

Security Activity dan Audit Log bukan database table dalam scope ini.

Kedua-duanya menggunakan JSONL filesystem storage seperti yang ditetapkan dalam Database Specification.

---

## 4. Migration Order

Migration mesti dilaksanakan mengikut dependency berikut:

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

Order ini memastikan parent table tersedia sebelum foreign key digunakan oleh child table.

---

## 5. Database Engine

Database target:

- MySQL 8.0 atau lebih baharu.

Migration tidak boleh bergantung kepada behaviour database yang hanya tersedia pada versi lebih lama atau tidak serasi dengan MySQL 8.0+.

---

## 6. UUID

Semua primary identifier foundation menggunakan UUID v7.

Format database:

- `CHAR(36)`

UUID dijana oleh application sebelum proses insert.

Database tidak bertanggungjawab menjana identity UUID.

Tiada auto-increment integer digunakan sebagai identity utama untuk table foundation.

---

## 7. Table `accounts`

Table ini merupakan root identity table.

Struktur mesti menyokong:

- account UUID
- account status
- timestamps
- `deactivated_at`
- `deleted_at`

Status account:

- `ACTIVE`
- `SUSPENDED`
- `DEACTIVATED`
- `DELETED`

Peraturan:

- `DELETED` adalah permanent state.
- Account yang telah `DELETED` tidak dipadam secara fizikal.
- UUID account yang telah digunakan tidak boleh digunakan semula.

Migration tidak boleh menambah status lain tanpa keputusan baharu.

---

## 8. Table `account_contacts`

Table ini menyimpan contact identifier account.

Jenis contact:

- `PHONE`
- `EMAIL`

Status contact:

- `ACTIVE`
- `RELEASED`

Maklumat utama:

- contact UUID
- `account_id`
- type
- value
- status
- verification state
- `verified_at`
- `released_at`
- timestamps

Peraturan:

- Phone menggunakan format E.164.
- Email dinormalisasi.
- Satu account hanya boleh mempunyai satu active phone.
- Satu account hanya boleh mempunyai satu active email.
- Active phone mesti unik secara global.
- Active email mesti unik secara global.
- Contact yang telah `RELEASED` boleh wujud sebagai historical record.

Mekanisme database bagi active uniqueness boleh menggunakan generated column + unique index atau mekanisme MySQL yang setara dan telah diuji.

Migration tidak boleh menganggap contact yang `RELEASED` sebagai unique active contact.

---

## 9. Table `account_profiles`

Setiap account mempunyai profile record.

Maklumat utama:

- `account_id`
- `display_name`
- `display_name_changed_at`
- `profile_photo`
- timestamps

`account_id` menjadi primary key dan foreign key kepada `accounts`.

Peraturan application layer:

- `display_name` hanya menerima Unicode letters dan spaces.
- Digit tidak dibenarkan.
- Special characters tidak dibenarkan.
- Perubahan display name tertakluk kepada rule 30 hari.

`profile_complete` tidak disimpan sebagai database field.

Profile completeness dikira berdasarkan keadaan data sebenar.

Dokumen architecture tidak menetapkan numeric maximum length untuk display name. Oleh itu migration tidak boleh memperkenalkan angka maksimum baharu sebagai architecture decision.

---

## 10. Table `account_passwords`

Table ini menyimpan password semasa account.

Maklumat utama:

- `account_id`
- `password_hash`
- timestamps

Password disimpan sebagai hash sahaja.

Plain-text password tidak boleh disimpan.

Peraturan password application layer:

- 8–12 characters.
- Mesti mempunyai uppercase.
- Mesti mempunyai lowercase.
- Mesti mempunyai number.
- Symbol tidak diwajibkan.

Password policy bukan tanggungjawab migration.

---

## 11. Table `account_password_history`

Table ini menyimpan historical password hash.

Maklumat utama:

- UUID
- `account_id`
- password hash
- `created_at`

Password history digunakan untuk menghalang password reuse.

Policy v0.5.0:

- Tepat satu password terdahulu dikekalkan.

Migration hanya menyediakan struktur penyimpanan.

Retention behaviour dilaksanakan oleh application/service layer.

---

## 12. Table `account_devices`

Table ini menyimpan device information yang berkaitan dengan account.

Maklumat utama:

- UUID
- `account_id`
- `device_identifier`
- `platform`
- `device_name`
- `last_seen_at`
- timestamps

Device bukan identity.

Account identity kekal berdasarkan `accounts`.

Satu device tidak boleh dianggap sebagai account.

Table ini tidak menggunakan `app` sebagai identity dimension.

---

## 13. Table `app_sessions`

Table ini menyimpan application session.

Maklumat utama:

- UUID
- `account_id`
- `device_id` nullable
- `app`
- `token_hash`
- `status`
- timestamps
- `revoked_at`
- `expires_at` nullable

Application values:

- `USER`
- `RUNNER`
- `ADMIN`

Session status:

- `ACTIVE`
- `REVOKED`
- `EXPIRED`

Peraturan:

- Maksimum satu `ACTIVE` session untuk satu Account + App.
- User dan Runner boleh mempunyai active session pada masa yang sama kerana ia merupakan app context yang berbeza.
- Successful login pada app yang sama akan revoke active session terdahulu.
- Failed login tidak revoke session.
- Logout revoke current session.
- Recovery yang berjaya revoke semua active sessions.
- Security event boleh revoke session.

Session tidak mempunyai:

- inactivity timeout
- fixed lifetime expiry

`expires_at` tidak digunakan sebagai fixed session lifetime.

Session kekal aktif sehingga logout, revoke, security action atau mekanisme lain yang telah ditetapkan.

Active uniqueness boleh dilaksanakan menggunakan generated column + unique index atau mekanisme MySQL yang setara dan telah diuji.

Sanctum tidak dianggap sebagai automatic replacement kepada `app_sessions`.

---

## 14. Table `account_security`

Table ini menyimpan security state account.

Maklumat utama:

- `account_id`
- `failed_attempts`
- `security_level`
- `locked_until`
- `admin_review_required`
- `admin_reviewed_at`
- `updated_at`

Default:

- `failed_attempts = 0`
- `security_level = 0`
- `admin_review_required = false`

Security level:

- `0`
- `1`
- `2`
- `3`

Policy:

### Level 1

Selepas 3 failed login attempts:

- Level 1
- lock selama 30 minit

### Level 2

Selepas 3 failed attempts seterusnya:

- Level 2
- lock selama 1 jam

### Level 3

Selepas 3 failed attempts seterusnya:

- Level 3
- `admin_review_required = true`

Level 3 tidak mempunyai fixed 24-hour automatic unlock.

Successful login pada Level 1 atau Level 2 reset security failure state mengikut policy foundation.

Security lock adalah berasingan daripada Account status.

Security lock tidak menukar account kepada `SUSPENDED` secara automatik.

---

## 15. Table `otp_challenges`

Table ini menyimpan OTP challenge.

Maklumat utama:

- UUID
- `account_id` nullable
- `contact_id`
- `purpose`
- `code_hash`
- `attempts`
- `resend_count`
- `expires_at`
- `last_sent_at`
- `consumed_at`
- `invalidated_at`
- `created_at`

OTP purposes:

- `REGISTER`
- `VERIFY_PHONE`
- `VERIFY_EMAIL`
- `CHANGE_PHONE`
- `CHANGE_EMAIL`
- `ACCOUNT_RECOVERY`

OTP policy:

- 6 digits.
- Valid selama 5 minit.
- Maksimum 3 verification attempts.
- Resend cooldown 5 minit.
- Maksimum 3 resends.
- Selepas 3 resends, perlu tunggu 24 jam dari resend terakhir atau hubungi Admin.
- OTP baharu akan invalidate OTP lama.
- OTP disimpan sebagai hash sahaja.
- OTP mesti bound kepada exact contact.

OTP policy dilaksanakan melalui application/service layer bersama database state.

---

## 16. Table `account_recovery_requests`

Table ini menyimpan recovery request.

Maklumat utama:

- UUID
- `account_id`
- `reason`
- `status`
- `requested_at`
- `reviewed_at`
- `reviewed_by`
- `recovery_expires_at`
- `completed_at`

Recovery reasons:

- `EMAIL_INACCESSIBLE`
- `PHONE_AND_EMAIL_INACCESSIBLE`
- `OTHER`

Recovery statuses:

- `PENDING`
- `APPROVED`
- `REJECTED`
- `CANCELLED`

Recovery menggunakan Model C:

1. User membuat Recovery Request.
2. Admin review request.
3. Admin approve atau reject.
4. Jika approved, Recovery Mode boleh digunakan.
5. Recovery Mode mempunyai tempoh 30 minit.
6. Password ditetapkan oleh user melalui recovery flow.
7. Admin tidak melihat atau menetapkan password.
8. Successful recovery revoke semua active sessions.
9. Account status tidak berubah.
10. Account `DELETED` tidak boleh dipulihkan.

---

## 17. Table `account_recovery_tokens`

Recovery Token disimpan dalam table berasingan.

Maklumat utama:

- UUID
- `account_id`
- `recovery_request_id`
- `token_hash`
- `expires_at`
- `used_at`
- `created_at`

Peraturan:

- Satu Recovery Request mempunyai satu Recovery Token.
- Token disimpan sebagai hash sahaja.
- Token valid selama 30 minit.
- Token adalah one-time use.
- Token yang expired tidak boleh digunakan.
- Token yang telah digunakan tidak boleh digunakan semula.
- Recovery Request baharu diperlukan untuk mendapatkan token baharu.
- `recovery_request_id` mesti unique.

Foreign key:

- `account_id` → `accounts`
- `recovery_request_id` → `account_recovery_requests`

Delete behaviour:

- `ON DELETE RESTRICT`

Migration tidak boleh menggunakan cascade delete untuk Recovery Token.

---

## 18. Foreign Key Strategy

Foreign key mesti menjaga integrity Account Foundation.

Default strategy bagi relationship foundation:

- `ON DELETE RESTRICT`

Migration tidak boleh menambah `ON DELETE CASCADE` secara global.

`ON UPDATE CASCADE` juga tidak boleh ditambah sebagai global rule.

Jika behaviour foreign key tertentu diperlukan kemudian, ia mesti melalui keputusan architecture/database yang berasingan.

---

## 19. Constraint Strategy

Constraint database hendaklah digunakan untuk perkara yang memang merupakan database integrity rule.

Antara constraint yang perlu diuji:

- Primary key.
- Foreign key.
- Unique constraint.
- Active contact uniqueness.
- Active session uniqueness.
- Recovery request → recovery token one-to-one.
- Valid status representation.
- Valid enum/value representation yang ditetapkan oleh specification.

Application policy yang kompleks tidak boleh dipaksa secara tidak perlu ke dalam migration.

---

## 20. Active Contact Uniqueness

Requirement:

### Per Account

Maksimum:

- satu active phone
- satu active email

### Global

Active contact value mesti unique berdasarkan type.

Contoh:

Phone yang ACTIVE tidak boleh digunakan oleh dua account.

Email yang ACTIVE tidak boleh digunakan oleh dua account.

Contact RELEASED tidak menghalang contact tersebut daripada digunakan semula selepas proses release yang sah.

Implementation boleh menggunakan:

- generated column + unique index

atau

- mekanisme MySQL yang setara.

Implementation mesti diuji pada MySQL 8.0+.

---

## 21. Active Session Uniqueness

Requirement:

Satu Account + satu App hanya boleh mempunyai satu `ACTIVE` session.

Contoh:

- Account A + USER → maksimum 1 ACTIVE.
- Account A + RUNNER → maksimum 1 ACTIVE.
- Account A + ADMIN → maksimum 1 ACTIVE.

User dan Runner boleh aktif serentak kerana app context berbeza.

Implementation boleh menggunakan generated column + unique index atau mekanisme MySQL yang setara.

Implementation mesti diuji.

---

## 22. JSONL Security Activity

Security Activity bukan table database.

Storage:

- JSONL
- private server filesystem
- application-layer access
- retention 3 bulan

Security Activity mesti dipisahkan secara fizikal daripada Audit Log.

Security Activity bukan public data.

User/owner access sahaja mengikut architecture foundation.

---

## 23. JSONL Audit Log

Audit Log bukan table database.

Storage:

- JSONL
- separate server filesystem
- append-only
- minimum retention 7 tahun
- monthly files
- hash chain
- monthly SHA-256 fingerprint

Access:

- Root Admin / Kapten sahaja.

Audit Log mesti kekal berasingan daripada Security Activity.

---

## 24. Laravel Default Migrations

Laravel default migrations yang telah sedia ada tidak boleh:

- dipadam
- diubah
- diganti

secara andaian.

Sebarang keputusan untuk mengubah Laravel default migration mesti dibuat sebagai keputusan berasingan.

Migration Account Foundation hendaklah diurus secara terkawal tanpa merosakkan skeleton Laravel yang sedia ada.

---

## 25. Migration Naming

Migration file menggunakan Laravel standard timestamp naming.

Contoh urutan:

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
    2026_10_02_000011_create_account_recovery_tokens_table.php

Timestamp sebenar boleh disesuaikan dengan keadaan repository semasa implementation.

Order dependency mesti dikekalkan.

---

## 26. Migration Implementation Rule

Migration implementation mesti:

1. Mengikuti schema specification.
2. Tidak menambah field yang tidak ditetapkan.
3. Tidak menghapus field yang ditetapkan.
4. Tidak menambah business logic.
5. Tidak menambah architecture baharu.
6. Tidak mengubah naming convention yang telah dipersetujui.
7. Tidak menambah dependency baharu tanpa keputusan.
8. Tidak mengubah Laravel skeleton secara senyap.

Jika implementation memerlukan keputusan yang belum ditetapkan, development mesti berhenti pada titik tersebut dan keputusan perlu dibuat dahulu.

---

## 27. Fresh Migration Test

Migration mesti diuji daripada database kosong.

Test:

1. Database kosong.
2. Jalankan migration.
3. Semua 11 table berjaya dicipta.
4. Semua foreign key berjaya.
5. Semua required constraint berjaya.
6. Schema dibandingkan dengan Database Specification.
7. Tiada table foundation yang hilang.
8. Tiada field tambahan yang tidak diluluskan.

---

## 28. Rollback Test

Setiap migration mesti menyokong rollback yang betul.

Test:

1. Jalankan semua migration.
2. Jalankan rollback.
3. Pastikan dependency foreign key tidak menyebabkan rollback failure.
4. Pastikan table kembali ke keadaan sebelum migration.
5. Jalankan migration semula.

Rollback tidak boleh meninggalkan schema separuh rosak.

---

## 29. Constraint Test

Test minimum mesti meliputi:

### Accounts

- UUID uniqueness.
- Account status validation.
- Deleted account retention behaviour.

### Contacts

- Active phone uniqueness.
- Active email uniqueness.
- One active phone per account.
- One active email per account.
- Released contact behaviour.

### Sessions

- One active session per Account + App.
- User and Runner simultaneous sessions.
- Revoked session behaviour.
- Expired session state.

### Recovery

- One Recovery Token per Recovery Request.
- Recovery Token uniqueness.
- Token foreign key integrity.
- Used token state.
- Expired token state.

### Foreign Keys

- Invalid account references rejected.
- Invalid contact references rejected.
- Invalid recovery references rejected.
- RESTRICT behaviour tested.

---

## 30. Security Schema Test

Security-related schema mesti diuji bagi memastikan database mampu menyimpan state yang diperlukan untuk:

- failed login attempts
- security level
- temporary lock
- admin review requirement
- OTP challenge state
- recovery request state
- recovery token state
- session revocation

Application policy tetap diuji pada service/authentication layer.

---

## 31. Recovery Token Integrity Test

Test khusus untuk `account_recovery_tokens` wajib dibuat.

Minimum:

1. Recovery Request boleh mempunyai satu token.
2. Recovery Request kedua tidak boleh menghasilkan dua active token untuk request yang sama.
3. Token disimpan sebagai hash.
4. Token expired tidak boleh digunakan.
5. Token used tidak boleh digunakan semula.
6. Token baharu memerlukan Recovery Request baharu.
7. Foreign key kepada Account mesti valid.
8. Foreign key kepada Recovery Request mesti valid.
9. Delete parent mesti mematuhi RESTRICT.

---

## 32. Scope Exclusions

Perkara berikut bukan sebahagian daripada migration v0.5.0:

- identity verification
- runner onboarding
- vehicle
- order
- job
- matching
- dispatch
- delivery workflow
- personal shopper workflow
- vendor
- fare
- payment
- wallet
- business workflow

Jangan bina table untuk perkara tersebut dalam migration v0.5.0.

---

## 33. Definition of Done

Migration Foundation v0.5.0 hanya dianggap selesai apabila:

1. Migration Plan telah diluluskan dan status ditukar kepada `LOCKED`.
2. Semua 11 migration dilaksanakan.
3. Fresh migration berjaya.
4. Rollback berjaya.
5. Migration semula berjaya.
6. Semua foreign key diuji.
7. Active contact uniqueness diuji.
8. Active session uniqueness diuji.
9. Recovery Token integrity diuji.
10. Security schema diuji.
11. Tiada regression terhadap existing repository.
12. Test suite berkaitan foundation lulus.
13. `VERSION` dikemas kini.
14. `CHANGELOG.md` dikemas kini.
15. `DEVELOPMENT_LOG.md` dikemas kini.

---

## 34. Status Dokumen

Status semasa:

**DRAFT**

Dokumen ini belum menjadi LOCKED implementation authority sehingga Kapten memberikan kelulusan.

Sebarang konflik antara dokumen ini dengan dokumen LOCKED mesti dihentikan dan diselesaikan sebelum implementation.

Dokumen LOCKED mempunyai priority berbanding Migration Plan selagi Migration Plan masih `DRAFT`.

---

## 35. Authority Order

Untuk implementation v0.5.0, rujukan hendaklah dibaca dalam urutan:

1. `docs/DEVELOPMENT_RULES.md`
2. `docs/architecture/account-foundation.md`
3. `docs/architecture/database-specification-v0.5.0.md`
4. `docs/decisions/authentication-foundation.md`
5. `docs/architecture/migration-plan-v0.5.0.md`

Migration Plan mesti mematuhi dokumen LOCKED di atas.

Migration Plan tidak boleh mengatasi keputusan yang telah LOCKED.

---

## 36. Implementation Gate

Sebelum migration coding bermula:

- Migration Plan mesti diaudit.
- Semua conflict mesti diselesaikan.
- Kapten mesti meluluskan plan.
- Status plan mesti ditukar daripada `DRAFT` kepada `LOCKED`.

Selepas gate ini diluluskan barulah implementation migration boleh dimulakan.

---

**END — ODP Migration Plan v0.5.0**
**Status: DRAFT**
