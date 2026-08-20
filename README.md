# WaliKelas Pro

SaaS multi-tenant untuk otomatisasi administrasi wali kelas — dibangun dengan **Laravel 11**, **Blade + Tailwind + Alpine.js**, database **MySQL/PostgreSQL**, dan integrasi **WhatsApp via n8n**.

> Scaffold ini berisi seluruh kode aplikasi (kode yang Anda tulis). Dependensi (`vendor/`, `node_modules/`) belum diikutkan — jalankan `composer install` & `npm install` setelah ekstrak.

## ✨ Fitur

| Modul | Keterangan |
|---|---|
| **Multi-tenancy** | Setiap wali kelas hanya melihat datanya sendiri. Diterapkan otomatis lewat `TenantScope` + trait `BelongsToTenant` (kunci: `user_id`). |
| **Absensi anti-curang** | Sistem membuat *Magic Link* (`/a/{token}`) + **PIN harian** ber-hash yang **kedaluwarsa** otomatis. Dikirim **dari nomor WhatsApp wali kelas ke petugas Seksi Absensi**. |
| **Manajemen kelas** | CRUD Siswa, Jadwal, Struktur Organisasi, Poin/Pelanggaran, Buku Kas, dan Denah Tempat Duduk interaktif. |
| **Absensi otomatis** | Sesi absensi dibuat sendiri dari jadwal (`auto_attendance`) lalu magic link dikirim ke petugas — dijalankan scheduler, menahan diri pada hari libur. |
| **API integrasi** | Endpoint Sanctum untuk CBT / ExamBrowser memverifikasi kehadiran siswa sebelum ujian. |
| **Arsip & pemulihan** | Menghapus kelas/siswa bersifat soft delete; kelas yang dipulihkan membawa kembali siswanya. |
| **Keamanan akun** | Rate limit login, reset kata sandi via OTP WhatsApp, batas percobaan PIN per sesi. |
| **Notifikasi WhatsApp** | Lewat webhook n8n. Arsitektur `NotificationChannel` mudah ditukar (n8n → Baileys, dll). |

## 🚀 Instalasi

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate
# edit .env: koneksi DB (mysql/pgsql) & WHATSAPP_DRIVER

# 3. Migrasi + data demo
php artisan migrate --seed

# 4. Aset frontend + server
npm run dev        # terminal 1
php artisan serve  # terminal 2
```

Login demo: **walas@walas.my.id** / **password**

## 🔐 Cara kerja multi-tenancy

- Trait `App\Models\Concerns\BelongsToTenant` memasang `TenantScope` (filter `WHERE user_id = Auth::id()`) ke **semua** query dan mengisi `user_id` otomatis saat membuat record.
- Kolom `user_id` didenormalisasi ke seluruh tabel domain agar isolasi seragam & cepat.
- Konteks tanpa login (magic link publik, seeder, job) memakai `Model::withoutTenant()` secara eksplisit.
- **Model akses: satu peran.** Wali kelas (pemilik kelas) melihat dan mengubah seluruh data kelasnya. Peran guru mapel / co-wali belum ada — tabel `class_user` sudah disiapkan tetapi belum dipakai.

## 🔗 Alur Absensi Anti-Curang

1. Wali kelas membuat sesi → sistem menghasilkan `token` acak (48 char) + `PIN` harian (di-hash) + `expires_at`.
2. Magic link + PIN dikirim **dari nomor WhatsApp wali kelas** ke siswa yang menjabat **Seksi Absensi** (lewat n8n). Bila jabatan itu kosong atau nomornya belum diisi, sistem memakai Ketua Kelas.
3. Petugas buka link → masukkan PIN → isi roster → submit.
4. Setelah submit, status sesi menjadi `submitted` (tak bisa diisi ulang). PIN plaintext hanya tampil **sekali** ke wali kelas.
5. Rute publik dibatasi `throttle:30,1` untuk mencegah brute-force PIN.

## 🧩 API (CBT / ExamBrowser)

Autentikasi Bearer token (Laravel Sanctum).

```bash
# Ambil token
curl -X POST http://localhost:8000/api/v1/tokens \
  -d "email=walas@walas.my.id&password=password&device_name=exambrowser"

# Verifikasi kehadiran siswa sebelum ujian (gate ExamBrowser)
curl -X POST http://localhost:8000/api/v1/exam/verify \
  -H "Authorization: Bearer <TOKEN>" -d "nis=2025001"
# => { "found": true, "status_today": "hadir", "exam_eligible": true }
```

Endpoint lain: `GET /api/v1/classes`, `GET /api/v1/classes/{class}/students`, `GET /api/v1/classes/{class}/attendance/today`.

## ⏰ Absensi otomatis dari jadwal

Aktifkan **Absensi otomatis** pada halaman edit kelas, lalu jalankan scheduler:

```cron
* * * * * cd /path/ke/app && php artisan schedule:run >> /dev/null 2>&1
```

Uji lebih dulu tanpa mengirim apa pun:

```bash
php artisan walikelas:generate-attendance --dry-run
```

Aturannya:

- **Satu sesi — dan satu pesan WhatsApp — per kelas per hari**, berapa pun jumlah mata
  pelajaran hari itu. Dijamin `unique(class_id, session_date)` di level database, jadi
  scheduler yang berjalan tiap menit tidak mungkin membuat duplikat.
- **Jamnya mengikuti jadwal, bukan jam tetap.** Sistem mengambil pelajaran paling awal
  pada hari itu dan mengirim tautan beberapa menit sebelumnya. Sekolah bersistem blok —
  yang jam pertamanya berbeda tiap hari — terlayani tanpa konfigurasi tambahan.
- **Hari tanpa jadwal dilewati** dengan sendirinya (akhir pekan, atau hari kosong pada
  sistem blok).
- **Tanggal di kalender libur ditahan**, sehingga tidak ada blast WhatsApp saat libur.
- Sesi berlaku sampai pelajaran terakhir hari itu selesai.

Bila pengiriman gagal, sesi ditandai `failed` dan muncul sebagai peringatan di dashboard
lengkap dengan tombol **kirim ulang** (PIN diterbitkan baru, PIN lama otomatis hangus).
Kegagalan tidak pernah dibiarkan senyap.

> Pengiriman WhatsApp berjalan lewat antrian. Di produksi **wajib** ada
> `php artisan queue:work` yang berjalan, atau tidak ada pesan yang terkirim.
> Lihat [`docs/deployment.md`](docs/deployment.md).

## 🤖 Integrasi WhatsApp via n8n

Lihat [`docs/n8n-whatsapp-workflow.md`](docs/n8n-whatsapp-workflow.md). Set `WHATSAPP_DRIVER=log` untuk mematikan pengiriman nyata saat dev.

## 🗂️ Struktur penting

```
app/
├─ Models/Concerns/BelongsToTenant.php   # trait tenancy
├─ Models/Scopes/TenantScope.php         # global scope user_id
├─ Services/AttendanceSessionService.php # token + PIN + magic link
├─ Support/Contracts/NotificationChannel.php
├─ Support/Notifications/{N8nWhatsAppChannel,LogChannel}.php
├─ Http/Controllers/                     # web + Api + Public (magic link)
routes/{web.php,api.php}
database/migrations/                     # skema lengkap
```

## ✅ Testing

```bash
php artisan test
```

Termasuk uji isolasi tenant dan alur magic link (`tests/Feature`).

## 🚢 Deploy ke produksi

Checklist lengkap (queue worker, cron, nginx, backup, batas rate) ada di
[`docs/deployment.md`](docs/deployment.md).

## 📝 Lisensi
MIT.
