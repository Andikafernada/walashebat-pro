<?php

namespace App\Support\Contracts;

use App\Models\User;

/**
 * Pengelolaan sesi WhatsApp milik masing-masing guru.
 *
 * Arsitektur yang dipilih: SATU NOMOR PER GURU. Setiap wali kelas menautkan
 * nomornya sendiri ke gateway (memindai QR sekali), sehingga pesan absensi
 * sampai ke siswa dari nomor guru yang mereka kenal — bukan dari nomor sistem
 * yang asing dan mudah dicurigai sebagai penipuan.
 *
 * Konsekuensinya gateway harus menyimpan satu sesi per guru dan mampu
 * memulihkannya setelah restart. Kontrak ini sengaja dibuat tipis agar
 * implementasinya bisa ditukar (n8n hari ini, layanan lain besok).
 */
interface WhatsAppSessionManager
{
    /**
     * Mulai penautan. Mengembalikan data QR untuk dipindai guru.
     *
     * @return array{session_id: string, qr: ?string, status: string}
     */
    public function startPairing(User $user): array;

    /**
     * Status terkini dari gateway: disconnected | pairing | connected.
     *
     * @return array{status: string, qr: ?string, error: ?string}
     */
    public function status(User $user): array;

    /**
     * Daftar grup WhatsApp yang diikuti nomor guru ini.
     *
     * Daftar kosong di sini ambigu: bisa berarti guru memang belum masuk grup
     * mana pun, bisa juga berarti pengambilannya gagal. Pakai groupsResult()
     * bila perlu membedakan keduanya.
     *
     * @return array<int, array{id: string, subject: string, peserta: int}>
     */
    public function groups(User $user): array;

    /**
     * Sama seperti groups(), tetapi melaporkan berhasil/gagalnya.
     *
     * WhatsApp membatasi laju pengambilan metadata grup dengan ketat
     * (rate-overlimit), jadi hasilnya di-cache sebentar. $paksaSegar melewati
     * cache untuk tombol "muat ulang" yang ditekan guru.
     *
     * @return array{ok: bool, groups: array<int, array{id: string, subject: string, peserta: int}>, cached: bool, error: ?string}
     */
    public function groupsResult(User $user, bool $paksaSegar = false): array;

    /**
     * Pengaturan balasan otomatis milik guru ini.
     *
     * @return array{enabled: bool, groups: array<int, string>, jam: ?string}
     */
    public function autoreplyStatus(User $user): array;

    /**
     * Apakah balasan otomatis benar-benar akan jalan untuk satu grup?
     *
     * "Tersimpan aktif" tidak sama dengan "akan membalas": sesi bisa putus,
     * jam kerja bisa sudah lewat, kuota harian bisa habis. Syarat per pesan
     * (panjang teks, tanda tanya, kata kunci) tidak dinilai di sini karena
     * bergantung pada isi pesan yang belum ada.
     *
     * @return array{siap: bool, syarat: array<string, bool|int>, jam: ?string, kuota_harian: int, terpakai_hari_ini: int, error: ?string}
     */
    public function autoreplyCheck(User $user, string $groupId): array;

    /**
     * Simpan pengaturan balasan otomatis.
     *
     * @param  array<int, string>  $groups  JID grup (…@g.us)
     * @param  array<string, string>  $students  Nomor orang tua => nama panggilan
     *                                           anak, dipakai gateway untuk menyapa
     *                                           dengan nama. Hanya berisi nomor yang
     *                                           menunjuk ke SATU siswa.
     */
    public function autoreplySave(User $user, bool $enabled, array $groups, array $permissionKeywords = [], array $sickKeywords = [], array $students = [], array $ragam = []): bool;

    /** Putuskan sesi dan lupakan kredensialnya di gateway. */
    public function disconnect(User $user): bool;
}
