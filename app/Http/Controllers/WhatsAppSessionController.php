<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use App\Support\Contracts\WhatsAppSessionManager;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Penautan nomor WhatsApp milik guru.
 *
 * Arsitekturnya satu nomor per guru, jadi tiap wali kelas harus memindai QR
 * sekali agar nomornya bisa dipakai mengirim magic link absensi. Halaman ini
 * juga tempat menyambungkan ulang ketika sesi putus — kejadian yang normal
 * (ganti HP, logout perangkat tertaut) dan harus bisa diatasi guru sendiri
 * tanpa bantuan admin.
 */
class WhatsAppSessionController extends Controller
{
    /** Simpan templat balasan otomatis izin orang tua. */
    public function templateSave(Request $request, WhatsAppSessionManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'wa_permission_template' => ['nullable', 'string', 'max:1000'],
            'wa_sick_template' => ['nullable', 'string', 'max:1000'],
            'wa_magic_link_template' => ['nullable', 'string', 'max:1000'],
            'wa_permission_keywords' => ['nullable', 'string', 'max:500'],
            'wa_sick_keywords' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $user->update([
            'wa_permission_template' => $data['wa_permission_template'] ?? null,
            'wa_sick_template' => $data['wa_sick_template'] ?? null,
            'wa_magic_link_template' => $data['wa_magic_link_template'] ?? null,
            'wa_permission_keywords' => $data['wa_permission_keywords'] ?? null,
            'wa_sick_keywords' => $data['wa_sick_keywords'] ?? null,
        ]);

        /*
         * Dorong ulang SELURUH konfigurasi, bukan sebagian.
         *
         * Sebelumnya di sini dipanggil autoreplySave() tanpa peta nama anak,
         * sehingga menyimpan templat diam-diam MENGHAPUS nama yang sudah
         * terkirim — balasannya kembali menyapa "Ananda" tanpa nama dan tidak
         * ada yang tahu penyebabnya. Satu jalur bersama menutup seluruh
         * keluarga kesalahan itu.
         */
        if ($user->whatsappConnected()) {
            $this->dorongKonfigurasi($user, $manager);
        }

        return back()->with('success', 'Templat & kata kunci balasan otomatis WhatsApp berhasil disimpan.');
    }

    /**
     * Kirim seluruh konfigurasi balasan otomatis ke gateway.
     *
     * SATU-SATUNYA tempat yang memanggil autoreplySave(). Selama pemanggilnya
     * lebih dari satu, setiap penambahan bagian baru (peta nama anak, ragam
     * kalimat, kata kunci) harus diingat di semua tempat — dan yang terlupa
     * tidak akan terlihat sebagai galat, hanya sebagai fitur yang diam-diam
     * berhenti bekerja.
     *
     * $enabled dan $groups boleh null: artinya "pertahankan yang sekarang",
     * dibaca balik dari gateway. Dipakai saat yang berubah hanya templat atau
     * kata kunci, bukan pilihan grupnya.
     *
     * @param  array<int, string>|null  $groups
     */
    private function dorongKonfigurasi(
        User $user,
        WhatsAppSessionManager $manager,
        ?bool $enabled = null,
        ?array $groups = null,
    ): bool {
        if ($groups === null) {
            $groups = $manager->autoreplyStatus($user)['groups'] ?? [];
        }

        if ($enabled === null) {
            $enabled = count($groups) > 0;
        }

        $pisahKoma = fn (?string $teks): array => array_values(array_filter(
            array_map('trim', explode(',', (string) $teks))
        ));

        return $manager->autoreplySave(
            $user,
            $enabled,
            $groups,
            $pisahKoma($user->wa_permission_keywords),
            $pisahKoma($user->wa_sick_keywords),
            $this->petaAnakPerNomorOrangTua(),
            [
                'izin' => $this->variasiDariTeks($user->wa_permission_template),
                'sakit' => $this->variasiDariTeks($user->wa_sick_template),
            ],
        );
    }

    /**
     * Ubah isian textarea menjadi daftar variasi kalimat.
     *
     * Satu baris = satu variasi. Beberapa baris membuat balasan berganti-ganti
     * alih-alih mengulang kalimat yang sama sepanjang tahun — keluhan yang
     * justru memulai seluruh perbaikan ini.
     *
     * Tanda {nama} yang dipakai wali kelas diterjemahkan ke {anak} milik
     * gateway. Istilah di layar sengaja berbeda dari istilah internal: yang
     * dilihat guru harus kata yang wajar, bukan nama variabel.
     *
     * @return array<int, string>
     */
    private function variasiDariTeks(?string $teks): array
    {
        $baris = preg_split('/\r\n|\r|\n/', (string) $teks) ?: [];

        $variasi = [];

        foreach ($baris as $satu) {
            $bersih = trim($satu);

            if ($bersih === '') {
                continue;
            }

            $variasi[] = str_replace('{nama}', '{anak}', $bersih);
        }

        return $variasi;
    }

    public function show(WhatsAppSessionManager $manager, NotificationChannel $channel): View
    {
        $user = Auth::user();

        /*
         * Pengaturan balasan otomatis diambil dari gateway, BUKAN disimpan di
         * database aplikasi. Gateway-lah yang benar-benar menjalankan fitur
         * ini; menyimpan salinannya di sini hanya akan menciptakan dua sumber
         * kebenaran yang bisa berbeda tanpa ada yang menyadarinya.
         *
         * Kalau gateway tidak terjangkau, autoreplyStatus() mengembalikan
         * keadaan mati — lebih aman daripada menampilkan "aktif" untuk sesuatu
         * yang belum tentu berjalan.
         */
        return view('whatsapp.index', [
            // $user sengaja TIDAK dikirim: view memakai auth()->user().
            'autoreply' => $user->whatsappConnected()
                ? $manager->autoreplyStatus($user)
                // Nomor belum ditautkan: benar-benar mati, bukan "tidak tahu".
                : ['enabled' => false, 'groups' => [], 'jam' => null, 'error' => null],
            /*
             * Nama grup yang sudah dipilih, dibaca dari simpanan — tanpa
             * menghubungi WhatsApp. Dengan ini halaman bisa menampilkan
             * pilihan yang tersimpan begitu dibuka, dan pemindaian grup baru
             * terjadi ketika guru memang menekan "Ubah pilihan".
             */
            'grupLabels' => $user->whatsappConnected() ? $manager->groupLabels($user) : [],
            'gatewayStatus' => [
                'healthy' => $manager->isHealthy(),
                'circuit' => method_exists($manager, 'getCircuitStatus')
                    ? $manager->getCircuitStatus()
                    : null,
            ],
            'channelStatus' => [
                'healthy' => method_exists($channel, 'isHealthy') ? $channel->isHealthy() : true,
                'circuit' => method_exists($channel, 'getCircuitStatus')
                    ? $channel->getCircuitStatus()
                    : null,
            ],
        ]);
    }

    /** Simpan pengaturan balasan otomatis untuk grup orang tua. */
    public function autoreplySave(Request $request, WhatsAppSessionManager $manager): RedirectResponse
    {
        $user = $request->user();

        if (! $user->whatsappConnected()) {
            return back()->with('warning', 'Tautkan nomor WhatsApp dulu sebelum mengatur balasan otomatis.');
        }

        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'groups' => ['sometimes', 'array'],
            // Hanya JID grup. Tanpa penjagaan ini, satu salah pilih bisa
            // membuat bot membalas di percakapan pribadi orang tua.
            'groups.*' => ['string', 'ends_with:@g.us'],
        ], [
            'groups.*.ends_with' => 'Hanya grup WhatsApp yang bisa dipilih, bukan chat pribadi.',
        ]);

        $enabled = (bool) ($data['enabled'] ?? false);
        $groups = $data['groups'] ?? [];

        if ($enabled && $groups === []) {
            return back()->with('warning', 'Pilih minimal satu grup sebelum menyalakan balasan otomatis.');
        }

        $berhasil = $this->dorongKonfigurasi($user, $manager, $enabled, $groups);

        if (! $berhasil) {
            return back()->with('error', 'Gateway tidak merespons. Pengaturan belum tersimpan, coba lagi sebentar.');
        }

        return back()->with('success', $enabled
            ? 'Balasan otomatis aktif untuk '.count($groups).' grup.'
            : 'Balasan otomatis dimatikan.');
    }

    /**
     * Peta nomor WhatsApp orang tua => nama panggilan anaknya.
     *
     * Dipakai gateway agar balasan menyebut nama ("Semoga Ananda Dinar lekas
     * sembuh") alih-alih menyapa siapa pun dengan kalimat yang sama. Itulah
     * yang paling membedakan balasan personal dari balasan template — jauh
     * lebih terasa daripada sekadar memutar-mutar susunan kalimat.
     *
     * Nomor yang dipakai LEBIH DARI SATU siswa sengaja dibuang: orang tua
     * dengan dua anak di satu kelas tidak bisa ditentukan sedang mengabarkan
     * yang mana, dan menyebut nama yang keliru di depan seluruh grup jauh
     * lebih buruk daripada tidak menyebut nama sama sekali. Nomor seperti itu
     * jatuh ke sapaan "Ananda" tanpa nama, yang tetap wajar.
     *
     * Query-nya otomatis ter-scope ke wali kelas yang login lewat TenantScope,
     * jadi tidak mungkin membocorkan siswa kelas lain ke gateway.
     *
     * @return array<string, string>
     */
    private function petaAnakPerNomorOrangTua(): array
    {
        $baris = Student::query()
            ->where('is_active', true)
            ->whereNotNull('parent_phone')
            ->where('parent_phone', '<>', '')
            ->get(['name', 'parent_phone']);

        $perNomor = [];

        foreach ($baris as $siswa) {
            $nomor = Phone::normalize($siswa->parent_phone);

            if (blank($nomor)) {
                continue;
            }

            $perNomor[$nomor][] = $siswa->name;
        }

        $peta = [];

        foreach ($perNomor as $nomor => $namaSiswa) {
            if (count($namaSiswa) !== 1) {
                continue; // ambigu — lihat catatan di atas
            }

            $panggilan = $this->namaPanggilan($namaSiswa[0]);

            if ($panggilan !== '') {
                $peta[$nomor] = $panggilan;
            }
        }

        return $peta;
    }

    /**
     * Nama panggilan dari nama lengkap.
     *
     * Data nama di lapangan tidak konsisten kapitalisasinya — ada "adzka
     * muhammad", ada "ALDY BAYU RIONA" — jadi diseragamkan dulu, kalau tidak
     * balasannya akan berteriak "Semoga Ananda ALDY BAYU RIONA lekas sembuh".
     *
     * Umumnya cukup kata pertama: "Ananda Dinar" terdengar wajar di sekolah
     * Indonesia. Pengecualiannya nama berawalan umum seperti Muhammad, Siti,
     * atau Nur — di satu kelas bisa ada beberapa "Muhammad", sehingga kata
     * kedua ikut disertakan agar jelas siapa yang dimaksud.
     */
    private function namaPanggilan(string $namaLengkap): string
    {
        $kata = preg_split('/\s+/', trim($namaLengkap), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($kata === []) {
            return '';
        }

        $rapikan = fn (string $k): string => mb_convert_case(mb_strtolower($k), MB_CASE_TITLE, 'UTF-8');

        $awalanUmum = ['muhammad', 'mohammad', 'muhamad', 'muh', 'moh', 'm', 'siti', 'nur', 'dwi', 'tri', 'eka', 'ahmad', 'abdul'];

        $pertama = $rapikan($kata[0]);

        if (isset($kata[1]) && in_array(mb_strtolower($kata[0]), $awalanUmum, true)) {
            return $pertama.' '.$rapikan($kata[1]);
        }

        return $pertama;
    }

    /** Mulai penautan; menampilkan QR dari gateway. */
    public function pair(Request $request, WhatsAppSessionManager $manager): RedirectResponse
    {
        $user = $request->user() ?? Auth::user();

        if (! $user) {
            return back()->withErrors(['whatsapp' => 'Anda harus login terlebih dahulu.']);
        }

        if (blank($user->whatsapp_number)) {
            return back()->withErrors([
                'whatsapp' => 'Isi dulu nomor WhatsApp Anda sebelum menautkan.',
            ]);
        }

        // Cek apakah gateway tersedia
        if (! $manager->isHealthy()) {
            $circuitStatus = $manager->getCircuitStatus();

            return back()->with('warning',
                'Gateway WhatsApp sedang tidak tersedia. '
                .'Mohon tunggu '.ceil($circuitStatus['time_until_retry'] / 60).' menit, lalu coba lagi.');
        }

        /*
         * Guru yang mendaftar dari ponsel tidak bisa memindai layar ponselnya
         * sendiri. Baginya satu-satunya jalan adalah kode pairing, jadi
         * pilihannya datang dari formulir — bukan ditebak dari user agent,
         * yang salah tebak justru mengunci guru di jalan buntu.
         */
        $metode = $request->input('metode') === WhatsAppSessionManager::METODE_KODE
            ? WhatsAppSessionManager::METODE_KODE
            : WhatsAppSessionManager::METODE_QR;

        try {
            $result = $manager->startPairing($user, $metode);

            $user->forceFill(['wa_session_id' => $result['session_id']])->save();
            $user->catatStatusSesi($result['status']);

            if ($result['status'] === 'connected') {
                return back()->with('success', 'Nomor WhatsApp tersambung.');
            }

            if ($metode === WhatsAppSessionManager::METODE_KODE) {
                if (blank($result['pairing_code'])) {
                    return back()->with('warning',
                        'Gateway belum bisa menerbitkan kode penautan. Biasanya karena WhatsApp '
                        .'sedang menolak koneksi setelah terlalu sering menyambung ulang. '
                        .'Tunggu beberapa menit, lalu coba lagi.');
                }

                return back()->with('wa_pairing_code', $result['pairing_code'])
                    ->with('success', 'Masukkan kode berikut di WhatsApp ponsel Anda.');
            }

            // QR bisa TIDAK terbit meski gateway menjawab - mis. saat WhatsApp
            // menolak koneksi (405). Menyuruh pindai QR yang tidak ada adalah
            // cara tercepat membuat guru mengira aplikasinya rusak.
            if (blank($result['qr'])) {
                return back()->with('warning',
                    'Gateway belum bisa menerbitkan QR. Biasanya karena WhatsApp sedang '
                    .'menolak koneksi setelah terlalu sering menyambung ulang. '
                    .'Tunggu beberapa menit, lalu coba lagi.');
            }

            return back()->with('wa_qr', $result['qr'])
                ->with('success', 'Pindai QR berikut dengan WhatsApp di ponsel Anda.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menautkan WhatsApp: '.$e->getMessage());
        }
    }

    /** Dipanggil berkala oleh halaman untuk memperbarui status. */
    public function status(Request $request, WhatsAppSessionManager $manager): JsonResponse
    {
        $user = $request->user();
        $result = $manager->status($user);

        $user->catatStatusSesi($result['status'], $result['error']);

        return response()->json([
            'status' => $result['status'],
            'qr' => $result['qr'],
            // Kode bisa terbit sesaat SETELAH /pair menjawab; tanpa ikut di
            // sini, guru yang menunggu tidak pernah melihatnya muncul.
            'pairing_code' => $result['pairing_code'] ?? null,
            'error' => $result['error'],
            'gateway_healthy' => $manager->isHealthy(),
            'gateway_circuit' => method_exists($manager, 'getCircuitStatus')
                ? $manager->getCircuitStatus()
                : null,
        ]);
    }

    /** Daftar grup untuk dipilih pada form kelas. */
    public function groups(Request $request, WhatsAppSessionManager $manager): JsonResponse
    {
        $user = $request->user();

        if (! $user->whatsappConnected()) {
            return response()->json(['connected' => false, 'ok' => true, 'groups' => []]);
        }

        if (! $manager->isHealthy()) {
            return response()->json([
                'connected' => true,
                'ok' => false,
                'groups' => [],
                'warning' => 'Gateway WhatsApp sedang tidak tersedia.',
                'gateway_healthy' => false,
            ]);
        }

        $hasil = $manager->groupsResult($user, $request->boolean('refresh'));

        /*
         * "ok" dilaporkan terpisah dari "gateway_healthy". Gateway bisa sehat
         * (health check lolos) tapi pengambilan grup tetap gagal, karena
         * WhatsApp membatasi laju groupFetchAllParticipating. Tanpa pembedaan
         * ini, kegagalan tampil sebagai daftar kosong yang tidak bisa
         * dibedakan dari "guru belum masuk grup mana pun".
         */
        $gagalMenyegarkan = $hasil['ok'] && $hasil['error'] !== null;

        return response()->json([
            'connected' => true,
            'ok' => $hasil['ok'],
            'groups' => $hasil['groups'],
            'cached' => $hasil['cached'],
            'warning' => match (true) {
                ! $hasil['ok'] => 'Daftar grup gagal dimuat. WhatsApp sedang membatasi permintaan — tunggu sebentar lalu coba lagi.',
                $gagalMenyegarkan => 'Gagal menyegarkan daftar grup, yang ditampilkan adalah data terakhir. Coba lagi sebentar.',
                default => null,
            },
            'gateway_healthy' => true,
        ], $hasil['ok'] ? 200 : 503);
    }

    /**
     * Periksa apakah balasan otomatis akan benar-benar jalan untuk satu grup.
     *
     * Berbeda dari testGroup(): tidak ada pesan apa pun yang dikirim, jadi
     * aman ditekan berkali-kali tanpa mengganggu grup orang tua.
     */
    public function autoreplyCheck(Request $request, WhatsAppSessionManager $manager): JsonResponse
    {
        $data = $request->validate([
            // Hanya JID grup, sejalan dengan penjagaan pada penyimpanan.
            'group_id' => ['required', 'string', 'max:100', 'ends_with:@g.us'],
        ]);

        $user = $request->user();

        if (! $user->whatsappConnected()) {
            return response()->json([
                'ok' => false,
                'siap' => false,
                'pesan' => 'Nomor WhatsApp Anda belum tersambung.',
            ], 422);
        }

        $hasil = $manager->autoreplyCheck($user, $data['group_id']);

        if ($hasil['error'] !== null) {
            return response()->json([
                'ok' => false,
                'siap' => false,
                'pesan' => $hasil['error'],
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'siap' => $hasil['siap'],
            'syarat' => $hasil['syarat'],
            'jam' => $hasil['jam'],
            'kuota_harian' => $hasil['kuota_harian'],
            'terpakai_hari_ini' => $hasil['terpakai_hari_ini'],
        ]);
    }

    /**
     * Kirim satu pesan uji ke grup yang sedang dipilih.
     *
     * Dikirim SINKRON, bukan lewat antrian: gunanya justru memberi kepastian
     * langsung kepada guru bahwa pilihannya benar.
     */
    public function testGroup(Request $request, NotificationChannel $channel): JsonResponse
    {
        $data = $request->validate([
            'group_id' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();

        if (! $user->whatsappConnected()) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Nomor WhatsApp Anda belum tersambung.',
            ], 422);
        }

        // Cek apakah gateway tersedia
        if (method_exists($channel, 'isHealthy') && ! $channel->isHealthy()) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Gateway WhatsApp sedang tidak tersedia. Mohon tunggu beberapa menit.',
            ], 503);
        }

        // Batasi agar tombol ini tidak jadi alat mengirim beruntun.
        $kunci = 'uji-grup|'.$user->id;

        if (RateLimiter::tooManyAttempts($kunci, 3)) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Sudah beberapa kali menguji. Coba lagi beberapa menit.',
            ], 429);
        }

        RateLimiter::hit($kunci, 300);

        $terkirim = $channel->send(
            $data['group_id'],
            implode("\n", [
                '*WaliKelas Pro — Pesan Uji*',
                '',
                'Grup ini akan menerima rekap absensi harian dari '.$user->name.'.',
                'Bila pesan ini masuk, pengaturannya sudah benar.',
            ]),
            ['type' => 'group_test', 'user_id' => $user->id],
            $user->whatsapp_number,
        );

        return response()->json([
            'ok' => $terkirim,
            'pesan' => $terkirim
                ? 'Pesan uji terkirim — cek grup Anda sekarang.'
                : 'Gagal mengirim. Pastikan nomor Anda masih anggota grup itu.',
        ], $terkirim ? 200 : 502);
    }

    public function disconnect(Request $request, WhatsAppSessionManager $manager): RedirectResponse
    {
        $user = $request->user() ?? Auth::user();

        if (! $user) {
            return back()->withErrors(['whatsapp' => 'Anda harus login terlebih dahulu.']);
        }

        $manager->disconnect($user);

        $user->catatStatusSesi('disconnected');
        $user->forceFill(['wa_session_id' => null])->save();

        return back()->with('success', 'Nomor WhatsApp diputus dari sistem.');
    }
}
