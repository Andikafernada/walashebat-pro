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
    /** Pindai QR dari layar — butuh perangkat kedua. */
    public const METODE_QR = 'qr';

    /** Ketik kode 8 karakter di WhatsApp — bisa dilakukan di ponsel yang sama. */
    public const METODE_KODE = 'kode';

    /**
     * Mulai penautan.
     *
     * Dua metode, karena QR mensyaratkan sesuatu yang tidak selalu ada: layar
     * kedua. Guru yang mendaftar langsung dari ponselnya tidak bisa memindai
     * layar ponsel itu dengan ponsel yang sama, dan tanpa jalan lain ia
     * berhenti di situ. METODE_KODE memakai penautan lewat nomor telepon:
     * gateway menerbitkan 8 karakter yang diketik guru di WhatsApp →
     * Perangkat Tertaut → Tautkan dengan nomor telepon.
     *
     * Hanya salah satu dari 'qr' atau 'pairing_code' yang terisi.
     *
     * @param  self::METODE_*  $metode
     * @return array{session_id: string, qr: ?string, pairing_code: ?string, metode: string, status: string}
     */
    public function startPairing(User $user, string $metode = self::METODE_QR): array;

    /**
     * Status terkini dari gateway: disconnected | pairing | connected.
     *
     * @return array{status: string, qr: ?string, pairing_code: ?string, error: ?string}
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
     * Nama grup yang pernah terlihat, untuk ditampilkan TANPA memindai ulang.
     *
     * Grup yang sudah dipilih dan disimpan hanya perlu ditampilkan namanya,
     * bukan dipilih ulang — dan memindai hanya demi sebuah nama membuat setiap
     * kali halaman dibuka menghubungi WhatsApp, yang dibatasi lajunya dengan
     * ketat. Peta ini diisi sebagai efek samping groupsResult() yang berhasil,
     * lalu dibaca murni dari simpanan: implementasinya TIDAK BOLEH menghubungi
     * gateway, karena justru itu yang ingin dihindari.
     *
     * Ini cache tampilan, bukan sumber kebenaran. Yang menentukan grup mana
     * yang aktif tetap gateway lewat autoreplyStatus(); di sini hanya namanya.
     * JID yang belum pernah terlihat tidak akan muncul, dan pemanggil harus
     * tetap bisa menampilkan JID mentahnya sebagai cadangan.
     *
     * @return array<string, array{subject: string, peserta: int}> berkunci JID
     */
    public function groupLabels(User $user): array;

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
