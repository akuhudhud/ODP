# ODP Development Rules

## 1. Source of Truth

GitHub ialah Source of Truth utama projek.

Branch utama:
main

## 2. Workflow

Semua development disimpan di GitHub.

Aliran kerja:

GitHub
↓
Development / Edit
↓
Codespace
↓
Test / Validate
↓
GitHub

Codespace bukan Source of Truth.

## 3. Read Before Code

Sebelum membuat perubahan:

- Baca keadaan repo semasa.
- Semak architecture yang telah dipersetujui.
- Semak keputusan LOCKED.
- Semak scope version semasa.

Jangan terus coding berdasarkan andaian atau memory.

## 4. Locked Decisions

Keputusan yang telah ditetapkan sebagai LOCKED tidak boleh direka semula atau diubah tanpa keputusan baharu daripada Kapten.

Jangan ulang perbincangan yang sudah selesai kecuali terdapat konflik sebenar.

## 5. One Step at a Time

Buat satu perubahan utama pada satu masa.

Selepas perubahan:

1. Semak.
2. Test.
3. Validate.
4. Commit.

Jangan campurkan perubahan yang tidak berkaitan.

## 6. Scope Control

Jangan menambah:

- Feature yang belum diminta.
- Business logic yang belum diperlukan.
- Architecture yang belum dipersetujui.
- Database table yang belum diperlukan.
- Dependency yang tidak diperlukan.

Perkara yang belum diperlukan ditandakan sebagai FUTURE.

## 7. Ask Before Expanding

Jika keputusan architecture belum jelas dan memberi kesan kepada implementation:

STOP.

Nyatakan perkara yang belum jelas dan minta keputusan Kapten.

Jangan membuat andaian besar.

## 8. Version Control

Setiap milestone perlu mengemas kini:

- VERSION
- CHANGELOG.md
- DEVELOPMENT_LOG.md

Test mesti lulus sebelum milestone dianggap selesai.

## 9. Documentation Language

Dokumentasi projek menggunakan Bahasa Malaysia.

Technical terms dan code identifiers boleh menggunakan English apabila sesuai.

## 10. Do Not Regress

Jangan:

- Menghidupkan semula keputusan yang telah dibatalkan.
- Menggunakan architecture lama tanpa arahan.
- Mencampurkan projek atau modul lain.
- Mengubah scope secara senyap.
- Menghapuskan keputusan LOCKED.

## 11. Current Version Scope

Development mesti kekal dalam scope version semasa.

Jika perbincangan masuk ke feature version akan datang:

FUTURE → rekod → kembali kepada scope semasa.

## 12. Development Principle

Build from foundation → validate → expand.

Foundation mesti stabil sebelum business logic dibina.

## 13. User Direction

Kapten ialah decision maker projek.

Assistant bertindak sebagai technical development partner.

Assistant tidak membuat keputusan architecture besar secara unilateral.

## 14. No Unnecessary Repetition

Jika sesuatu keputusan sudah LOCKED:

- Jangan ulang dari awal.
- Jangan minta keputusan yang sama lagi.
- Terus gunakan keputusan tersebut.

Hanya buka semula jika Kapten sendiri mahu mengubahnya atau terdapat konflik teknikal yang nyata.
