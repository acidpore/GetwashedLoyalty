# Project Plan: Getwashed Loyalty System (Full Version)

## 1. Project Overview

Sistem loyalitas pelanggan berbasis Web Mobile dengan dashboard admin.

Tujuan: Menggantikan kartu stempel fisik dengan sistem digital berbasis nomor WhatsApp.

Trigger: Pelanggan scan QR Code di kasir/lokasi.

Output: 
- Data pelanggan tercatat
- Poin bertambah otomatis
- Notifikasi via WhatsApp
- Admin dapat monitoring via Dashboard

---

## 2. Tech Stack

- Backend Framework: Laravel 12 (PHP 8.2+)
- Admin Dashboard: Filament v3 (Panel Admin Modern)
- Frontend: Blade Templates + Tailwind CSS
- Database: MySQL / MariaDB
- WhatsApp Gateway: 3rd Party API (Fonnte / Wablas / Twilio)
- Authentication: Laravel Breeze (Simple & Clean)

---

## 3. Database Architecture

### A. Entity Relationship Diagram (ERD) Concept

Relasi antar tabel:
- Satu User bisa menjadi Admin atau Customer (Role-based)
- Satu Customer bisa memiliki banyak VisitHistory
- Customer linked ke User (auto-created saat pertama scan QR)
- Phone Number = Unique Identifier untuk Customer

### B. Table Structure

#### 1. Table: users (Laravel Default + Custom Fields)

Menyimpan data autentikasi untuk Admin dan Customer.

KONSEP PENTING:
- Admin: Login wajib pakai email + password
- Customer: Auto-register saat pertama scan QR, phone sebagai unique ID
- Customer TIDAK PERLU LOGIN untuk check-in (passwordless untuk customer)
- Customer bisa login OPTIONAL jika ingin lihat dashboard (set password via kasir/admin)

| Column Name | Data Type | Key | Attributes | Description |
|------------|-----------|-----|------------|-------------|
| id | BIGINT | PK | UNSIGNED, AUTO_INCREMENT | Primary Key |
| name | VARCHAR(255) | - | NOT NULL | Nama lengkap user |
| email | VARCHAR(255) | UK | NULLABLE, UNIQUE | Email (wajib untuk Admin, null untuk Customer) |
| phone | VARCHAR(20) | UK | NULLABLE, UNIQUE | Nomor WA (Format: 628xxx) - UNIQUE ID untuk Customer |
| password | VARCHAR(255) | - | NULLABLE | Password hash (wajib Admin, nullable Customer) |
| role | ENUM | - | DEFAULT 'customer' | Role: 'admin' atau 'customer' |
| email_verified_at | TIMESTAMP | - | NULLABLE | Verifikasi email (admin only) |
| remember_token | VARCHAR(100) | - | NULLABLE | Token remember me |
| created_at | TIMESTAMP | - | NULLABLE | Auto-created saat scan QR pertama kali |
| updated_at | TIMESTAMP | - | NULLABLE | - |

Indexes:
- email: Untuk login admin
- phone: UNIQUE INDEX - Primary identifier untuk customer

Notes:
- Customer auto-created dengan: name (dari form), phone (unique), role='customer', password=NULL
- Admin manually created via seeder/Filament dengan: email, password, role='admin'

#### 2. Table: customers

Menyimpan data loyalitas pelanggan (Extension dari users).

| Column Name | Data Type | Key | Attributes | Description |
|------------|-----------|-----|------------|-------------|
| id | BIGINT | PK | UNSIGNED, AUTO_INCREMENT | Primary Key |
| user_id | BIGINT | FK | UNSIGNED, UNIQUE, NOT NULL | Merujuk ke users.id |
| current_points | INT | - | DEFAULT 0 | Jumlah poin aktif yang bisa ditukar |
| total_visits | INT | - | DEFAULT 0 | Total kedatangan seumur hidup |
| last_visit_at | DATETIME | - | NULLABLE | Tanggal terakhir check-in |
| created_at | TIMESTAMP | - | NULLABLE | Auto-created bersamaan dengan user |
| updated_at | TIMESTAMP | - | NULLABLE | Updated setiap check-in |

Foreign Key Constraint:
- user_id REFERENCES users(id) ON DELETE CASCADE

#### 3. Table: visit_histories

Mencatat log setiap kali pelanggan melakukan scan/check-in.

| Column Name | Data Type | Key | Attributes | Description |
|------------|-----------|-----|------------|-------------|
| id | BIGINT | PK | UNSIGNED, AUTO_INCREMENT | Primary Key log kunjungan |
| customer_id | BIGINT | FK | UNSIGNED, NOT NULL | Merujuk ke customers.id |
| points_earned | INT | - | DEFAULT 1 | Poin yang didapat di kunjungan ini |
| visited_at | DATETIME | - | NOT NULL | Waktu tepat check-in dilakukan |
| ip_address | VARCHAR(45) | - | NULLABLE | IP address untuk anti-spam |
| created_at | TIMESTAMP | - | NULLABLE | - |
| updated_at | TIMESTAMP | - | NULLABLE | - |

Foreign Key Constraint:
- customer_id REFERENCES customers(id) ON DELETE CASCADE

#### 4. Table: otp_codes

Menyimpan kode OTP untuk login customer (passwordless authentication).

| Column Name | Data Type | Key | Attributes | Description |
|------------|-----------|-----|------------|-------------|
| id | BIGINT | PK | UNSIGNED, AUTO_INCREMENT | Primary Key |
| phone | VARCHAR(20) | - | NOT NULL | Nomor WA yang request OTP (Format: 628xxx) |
| otp_code | VARCHAR(6) | - | NOT NULL | 6 digit kode OTP |
| expires_at | DATETIME | - | NOT NULL | Waktu expire OTP (5 menit dari created) |
| is_used | BOOLEAN | - | DEFAULT 0 | Status apakah OTP sudah dipakai |
| created_at | TIMESTAMP | - | NULLABLE | - |
| updated_at | TIMESTAMP | - | NULLABLE | - |

Indexes:
- phone: Untuk pencarian cepat saat verify OTP
- expires_at: Untuk auto-cleanup OTP yang expired

Notes:
- OTP expire dalam 5 menit
- Satu phone hanya bisa punya 1 OTP aktif (yang lama invalid otomatis)
- Setelah OTP dipakai, is_used = 1 (tidak bisa dipakai lagi)

---

## 4. Complete System Flow

### Flow 1: Landing Page (Homepage)

URL: https://getwashed.com/

Tampilan:
- Hero Section dengan gambar cuci mobil/motor
- Penjelasan singkat tentang program loyalitas
- CTA Button: "Scan QR untuk Poin" (ke /checkin)
- CTA Button: "Login Admin/User" (ke /login)

### Flow 2: Customer Check-In (QR Scan) - AUTO-REGISTRATION

URL: https://getwashed.com/checkin

KONSEP: Phone Number = Unique ID. Customer TIDAK PERLU register manual atau login!

Frontend Flow:
1. Pelanggan scan QR Code di kasir
2. Browser terbuka ke halaman /checkin
3. Tampilan Form Mobile-First:
   - Input: Nama Lengkap
   - Input: No WhatsApp
   - Button: "Dapatkan Poin Sekarang!"
4. Submit form

Backend Processing (AUTO-REGISTRATION LOGIC):
1. Validasi input:
   - Name: required, min 3 characters
   - Phone: required, numeric

2. Normalisasi nomor HP:
   - Input: 0812-3456-7890 atau +62 812... atau 62812...
   - Output: 6281234567890 (clean numeric, prefix 62)

3. Cek apakah phone sudah ada di tabel users:
   
   SKENARIO A - PELANGGAN BARU (Phone BELUM ada):
   - Step 1: Buat record di table users:
     * name: dari form
     * phone: 628xxx (normalized)
     * email: NULL
     * password: NULL (customer tidak perlu password untuk check-in)
     * role: 'customer'
   - Step 2: Buat record di table customers:
     * user_id: dari step 1
     * current_points: 1
     * total_visits: 1
     * last_visit_at: NOW()
   - Step 3: Buat record di table visit_histories:
     * customer_id: dari step 2
     * points_earned: 1
     * visited_at: NOW()
     * ip_address: request IP
   
   SKENARIO B - PELANGGAN LAMA (Phone SUDAH ada):
   - Step 1: Ambil data user berdasarkan phone
   - Step 2: Ambil data customer dari user_id
   - Step 3: Update table customers:
     * current_points = current_points + 1
     * total_visits = total_visits + 1
     * last_visit_at = NOW()
   - Step 4: Insert ke table visit_histories:
     * customer_id: dari step 2
     * points_earned: 1
     * visited_at: NOW()
     * ip_address: request IP

4. Cek Reward Logic (Business Rules):
   - Jika current_points < 5:
     - Message: "Halo [Nama], poin kamu: [X]/5. Kumpulkan [5-X] poin lagi untuk DISKON!"
   - Jika current_points == 5:
     - Message: "SELAMAT [Nama]! Kamu dapat DISKON! Tunjukkan pesan ini ke kasir."
     - Action: Reset current_points = 0 (agar bisa kumpul lagi)
   
5. Kirim WhatsApp (via WhatsAppService):
   - Phone: 628xxx
   - Message: sesuai reward logic di atas

6. Redirect ke: /success?points=[current_points_sebelum_reset]&name=[nama]

### Flow 3: Success Page (After Check-In)

URL: https://getwashed.com/success?points=3

Tampilan:
- Checkmark animation (Success!)
- "Terima kasih [Nama]!"
- "Poin kamu sekarang: [X]/5"
- Progress bar visual (3 dari 5 sudah terisi)
- Pesan WhatsApp akan dikirim dalam beberapa detik
- Button: "Kembali ke Beranda"

### Flow 4: Login System (Dual Method)

URL: https://getwashed.com/login

Tampilan:
- Form Login dengan 2 Tab/Option:
  - Tab 1: "Customer Login (OTP)" 
  - Tab 2: "Admin Login (Password)"

---

#### A. Customer Login (OTP - Passwordless)

Step 1 - Request OTP:
1. Customer input No HP
2. Klik "Kirim Kode OTP"
3. Backend Process:
   - Validasi: Apakah HP terdaftar di table users?
     - Jika TIDAK: Error "Nomor tidak terdaftar. Silakan scan QR untuk check-in dulu."
     - Jika YA: Lanjut step berikut
   - Generate 6 digit random OTP (contoh: 123456)
   - Invalidate OTP lama (jika ada) untuk phone ini
   - Insert ke table otp_codes:
     * phone: 628xxx
     * otp_code: 123456
     * expires_at: NOW() + 5 minutes
     * is_used: 0
   - Kirim WhatsApp:
     * "Kode OTP Getwashed kamu: 123456. Berlaku 5 menit. Jangan bagikan ke siapapun!"
4. Form berubah: Muncul input "Masukkan Kode OTP"

Step 2 - Verify OTP:
1. Customer input 6 digit OTP
2. Klik "Verifikasi & Login"
3. Backend Process:
   - Query: SELECT * FROM otp_codes WHERE phone = '628xxx' AND otp_code = '123456' AND is_used = 0 AND expires_at > NOW()
   - Jika TIDAK DITEMUKAN: Error "Kode OTP salah atau sudah expired"
   - Jika DITEMUKAN:
     * Update: is_used = 1 (mark as used)
     * Login customer (set session)
     * Redirect ke /dashboard (Customer Dashboard)

Security:
- OTP expire 5 menit
- OTP cuma bisa dipakai 1x
- Max 3x request OTP per 1 jam per phone (anti spam)

---

#### B. Admin Login (Email + Password)

Standard Laravel Auth:
1. Admin input Email
2. Admin input Password
3. Klik "Login"
4. Backend Process:
   - Validasi credentials
   - Cek role == 'admin'
   - Jika valid: Redirect ke /admin (Filament Dashboard)
   - Jika invalid: Error "Email atau password salah"

---

### Flow 5: Admin Dashboard (Filament)

URL: https://getwashed.com/admin

Fitur:
1. Dashboard Overview:
   - Total Customer
   - Total Visits Hari Ini
   - Total Poin Diberikan
   - Total Diskon Ditukar
2. Manajemen Customer:
   - List semua customer (tabel)
   - Filter: Berdasarkan poin, tanggal bergabung
   - Action: View detail, Edit poin manual, Delete
3. Visit History:
   - Log semua kunjungan
   - Filter: Tanggal, Customer
   - Export to Excel
4. Settings:
   - Ubah threshold poin (default: 5)
   - Template pesan WhatsApp
   - WhatsApp API Configuration

### Flow 6: Customer Dashboard (Optional)

URL: https://getwashed.com/dashboard

Fitur:
- My Points: [X]/5
- History kunjungan saya
- Button: Check-In Sekarang (ke /checkin)

---

## 5. Development Roadmap (Updated)

### Step 1: Setup Laravel & Dependencies

- [ ] Install Laravel 12 (sudah ada)
- [ ] Install Filament: composer require filament/filament:"^3.0"
- [ ] Install Laravel Breeze (optional, atau pakai Filament auth): composer require laravel/breeze --dev
- [ ] Setup Breeze: php artisan breeze:install blade
- [ ] Install Tailwind (jika belum): npm install && npm run dev

### Step 2: Database & Models

- [ ] Modifikasi migration users (tambah field: phone, role)
- [ ] Buat Migration: php artisan make:migration create_customers_table
- [ ] Buat Migration: php artisan make:migration create_visit_histories_table
- [ ] Buat Migration: php artisan make:migration create_otp_codes_table
- [ ] Jalankan: php artisan migrate
- [ ] Buat Model Customer dengan relasi ke User
- [ ] Buat Model VisitHistory dengan relasi ke Customer
- [ ] Buat Model OtpCode

### Step 3: Setup Filament Admin Panel

- [ ] Setup Filament: php artisan filament:install --panels
- [ ] Buat Filament Resource: php artisan make:filament-resource Customer
- [ ] Buat Filament Resource: php artisan make:filament-resource VisitHistory
- [ ] Buat Filament Widget untuk Dashboard (Stats)
- [ ] Konfigurasi Filament middleware untuk role 'admin'

### Step 4: Backend Controllers & Logic

- [ ] Buat HomeController (untuk landing page)
- [ ] Buat CheckinController (untuk proses check-in)
- [ ] Buat SuccessController (untuk halaman sukses)
- [ ] Buat LoginController (untuk handle OTP & admin login)
- [ ] Implementasi Validasi & Normalisasi Phone
- [ ] Implementasi Logic Poin & Reward

### Step 5: WhatsApp Service Integration

- [ ] Pilih Provider (Fonnte/Wablas)
- [ ] Buat app/Services/WhatsAppService.php
- [ ] Implementasi method sendMessage($phone, $message)
- [ ] Test koneksi API

### Step 6: OTP Service Implementation

- [ ] Buat app/Services/OtpService.php
- [ ] Implementasi method generateOtp($phone) - Generate & save OTP
- [ ] Implementasi method verifyOtp($phone, $code) - Verify OTP
- [ ] Implementasi rate limiting (max 3x request per jam)
- [ ] Setup auto-cleanup untuk OTP expired (Scheduler/Command)

### Step 7: Frontend Views (Blade)

- [ ] Buat resources/views/landing.blade.php (Homepage)
- [ ] Buat resources/views/checkin.blade.php (Form Check-in)
- [ ] Buat resources/views/success.blade.php (Success Page)
- [ ] Buat resources/views/login.blade.php (Login Page dengan 2 tab: OTP & Password)
- [ ] Buat resources/views/dashboard/customer.blade.php (Customer Dashboard)
- [ ] Styling dengan Tailwind CSS (Mobile-First)

### Step 8: Routes Configuration

- [ ] Setup web.php:
  - GET / -> landing page
  - GET /checkin -> form check-in
  - POST /checkin -> process check-in
  - GET /success -> success page
  - GET /login -> login page (tab: OTP untuk customer, password untuk admin)
  - POST /login/otp/request -> request OTP code
  - POST /login/otp/verify -> verify OTP & login customer
  - POST /login/admin -> admin login with password
  - GET /admin -> Filament admin panel (middleware: auth, role:admin)
  - GET /dashboard -> customer dashboard (middleware: auth, role:customer)

### Step 9: Testing & QR Code Generation

- [ ] Test flow lengkap dari QR scan sampai WhatsApp
- [ ] Test OTP flow (request & verify)
- [ ] Test anti-spam rate limiting OTP
- [ ] Generate QR Code yang ngelink ke /checkin
- [ ] Print QR Code untuk kasir

### Step 10: Deployment

- [ ] Setup .env production
- [ ] Deploy ke hosting (VPS/Shared Hosting)
- [ ] Setup HTTPS (SSL Certificate)
- [ ] Test di production

---

## 6. Authentication Flow Summary

Satu halaman login untuk semua:

Login Flow:
1. User masuk ke /login
2. Input: Email/Phone + Password
3. Laravel cek credentials
4. Redirect berdasarkan role:
   - Admin -> /admin (Filament Dashboard)
   - Customer -> /dashboard (Customer Dashboard)

Catatan Khusus:
- Customer yang baru check-in TIDAK OTOMATIS bisa login
- Customer perlu set password dulu (via link "Lupa Password" atau kasir set manual)
- Atau: Customer tidak perlu login sama sekali, cukup scan QR setiap kali datang

---

## 7. Security & Best Practices

- Validasi dan sanitasi semua input user
- Rate limiting untuk mencegah spam check-in (max 1x per 1 jam per IP untuk phone yang sama)
- HTTPS wajib untuk production
- Backup database secara berkala
- Log semua transaksi poin untuk audit trail
- Middleware untuk protect admin routes (hanya role admin yang bisa akses)

---

## 8. Tech Stack Summary

Pages & Components:
1. Landing Page (/)
2. Check-In Form (/checkin)
3. Success Page (/success)
4. Login Page (/login)
5. Admin Dashboard (/admin) - Filament
6. Customer Dashboard (/dashboard) - Blade

Total: 6 halaman utama
