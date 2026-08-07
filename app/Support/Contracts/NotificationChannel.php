<?php

namespace App\Support\Contracts;

/**
 * Kontrak pengiriman notifikasi keluar (mis. WhatsApp).
 *
 * Implementasi disuntik lewat container sehingga gateway dapat ditukar
 * (n8n hari ini, Baileys/whatsapp-web.js besok) tanpa mengubah pemanggil.
 */
interface NotificationChannel
{
    /**
     * @param  string  $to       Nomor tujuan, format internasional tanpa "+", cth: 6281234567890
     * @param  string  $message  Isi pesan teks.
     * @param  array<string,mixed>  $meta  Metadata tambahan (dipakai workflow n8n).
     * @param  string|null  $from  Nomor PENGIRIM. Untuk absensi ini adalah nomor
     *                             WhatsApp wali kelas, sehingga siswa menerima
     *                             pesan dari nomor guru yang mereka kenal, bukan
     *                             dari nomor sistem yang asing. null = pakai
     *                             sesi pengirim bawaan gateway.
     */
    public function send(string $to, string $message, array $meta = [], ?string $from = null): bool;
}
