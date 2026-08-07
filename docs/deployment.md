# Deployment & Kesiapan Produksi

Panduan menaikkan WaliKelas Pro dari localhost ke layanan yang dipakai banyak sekolah.

---

## 1. Yang WAJIB sebelum publish

### 1.1 Environment

```env
APP_ENV=production
APP_DEBUG=false          # kritis: true membocorkan isi .env di halaman error
APP_URL=https://walas.my.id
APP_TIMEZONE=Asia/Jakarta

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true   # cookie hanya lewat HTTPS
SESSION_ENCRYPT=true

QUEUE_CONNECTION=database    # JANGAN 'sync' — pengiriman WhatsApp akan menghambat request
CACHE_STORE=database

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning            # 'debug' di produksi membuat log membengkak cepat

WHATSAPP_DRIVER=n8n
N8N_WEBHOOK_SECRET=<acak-panjang>
```

Hasilkan kunci aplikasi sekali saja lalu simpan aman — mengganti `APP_KEY` membuat
seluruh data terenkripsi dan sesi login tidak bisa dibaca lagi:

```bash
php artisan key:generate
```

### 1.2 Rilis

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Setiap kali `.env` atau file `config/` berubah, jalankan ulang `php artisan config:cache`.

### 1.3 Queue worker — WAJIB

Pengiriman WhatsApp berjalan lewat antrian. **Tanpa worker, tidak ada satu pun
pesan yang terkirim** meskipun aplikasi tampak normal.

`/etc/supervisor/conf.d/walikelas-worker.conf`:

```ini
[program:walikelas-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/walikelas-pro/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/walikelas-pro/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start walikelas-worker:*
```

Setelah setiap deploy: `php artisan queue:restart` (worker memuat kode ke memori).

### 1.4 Scheduler — WAJIB untuk absensi otomatis

Satu entri cron menggerakkan pembuatan sesi absensi dari jadwal:

```cron
* * * * * cd /var/www/walikelas-pro && php artisan schedule:run >> /dev/null 2>&1
```

Uji tanpa mengirim apa pun:

```bash
php artisan walikelas:generate-attendance --dry-run
```

### 1.5 Hari libur

Isi lewat menu **Kalender Libur**, atau langsung ke tabel `holidays` untuk libur
nasional (baris dengan `user_id` NULL berlaku bagi semua sekolah). Tanpa ini, libur
panjang memicu pengiriman WhatsApp ke seluruh kelas sekaligus — mengganggu pengguna
dan berisiko membuat nomor gateway diblokir.

Isi sebelum tahun ajaran berjalan, bukan setelah libur pertama terlewat.

---

## 2. Web server

Document root menunjuk ke `public/`, **bukan** root proyek. Salah di sini membuat
`.env` bisa diunduh publik.

```nginx
server {
    listen 443 ssl http2;
    server_name walas.my.id;
    root /var/www/walikelas-pro/public;

    index index.php;
    charset utf-8;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }

    client_max_body_size 8m;
}
```

Aplikasi sudah memakai `trustProxies(at: '*')`. Bila berada di belakang
Cloudflare atau load balancer, persempit ke rentang IP proxy yang sebenarnya
pada `bootstrap/app.php` agar `$request->ip()` tidak bisa dipalsukan.

### Izin folder

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 3. Basis data

- Gunakan user MySQL/PostgreSQL khusus aplikasi, bukan `root`.
- Batasi akses hanya dari host aplikasi.
- Backup harian + **uji pemulihannya**. Backup yang tidak pernah diuji bukan backup.

```bash
# contoh backup harian
mysqldump --single-transaction walikelas_pro | gzip > /backup/walikelas-$(date +%F).sql.gz
```

Data siswa bersifat pribadi. Simpan backup terenkripsi, dan batasi siapa yang
punya akses SSH maupun kredensial basis data.

---

## 4. Skala

Indeks penting sudah ada di migrasi (`user_id`, `class_id`, `session_date`,
`token`). Yang perlu diperhatikan saat pengguna bertambah:

| Gejala | Penyebab umum | Tindakan |
|---|---|---|
| Dashboard melambat | agregasi absensi lintas tahun | tambah filter rentang tanggal atau tabel rekap |
| Antrian menumpuk | gateway WhatsApp lambat | tambah `numprocs`, atau pindah antrian ke Redis |
| Login lambat saat jam sibuk | `BCRYPT_ROUNDS` terlalu tinggi | 12 sudah memadai; jangan naikkan tanpa alasan |
| Tabel `jobs`/`sessions` membengkak | tidak ada pembersihan | jadwalkan `queue:prune-failed` dan bersihkan sesi lama |

---

## 5. Batas yang sudah terpasang

| Titik | Batas |
|---|---|
| Login | 5 percobaan gagal per email+IP, kunci 60 detik |
| PIN magic link | 10 percobaan per sesi, kunci 10 menit; plus 30 request/menit per IP |
| Permintaan OTP | 5 per IP tiap 5 menit |
| Verifikasi OTP | 5 percobaan per email+IP |
| Terbit token API | 10 per menit |

---

## 6. Sebelum menerima pengguna nyata

- [ ] `APP_DEBUG=false` dan halaman error 500/404 tidak membocorkan detail
- [ ] HTTPS aktif dan HTTP dialihkan ke HTTPS
- [ ] Queue worker berjalan dan bertahan setelah reboot
- [ ] Cron scheduler terpasang; `--dry-run` sudah diuji
- [ ] Backup harian berjalan **dan** sekali dipulihkan ke server uji
- [ ] Tabel `holidays` terisi untuk tahun ajaran berjalan
- [ ] Uji alur lengkap sebagai dua wali kelas berbeda: pastikan tidak ada data yang saling terlihat
- [ ] Uji lupa kata sandi sampai OTP benar-benar diterima di WhatsApp
- [ ] Pantau `storage/logs/laravel.log` dan `worker.log` di hari-hari pertama
