# Arah baru: "Kertas Hangat"

Ganti total dari arah lembayung/SaaS sebelumnya. Sekarang: dasar krem gading
(seperti kertas HVS), aksen terracotta/rust, judul pakai serif editorial
(Newsreader), badan teks & tabel tetap sans netral (Instrument Sans), kode
margin & data tetap mono (IBM Plex Mono).

## Kenapa cuma 7 berkas tapi dampaknya ke seluruh app

`tailwind.config.js` di project ini memetakan ulang NAMA warna Tailwind
(`slate`, `indigo`, `sky`, `emerald`, dst) ke satu set token kustom — jadi
`bg-indigo-600` di 90 berkas Blade manapun otomatis merender warna yang sama.
Mengganti definisi warnanya di SATU tempat ini mengganti tampilan SELURUH
aplikasi, tanpa menyentuh satu pun berkas Blade lain di luar yang memang
sudah dirapikan sebelumnya (header, dashboard, 3 partial kecil).

Timpa 7 berkas ini ke project aslimu:

- `tailwind.config.js` — palet warna, radius sudut, bayangan, font judul
- `resources/css/app.css` — 2 baris kecil: judul (h1–h4, `.blok__judul`) pakai
  `font-display` (serif) sekarang, bukan Plus Jakarta Sans yang sudah tidak
  ada
- `resources/views/layouts/app.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/partials/class-nav.blade.php`
- `resources/views/partials/masa-otomasi.blade.php`
- `resources/views/partials/flash.blade.php`

## Yang berubah dari arah sebelumnya

- **Warna**: lembayung → terracotta/rust (`#ad4f22`). Netral abu sejuk →
  krem-cokelat hangat. Hijau/merah/kuning dinaikkan kehangatannya (lumut,
  bata, mustar) supaya sekeluarga dengan dasarnya.
- **Sudut**: sedikit ditajamkan (radius diturunkan ±25%) — kartu kertas tidak
  sebulat tombol aplikasi ponsel.
- **Bayangan**: nadanya cokelat-gelap sekarang, bukan biru-gelap — supaya
  tidak terasa dingin di atas dasar hangat.
- **Judul**: Newsreader (serif) untuk h1–h4 dan judul blok/section — sudah
  dilayani sendiri sejak awal untuk landing page, jadi tidak ada unduhan font
  baru. Badan teks, tombol, form, tabel tetap Instrument Sans supaya tetap
  gampang dibaca dan padat.
- Warna grafik Chart.js dan `theme-color` PWA disamakan ke terracotta.

## Yang BELUM ikut

`classes/show.blade.php`, halaman auth (login/register/dll), dan
`landing.blade.php` masih ada sisa referensi "Plus Jakarta Sans" — karena
Google Fonts sudah tidak dimuat lagi, elemen itu sekarang jatuh ke font
sistem alih-alih rusak, tapi belum ikut memakai Newsreader. Kalau mau,
bilang saja halaman mana yang mau disusul.

## Cara pasang

```
npm run build
php artisan view:clear
```
Lalu hard refresh browser.
