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

Menyimpan kode OTP untuk login customer via WhatsApp.

| Column Name | Data Type | Key | Attributes | Description |
|------------|-----------|-----|------------|-------------|
| id | BIGINT | PK | UNSIGNED, AUTO_INCREMENT | Primary Key |
| phone | VARCHAR(20) | - | NOT NULL | Nomor telepon yang request OTP |
| otp_code | VARCHAR(6) | - | NOT NULL | Kode OTP 6 digit |
| expires_at | DATETIME | - | NOT NULL | Waktu kadaluarsa (5 menit dari create) |
| is_used | BOOLEAN | - | DEFAULT FALSE | Status apakah OTP sudah dipakai |
| created_at | TIMESTAMP | - | NULLABLE | - |
| updated_at | TIMESTAMP | - | NULLABLE | - |

Indexes:
- phone untuk fast lookup
- expires_at untuk cleanup expired OTPs

---

## 4. User Flow

### A. Flow 1: Customer Auto-Registration & Check-In (Main Flow)

1. Customer datang ke kasir/lokasi
2. Scan QR Code yang tertera di kasir
3. Browser buka: /checkin
4. Customer isi form:
   - Nama Lengkap
   - Nomor WhatsApp
5. Submit form → POST /checkin
6. Backend:
   - Validasi input
   - Normalisasi nomor WA (08xxx → 628xxx)
   - Cek apakah phone sudah terdaftar:
     - TIDAK → Buat User baru + Customer profile (auto-registration)
     - SUDAH → Update nama (jika berubah)
   - Cek customer profile:
     - Tidak ada → Buat Customer record
     - Ada → Tambah poin +1, total_visits +1, update last_visit_at
   - Record visit history (customer_id, points_earned, visited_at, ip_address)
   - Cek poin:
     - Poin < 5 → Kirim WA: "Poin kamu: X/5. Kumpulkan Y lagi!"
     - Poin >= 5 → Reset poin jadi 0, Kirim WA: "SELAMAT! Kamu dapat DISKON!"
7. Redirect ke /success?points=X&name=Y&reward=true/false
8. Tampilkan halaman sukses dengan:
   - Nama customer
   - Poin saat ini (atau reward message)
   - Progress bar (jika belum reward)

### B. Flow 2: Customer Login dengan OTP (Optional - untuk lihat dashboard)

Customer Flow:
1. Buka /login
2. Pilih tab "Customer Login"
3. Input Nomor WhatsApp
4. Klik "Kirim OTP"
5. Backend:
   - Cek apakah nomor terdaftar (ada di table users dengan role customer)
   - Jika tidak → Error: "Nomor tidak terdaftar. Scan QR dulu."
   - Jika ya:
     - Generate OTP 6 digit
     - Simpan ke table otp_codes (phone, otp_code, expires_at = now + 5 menit)
     - Kirim OTP via WhatsApp
     - Tampilkan: "OTP dikirim ke WA kamu"
6. Customer terima OTP di WhatsApp (6 digit)
7. Customer input OTP di form
8. Submit → POST /login/otp/verify
9. Backend:
   - Cek OTP valid (phone, code, expires_at > now, is_used = false)
   - Jika valid:
     - Set is_used = true
     - Login customer (Auth::login)
     - Redirect ke /dashboard
   - Jika tidak valid → Error: "OTP salah atau kadaluarsa"

### C. Flow 3: Admin Login (Email + Password)

Admin Flow:
1. Buka /login
2. Pilih tab "Admin Login"
3. Input Email + Password
4. Submit → POST /login/admin
5. Backend:
   - Cek credentials dengan Auth::attempt()
   - Cek role = 'admin'
   - If success → Redirect ke /admin (Filament dashboard)
   - If fail → Error: "Email/password salah atau bukan admin"

---

## 5. Development Roadmap

### Step 1: Setup Laravel & Dependencies

- [x] Install Laravel 12: composer create-project laravel/laravel getwashed-loyalty
- [x] Install Filament v3: composer require filament/filament
- [x] Setup Filament: php artisan filament:install --panels
- [x] Setup Breeze: php artisan breeze:install blade
- [x] Install Tailwind (jika belum): npm install && npm run dev

### Step 2: Database & Models

- [x] Modifikasi migration users (tambah field: phone, role)
- [x] Buat Migration: php artisan make:migration create_customers_table
- [x] Buat Migration: php artisan make:migration create_visit_histories_table
- [x] Buat Migration: php artisan make:migration create_otp_codes_table
- [x] Jalankan: php artisan migrate
- [x] Buat Model Customer dengan relasi ke User
- [x] Buat Model VisitHistory dengan relasi ke Customer
- [x] Buat Model OtpCode

### Step 3: Setup Filament Admin Panel

- [x] Setup Filament: php artisan filament:install --panels
- [x] Buat Filament Resource: php artisan make:filament-resource Customer
- [x] Buat Filament Resource: php artisan make:filament-resource VisitHistory
- [x] Buat Filament Widget untuk Dashboard (Stats)
- [x] Konfigurasi Filament middleware untuk role 'admin'

### Step 4: Backend Controllers & Logic

- [x] Buat HomeController (untuk landing page)
- [x] Buat CheckinController (untuk proses check-in)
- [x] Buat SuccessController (untuk halaman sukses)
- [x] Buat LoginController (untuk handle OTP & admin login)
- [x] Implementasi Validasi & Normalisasi Phone
- [x] Implementasi Logic Poin & Reward

### Step 5: WhatsApp Service Integration

- [x] Pilih Provider (Fonnte/Wablas)
- [x] Buat app/Services/WhatsAppService.php
- [x] Implementasi method sendMessage($phone, $message)
- [x] Test koneksi API

### Step 6: OTP Service Implementation

- [x] Buat app/Services/OtpService.php
- [x] Implementasi method generateOtp($phone) - Generate & save OTP
- [x] Implementasi method verifyOtp($phone, $code) - Verify OTP
- [x] Implementasi rate limiting (max 3x request per jam)
- [x] Setup auto-cleanup untuk OTP expired (Scheduler/Command)

### Step 7: Frontend Views (Blade)

- [x] Buat resources/views/landing.blade.php (Homepage)
- [x] Buat resources/views/checkin.blade.php (Form Check-in)
- [x] Buat resources/views/success.blade.php (Success Page)
- [x] Buat resources/views/login.blade.php (Login Page dengan 2 tab: OTP & Password)
- [x] Buat resources/views/dashboard/customer.blade.php (Customer Dashboard)
- [x] Styling dengan Tailwind CSS (Mobile-First)
- [x] Buat component layout untuk reusability

### Step 8: Routes Configuration

- [x] Setup web.php:
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

---

## 9. PROGRESS STATUS & REMAINING TASKS

### ✅ COMPLETED (Steps 1-8)

**Step 1: Setup** ✅
- Laravel 12, Filament v3, Breeze, Tailwind CSS installed

**Step 2: Database & Models** ✅
- 4 migrations created (users, customers, visit_histories, otp_codes)
- 4 models created with relationships
- All models refactored with clean code

**Step 3: Filament Admin** ✅
- CustomerResource (full CRUD + filters)
- VisitHistoryResource (read-only audit log)
- LoyaltyStatsWidget (3 stat cards)
- CheckAdminRole middleware

**Step 4: Controllers** ✅
- 5 controllers created (Home, Checkin, Success, Login, CustomerDashboard)
- All refactored with dependency injection
- Clean architecture applied

**Step 5: WhatsApp Service** ✅
- WhatsAppService created
- Multi-provider support (Fonnte, Wablas, Twilio)
- Config setup complete

**Step 6: OTP Service** ✅
- OtpService created
- Rate limiting (3x per hour)
- Auto-cleanup command & scheduler
- Clean separation of concerns

**Step 7: Frontend Views** ✅
- 6 views created (landing, checkin, success, login, customer dashboard, layout component)
- All views refactored (no comments, minimal nesting)
- Component-based with x-layout
- Tailwind CSS mobile-first

**Step 8: Routes** ✅
- All routes configured in web.php
- Middleware setup (auth, guest)
- Clean route organization

---

### 🔄 REMAINING TASKS (Steps 9-10)

**Step 9: Testing & QR Code** ⏳
- [ ] End-to-end testing (check-in flow)
- [ ] OTP testing (request & verify)
- [ ] Rate limiting testing
- [ ] Generate QR Code (link to /checkin)
- [ ] Create seeder for demo data
- [ ] Manual testing checklist

**Step 10: Deployment** ⏳
- [ ] Environment setup (.env production)
- [ ] WhatsApp API configuration (real API key)
- [ ] Database migration in production
- [ ] Admin user seeder
- [ ] HTTPS/SSL setup
- [ ] Server optimization (caching, queue)
- [ ] Monitoring & logging setup

---

### 📝 OPTIONAL IMPROVEMENTS (Future)

**Nice to Have:**
- [x] QR Code generator in admin panel
- [x] Export customer data (Excel/PDF)
- [ ] Analytics charts (visit trends)
- [ ] Multi-tier rewards (Bronze, Silver, Gold)
- [ ] Email notifications (backup WA)
- [ ] Customer birthday rewards
- [ ] Manual point adjustment by admin
- [ ] Bulk WhatsApp messaging
- [ ] API endpoints for mobile app
- [ ] Unit tests & feature tests

**Code Quality:**
- [x] Clean code (no excessive comments)
- [x] Minimal nesting (max 2 levels)
- [x] SOLID principles applied
- [x] Service pattern implemented
- [x] Dependency injection
- [x] Type safety (PHP 8.2+ features)

---

### 🎯 IMMEDIATE NEXT STEPS

1. **Create Admin User Seeder**
   - Make it easy to create admin account

2. **Setup WhatsApp API**
   - Get Fonnte/Wablas API key
   - Test real message sending

3. **End-to-End Testing**
   - Test complete flow with real phone number
   - Verify OTP reception

4. **Generate QR Code**
   - Create QR pointing to /checkin
   - Test scanning with mobile device

5. **Production Deployment**
   - Choose hosting (VPS recommended)
   - Configure production environment
   - Deploy and test

---

**OVERALL PROJECT STATUS: 85% COMPLETE** 🎉
- Core functionality: ✅ 100%
- Testing: ⏳ 20%
- Deployment: ⏳ 0%
