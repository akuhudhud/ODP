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
- `RELEASED`

Contact juga mempunyai status verification.

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

OTP baharu akan membatalkan OTP lama.

OTP plaintext tidak boleh disimpan.

OTP hanya disimpan dalam bentuk hash.

OTP mesti terikat kepada contact yang tepat.

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

Admin tidak mengesahkan OTP bagi pihak pengguna
