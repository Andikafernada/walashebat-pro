# Panduan Setup WhatsApp n8n untuk WaliKelas Pro

## Prasyarat

1. **n8n sudah terinstall** (self-hosted atau cloud)
2. **WhatsApp Gateway** - Pilih salah satu:
   - **Fonnte** (Recommended - mudah setup)
   - **WaBlas**
   - **Custom Baileys/whatsapp-web.js**

---

## Langkah 1: Import Workflow ke n8n

### 1.1 Buka n8n
```
https://n8n.walas.my.id
```

### 1.2 Import Workflow Send Message
1. Klik **"+"** untuk membuat workflow baru
2. Klik **Settings** (gear icon)
3. Pilih **"Import from JSON"**
4. Paste isi dari file: `docs/n8n-workflow-whatsapp-send.json`
5. Klik **Save**

### 1.3 Import Workflow Session Manager
1. Buat workflow baru lagi
2. Import dari: `docs/n8n-workflow-session-manager.json`
3. Klik **Save**

---

## Langkah 2: Konfigurasi Environment Variables

Di n8n, buka **Settings** → **Variables**, tambahkan:

```env
# Secret untuk validasi request dari Laravel
WHATSAPP_WEBHOOK_SECRET=rahasia-yang-sangat-kuat-disini

# Fonnte API (pilih salah satu provider)
FONNTE_API_KEY=your-fonnte-api-key
FONNTE_API_URL=https://api.fonnte.com/api/send-message

# Atau WaBlas
WABLAS_API_KEY=your-wablas-api-key
WABLAS_API_URL=https://api.wablas.com/api/v2/send-message
```

---

## Langkah 3: Setup Fonnte (Recommended)

### 3.1 Daftar Fonnte
1. Buka https://fonnte.com
2. Daftar dan login
3. Dapatkan **API Key** dari dashboard

### 3.2 Update Workflow
Edit node **"Fonnte API"** di workflow Send Message:
- Hapus node WaBlas atau nonaktifkan

### 3.3 Format Pesan
Fonnte menerima format:
```json
{
  "target": "6281234567890",
  "message": "Isi pesan di sini",
  "countryCode": "62"
}
```

---

## Langkah 4: Setup WaBlas (Alternatif)

### 4.1 Daftar WaBlas
1. Buka https://wablas.com
2. Daftar dan login
3. Dapatkan **API Token** dari dashboard

### 4.2 Update Workflow
Edit node **"WaBlas API"** di workflow Send Message:
- Masukkan URL dan API Token WaBlas

### 4.3 Format Pesan
WaBlas menerima format:
```json
{
  "phone": "6281234567890",
  "message": "Isi pesan di sini",
  "type": "text"
}
```

---

## Langkah 5: Setup Session Manager (Pairing/QR Code)

Workflow Session Manager memerlukan **WhatsApp Library** untuk generate QR code dan manage sesi.

### Opsi A: Menggunakan Fonnte/WaBlas Device API

Jika provider Anda支持 device management:

```javascript
// Di node "Pair Action"
const fonnteDeviceUrl = 'https://api.fonnte.com/api/device';
const apiKey = $env.FONNTE_API_KEY;

// Request QR code dari Fonnte
const response = await fetch(fonnteDeviceUrl + '/pair', {
  method: 'POST',
  headers: {
    'Authorization': apiKey,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    session_id: sessionId,
    msisdn: msisdn
  })
});

const data = await response.json();

return {
  json: {
    status: data.status || 'pairing',
    session_id: sessionId,
    qr: data.qr,
    error: data.error
  }
};
```

### Opsi B: Baileys + Pairing Code

Untuk mendukung **QR Code** dan **Kode 8 Digit**:

1. Install Baileys di server n8n:
   ```bash
   npm install @whiskeysockets/baileys
   ```

2. Lihat panduan lengkap: `docs/BAILEYS-N8N-GUIDE.md`

3. Workflow Session Manager sudah mendukung metode:
   - `metode=qr` - QR Code
   - `metode=kode` - Kode 8 Digit

---

## Langkah 6: Update Konfigurasi Laravel

Edit file `.env`:

```env
# Ubah dari log ke n8n
WHATSAPP_DRIVER=n8n

# URL webhook n8n
N8N_WEBHOOK_URL=https://n8n.walas.my.id/webhook/whatsapp-send
N8N_SESSION_WEBHOOK_URL=https://n8n.walas.my.id/webhook/whatsapp-session

# Secret yang sama dengan di n8n
N8N_WEBHOOK_SECRET=rahasia-yang-sangat-kuat-disini
```

---

## Langkah 7: Aktifkan Workflow

### 7.1 Aktifkan Workflow Send Message
1. Buka workflow WhatsApp Send
2. Toggle **Active** switch → ON
3. Workflow akan menerima request di `/webhook/whatsapp-send`

### 7.2 Aktifkan Workflow Session Manager
1. Buka workflow Session Manager
2. Toggle **Active** switch → ON
3. Workflow akan menerima request di `/webhook/whatsapp-session`

---

## Langkah 8: Test

### 8.1 Test kirim pesan
```bash
curl -X POST https://n8n.walas.my.id/webhook/whatsapp-send \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: rahasia-yang-sangat-kuat-disini" \
  -d '{
    "from": "6281234567890",
    "to": "6281234567890",
    "message": "Test dari WaliKelas Pro"
  }'
```

### 8.2 Test pairing QR
```bash
curl -X POST https://n8n.walas.my.id/webhook/whatsapp-session \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: rahasia-yang-sangat-kuat-disini" \
  -d '{
    "action": "pair",
    "session_id": "guru-1",
    "msisdn": "6281234567890",
    "metode": "qr"
  }'
```

### 8.3 Test pairing kode 8 digit
```bash
curl -X POST https://n8n.walas.my.id/webhook/whatsapp-session \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: rahasia-yang-sangat-kuat-disini" \
  -d '{
    "action": "pair",
    "session_id": "guru-2",
    "msisdn": "6281234567890",
    "metode": "kode"
  }'
```

---

## Troubleshooting

### Pesan tidak terkirim
1. Cek **Logs** di n8n untuk error details
2. Pastikan API key valid
3. Pastikan nomor tujuan sudah benar format (6281..., bukan 081...)

### QR Code tidak muncul
1. Pastikan provider mendukung device pairing API
2. Cek apakah WhatsApp Web sudah logout dari HP
3. Pastikan session tidak duplicate

### Secret mismatch
1. Pastikan `N8N_WEBHOOK_SECRET` di `.env` sama persis dengan di n8n
2. Hapus spasi atau karakter tersembunyi

---

## Arsitektur Akhir

```
┌─────────────────────────────────────────────────────────────┐
│                    WaliKelas Pro                            │
│                                                             │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐ │
│  │   Absensi    │    │  Reset OTP   │    │  Dashboard   │ │
│  │   Service    │    │  Controller  │    │    Alert     │ │
│  └──────┬───────┘    └──────┬───────┘    └──────────────┘ │
│         │                   │                              │
│         └───────────┬───────┘                              │
│                     │                                      │
│         ┌───────────▼───────────┐                          │
│         │  NotificationChannel  │                          │
│         └───────────┬───────────┘                          │
│                     │                                      │
└─────────────────────│─────────────────────────────────────┘
                      │
                      │ POST webhook
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                       n8n                                   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │            WhatsApp Send Workflow                    │   │
│  │  Webhook → Validate → Format → Fonnte/WaBlas API    │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           Session Manager Workflow                     │   │
│  │  Webhook → Pair/Status/Disconnect → QR Generation    │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                             │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ WhatsApp API
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                  WhatsApp Gateway                           │
│                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │   Fonnte    │  │   WaBlas    │  │  Baileys (Self)     │ │
│  └─────────────┘  └─────────────┘  └─────────────────────┘ │
│                                                             │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ Send Message
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                     WhatsApp                                │
│                                                             │
│  ┌─────────────┐                    ┌─────────────────────┐ │
│  │ Wali Kelas  │ ──── Message ──── │   Seksi Absensi     │ │
│  │   (Sender)  │                    │     (Receiver)      │ │
│  └─────────────┘                    └─────────────────────┘ │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Checklist Production

- [ ] Workflow Send Message **Active**
- [ ] Workflow Session Manager **Active**
- [ ] `WHATSAPP_DRIVER=n8n` di `.env`
- [ ] `N8N_WEBHOOK_SECRET` sudah di-set
- [ ] `php artisan queue:work` berjalan
- [ ] Test kirim pesan berhasil
- [ ] QR code pairing berfungsi
