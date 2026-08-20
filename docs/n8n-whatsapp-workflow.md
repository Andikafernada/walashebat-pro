# Integrasi WhatsApp via n8n

WaliKelas Pro mengirim pesan dengan mem-POST payload JSON ke sebuah webhook n8n.
Workflow n8n bertugas meneruskan pesan ke session pengirim WhatsApp (Baileys /
whatsapp-web.js / provider lain).

## 1. Konfigurasi Laravel (`.env`)

```env
WHATSAPP_DRIVER=n8n
N8N_WEBHOOK_URL=https://n8n.walas.my.id/webhook/whatsapp-send
N8N_WEBHOOK_SECRET=rahasia-yang-kuat
```

`WHATSAPP_DRIVER=log` → pesan hanya ditulis ke `storage/logs` (mode dev).

## 2. Payload yang dikirim aplikasi

```json
{
  "from": "6281234567890",
  "to": "6285700000001",
  "message": "*WaliKelas Pro - Absensi Kelas XII RPL 1*\n...PIN Harian: *123456*...",
  "meta": {
    "type": "attendance_magic_link",
    "session_id": 12,
    "class_id": 3
  }
}
```

`from` adalah nomor WhatsApp **wali kelas**, `to` adalah nomor siswa yang menjabat
**Seksi Absensi**. Pesan sengaja dikirim dari nomor gurunya sendiri supaya siswa
menerima dari nomor yang dikenal, bukan dari nomor sistem yang asing.

Field `from` hanya muncul bila nomornya tersedia. Bila workflow Anda memakai satu
sesi pengirim terpusat, abaikan saja field ini.

Header: `X-Webhook-Secret: <N8N_WEBHOOK_SECRET>` — gunakan untuk memvalidasi asal request di n8n.

## 3. Rancangan workflow n8n

1. **Webhook** node (POST, path `whatsapp-send`).
2. **IF / Function** node: bandingkan header `X-Webhook-Secret` dengan secret. Tolak bila tidak cocok.
3. **Switch/Function** node: pilih sesi pengirim berdasarkan `from`.
4. **HTTP Request / node WhatsApp** ke gateway pengirim, kirim `to` + `message`.
5. **Respond to Webhook** node: balas `200` bila sukses (aplikasi menganggap gagal jika status non-2xx).

## 4. Menukar gateway

Implementasi pengiriman ada di balik interface `App\Support\Contracts\NotificationChannel`.
Untuk pindah dari n8n ke library langsung (mis. Baileys), cukup buat implementasi baru
dan ubah binding di `App\Providers\AppServiceProvider` — pemanggil (service absensi) tidak berubah.

## 5. Workflow kedua: mengelola sesi per guru

Karena setiap wali kelas memakai nomornya sendiri, gateway perlu satu sesi per guru.
Aplikasi memanggil `N8N_SESSION_WEBHOOK_URL` dengan field `action`:

```json
{ "action": "pair",       "session_id": "guru-12", "msisdn": "6281234567890" }
{ "action": "status",     "session_id": "guru-12", "msisdn": "6281234567890" }
{ "action": "disconnect", "session_id": "guru-12", "msisdn": "6281234567890" }
```

Balasan yang diharapkan:

```json
{ "status": "pairing", "qr": "<isi string QR>", "error": null }
{ "status": "connected", "qr": null, "error": null }
{ "ok": true }
```

`session_id` sengaja memakai id guru (`guru-<id>`), bukan nomor telepon, supaya guru
bisa berganti nomor tanpa kehilangan identitas sesinya.

### Dorongan status balik ke aplikasi

Sesi bisa putus kapan saja tanpa aplikasi tahu. Saat status berubah, gateway
mem-POST ke aplikasi:

```
POST https://walas.my.id/api/webhooks/whatsapp-session
X-Webhook-Secret: <N8N_WEBHOOK_SECRET>

{ "session_id": "guru-12", "status": "disconnected", "error": "Perangkat tertaut dihapus." }
```

Aplikasi memperbarui status guru tersebut dan menampilkan peringatan di dashboard,
sehingga guru tahu sebelum absensi gagal — bukan setelahnya.

## 6. Konsekuensi mengirim dari nomor guru

Karena `from` berbeda-beda per wali kelas, gateway perlu **satu sesi WhatsApp per
guru** — masing-masing memindai QR sekali untuk menautkan nomornya. Ini yang perlu
diperhitungkan sebelum rilis massal:

- Penyimpanan dan pemulihan sesi saat gateway di-restart.
- Sesi bisa putus sendiri (ganti HP, logout dari perangkat, WhatsApp Web dibersihkan);
  perlu cara guru menautkan ulang tanpa bantuan admin.
- Risiko pemblokiran menempel pada nomor pribadi guru, bukan nomor perusahaan.

Perlu diingat juga: pustaka WhatsApp tidak resmi (Baileys, whatsapp-web.js) berada di
luar ketentuan layanan WhatsApp, dan nomor yang dianggap menyalahgunakan bisa diblokir.
Dengan arsitektur satu nomor per guru, risiko itu jatuh pada nomor pribadi guru.
Sampaikan hal ini terbuka saat mereka mendaftar, dan jaga volume pesan tetap wajar —
desain "satu pesan per kelas per hari" sudah membantu banyak di sini.
