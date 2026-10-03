# ODP

ODP ialah kod nama pembangunan dalaman projek ini.

## Status

Peringkat foundation — v0.5.0.

## Skop v0.5.0

Versi ini menetapkan foundation Account & Authentication.

Komponen yang telah disediakan:

- Account sebagai identity utama ODP.
- Account ID menggunakan UUID v7.
- Contact phone dan email.
- Profile Account.
- Password dan password history.
- Device dan app session.
- Account security.
- OTP challenge.
- Account recovery.
- Account lifecycle.
- Security Activity.
- Audit Log.

Business logic masih belum dilaksanakan:

- Tiada Runner eligibility.
- Tiada vehicle system.
- Tiada order engine.
- Tiada fare/price engine.
- Tiada payment.
- Tiada wallet.
- Tiada vendor system.
- Tiada delivery workflow.
- Tiada personal shopper workflow.

## Struktur

- `backend/` — aplikasi backend
- `user-app/` — aplikasi mudah alih pengguna
- `runner-app/` — aplikasi mudah alih runner
- `admin/` — sistem pentadbiran
- `database/` — aset berkaitan database
- `docs/` — architecture, API dan keputusan projek
- `tests/` — aset pengujian

## Prinsip Pembangunan

1. Bina foundation terlebih dahulu.
2. Kekalkan pemisahan antara module.
3. Elakkan business logic terlalu awal.
4. Gunakan version control dari awal.
5. GitHub ialah Source of Truth.
6. Kekalkan nama pembangunan di bawah kod nama ODP.

## Versioning

Gaya Semantic Versioning digunakan.

Versi semasa:

`0.5.0`
