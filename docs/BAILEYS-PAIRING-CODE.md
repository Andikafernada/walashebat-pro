# Pairing Code dengan Baileys

## ⚠️ DOKUMENTASI LENGKAP

**Lihat [`BAILEYS-N8N-GUIDE.md`](BAILEYS-N8N-GUIDE.md) untuk panduan lengkap implementasi Baileys dengan QR + Pairing Code di n8n.**

---

## Quick Reference

### Format Nomor HP

| Format | Contoh | Benar |
|--------|--------|-------|
| E.164 Indonesia | `6281234567890` | ✅ |
| Dengan + | `+6281234567890` | ❌ Hapus + |
| Local | `081234567890` | ❌ Ganti 0 → 62 |
| Dengan tanda baca | `62812-345-67890` | ❌ Hapus semua |

### Code Snippet Pairing Code

```javascript
const { default: makeWASocket } = require('@whiskeysockets/baileys');

const sock = makeWASocket({ printQRInTerminal: false });

// Request pairing code dengan nomor HP
const code = await sock.requestPairingCode('6281234567890');
console.log('Pairing code:', code);
```

### Troubleshooting

| Masalah | Penyebab | Solusi |
|--------|----------|--------|
| Code tidak muncul | Format nomor salah | Pakai `628...` tanpa `+` |
| Code expired | Proses terlalu lama | Langsung masukkan setelah muncul |
| Koneksi gagal | Device sudah tertaut | Putuskan device lain di WhatsApp |
