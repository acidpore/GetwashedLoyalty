# Milestone Loyalty — Status & Keputusan

Catatan kerja fitur milestone loyalty (mengganti threshold tunggal menjadi beberapa tier hadiah).

Terakhir diperbarui: 5 Agustus 2026. Status: **kode selesai, siap migrate dan set config.**

---

## 1. Aturan yang Dipakai

Sumber: client (helmi aziz), WhatsApp 27 Juli 2026 pukul 13.37.

- Poin mengunci di setiap milestone. Customer di poin 10 harus klaim dulu sebelum bisa lanjut ke poin 11.
- Tidak bisa skip tier untuk mengejar hadiah yang lebih besar.
- Klaim tier tengah **tidak** mereset poin, hanya membuka kunci agar poin bisa lanjut bertambah.
- Reset ke 0 hanya terjadi saat tier terakhir diklaim.

Aturan ini menggantikan rancangan awal (poin menabung sampai max, tier bawah hangus).

## 2. Keputusan: Jalan Terus (5 Agustus 2026)

Client sudah acc menaikkan tier pertama meskipun ada customer yang kehilangan hak klaim. Tidak ada migrasi data, tidak ada klaim massal sebelum config diubah.

Dampak yang diterima secara sadar:

| Poin sekarang | Sebelum | Sesudah diganti ke 10/20/30 |
| --- | --- | --- |
| 7 | belum bisa klaim | belum bisa klaim, target geser 5 ke 10 |
| 5 atau 6 | bisa klaim gratis cuci | tidak bisa klaim lagi, kurang 4 poin |
| 25 | bisa klaim | bisa klaim, bahkan dapat tier 10 lalu 20 |

Kasir perlu diberi tahu supaya siap menjelaskan ke customer di poin 5-6.

## 3. Koreksi Asumsi

Sempat dipahami bahwa perombakan ini akan mereset poin customer dan semua mulai dari awal. Itu tidak benar.

- Migration hanya menambah kolom baru. Kolom poin tidak disentuh.
- `SystemSetting::milestones()` punya fallback ke `{type}_reward_threshold` yang lama, sehingga selama halaman Settings belum diisi, perilakunya identik dengan sistem sekarang.
- Customer yang sekarang di poin 25 tidak kehilangan apa pun. Ia bisa klaim tier 10, lalu tier 20, poin tetap 25, lalu lanjut ke 30.

## 4. Yang Belum Dikerjakan

- Tabel "affected customers" di halaman Settings baru menampilkan customer dengan poin di atas max baru. Belum menampilkan customer yang kehilangan hak klaim karena tier pertama dinaikkan. Sengaja tidak dikerjakan, keputusan section 2 membuat peringatan itu tidak mengubah tindakan apa pun.

## 5. File Terkait

- `app/Models/SystemSetting.php` — definisi milestone, normalisasi, `maxPoints()`
- `app/Models/Customer.php` — `pointCap()`, `earnedMilestone()`, `claimReward()`, `addPoints()`
- `app/Models/RewardClaim.php` — log klaim hadiah
- `app/Filament/Actions/ClaimRewardAction.php` — tombol klaim untuk staff
- `app/Filament/Pages/Settings.php` — repeater milestone dan tabel customer terdampak
- `app/Services/WhatsAppService.php` — pesan progres dan pesan hadiah
- `database/migrations/2026_07_27_000001_create_reward_claims_table.php`
- `database/migrations/2026_07_27_000002_add_claimed_points_to_customers_table.php`

## 6. Perintah yang Perlu Dijalankan Developer

```
php artisan migrate
```
