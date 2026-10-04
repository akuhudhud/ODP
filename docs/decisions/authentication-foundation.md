# Asas Akaun dan Pengesahan

## Status

LOCKED

## Versi

v0.5.0 — Asas Akaun dan Pengesahan

## Tujuan

Dokumen ini menetapkan architecture rasmi untuk Account dan Authentication ODP bagi v0.5.0.

Dokumen ini menjadi rujukan utama implementation.

Keputusan yang dinyatakan sebagai LOCKED tidak boleh diubah tanpa keputusan baharu daripada Kapten.

---

# 1. Prinsip Akaun

ODP menggunakan satu identiti akaun berpusat.

Satu Account mewakili satu identiti dalam platform ODP.

Account yang sama boleh digunakan oleh aplikasi atau fungsi ODP yang berbeza tanpa mewujudkan account berasingan.

Account ID menggunakan UUID v7.

Account ID tidak boleh digunakan semula.

---

# 2. Account

Table utama:

`accounts`

Field:

- `id` — UUID v7
- `status`
- `created_at`
- `updated_at`
- `deactivated_at`
- `deleted_at`

Status Account:

- `ACTIVE`
- `SUSPENDED`
- `DEACTIVATED`
- `DELETED`

## DELETED

Status `DELETED` adalah kekal.

Data dan rekod account dikekalkan mengikut keperluan retention.

Account ID tidak boleh digunakan semula.

Account yang telah `DELETED` tidak boleh dipulihkan melalui Account Recovery.

---

# 3. Contact

Table:

`account_contacts`

Jenis contact:

- Phone
- Email

Setiap contact mempunyai status:

- `ACTIVE`
- `PENDING`
- `RELEASED`

`PENDING` digunakan untuk contact baharu yang sedang melalui proses verification sebelum menjadi contact `ACTIVE`.

Contact `PENDING` tidak dianggap sebagai active identity contact.

## Phone

Phone number menggunakan format E.164.

Phone verification menggunakan WhatsApp OTP.

## Email

Email digunakan untuk:

- Verification
- Password change
- Password reset
- Account recovery

Email mesti dinormalisasi secara konsisten.

Normalisasi tidak boleh bergantung kepada alias atau behaviour khusus provider.

## Active Contact

Satu account boleh mempunyai maksimum:

- satu active phone
- satu active email

Contact `PENDING` boleh wujud sementara proses verification berjalan.

Satu account hanya boleh mempunyai:

- satu `PENDING` phone
- satu `PENDING` email

pada satu masa.

Contact `PENDING` tidak boleh menjadi active identity contact sehingga verification berjaya.

Contact `PENDING` juga tidak boleh digunakan oleh account lain kerana nilai contact mesti kekal unik sepanjang proses verification.

Selepas verification berjaya:

1. contact baharu menjadi `ACTIVE`
2. contact lama bagi type yang sama menjadi `RELEASED`

Peralihan tersebut mesti dilakukan secara atomic dalam satu transaction.

Contact yang telah `RELEASED` boleh digunakan oleh account lain selepas verification berjaya.

---

# 4. Profile

Table:

`account_profiles`

Field utama:

- `display_name`
- `display_name_changed_at`
- `profile_photo`

## Display Name

Display name:

- wajib untuk profile lengkap
- tidak unik
- hanya boleh mengandungi huruf Unicode dan ruang
- tidak boleh mengandungi nombor
- tidak boleh mengandungi special character

Display name hanya boleh ditukar sekali setiap 30 hari.

## Profile Lengkap

Profile dianggap lengkap apabila:

- display name wujud
- phone telah disahkan
- email telah disahkan

Tiada field `profile_complete` disimpan dalam database.

Status profile lengkap ditentukan berdasarkan keadaan sebenar account.

---

# 5. Kata Laluan

Table:

`account_passwords`

Sejarah kata laluan:

`account_password_history`

Kata laluan hanya disimpan dalam bentuk hash.

Password plaintext tidak boleh disimpan.

## Polisi Kata Laluan

Panjang:

- minimum 8 aksara
- maksimum 12 aksara

Mesti mempunyai:

- sekurang-kurangnya satu huruf besar
- sekurang-kurangnya satu huruf kecil
- sekurang-kurangnya satu nombor

Simbol tidak diwajibkan.

Password confirmation tidak disimpan.

## Tukar dan Reset Password

Password change dan password reset hanya boleh dilakukan melalui email yang telah disahkan.

Phone OTP tidak digunakan untuk password change atau password reset.

Password change tidak memerlukan current password.

Current password tidak boleh digunakan semula serta-merta.

Password history tidak pernah menyimpan plaintext.

---

# 6. Device

Table:

`account_devices`

Device bukan identity.

Device identifier bukan permanent identity account.

Device digunakan untuk mengenal pasti konteks peranti dan session.

---

# 7. Session

Table:

`app_sessions`

Aplikasi ODP:

- `USER`
- `RUNNER`
- `ADMIN`

Maksimum satu session `ACTIVE` bagi kombinasi:

`Account + App`

User App dan Runner App boleh mempunyai session aktif secara serentak.

## Login Baharu

Login yang berjaya pada aplikasi yang sama akan revoke session lama bagi account tersebut.

Login yang gagal tidak akan revoke session lama.

Security lock berasingan daripada Account Status.

---

# 8. Login Security

Sistem menggunakan escalation berdasarkan percubaan password yang gagal.

## Level 1

Selepas 3 percubaan password gagal:

- Level 1
- lock selama 30 minit

## Level 2

Selepas 3 percubaan gagal seterusnya:

- Level 2
- lock selama 1 jam

## Level 3

Selepas 3 percubaan gagal seterusnya:

- Level 3
- Admin Review diperlukan

Level 3 tidak mempunyai timer tetap 24 jam.

Account tidak boleh login sehingga Admin Review diselesaikan.

Login berjaya pada Level 1 atau Level 2 akan reset escalation.

---

# 9. OTP

OTP menggunakan 6 digit.

Tempoh sah:

- 5 minit

Percubaan memasukkan OTP:

- maksimum 3 kali

Resend:

- cooldown 5 minit
- maksimum 3 resend

Selepas maksimum 3 resend dicapai:

- resend disekat
- pengguna perlu menunggu 24 jam
- 24 jam dikira dari `last_sent_at` resend terakhir
- pengiraan menggunakan rolling 24 jam
- perubahan tarikh atau 00:00 tidak mereset sekatan

Pengguna boleh menghubungi Admin untuk bantuan sekiranya resend masih disekat.

OTP baharu akan membatalkan OTP lama bagi contact dan purpose yang sama.

OTP plaintext tidak boleh disimpan.

OTP hanya disimpan dalam bentuk hash.

OTP mesti terikat kepada contact yang tepat.

OTP untuk phone mesti berkait dengan phone contact yang tepat.

OTP untuk email mesti berkait dengan email contact yang tepat.

OTP untuk contact `PENDING` digunakan untuk menyelesaikan verification sebelum contact tersebut boleh menjadi `ACTIVE`.

## OTP Purpose

OTP boleh digunakan untuk:

- `REGISTER`
- `VERIFY_PHONE`
- `VERIFY_EMAIL`
- `CHANGE_PHONE`
- `CHANGE_EMAIL`
- `ACCOUNT_RECOVERY`

Password change dan password reset kekal menggunakan verified email dan tidak menggunakan phone OTP.

---

# 10. Admin OTP Override

Admin mempunyai dua tindakan khas berkaitan OTP resend dan verification.

## RESET_OTP_RESEND_LOCK

Admin boleh membuka semula sekatan resend.

Selepas sekatan dibuka:

- pengguna boleh request OTP baharu
- OTP tetap dihantar melalui channel OTP yang sah
- pengguna tetap perlu melalui proses verification biasa

Admin tidak mengesahkan OTP bagi pihak pengguna melalui tindakan ini.

## BYPASS_OTP_VERIFICATION

Admin boleh melakukan bypass verification apabila tindakan tersebut diperlukan.

Apabila bypass diluluskan:

- sistem menandakan verification sebagai berjaya
- pengguna tidak perlu memasukkan OTP untuk verification tersebut

Admin:

- tidak melihat OTP
- tidak menetapkan OTP
- tidak menerima OTP plaintext

Kedua-dua tindakan:

- `RESET_OTP_RESEND_LOCK`
- `BYPASS_OTP_VERIFICATION`

mestilah:

- dilakukan oleh Admin yang mempunyai authorization yang sesuai
- mempunyai `reason`
- direkodkan dalam Audit Log

Kedua-dua tindakan ini tidak mengubah Account Status.

---

# 11. Account Recovery

ODP menggunakan Model C untuk Account Recovery.

Account Recovery memerlukan Recovery Request.

Admin sahaja boleh:

- approve
- reject

Recovery Request.

Admin tidak melihat atau menetapkan password pengguna.

Sistem menentukan recovery path berdasarkan keadaan account dan verification yang tersedia.

## Recovery Mode

Selepas Recovery Request diluluskan:

- Recovery Mode sah selama 30 minit

Recovery yang berjaya akan revoke semua active sessions.

Account Recovery tidak mengubah Account Status.

Status selepas recovery dikekalkan:

- `ACTIVE` → `ACTIVE`
- `SUSPENDED` → `SUSPENDED`
- `DEACTIVATED` → `DEACTIVATED`

Account `DELETED` tidak boleh menjalani Account Recovery.

## Recovery Reason

Reason yang dibenarkan:

- `EMAIL_INACCESSIBLE`
- `PHONE_AND_EMAIL_INACCESSIBLE`
- `OTHER`

## Recovery Status

Status:

- `PENDING`
- `APPROVED`
- `REJECTED`
- `CANCELLED`

---

# 12. Security Activity

Security Activity ialah rekod aktiviti keselamatan milik pemilik Account.

Hanya pemilik Account boleh melihat Security Activity sendiri.

Admin tidak mempunyai akses biasa kepada Security Activity pengguna melalui fungsi ini.

Format:

`JSONL`

Retention:

3 bulan

Security Activity mesti disimpan secara fizikal berasingan daripada Audit Log.

## Event

Security Activity boleh merekod event seperti:

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

Result:

- `SUCCESS`
- `FAILED`
- `BLOCKED`

App:

- `USER`
- `RUNNER`
- `ADMIN`

---

# 13. Audit Log

Audit Log adalah untuk kegunaan dalaman.

Akses:

- Root Admin
- Kapten

Audit Log:

- format JSONL
- append-only
- retention minimum 7 tahun
- fail dipecahkan mengikut bulan
- menggunakan hash chain
- mempunyai fingerprint SHA-256 bulanan

Aktiviti export atau logging yang berkaitan juga mesti diaudit.

Security Activity dan Audit Log tidak boleh dicampurkan.

## High-Risk Admin Events

Antara event high-risk:

- `OTP_RESEND_LOCK_RESET`
- `OTP_VERIFICATION_BYPASSED`

High-risk Admin action mesti mempunyai:

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

# 14. Authentication dan Authorization

Authentication menentukan identiti account.

## Account Status Access Rules

Authentication dan service access tidak ditentukan oleh satu rule yang sama.

### ACTIVE

Account `ACTIVE`:

- boleh login
- boleh mengurus profile tertakluk kepada policy
- boleh menggunakan business service tertakluk kepada authorization

### SUSPENDED

Account `SUSPENDED`:

- masih boleh login
- boleh melihat status account dan maklumat yang dibenarkan
- boleh menggunakan fungsi komunikasi atau rayuan dengan Admin apabila disediakan
- tidak boleh mengubah profile
- tidak boleh menukar contact atau credential melalui flow biasa
- tidak boleh menggunakan business service
- kekal dalam kawalan pentadbiran sehingga suspension diselesaikan

`SUSPENDED` ialah restricted access, bukan authentication termination.

Tujuan status ini termasuk keadaan seperti investigation, audit atau administrative review.

### DEACTIVATED

Account `DEACTIVATED`:

- tidak boleh login melalui authentication biasa
- existing active session tidak boleh digunakan
- data Account dikekalkan
- boleh menjalani Reactivation / Account Recovery Flow yang sah
- selepas reactivation yang berjaya, status Account boleh kembali kepada `ACTIVE`

`DEACTIVATED` bukan status terminal.

### DELETED

Account `DELETED`:

- tidak boleh login
- tidak boleh menggunakan existing session
- tidak boleh menggunakan business service
- tidak boleh dipulihkan melalui Account Recovery biasa

`DELETED` ialah terminal Account Status.

### Separation of Concerns

Account Status menentukan keadaan lifecycle Account.

Authentication menentukan sama ada Account boleh mendapatkan authenticated session.

Authorization menentukan fungsi yang boleh digunakan selepas authentication.

Business service access tidak boleh ditentukan semata-mata berdasarkan kewujudan authenticated session.

Authorization menentukan apa yang account tersebut dibenarkan lakukan.

Kedua-dua concern mesti kekal berasingan.

Account yang sama boleh mempunyai akses kepada aplikasi yang berbeza.

Aplikasi awal:

- User App
- Runner App
- Admin

Permission dan capability khusus aplikasi akan dibina secara berasingan daripada identity foundation.

USER ialah base capability dan tidak memerlukan capability row berasingan.

Runner capability dan Runner eligibility ialah dua perkara berbeza.

Identity Verification bukan authentication, capability, authorization atau service access.

---

# 15. API

Authentication API berada di bawah:

`/api/v1/auth`

API menggunakan standard response ODP yang telah ditetapkan.

User-facing message mesti menggunakan Bahasa Malaysia.

Technical identifiers seperti:

- `status`
- `message`
- `data`
- endpoint path
- class name
- method name

boleh kekal dalam English apabila diperlukan oleh technical implementation.

---

# 16. Bahasa Projek

Bahasa rasmi ODP ialah Bahasa Malaysia.

Bahasa Malaysia digunakan untuk:

- User App
- Runner App
- Admin
- API user-facing messages
- Error messages
- Validation messages
- Notification
- OTP
- README
- CHANGELOG
- DEVELOPMENT_LOG
- Architecture documentation
- API documentation
- Decision records
- Development Rules

English hanya dikekalkan apabila diperlukan sebagai technical identifier, nama framework, standard teknikal atau code identifier.

---

# 17. Di Luar Scope v0.5.0

Perkara berikut tidak termasuk dalam v0.5.0:

- Identity Verification
- Runner onboarding
- Vehicle
- Order
- Job
- Matching
- Dispatch
- Delivery operation
- Personal Shopper operation
- Business-specific operational workflow

Semua perkara tersebut ialah `FUTURE`.

Ia tidak boleh dimasukkan ke implementation v0.5.0 tanpa keputusan scope baharu.

---

# 18. Prinsip Architecture

Account ialah identity foundation.

Authentication mengesahkan identity.

Authorization menentukan capability.

Profile menyimpan maklumat profil.

Device dan Session mengurus konteks akses.

Security Activity merekod aktiviti keselamatan pengguna.

Audit Log merekod aktiviti dalaman yang memerlukan audit.

OTP verification ialah proses pengesahan contact.

Identity Verification ialah concern berasingan.

Runner eligibility ialah concern berasingan.

Setiap concern hendaklah kekal berasingan.

Architecture hendaklah mengelakkan business logic daripada masuk terlalu awal ke Identity Foundation.

Foundation mesti boleh digunakan oleh User App, Runner App dan Admin tanpa mewujudkan identity account yang berasingan.

---

# 19. Status Keputusan

Architecture dalam dokumen ini adalah:

`LOCKED`

Sebarang perubahan kepada keputusan ini memerlukan keputusan baharu daripada Kapten sebelum implementation diteruskan.
