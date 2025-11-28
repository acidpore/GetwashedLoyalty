# 🚗 Getwashed Loyalty System

> Modern digital loyalty program untuk bisnis cuci kendaraan berbasis WhatsApp.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.x-F59E0B?style=flat&logo=laravel)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Tentang Project

Getwashed Loyalty System adalah aplikasi web mobile yang menggantikan kartu stempel fisik dengan sistem poin digital berbasis nomor WhatsApp. Pelanggan scan QR Code, input nama & nomor WA, otomatis dapat poin. Kumpulkan 5 poin → Dapat diskon otomatis via WhatsApp.

---

## ✨ Fitur Utama

- 🎯 Auto-Registration: Pelanggan otomatis terdaftar saat pertama scan QR
- 📱 Passwordless Login: Customer login pakai OTP WhatsApp (6 digit)
- 💳 Points System: Sistem poin 5x kunjungan = 1 reward
- 🔔 WhatsApp Notifications: Real-time notifikasi poin & diskon
- 📊 Admin Dashboard: Monitoring via Filament v3
- 🔒 Role-Based Access: Admin & Customer terpisah

---

## 🛠️ Tech Stack

| Category | Technology |
|----------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Blade Templates + Tailwind CSS |
| Admin Panel | Filament v3 |
| Database | MySQL |
| Authentication | Laravel Breeze + Custom OTP |
| WhatsApp API | Fonnte / Wablas / Twilio |

---

## 📊 Database Schema

### Tables
- users: Admin & Customer authentication
- customers: Loyalty data (points, visits)
- visit_histories: Check-in logs
- otp_codes: OTP verification (5 min expire)

### Relationships
- User → hasOne Customer
- Customer → hasMany VisitHistory
- Phone Number = Unique identifier

---

## � Documentation

Detailed technical specs: [PROJECT_PLAN.md](PROJECT_PLAN.md)

---

## 📝 License

MIT License - see [LICENSE](LICENSE)

---

<p align="center">Made with ❤️ for better customer loyalty experience</p>
