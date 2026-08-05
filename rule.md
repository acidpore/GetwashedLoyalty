# Development Rules — Travel System

Perjanjian kerja antara developer dan AI assistant dalam membangun project ini.

---

## 1. Clean Code

- Nama variabel, fungsi, dan komponen harus deskriptif dan jelas, tanpa singkatan ambigu.
- Satu fungsi hanya melakukan satu hal (single responsibility).
- Tidak ada dead code, console.log sisa debug, atau kode yang di-comment tanpa alasan.
- Hindari magic number — gunakan konstanta bernama.

## 2. Arsitektur Rapi

- Setiap fitur mengikuti struktur yang sudah ada: `modules/[nama]/[nama].routes.ts + controller.ts + service.ts`.
- Frontend: logika API di layer `api/`, business logic di dalam page atau custom hook, bukan langsung di komponen UI.
- Tidak ada logika bisnis di dalam komponen presentasi.
- Tipe harus selalu didefinisikan di `types/index.ts`, tidak inline di komponen.
- Database query hanya boleh ada di `*.service.ts`, tidak di controller atau routes.

## 3. Scalable Build

- Komponen yang dipakai lebih dari satu tempat wajib dipindah ke `components/shared/`.
- Tidak ada hardcode URL, konfigurasi, atau credential di dalam kode — semua lewat environment variable.
- Backend response selalu menggunakan format standar: `{ success, message, data }`.
- Setiap endpoint baru wajib dilindungi `authMiddleware` jika bersifat admin.
- Prisma schema adalah sumber kebenaran tunggal — frontend types harus sinkron dengan schema.

## 4. Tanpa Emoji

- Tidak ada emoji di dalam kode, komentar, template pesan bot, log, atau response API.
- Teks UI boleh ekspresif, tapi tetap profesional dan tanpa karakter non-ASCII dekoratif.

## 5. Komentar Efisien

- Komentar hanya ditulis jika kode tidak bisa menjelaskan dirinya sendiri.
- Gunakan komentar untuk menjelaskan MENGAPA, bukan APA yang dilakukan kode.
- Section divider (`// ===`) boleh dipakai secukupnya untuk memisahkan blok logis yang panjang.
- Tidak ada komentar TODO yang dibiarkan tanpa tindak lanjut.

## 6. Testing oleh Developer

- AI tidak perlu menjalankan testing, build, dev server, atau TypeScript compilation check.
- Semua pengujian dan validasi dilakukan secara manual oleh developer.
- AI cukup fokus pada penulisan kode yang benar dan sesuai aturan di atas.

## 7. Tanpa Akses CMD

- AI tidak boleh menjalankan command apapun di terminal (npm install, mkdir, copy, dll).
- AI cukup memberikan instruksi yang jelas agar developer menjalankannya sendiri.
- Jika ada command yang perlu dijalankan, tulis dalam format code block yang siap di-copy-paste.

## 8. Hemat Token

- AI harus seefisien mungkin dalam penggunaan token.
- Hindari membaca ulang file yang sudah pernah dibaca kecuali benar-benar diperlukan.
- Jawaban harus ringkas dan langsung ke inti, tanpa penjelasan berlebihan.
- Jangan mengulangi informasi yang sudah disampaikan sebelumnya.

## 9. Konteks Utama AI Agent

- AI harus mengutamakan prompt dari developer dan kondisi aktual project sebagai sumber kebenaran.
- Jangan terpaku pada folder `.claude/` (rules, memory, atau konfigurasi internal) sebagai panduan utama.
- Eksplorasi langsung ke file project (models, controllers, migrations, pages, dll) untuk memahami konteks sebelum membuat keputusan.
- Jika ada konflik antara isi `.claude/` dan kondisi aktual project, ikuti kondisi aktual project.

---
