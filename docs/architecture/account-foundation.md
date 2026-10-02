ODP Account Foundation

Status

LOCKED

Versi

v0.5.0 — Asas Akaun dan Pengesahan

Tujuan

Dokumen ini menetapkan blueprint rasmi untuk Account Foundation ODP.

Account Foundation menjadi asas identiti, pengesahan, profil, device, session dan keselamatan account sebelum business workflow dibangunkan.

Dokumen ini menjadi rujukan utama untuk implementation v0.5.0.

Keputusan yang dinyatakan sebagai LOCKED tidak boleh diubah tanpa keputusan baharu daripada Kapten.

---

1. Prinsip Account

ODP menggunakan satu identiti account berpusat.

Satu Account mewakili satu identiti dalam platform ODP.

Account yang sama boleh digunakan oleh aplikasi atau fungsi ODP yang berbeza tanpa mewujudkan account berasingan.

Account ID menggunakan UUID v7.

Account ID tidak boleh digunakan semula.

UUID v7 dijana oleh application layer sebelum rekod Account disimpan ke database.

Database hanya menyimpan UUID v7 yang telah dijana oleh application.

ODP tidak bergantung kepada database untuk menjana UUID v7.

---

2. Account

Table utama:

"accounts"

Field:

- "id" — UUID v7
- "status"
- "created_at"
- "updated_at"
- "deactivated_at"
- "deleted_at"

Status Account:

- "ACTIVE"
- "SUSPENDED"
- "DEACTIVATED"
- "DELETED"

ACTIVE

Account boleh digunakan seperti biasa tertakluk kepada authentication dan authorization.

SUSPENDED

Account disekat sementara mengikut keputusan sistem atau pentadbiran.

DEACTIVATED

Account tidak aktif tetapi masih wujud dalam sistem.

DELETED

Status "DELETED" adalah kekal.

Data dan rekod Account dikekalkan mengikut keperluan retention.

Account ID tidak boleh digunakan semula.

Account yang telah "DELETED" tidak boleh dipulihkan melalui Account Recovery.

Account deletion tidak bermaksud rekod Account dibuang secara fizikal daripada database.

---

3. Contact

Table:

"account_contacts"

Jenis contact:

- Phone
- Email

Setiap contact mempunyai:

- jenis contact
- nilai contact
- status
- verification status
- masa verification apabila berkaitan

Status contact:

- "ACTIVE"
- "RELEASED"

Phone

Phone number menggunakan format E.164.

Phone verification menggunakan WhatsApp OTP.

Satu Account boleh mempunyai maksimum:

- satu active phone

Phone yang telah "RELEASED" boleh digunakan oleh Account lain selepas verification berjaya.

Email

Email digunakan untuk:

- Verification
- Password change
- Password reset
- Account recovery

Email mesti dinormalisasi secara konsisten.

Normalisasi tidak boleh bergantung kepada alias atau behaviour khusus provider.

Satu Account boleh mempunyai maksimum:

- satu active email

Email yang telah "RELEASED" boleh digunakan oleh Account lain selepas verification berjaya.

Active Contact

Constraint:

- maksimum satu active phone bagi Account
- maksimum satu active email bagi Account

Contact yang tidak aktif tidak dianggap sebagai active identity contact.

---

4. Profile

Table:

"account_profiles"

Field utama:

- "display_name"
- "display_name_changed_at"
- "profile_photo"

Profile berkait terus dengan Account.

Display Name

Display name:

- wajib untuk profile lengkap
- tidak unik
- hanya boleh mengandungi huruf Unicode dan ruang
- tidak boleh mengandungi nombor
- tidak boleh mengandungi special character

Display name hanya boleh ditukar sekali setiap 30 hari.

Sistem menentukan kelayakan perubahan berdasarkan:

"display_name_changed_at"

Tiada counter tambahan diperlukan.

Profile Lengkap

Profile dianggap lengkap apabila:

- display name wujud
- phone telah disahkan
- email telah disahkan

Tiada field:

"profile_complete"

disimpan dalam database.

Status profile lengkap ditentukan berdasarkan keadaan sebenar Account.

---

5. Kata Laluan

Table:

"account_passwords"

Sejarah kata laluan:

"account_password_history"

Kata laluan hanya disimpan dalam bentuk hash.

Password plaintext tidak boleh disimpan.

Polisi Kata Laluan

Panjang:

- minimum 8 aksara
- maksimum 12 aksara

Mesti mempunyai:

- sekurang-kurangnya satu huruf besar
- sekurang-kurangnya satu huruf kecil
- sekurang-kurangnya satu nombor

Simbol tidak diwajibkan.

Password confirmation tidak disimpan.

Tukar Password

Password change hanya boleh dilakukan melalui email yang telah disahkan.

Phone OTP tidak digunakan untuk password change.

Password change tidak memerlukan current password.

Current password tidak boleh digunakan semula serta-merta.

Password history digunakan untuk menghalang penggunaan semula password yang baru digunakan.

Reset Password

Password reset hanya boleh dilakukan melalui email yang telah disahkan.

Phone OTP tidak digunakan untuk password reset.

Password reset tidak menyimpan password plaintext.

Password history tidak pernah menyimpan plaintext.

---

6. Device

Table:

"account_devices"

Device bukan identity.

Device identifier bukan permanent identity Account.

Device digunakan untuk mengenal pasti konteks peranti dan session.

Device boleh digunakan semula atau berubah.

Identity Account kekal berdasarkan Account ID dan bukan device identifier.

Device hendaklah mempunyai kaitan dengan Account yang berkaitan.

Maklumat device yang diperlukan untuk konteks session boleh disimpan.

---

7. Session

Table:

"app_sessions"

Aplikasi ODP:

- "USER"
- "RUNNER"
- "ADMIN"

Maksimum satu session "ACTIVE" bagi kombinasi:

"Account + App"

Contoh:

Account A boleh mempunyai:

- User App — ACTIVE
- Runner App — ACTIVE

secara serentak.

Tetapi Account yang sama tidak boleh mempunyai dua session "ACTIVE" untuk User App pada masa yang sama.

Login Baharu

Login yang berjaya pada aplikasi yang sama akan:

1. mewujudkan session baharu
2. revoke session lama bagi Account + App
3. mengaktifkan session baharu

Login yang gagal tidak akan revoke session lama.

Security Lock

Security lock adalah berasingan daripada Account Status.

Contoh:

Account boleh mempunyai status:

"ACTIVE"

tetapi login masih disekat kerana security lock.

---

8. Login Security

Sistem menggunakan escalation berdasarkan percubaan password yang gagal.

Level 1

Selepas:

"3" percubaan password gagal

Sistem:

- menetapkan Level 1
- lock selama 30 minit

Level 2

Selepas 3 percubaan gagal seterusnya:

- menetapkan Level 2
- lock selama 1 jam

Level 3

Selepas 3 percubaan gagal seterusnya:

- menetapkan Level 3
- Admin Review diperlukan

Level 3 tidak mempunyai timer tetap 24 jam.

Account tidak boleh login sehingga Admin Review diselesaikan.

Reset Escalation

Login yang berjaya pada:

- Level 1
- Level 2

akan reset escalation.

Login yang berjaya tidak dibenarkan pada Level 3 sebelum Admin Review selesai.

Security lock tidak mengubah:

"accounts.status"

---

9. OTP

OTP menggunakan:

"6 digit"

Tempoh sah:

"5 minit"

OTP Entry

Maksimum percubaan memasukkan OTP:

"3 kali"

Resend

Cooldown:

"5 minit"

Maksimum resend:

"3 kali"

Selepas maksimum resend dicapai:

Pengguna perlu menunggu sehingga hari berikutnya.

OTP Baharu

OTP baharu akan membatalkan OTP lama untuk tujuan/contact yang berkaitan.

OTP plaintext tidak boleh disimpan.

OTP hanya disimpan dalam bentuk hash.

OTP mesti terikat kepada contact yang tepat.

OTP untuk phone mesti berkait dengan phone contact yang tepat.

OTP untuk email mesti berkait dengan email contact yang tepat.

OTP Purpose

OTP boleh digunakan untuk:

- "REGISTER"
- "VERIFY_PHONE"
- "VERIFY_EMAIL"
- "CHANGE_PHONE"
- "CHANGE_EMAIL"
- "ACCOUNT_RECOVERY"

Password change dan password reset kekal menggunakan verified email dan tidak menggunakan phone OTP.

---

10. Account Recovery

ODP menggunakan:

"Model C"

untuk Account Recovery.

Account Recovery memerlukan Recovery Request.

Admin sahaja boleh:

- approve
- reject

Recovery Request.

Admin tidak melihat atau menetapkan password pengguna.

Sistem menentukan recovery path berdasarkan keadaan Account dan verification yang tersedia.

Recovery Mode

Selepas Recovery Request diluluskan:

Recovery Mode sah selama:

"30 minit"

Recovery Mode mempunyai tempoh terhad dan tidak menjadi session biasa.

Successful Recovery

Recovery yang berjaya akan:

- revoke semua active sessions
- membolehkan Account mendapatkan semula akses melalui recovery flow yang sah

Account Recovery tidak mengubah:

"Account Status"

Deleted Account

Account dengan status:

"DELETED"

tidak boleh menjalani Account Recovery.

Recovery Reason

Reason yang dibenarkan:

- "EMAIL_INACCESSIBLE"
- "PHONE_AND_EMAIL_INACCESSIBLE"
- "OTHER"

Recovery Status

Status:

- "PENDING"
- "APPROVED"
- "REJECTED"
- "CANCELLED"

---

11. Security Activity

Security Activity adalah rekod aktiviti keselamatan berkaitan Account.

Security Activity hanya boleh dilihat oleh:

- pemilik Account
- user yang berkaitan mengikut authorization yang sah

Format:

"JSONL"

Retention:

"3 bulan"

Security Activity mesti disimpan secara fizikal berasingan daripada Audit Log.

Security Activity bukan Audit Log.

Kedua-duanya tidak boleh dicampurkan.

---

12. Audit Log

Audit Log adalah untuk kegunaan dalaman.

Akses:

- Root Admin
- Kapten

Audit Log menggunakan:

"JSONL"

Audit Log mempunyai ciri:

- append-only
- retention minimum 7 tahun
- fail dipecahkan mengikut bulan
- hash chain
- fingerprint SHA-256 bulanan

Aktiviti berikut juga perlu diaudit apabila berkaitan:

- export
- logging
- tindakan pentadbiran
- perubahan keselamatan
- aktiviti dalaman yang memerlukan audit

Security Activity dan Audit Log tidak boleh dicampurkan.

---

13. Authentication dan Authorization

Authentication menentukan identiti Account.

Authorization menentukan apa yang Account tersebut dibenarkan lakukan.

Kedua-dua concern mesti kekal berasingan.

Account yang sama boleh mempunyai akses kepada aplikasi yang berbeza.

Aplikasi awal:

- User App
- Runner App
- Admin

Permission dan capability khusus aplikasi akan dibina secara berasingan daripada Identity Foundation.

Account Foundation tidak menentukan business permission yang belum diperlukan.

---

14. API

Authentication API berada di bawah:

"/api/v1/auth"

API menggunakan standard response ODP yang telah ditetapkan.

Standard response:

- "status"
- "message"
- "data"

User-facing message mesti menggunakan Bahasa Malaysia.

Technical identifiers seperti:

- "status"
- "message"
- "data"
- endpoint path
- class name
- method name
- table name
- field name

boleh kekal dalam English apabila diperlukan oleh technical implementation.

Authentication API tidak boleh mencampurkan business workflow yang belum termasuk dalam v0.5.0.

---

15. Bahasa Projek

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

English hanya dikekalkan apabila diperlukan sebagai:

- technical identifier
- nama framework
- standard teknikal
- code identifier
- endpoint
- nama class
- nama method
- nama table
- nama field

---

16. Di Luar Scope v0.5.0

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
- Vendor workflow
- Fare/price engine
- Payment workflow
- Wallet workflow

Semua perkara tersebut ialah:

"FUTURE"

Ia tidak boleh dimasukkan ke implementation v0.5.0 tanpa keputusan scope baharu.

---

17. Prinsip Architecture

Account ialah Identity Foundation.

Authentication mengesahkan identity.

Authorization menentukan capability.

Profile menyimpan maklumat profil.

Device dan Session mengurus konteks akses.

Security Activity merekod aktiviti keselamatan pengguna.

Audit Log merekod aktiviti dalaman yang memerlukan audit.

Setiap concern hendaklah kekal berasingan.

Architecture hendaklah mengelakkan business logic daripada masuk terlalu awal ke Identity Foundation.

Foundation mesti boleh digunakan oleh User App, Runner App dan Admin tanpa mewujudkan identity account yang berasingan.

---

18. Status Keputusan

Architecture dalam dokumen ini adalah:

LOCKED

Versi:

"v0.5.0 — Asas Akaun dan Pengesahan"

Sebarang perubahan kepada keputusan dalam dokumen ini memerlukan keputusan baharu daripada Kapten sebelum implementation diteruskan.

Blueprint ini menjadi rujukan utama untuk pembangunan Account Foundation v0.5.0.
