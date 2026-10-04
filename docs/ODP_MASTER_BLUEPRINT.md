# ODP MASTER BLUEPRINT

**Status:** DRAFT — MENUNGGU LOCK
**Version:** 1.0
**Role:** Architecture + Specification + Development Checklist + Progress Tracker + Source of Truth
**Baseline Audit:** ODP `main`, 4 October 2026

---

# 1. MASTER RULE

Master Blueprint ialah SATU sumber utama ODP untuk:

- architecture
- functional specification
- locked decisions
- development checklist
- progress tracking
- validation status

Dokumen architecture/decision lain boleh kekal sebagai technical reference atau rekod sejarah.

Keputusan architecture baharu mesti selaras dengan Master Blueprint.

Jika berlaku konflik antara dokumen architecture, Master Blueprint menjadi rujukan utama selepas keputusan tersebut dikunci.

Master Blueprint tidak menggantikan:

- `DEVELOPMENT_RULES.md`
- `CHANGELOG`
- `DEVELOPMENT_LOG`
- API documentation
- detailed technical documentation

Dokumen tersebut kekal digunakan untuk tujuan masing-masing.

---

# 2. CORE DEVELOPMENT PRINCIPLE

ODP dibangunkan mengikut urutan:

AUDIT
→ BLUEPRINT
→ LOCK DECISION
→ IMPLEMENT
→ REVIEW
→ TEST
→ COMMIT
→ UPDATE BLUEPRINT
→ NEXT

Jangan membangunkan code berdasarkan andaian.

Jangan menambah architecture baharu tanpa keputusan.

Jangan mengubah locked decision secara senyap.

Jangan test setiap fail kecil secara berulang jika functional foundation belum lengkap.

Functional foundation mesti dilengkapkan dahulu, kemudian dibuat comprehensive test.

Contoh:

REGISTER
→ lengkapkan Registration Foundation
→ comprehensive test
→ validate
→ commit
→ LOGIN

---

# 3. STATUS SYSTEM

| Status | Maksud |
|---|---|
| ⬜ | Belum dibangunkan |
| 🟡 | Dalam pembangunan |
| 🟢 | Foundation/code tersedia |
| 🔵 | Review selesai |
| ✅ | Implemented + reviewed + tested/validated |
| 🔴 | Perlu pembaikan |

`🟢` tidak bermaksud fully validated.

Hanya `✅` bermaksud functional foundation telah disahkan.

Komponen tidak boleh ditanda `✅` hanya kerana:

- file sudah wujud;
- migration sudah dibuat;
- controller sudah dibuat;
- code kelihatan betul.

Validation wajib.

---

# 4. SOURCE OF TRUTH

Repository:

`akuhudhud/ODP`

Branch:

`main`

GitHub `main` ialah Source of Truth.

Development workflow:

GitHub
→ Development / Edit
→ Codespace apabila diperlukan
→ Test / Validate
→ GitHub

Codespace bukan Source of Truth.

Codespace tidak digunakan sekadar untuk `git pull`.

Codespace digunakan apabila ada kerja sebenar seperti:

- install/update dependency jika perlu;
- migration;
- test;
- runtime validation;
- debugging;
- build validation.

---

# 5. SYSTEM ARCHITECTURE

ODP menggunakan architecture:

CORE
+
BUSINESS MODULES

Core menyediakan:

- Identity
- Account
- Contact
- Profile
- Password
- OTP
- Authentication
- Session
- Security
- Authorization
- Service Eligibility
- Recovery
- Audit
- shared infrastructure

Business module ditambah kemudian tanpa merosakkan Core Foundation.

---

# 6. SYSTEM APPS

App types:

- USER
- RUNNER
- ADMIN

USER, RUNNER dan ADMIN bukan identity database berasingan.

Ia ialah capability / access context kepada Account.

---

# 7. IDENTITY MODEL

Identity utama ialah:

ACCOUNT

Satu Account boleh mempunyai capability:

- USER
- RUNNER
- ADMIN

Runner dan Admin bukan Account type berasingan.

---

# 8. ACCOUNT

## 8.1 Account ID

Account menggunakan UUID v7.

Database semasa:

`CHAR(36)`

Account ID:

- unik;
- tidak boleh digunakan semula;
- digunakan sebagai internal identity;
- tidak boleh ditukar selepas Account dicipta.

## 8.2 Account Status

Status yang sah:

- ACTIVE
- SUSPENDED
- DEACTIVATED
- DELETED

Tiada Account status:

`PENDING`

---

# 9. ACCOUNT LIFECYCLE

## ACTIVE

Account normal.

Boleh:

- login;
- menggunakan app;
- melihat account;
- mengubah data yang dibenarkan;
- menggunakan business service jika memenuhi eligibility.

## SUSPENDED

Account masih wujud tetapi berada di bawah restriction.

SUSPENDED:

- authentication masih boleh dikenal pasti;
- akses sangat terhad;
- tidak boleh menggunakan business service;
- tidak boleh mengubah profile;
- tidak boleh melakukan contact/credential changes melalui normal flow;
- boleh berkomunikasi atau appeal dengan Admin apabila fungsi tersebut tersedia;
- boleh dipulihkan oleh Admin.

SUSPENDED bukan authentication termination.

## DEACTIVATED

Account dinyahaktifkan.

Normal login tidak dibenarkan.

Session aktif tidak boleh digunakan.

Data account dikekalkan.

Reactivation boleh mengembalikan Account kepada ACTIVE.

DEACTIVATED bukan terminal state.

## DELETED

Account terminal state.

Tidak boleh:

- login;
- menggunakan session;
- menggunakan business service;
- menggunakan normal recovery.

Account ID tidak boleh digunakan semula.

---

# 10. ACCOUNT FOUNDATION STATUS

| Component | Status |
|---|---|
| Account entity | 🟢 |
| Account model | 🟢 |
| UUID v7 | 🟢 |
| Account status | 🟢 |
| Account lifecycle foundation | 🟢 |
| Lifecycle comprehensive validation | ⬜ |

---

# 11. CONTACT ARCHITECTURE

Contact types:

- PHONE
- EMAIL

Contact statuses:

- ACTIVE
- PENDING
- RELEASED

ACTIVE:

Contact telah berjaya diverifikasi dan boleh digunakan sebagai identity/login identifier.

PENDING:

Contact sedang menunggu verification dan belum menjadi active identity.

RELEASED:

Contact yang pernah dimiliki Account tetapi telah digantikan.

RELEASED contact boleh digunakan semula selepas verification yang sah.

---

# 12. CONTACT RULES

1. Satu Account maksimum satu ACTIVE PHONE.
2. Satu Account maksimum satu ACTIVE EMAIL.
3. Satu Account maksimum satu PENDING PHONE.
4. Satu Account maksimum satu PENDING EMAIL.
5. ACTIVE/PENDING contact value mesti mematuhi global uniqueness.
6. Phone mesti canonical E.164.
7. Email mesti dinormalisasi secara konsisten.
8. Contact hanya menjadi ACTIVE selepas verification berjaya.
9. Apabila contact baharu berjaya diverifikasi:
   - contact baharu menjadi ACTIVE;
   - contact lama bagi type yang sama menjadi RELEASED;
   - operasi mesti atomic.

---

# 13. CONTACT STATUS

| Component | Status |
|---|---|
| Account contacts table | 🟢 |
| PHONE | 🟢 |
| EMAIL | 🟢 |
| ACTIVE | 🟢 |
| PENDING | 🟢 |
| RELEASED | 🟢 |
| Contact uniqueness | 🟢 |
| Phone normalization | 🟢 |
| Email normalization | 🟢 |
| Contact read API | 🟢 |
| Contact verification API | ⬜ |
| Contact change workflow | ⬜ |

---

# 14. REGISTRATION ARCHITECTURE

Registration boleh bermula menggunakan salah satu:

- PHONE
- EMAIL

Primary contact mesti diverifikasi.

Secondary contact tidak wajib semasa Registration.

Registration tidak menggunakan Account status PENDING.

---

# 15. REGISTRATION PRINCIPLE

Registration Complete bermaksud:

- Account ACTIVE;
- primary contact VERIFIED;
- display name wujud;
- password wujud;
- device/session diwujudkan;
- auto login berjaya.

Registration Complete tidak bermaksud:

- Profile Complete;
- semua contact lengkap;
- semua Service Eligible.

---

# 16. REGISTRATION FLOW

USER
→ Choose Registration Contact
→ PHONE / EMAIL
→ Normalize Contact
→ Check Availability
→ Create Registration Context
→ Request OTP
→ OTP Delivery
→ Verify OTP
→ Primary Contact Verified
→ Display Name
→ Password
→ Create Account
→ Create ACTIVE Primary Contact
→ Create Profile
→ Create / Bind Device
→ Create Session
→ Account ACTIVE
→ Auto Login

---

# 17. REGISTRATION PRE-ACCOUNT RULE

Registration mempunyai state sebelum Account wujud.

Account tidak boleh menggunakan:

`ACCOUNT STATUS = PENDING`

untuk menyelesaikan Registration.

Registration pre-account state mesti menjadi context sementara dan bukan Account identity.

Persistence mechanism untuk Registration Context mesti ditentukan semasa implementation Registration Foundation.

Jangan create migration Registration Context secara berasingan sebelum design Registration Foundation disahkan.

---

# 18. REGISTRATION PRIMARY CONTACT

Primary contact ialah contact yang digunakan untuk memulakan Registration.

Ia boleh menjadi:

- PHONE
- EMAIL

Primary contact mesti:

- normalized;
- unique;
- verified.

Hanya selepas verification berjaya Account boleh diwujudkan.

---

# 19. REGISTRATION SECONDARY CONTACT

Secondary contact optional.

Contoh:

Register menggunakan PHONE
→ Email belum ada.

Account tetap:

ACTIVE

User masih boleh:

- login;
- browse;
- explore;
- menggunakan fungsi yang tidak memerlukan Email.

Dashboard akan mengingatkan user untuk melengkapkan contact apabila fungsi tersebut tersedia.

Reverse flow juga mesti disokong:

Register menggunakan EMAIL
→ Phone belum ada.

---

# 20. REGISTRATION CHECKLIST

| Component | Status |
|---|---|
| Registration architecture | 🔵 |
| Register Phone | ⬜ |
| Register Email | ⬜ |
| Registration context | ⬜ |
| REGISTER OTP | 🔴 |
| Verify primary contact | ⬜ |
| Display Name | ⬜ |
| Password creation | ⬜ |
| Account creation | ⬜ |
| ACTIVE Account | ⬜ |
| Primary contact ACTIVE | ⬜ |
| Profile creation | ⬜ |
| Device binding | ⬜ |
| Auto Login | ⬜ |
| Registration tests | ⬜ |
| End-to-end validation | ⬜ |

---

# 21. CURRENT REGISTER OTP ISSUE

OTP foundation telah tersedia.

Namun current REGISTER policy menganggap Account telah wujud.

Ini tidak selaras dengan Registration Architecture.

Current:

REGISTER OTP
→ current policy expects Account

Required:

REGISTER OTP
→ Registration Context
→ Account belum wujud

Status:

🔴 PERLU PEMBAIKAN

REGISTER implementation tidak boleh dianggap lengkap sebelum mismatch ini diselesaikan.

---

# 22. PROFILE

Profile fields:

- display_name
- display_name_changed_at
- profile_photo

Profile completion ialah derived state:

Display Name exists
+
Phone verified
+
Email verified

Tiada database field:

`profile_complete`

---

# 23. PROFILE STATUS

| Component | Status |
|---|---|
| Account Profile table | 🟢 |
| Display Name | 🟢 |
| Profile Photo | 🟢 |
| Profile read | 🟢 |
| Profile update | 🟢 |
| Display Name runtime tests | 🟢 |
| Profile completion calculation | ⬜ |
| Completion UI | ⬜ |

---

# 24. PASSWORD

Password policy:

- minimum 8;
- maximum 12;
- uppercase required;
- lowercase required;
- number required;
- symbol optional.

Password:

- hanya disimpan sebagai hash;
- plaintext tidak disimpan;
- tidak boleh masuk log;
- tidak boleh masuk audit payload.

Password change/reset menggunakan verified email.

---

# 25. PASSWORD STATUS

| Component | Status |
|---|---|
| Account passwords | 🟢 |
| Password hash | 🟢 |
| Password history | 🟢 |
| Password verification | 🟢 |
| Register password creation | ⬜ |
| Password change | ⬜ |
| Password reset | ⬜ |

---

# 26. OTP ARCHITECTURE

OTP:

- 6 digit;
- valid 5 minutes;
- maximum 3 verification attempts;
- resend cooldown 5 minutes;
- maximum 3 resends;
- selepas resend limit: 24-hour rolling lock;
- OTP baharu invalidate OTP lama bagi purpose/contact yang sama;
- plaintext OTP tidak disimpan;
- OTP terikat kepada exact contact/context.

---

# 27. OTP PURPOSES

- REGISTER
- VERIFY_PHONE
- VERIFY_EMAIL
- CHANGE_PHONE
- CHANGE_EMAIL
- ACCOUNT_RECOVERY

---

# 28. OTP DELIVERY

Initial delivery direction:

WhatsApp OTP

Email verification/recovery:

Email OTP

Delivery provider ialah infrastructure layer.

OTP Challenge Service tidak boleh bergantung terus kepada provider tertentu.

Architecture:

OTP Policy
→ OTP Challenge Service
→ OTP Delivery Interface
→ WhatsApp / Email

---

# 29. OTP STATUS

| Component | Status |
|---|---|
| OTP migration | 🟢 |
| OTP model | 🟢 |
| OTP challenge service | 🟢 |
| OTP policy foundation | 🟢 |
| Expiry | 🟢 |
| Attempt limit | 🟢 |
| Resend cooldown | 🟢 |
| Resend limit | 🟢 |
| Invalidation | 🟢 |
| Purpose | 🟢 |
| OTP policy tests | 🔵 |
| REGISTER integration | 🔴 |
| Phone verification | ⬜ |
| Email verification | ⬜ |
| WhatsApp delivery | ⬜ |
| Email delivery | ⬜ |

---

# 30. LOGIN

Login identifier boleh berupa:

- ACTIVE VERIFIED PHONE;
- ACTIVE VERIFIED EMAIL.

Missing secondary contact tidak menghalang login.

---

# 31. LOGIN FLOW

Identifier
→ Normalize
→ Find ACTIVE Contact
→ Resolve Account
→ Check Account Status
→ Check Security Lock
→ Verify Password
→ Validate Device
→ Create / Replace Active Session
→ Reset Login Security Counter
→ Return Authenticated Session

---

# 32. LOGIN STATUS

| Component | Status |
|---|---|
| Login controller | 🟢 |
| Identifier lookup | 🟢 |
| Password verification | 🟢 |
| Account status | 🟢 |
| Security check | 🟢 |
| Device check | 🟢 |
| Session creation | 🟢 |
| Active session replacement | 🟢 |
| Login response | 🟢 |
| Comprehensive login validation | ⬜ |
| Register → Login integration | ⬜ |

---

# 33. DEVICE

Device ialah identity context untuk session.

Device terikat kepada Account.

App context:

- USER
- RUNNER
- ADMIN

Device tidak mencipta identity baharu.

---

# 34. SESSION

Rule utama:

1 ACTIVE SESSION per ACCOUNT + APP.

Apabila session baharu diwujudkan:

Previous ACTIVE session
→ REVOKED

New session
→ ACTIVE

---

# 35. SESSION STATUS

| Component | Status |
|---|---|
| Device table | 🟢 |
| App session table | 🟢 |
| Session hashing | 🟢 |
| Session creation | 🟢 |
| Session replacement | 🟢 |
| Session middleware | 🟢 |
| Logout | 🟢 |
| Full session validation | ⬜ |

---

# 36. LOGIN SECURITY

Security levels:

LEVEL 0
→ Normal

LEVEL 1
→ selepas 3 failed attempts
→ lock 30 minutes

LEVEL 2
→ selepas next 3 failed attempts
→ lock 1 hour

LEVEL 3
→ Admin Review

Successful login reset applicable failed-attempt counters.

---

# 37. SECURITY STATUS

| Component | Status |
|---|---|
| Failed attempt tracking | 🟢 |
| Level 1 | 🟢 |
| Level 2 | 🟢 |
| Level 3 | 🟢 |
| Successful login reset | 🟢 |
| Comprehensive security validation | ⬜ |

---

# 38. AUTHENTICATION VS AUTHORIZATION

Authentication menjawab:

SIAPA ACCOUNT INI?

Authorization menjawab:

APA YANG ACCOUNT INI BOLEH BUAT?

Service Eligibility menjawab:

ADAKAH ACCOUNT INI MEMENUHI SYARAT SERVICE?

Ketiga-tiga concern mesti berasingan.

---

# 39. AUTHORIZATION

Basic USER capability tidak memerlukan capability row tambahan.

RUNNER ialah capability + eligibility layer.

ADMIN ialah privileged capability.

User, Runner dan Admin bukan identity database berasingan.

---

# 40. AUTHORIZATION STATUS

| Component | Status |
|---|---|
| Authentication foundation | 🟢 |
| Authorization architecture | 🟡 |
| User capability | ⬜ |
| Runner capability | ⬜ |
| Admin capability | ⬜ |
| Permission layer | ⬜ |
| Resource authorization | ⬜ |

---

# 41. SERVICE ELIGIBILITY

Business service tidak boleh bergantung kepada satu global:

`profile_complete = true`

Setiap service menentukan requirement sendiri.

---

# 42. DELIVERY SERVICE EXAMPLE

Contoh requirement Delivery Request:

- Account ACTIVE;
- authenticated;
- Display Name exists;
- Phone verified;
- Email verified;
- service authorization.

Requirement ini hanya untuk service yang memerlukannya.

Ia bukan global Login requirement.

---

# 43. SERVICE ELIGIBILITY STATUS

| Component | Status |
|---|---|
| Eligibility architecture | 🟡 |
| Eligibility policy | ⬜ |
| Requirement engine | ⬜ |
| Eligibility API | ⬜ |
| Delivery eligibility | ⬜ |
| Runner eligibility | ⬜ |

---

# 44. DASHBOARD COMPLETION

Dashboard perlu menunjukkan missing account requirements.

Contoh:

Display Name ✓
Phone ✓
Email ✕

→ Verify Email

Jika registration menggunakan email:

Display Name ✓
Email ✓
Phone ✕

→ Verify Phone

User tetap boleh menggunakan fungsi yang tidak memerlukan contact kedua.

---

# 45. DASHBOARD STATUS

| Component | Status |
|---|---|
| Completion calculation | ⬜ |
| Missing requirement detection | ⬜ |
| Reminder | ⬜ |
| Verify action | ⬜ |

---

# 46. ACCOUNT RECOVERY

Recovery database foundation telah tersedia:

- account_recovery_requests
- account_recovery_tokens

Normal password recovery memerlukan verified email.

DELETED Account tidak mempunyai normal recovery.

---

# 47. RECOVERY STATUS

| Component | Status |
|---|---|
| Recovery requests table | 🟢 |
| Recovery tokens table | 🟢 |
| Recovery API | ⬜ |
| Verified email check | ⬜ |
| Recovery OTP | ⬜ |
| Password reset | ⬜ |
| Recovery tests | ⬜ |

---

# 48. SECURITY ACTIVITY

Security Activity:

- JSONL;
- retention 3 months.

Digunakan untuk security events yang berkaitan dengan Account.

---

# 49. SECURITY ACTIVITY STATUS

| Component | Status |
|---|---|
| Architecture | 🟡 |
| JSONL implementation | ⬜ |
| Retention | ⬜ |
| Security event integration | ⬜ |

---

# 50. AUDIT LOG

Audit Log:

- JSONL;
- monthly files;
- minimum 7 years;
- SHA-256 fingerprint;
- Root Admin / Kapten access.

Sensitive values tidak boleh dilog dalam plaintext:

- password;
- OTP;
- session token;
- recovery token.

---

# 51. AUDIT LOG STATUS

| Component | Status |
|---|---|
| Audit architecture | 🟡 |
| JSONL implementation | ⬜ |
| Monthly storage | ⬜ |
| SHA-256 fingerprint | ⬜ |
| 7-year retention | ⬜ |
| Restricted access | ⬜ |

---

# 52. API ARCHITECTURE

Current API version:

`v1`

Base:

`/api/v1`

Current foundation:

GET /health

POST /auth/login

GET /account

GET /account/profile

GET /account/contacts

PUT /account/profile

POST /auth/logout

---

# 53. API RESPONSE

Standard response:

{
  "status": "...",
  "message": "...",
  "data": {}
}

API responses mesti konsisten.

Error response tidak boleh mencipta format berbeza tanpa architectural decision.

---

# 54. API STATUS

| Component | Status |
|---|---|
| API v1 | 🟢 |
| Health | 🟢 |
| Response standard | 🟢 |
| Login | 🟢 |
| Logout | 🟢 |
| Account | 🟢 |
| Profile | 🟢 |
| Contacts | 🟢 |
| Register | ⬜ |
| OTP API | ⬜ |
| Contact verification | ⬜ |
| Contact change | ⬜ |
| Recovery | ⬜ |
| Eligibility | ⬜ |

---

# 55. DATABASE FOUNDATION

Current Account/Auth foundation:

- accounts
- account_contacts
- account_profiles
- account_passwords
- account_password_history
- account_devices
- account_security
- app_sessions
- otp_challenges
- account_recovery_requests
- account_recovery_tokens

---

# 56. DATABASE STATUS

| Component | Status |
|---|---|
| accounts | 🟢 |
| account_contacts | 🟢 |
| account_profiles | 🟢 |
| account_passwords | 🟢 |
| account_password_history | 🟢 |
| account_devices | 🟢 |
| account_security | 🟢 |
| app_sessions | 🟢 |
| otp_challenges | 🟢 |
| account_recovery_requests | 🟢 |
| account_recovery_tokens | 🟢 |
| Registration context | ⬜ |
| Security Activity storage | ⬜ |
| Audit Log storage | ⬜ |

---

# 57. DATABASE RULES

Database mesti menjaga:

- identity uniqueness;
- contact uniqueness;
- state integrity;
- foreign key integrity;
- session integrity;
- security state integrity.

Application logic tidak boleh menjadi satu-satunya protection untuk rule yang boleh dijaga oleh database.

---

# 58. BUSINESS MODULE ARCHITECTURE

Business module pertama:

FOOD / DELIVERY

Food module boleh berkembang kepada:

- Food;
- Delivery;
- Grocery;
- Vendor;
- Runner;
- Order;
- Dispatch.

Business module tidak dibangunkan sebelum Core foundation yang diperlukan stabil.

---

# 59. BUSINESS MODULE STATUS

| Module | Status |
|---|---|
| Food | ⬜ |
| Delivery | ⬜ |
| Grocery | ⬜ |
| Vendor | ⬜ |
| Runner business workflow | ⬜ |
| Order | ⬜ |
| Dispatch | ⬜ |
| Payment | ⬜ |
| Wallet | ⬜ |

---

# 60. USER / RUNNER / ADMIN

## USER

Basic Account capability.

## RUNNER

Runner ialah capability + eligibility.

Runner bukan Account type berasingan.

## ADMIN

Admin ialah privileged capability.

Admin access mesti mempunyai authorization lebih tinggi.

---

# 61. TEST STRATEGY

Testing dibuat berdasarkan functional foundation.

Bukan berdasarkan jumlah file.

Functional foundation mesti lengkap dahulu sebelum comprehensive test.

---

# 62. CURRENT TEST STATUS

| Area | Status |
|---|---|
| Account tests | 🟢 |
| Profile tests | 🟢 |
| Contact tests | 🟢 |
| Login tests | 🟢 |
| Logout tests | 🟢 |
| OTP policy tests | 🔵 |
| Registration tests | ⬜ |
| Register → Login E2E | ⬜ |
| Recovery tests | ⬜ |
| Eligibility tests | ⬜ |

---

# 63. REGISTRATION FINAL TEST

Phone registration:

REGISTER PHONE
→ Request OTP
→ Receive OTP
→ Verify OTP
→ Display Name
→ Password
→ Create Account
→ ACTIVE
→ Create Device
→ Auto Login
→ Account Access
→ Logout
→ Login

Email registration:

REGISTER EMAIL
→ Request OTP
→ Receive OTP
→ Verify OTP
→ Display Name
→ Password
→ Create Account
→ ACTIVE
→ Create Device
→ Auto Login
→ Account Access
→ Logout
→ Login

Kedua-dua flow mesti lulus sebelum Registration Foundation ditanda:

`✅`

---

# 64. LOGIN FINAL TEST

Selepas Registration Foundation:

REGISTER
→ AUTO LOGIN
→ LOGOUT
→ LOGIN USING VERIFIED PRIMARY CONTACT
→ SESSION ACTIVE
→ ACCOUNT ACCESS

Kemudian validate:

- wrong password;
- failed attempts;
- security lock;
- device validation;
- session replacement;
- logout.

---

# 65. ERROR ARCHITECTURE

General HTTP semantics:

| Code | Meaning |
|---|---|
| 400 | Malformed request |
| 401 | Authentication required / invalid authentication |
| 403 | Authenticated but not authorized |
| 409 | Resource/state conflict |
| 422 | Validation failure |
| 423 | Security lock / restricted state where applicable |

Exact application error codes boleh berkembang tanpa mengubah response envelope.

---

# 66. VERSIONING

Current milestones:

v0.1.0
Repository Foundation

v0.5.0
Account & Authentication Foundation

Setiap milestone mesti mengemas kini:

- VERSION;
- CHANGELOG;
- DEVELOPMENT_LOG;
- Master Blueprint.

---

# 67. CURRENT v0.5.0 FOUNDATION

v0.5.0 foundation merangkumi:

- Account;
- Account Contact;
- Account Profile;
- Password storage;
- Password history;
- Device;
- Session;
- Login Security;
- OTP Challenge foundation;
- Account Recovery database foundation;
- Account/Profile API;
- Login/Logout foundation.

v0.5.0 belum lengkap untuk:

- Registration workflow;
- OTP delivery API;
- Contact verification workflow;
- Contact change workflow;
- Password change;
- Password reset;
- Recovery API;
- Service eligibility;
- Business modules.

---

# 68. DEVELOPMENT PRIORITY

## PHASE 1 — REGISTRATION FOUNDATION

1. Fix REGISTER OTP architecture.
2. Define Registration Context.
3. Register Phone.
4. Register Email.
5. Primary Contact Verification.
6. Display Name.
7. Password Creation.
8. Account Creation.
9. ACTIVE Account.
10. Device Binding.
11. Auto Login.
12. Registration Tests.
13. Comprehensive Validation.

## PHASE 2 — LOGIN INTEGRATION

1. Phone Login.
2. Email Login.
3. Device validation.
4. Session validation.
5. Security validation.
6. REGISTER → LOGIN comprehensive test.

## PHASE 3 — CONTACT COMPLETION

1. Verify secondary contact.
2. Contact change.
3. Dashboard reminder.
4. Completion state.

## PHASE 4 — SERVICE ELIGIBILITY

1. Authorization.
2. Eligibility architecture.
3. Delivery requirements.
4. Runner eligibility.

---

# 69. IMPLEMENTATION DISCIPLINE

Sebelum coding:

AUDIT

Kemudian:

BLUEPRINT

Kemudian:

LOCK

Baru:

IMPLEMENT

Tidak boleh:

CODE FIRST
THINK LATER

---

# 70. FILE CHANGE DISCIPLINE

Setiap perubahan mesti dinyatakan sebagai:

CREATE

atau:

EDIT

Apabila file dibuat/diedit secara manual melalui GitHub, gunakan full file content.

Jangan bergantung kepada replacement fragment jika full file diperlukan.

Satu functional change disiapkan sebagai satu unit.

---

# 71. GITHUB DISCIPLINE

GitHub `main` ialah Source of Truth.

Selepas commit:

AUDIT REPO
→ CONFIRM FILE
→ REVIEW DIFF
→ CONTINUE

Jangan assume perubahan berjaya hanya kerana arahan telah diberi.

---

# 72. LOCKED ARCHITECTURE RULES

Selepas Master Blueprint diluluskan, rules berikut menjadi locked:

1. Master Blueprint ialah architecture source of truth.
2. Account tidak mempunyai status PENDING.
3. Registration boleh bermula menggunakan Phone atau Email.
4. Registration primary contact mesti verified.
5. Secondary contact tidak wajib semasa Registration.
6. Registration Complete ≠ Profile Complete.
7. Profile Complete ≠ Service Eligible.
8. ACTIVE Account boleh menggunakan app walaupun secondary contact belum verified.
9. Business service menentukan eligibility sendiri.
10. Authentication, Authorization dan Service Eligibility ialah concern berasingan.
11. Account ID menggunakan UUID v7.
12. Account ID tidak boleh digunakan semula.
13. OTP menggunakan policy yang telah ditetapkan.
14. Password plaintext tidak boleh disimpan.
15. Maksimum satu ACTIVE session bagi Account + App.
16. DELETED ialah terminal state.
17. SUSPENDED ialah restricted access, bukan authentication termination.
18. GitHub main ialah Source of Truth.
19. Codespace bukan Source of Truth.
20. Codespace tidak digunakan sekadar untuk pull.
21. Functional foundation mesti dilengkapkan sebelum comprehensive test.
22. Status `✅` hanya selepas review + test/validation.
23. REGISTER OTP mesti menyokong pre-account Registration Context.
24. Jangan create Account PENDING untuk Registration.
25. Jangan create migration Registration Context sebelum implementation design Registration Foundation disahkan.

---

# 73. ARCHITECTURE CHANGE CONTROL

Jika architecture perlu berubah:

IDENTIFY CONFLICT
→ AUDIT
→ PROPOSE CHANGE
→ DECISION
→ UPDATE MASTER BLUEPRINT
→ IMPLEMENT
→ TEST

Tidak boleh ubah locked architecture secara senyap dalam code.

---

# 74. CURRENT MASTER BLUEPRINT STATUS

Architecture:

DRAFT — MENUNGGU LOCK

Repository:

akuhudhud/ODP

Branch:

main

Current Version:

v0.5.0

Current Functional Priority:

REGISTER

Next:

LOCK MASTER BLUEPRINT

---

# 75. NEXT DEVELOPMENT TARGET

Selepas Master Blueprint di-Lock:

REGISTER FOUNDATION

Bukan terus:

- business module;
- delivery;
- runner;
- payment.

Foundation mesti stabil dahulu.

---

# 76. FINAL ARCHITECTURE PRINCIPLE

IDENTITY
→ ACCOUNT
→ CONTACT
→ VERIFICATION
→ CREDENTIAL
→ AUTHENTICATION
→ SESSION
→ AUTHORIZATION
→ SERVICE ELIGIBILITY
→ BUSINESS MODULE

Setiap layer mempunyai tanggungjawab sendiri.

Tiada layer boleh mengambil alih tanggungjawab layer lain tanpa architectural decision.

BUILD FOUNDATION
→ VALIDATE
→ EXPAND
