<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
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
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsAppSessionController extends Controller
{
    /**
     * Tautkan satu kelas ke grup WhatsApp langsung dari halaman kelas.
     *
     * Berbeda dari pilihan grup balasan otomatis di halaman WhatsApp yang
     * sifatnya global untuk nomor guru, di sini relasinya 1-ke-1 dengan kelas
     * bersangkutan — dipisah agar wali kelas yang mengampu lebih dari satu
     * kelas tidak bingung grup mana milik kelas mana.
     */
    public function setParentGroup(Request $request, Classroom $classroom): RedirectResponse
    {
        $data = $request->validate([
            'parent_group_wa' => ['nullable', 'string', 'max:100'],
        ]);

        $classroom->update(['parent_group_wa' => $data['parent_group_wa'] ?: null]);

        // Dorong ulang pemetaan tautan jika sesi WA sedang aktif
        $user = $request->user();
        if ($user && $user->whatsappConnected()) {
            $manager = app(WhatsAppSessionManager::class);
            $this->dorongKonfigurasi($user, $manager);
        }

        return back()->with('success', 'Grup WhatsApp orang tua berhasil diperbarui.');
    }

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

        if ($user->whatsappConnected()) {
            $this->dorongKonfigurasi($user, $manager);
        }

        return back()->with('success', 'Templat & kata kunci balasan otomatis WhatsApp berhasil disimpan.');
    }

    /**
     * Kirim seluruh konfigurasi balasan otomatis ke gateway.
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
            $this->petaAnakPerNomorOrangTua($user),
            [
                'izin' => $this->variasiDariTeks($user->wa_permission_template),
                'sakit' => $this->variasiDariTeks($user->wa_sick_template),
            ],
            $this->linkIzinPerGrup($user, $groups),
        );
    }

    /**
     * Peta JID grup WhatsApp => tautan formulir izin/sakit kelas itu.
     *
     * Gateway menempelkan tautan ini di SETIAP balasan izin/sakit untuk grup
     * bersangkutan, di luar kalimat acak mana pun yang terpilih.
     *
     * @return array<string, string>
     */
    private function linkIzinPerGrup(?User $user = null, ?array $groups = null): array
    {
        $map = Classroom::where('is_active', true)
            ->whereNotNull('parent_group_wa')
            ->where('parent_group_wa', '<>', '')
            ->get()
            ->reject(fn (Classroom $k) => $k->kelasAjar())
            ->mapWithKeys(fn (Classroom $k) => [
                $k->parent_group_wa => route('public.excuse.show', $k->tokenPublik()),
            ])
            ->all();

        // Jika guru memiliki grup terpilih yang belum tercatat di kolom parent_group_wa classroom
        if ($user && !empty($groups)) {
            $userClasses = $user->classes()
                ->where('is_active', true)
                ->get()
                ->reject(fn (Classroom $k) => $k->kelasAjar());

            if ($userClasses->count() === 1) {
                $primaryClass = $userClasses->first();
                $link = route('public.excuse.show', $primaryClass->tokenPublik());
                foreach ($groups as $g) {
                    if (!isset($map[$g])) {
                        $map[$g] = $link;
                        if (empty($primaryClass->parent_group_wa)) {
                            $primaryClass->update(['parent_group_wa' => $g]);
                        }
                    }
                }
            } elseif ($userClasses->count() > 1) {
                foreach ($groups as $g) {
                    if (!isset($map[$g])) {
                        $matchedClass = $userClasses->firstWhere('parent_group_wa', $g)
                            ?: $userClasses->firstWhere('parent_group_wa', null)
                            ?: $userClasses->first();
                        if ($matchedClass) {
                            $map[$g] = route('public.excuse.show', $matchedClass->tokenPublik());
                            if (empty($matchedClass->parent_group_wa)) {
                                $matchedClass->update(['parent_group_wa' => $g]);
                            }
                        }
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Ubah isian textarea menjadi daftar variasi kalimat.
     *
     * @return array<int, string>
     */
    private function variasiDariTeks(?string $teks): array
    {
        $teks = trim((string) $teks);

        if ($teks === '') {
            return [];
        }

        // Jika guru memisahkan beberapa opsi alternatif dengan '---' atau '==='
        if (str_contains($teks, '---') || str_contains($teks, '===')) {
            $blok = preg_split('/\r\n---\r\n|\n---\n|\r\n===\r\n|\n===\n/', $teks) ?: [];
            $variasi = [];
            foreach ($blok as $b) {
                $b = trim($b);
                if ($b !== '') {
                    $variasi[] = str_replace('{nama}', '{anak}', $b);
                }
            }
            return $variasi;
        }

        // Pertahankan seluruh teks sebagai 1 pesan utuh
        return [str_replace('{nama}', '{anak}', $teks)];
    }

    public function show(WhatsAppSessionManager $manager, NotificationChannel $channel): View
    {
        $user = Auth::user();

        $kelasWali = $user->classes()
            ->where('is_active', true)
            ->get()
            ->reject(fn (Classroom $c) => $c->kelasAjar());

        return view('whatsapp.index', [
            'autoreply' => $user->whatsappConnected()
                ? $manager->autoreplyStatus($user)
                : ['enabled' => false, 'groups' => [], 'jam' => null, 'error' => null],
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
            'kelasWali' => $kelasWali,
            'sppClasses' => $kelasWali,
        ]);
    }

    public function sppReminderSave(Request $request): RedirectResponse
    {
        $user = $request->user();

        $classes = $user->classes()
            ->where('is_active', true)
            ->get()
            ->reject(fn (Classroom $c) => $c->kelasAjar())
            ->keyBy('id');

        $data = $request->validate([
            'spp' => ['array'],
            'spp.*.target' => ['nullable', 'string', 'in:disabled,group,private,both'],
            'spp.*.day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'spp.*.time' => ['nullable', 'date_format:H:i'],
            'spp.*.nominal' => ['nullable', 'numeric', 'min:0'],
            'spp.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $diperbarui = 0;

        foreach ($data['spp'] ?? [] as $classId => $row) {
            $class = $classes->get($classId);
            if (! $class) {
                continue;
            }

            $target = $row['target'] ?? 'disabled';

            $class->update([
                'spp_reminder_target' => $target,
                'spp_reminder_day' => $row['day'] ?? 1,
                'spp_reminder_time' => $row['time'] ?? '07:00',
                'spp_monthly_amount' => $row['nominal'] ?: null,
                'spp_notes' => $row['notes'] ?: null,
            ]);

            $diperbarui++;
        }

        return back()->with('success', 'Pengaturan pengingat SPP untuk '.$diperbarui.' kelas berhasil disimpan.');
    }

    public function autoreplySave(Request $request, WhatsAppSessionManager $manager): RedirectResponse
    {
        $user = $request->user();

        if (! $user->whatsappConnected()) {
            return back()->with('warning', 'Tautkan nomor WhatsApp dulu sebelum mengatur balasan otomatis.');
        }

        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'groups' => ['sometimes', 'array'],
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
     * Peta nomor HP orang tua => nama panggilan anak.
     *
     * @return array<string, string>
     */
    private function petaAnakPerNomorOrangTua(?User $user = null): array
    {
        $query = Student::query()
            ->where('is_active', true)
            ->whereNotNull('parent_phone')
            ->where('parent_phone', '<>', '');

        if ($user) {
            $classIds = $user->classes()->pluck('id');
            $query->whereIn('class_id', $classIds);
        }

        $baris = $query->get(['name', 'parent_phone']);

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
                continue;
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
     */
    private function namaPanggilan(string $namaLengkap): string
    {
        $kata = array_values(array_filter(explode(' ', trim($namaLengkap))));

        if (empty($kata)) {
            return '';
        }

        $abaikan = ['muhammad', 'moch', 'moch.', 'mochammad', 'muh', 'muh.', 'm.', 'md', 'siti', 'nur'];

        if (count($kata) > 1 && in_array(mb_strtolower($kata[0]), $abaikan, true)) {
            return Str::title($kata[1]);
        }

        return Str::title($kata[0]);
    }

    public function connect(Request $request, WhatsAppSessionManager $manager): RedirectResponse
    {
        $user = $request->user();

        if (blank($user->whatsapp_number)) {
            return back()->withErrors(['whatsapp' => 'Nomor WhatsApp belum diisi di profil Anda.']);
        }

        $metode = $request->input('metode') === 'kode'
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
                if (blank($result['pairing_code'] ?? null)) {
                    return back()->with('warning',
                        'Kode penautan belum terbit. Tunggu sebentar lalu coba lagi, '
                        .'atau gunakan metode QR.');
                }

                return back()->with('wa_pairing_code', $result['pairing_code'])
                    ->with('success', 'Kode penautan berhasil dibuat.');
            }

            if (blank($result['qr'] ?? null)) {
                return back()->with('warning',
                    'QR code belum terbit. Tunggu sebentar lalu coba lagi.');
            }

            return back()->with('wa_qr', $result['qr'])
                ->with('success', 'Pindai QR berikut dengan WhatsApp di ponsel Anda.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menautkan WhatsApp: '.$e->getMessage());
        }
    }

    public function status(Request $request, WhatsAppSessionManager $manager): JsonResponse
    {
        $user = $request->user();
        $result = $manager->status($user);

        $user->catatStatusSesi($result['status'], $result['error']);

        return response()->json([
            'status' => $result['status'],
            'qr' => $result['qr'] ?? null,
            'pairing_code' => $result['pairing_code'] ?? null,
            'error' => $result['error'] ?? null,
            'gateway_healthy' => $manager->isHealthy(),
            'gateway_circuit' => method_exists($manager, 'getCircuitStatus')
                ? $manager->getCircuitStatus()
                : null,
        ]);
    }

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

    public function autoreplyCheck(Request $request, WhatsAppSessionManager $manager): JsonResponse
    {
        $data = $request->validate([
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

        if (method_exists($channel, 'isHealthy') && ! $channel->isHealthy()) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Gateway WhatsApp sedang tidak tersedia. Mohon tunggu beberapa menit.',
            ], 503);
        }

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
