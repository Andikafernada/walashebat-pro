# Integrasi WhatsApp Baileys untuk n8n

Panduan implementasi lengkap untuk gateway WhatsApp dengan dukungan **QR Code** dan **Pairing Code** (8 digit).

---

## Arsitektur

```
Laravel App                  n8n Workflow                 WhatsApp
    │                           │                          │
    ├── POST /pair (QR) ─────► │ ──► Baileys ─────────► QR Code
    │                           │                          │
    ├── POST /pair (KODE) ───► │ ──► Baileys ─────────► 8-digit Code
    │                           │                          │
    ├── GET /status ─────────► │ ──► Check Session ────► Status
    │                           │                          │
    └── POST /disconnect ─────► │ ──► Logout ─────────► Disconnected
```

---

## 1. Install Dependencies

Di environment n8n (Docker atau server):

```bash
# Install Baileys
npm install @whiskeysockets/baileys

# Atau untuk versi spesifik
npm install @whiskeysockets/baileys@1.x.x
```

---

## 2. Node Code untuk Pair (QR + Pairing Code)

### Pair Action dengan Baileys

```javascript
// n8n Code Node - Pair Action
const { default: makeWASocket, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const fs = require('fs');
const path = require('path');

const sessionId = $input.first().json.body.session_id;
const msisdn = $input.first().json.body.msisdn || '';
const metode = $input.first().json.body.metode || 'qr';

// Normalisasi nomor HP
const phone = msisdn.replace(/\D/g, '');

// Validasi format Indonesia
if (!/^(62|08)\d{8,11}$/.test(phone)) {
  return {
    json: {
      status: 'error',
      session_id: sessionId,
      qr: null,
      pairing_code: null,
      error: 'Format nomor salah. Gunakan: 6281234567890'
    }
  };
}

// Path auth state
const authDir = `/data/wa-sessions/${sessionId}`;

// Pastikan folder ada
if (!fs.existsSync(authDir)) {
  fs.mkdirSync(authDir, { recursive: true });
}

// Setup auth state
const { state, saveCreds } = await useMultiFileAuthState(authDir);

// Buat socket
const sock = makeWASocket({
  auth: state.creds,
  printQRInTerminal: false,  // WAJIB: matikan QR di terminal
  logger: console
});

let qr = null;
let pairingCode = null;

// Event: connection update
sock.ev.on('connection.update', async ({ connection, qr: qrString }) => {
  // QR tersedia untuk scanning
  if (qrString) {
    qr = qrString;
  }
  
  // Koneksi berhasil
  if (connection === 'open') {
    console.log('WhatsApp connected!');
    await sock.end();
  }
});

// Event: pairing code untuk metode kode
if (metode === 'kode') {
  try {
    // Request pairing code dari Baileys
    pairingCode = await sock.requestPairingCode(phone);
    console.log('Pairing code:', pairingCode);
  } catch (err) {
    console.error('Pairing code error:', err.message);
  }
}

// Simpan credentials saat update
sock.ev.on('creds.update', saveCreds);

// Tunggu sebentar untuk QR/pairing code
await new Promise(resolve => setTimeout(resolve, 5000));

// Cleanup socket
try {
  sock.end();
} catch (e) {}

// Response
return {
  json: {
    status: qr || pairingCode ? 'pairing' : 'error',
    session_id: sessionId,
    qr: qr,
    pairing_code: pairingCode,
    error: (!qr && !pairingCode) ? 'Gagal membuat QR/pairing code' : null
  }
};
```

---

## 3. Status Action dengan Baileys

```javascript
// n8n Code Node - Status Action
const { useMultiFileAuthState } = require('@whiskeysockets/baileys');
const fs = require('fs');

const sessionId = $input.first().json.body.session_id;
const authDir = `/data/wa-sessions/${sessionId}`;

// Cek apakah auth state ada
if (!fs.existsSync(authDir)) {
  return {
    json: {
      status: 'disconnected',
      session_id: sessionId,
      qr: null,
      error: 'Sesi tidak ditemukan'
    }
  };
}

// Coba load auth state
try {
  const { state, loadState } = await useMultiFileAuthState(authDir);
  
  if (state.creds?.registered) {
    return {
      json: {
        status: 'connected',
        session_id: sessionId,
        qr: null,
        error: null
      }
    };
  }
} catch (err) {
  // Auth state corrupt atau tidak ada
}

return {
  json: {
    status: 'disconnected',
    session_id: sessionId,
    qr: null,
    error: null
  }
};
```

---

## 4. Disconnect Action

```javascript
// n8n Code Node - Disconnect Action
const { default: makeWASocket, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const fs = require('fs').promises;

const sessionId = $input.first().json.body.session_id;
const authDir = `/data/wa-sessions/${sessionId}`;

// Logout dari WhatsApp
try {
  if (fs.existsSync(authDir)) {
    const { state } = await useMultiFileAuthState(authDir);
    const sock = makeWASocket({ auth: state.creds });
    await sock.logout();
    sock.end();
  }
} catch (err) {
  console.log('Logout error:', err.message);
}

// Hapus folder auth
try {
  await fs.rm(authDir, { recursive: true, force: true });
} catch (err) {
  console.log('Folder cleanup error:', err.message);
}

return {
  json: {
    ok: true,
    session_id: sessionId,
    disconnected_at: new Date().toISOString()
  }
};
```

---

## 5. Format Nomor HP

| Input | Format E.164 (benar) | Error |
|-------|---------------------|-------|
| `+62 812 3456 7890` | `6281234567890` | ✅ |
| `0812-3456-7890` | `6281234567890` | ✅ |
| `81234567890` | `6281234567890` | ✅ |
| `628123456789` | ❌ | Kurang digit |

**Rules:**
- Awali `62` untuk Indonesia (bukan `0` atau `+`)
- Hapus semua spasi, `-`, `()`, `+`
- Minimal 10 digit, maksimal 13 digit

---

## 6. Troubleshooting

### QR Code tidak muncul
```
1. Cek console n8n untuk error
2. Pastikan printQRInTerminal: false
3. Cek network ke WhatsApp
```

### Pairing Code gagal
```
1. Format nomor harus E.164: 6281234567890
2. Nomor tidak boleh sudah tertaut device lain
3. WhatsApp harus aktif di HP utama
4. Kode expired dalam 60 detik
```

### Session tidak persist
```
1. Pastikan folder auth ada dan writable
2. Mount volume di Docker: -v /data:/data
3. Cek disk space
```

---

## 7. Docker Compose Example

```yaml
version: '3.8'
services:
  n8n:
    image: n8nio/n8n
    ports:
      - "5678:5678"
    volumes:
      - n8n_data:/home/node/.n8n
      - /data/wa-sessions:/data/wa-sessions  # WhatsApp sessions
    environment:
      - N8N_BASIC_AUTH_ACTIVE=true
      - N8N_BASIC_AUTH_USER=admin
      - N8N_BASIC_AUTH_PASSWORD=your-password
      - WEBHOOK_URL=https://your-n8n-domain.com/
    restart: unless-stopped

volumes:
  n8n_data:
```

---

## 8. API Reference

### POST /webhook/whatsapp-session

**Payload:**
```json
{
  "action": "pair",
  "session_id": "guru-123",
  "msisdn": "6281234567890",
  "metode": "qr"  // atau "kode"
}
```

**Response (QR):**
```json
{
  "status": "pairing",
  "session_id": "guru-123",
  "qr": "2@ABC...",
  "pairing_code": null,
  "error": null
}
```

**Response (Pairing Code):**
```json
{
  "status": "pairing",
  "session_id": "guru-123",
  "qr": null,
  "pairing_code": "ABCD-1234",
  "error": null
}
```

### POST /webhook/whatsapp-session

**Payload:**
```json
{
  "action": "status",
  "session_id": "guru-123"
}
```

**Response:**
```json
{
  "status": "connected",
  "session_id": "guru-123",
  "qr": null,
  "error": null
}
```

### POST /webhook/whatsapp-session

**Payload:**
```json
{
  "action": "disconnect",
  "session_id": "guru-123"
}
```

**Response:**
```json
{
  "ok": true,
  "session_id": "guru-123",
  "disconnected_at": "2024-01-15T10:30:00.000Z"
}
```

---

## 9. Security Notes

1. **Webhook Secret** - Wajib validasi `X-Webhook-Secret` header
2. **Session Isolation** - Setiap guru punya folder terpisah
3. **Credential Storage** - Simpan di volume terenkripsi
4. **Rate Limiting** - Batasi request per IP
5. **Session Expiry** - Cleanup otomatis sesi yang tidak aktif

---

## 10. Quick Test

```bash
# Test pair with QR
curl -X POST https://n8n.domain.com/webhook/whatsapp-session \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: your-secret" \
  -d '{
    "action": "pair",
    "session_id": "test-guru-1",
    "msisdn": "6281234567890",
    "metode": "qr"
  }'

# Test pair with kode
curl -X POST https://n8n.domain.com/webhook/whatsapp-session \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: your-secret" \
  -d '{
    "action": "pair",
    "session_id": "test-guru-2",
    "msisdn": "6281234567890",
    "metode": "kode"
  }'
```
