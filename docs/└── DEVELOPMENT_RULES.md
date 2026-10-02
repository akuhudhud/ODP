# ODP Development Rules

## 1. Source of Truth

GitHub adalah source of truth utama projek.

## 2. Workflow

Semua code ditulis dan disimpan di GitHub terlebih dahulu.

Codespace hanya digunakan untuk:
- install dependency jika perlu
- test
- validate
- run application
- debug

Codespace bukan source of truth.

## 3. Development Scope

Sebelum coding:
- semak architecture yang telah dipersetujui
- semak keputusan terdahulu
- jangan reka semula perkara yang telah LOCKED

## 4. One Step at a Time

Satu perubahan utama pada satu masa.

Selepas perubahan:
1. semak
2. test
3. validate
4. commit

Jangan campurkan banyak modul yang belum berkaitan.

## 5. Locked Decisions

Perkara yang telah ditetapkan sebagai LOCKED tidak boleh diubah atau direka semula tanpa keputusan baharu daripada Kapten.

## 6. No Scope Creep

Jangan menambah:
- feature
- business logic
- architecture
- database table
- dependency
- workflow

yang belum diperlukan untuk scope semasa.

Jika sesuatu belum diperlukan, tandakan sebagai FUTURE.

## 7. Ask Before Expanding

Jika terdapat keputusan architecture yang belum jelas dan ia memberi kesan kepada implementation:
- berhenti
- nyatakan keputusan yang diperlukan
- tunggu arahan Kapten

Jangan membuat andaian besar.

## 8. Version Control

Setiap milestone mesti:
- dikemas kini dalam VERSION
- direkod dalam CHANGELOG.md
- direkod dalam DEVELOPMENT_LOG.md
- diuji sebelum release

## 9. Documentation Language

Dokumentasi projek menggunakan Bahasa Malaysia.

Technical terms dan code identifiers boleh menggunakan English apabila sesuai.

## 10. Project Discipline

Jangan:
- ulang keputusan yang sudah LOCKED tanpa sebab
- kembali kepada architecture lama yang telah dibatalkan
- mencampurkan projek/modul lain
- menukar scope tanpa arahan
- terus coding apabila architecture belum dipersetujui

## 11. Current Development Principle

Build from foundation → validate → expand.

Jangan membina feature sebelum foundation yang diperlukan stabil.
