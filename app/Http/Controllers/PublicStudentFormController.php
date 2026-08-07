<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppMessage;
use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PublicStudentFormController extends Controller
{
    /** Form pengisian biodata mandiri oleh siswa / orang tua. */
    public function showBiodataForm(Classroom $class)
    {
        $students = $class->students()->orderBy('name')->get();
        return view('public.student_biodata_form', compact('class', 'students'));
    }

    /** Simpan / perbarui biodata mandiri siswa. */
    public function storeBiodata(Request $request, Classroom $class)
    {
        $validated = $request->validate([
            // Disaring ke kelas ini: halaman terbuka tanpa login, dan aturan
            // exists polos menerima id siswa kelas mana pun.
            'student_id' => [
                'nullable',
                Rule::exists('students', 'id')->where('class_id', $class->id),
            ],
            /*
             * Nama wajib HANYA bila siswa tidak memilih namanya dari daftar.
             *
             * Memilih dari daftar adalah jalur yang dianjurkan halaman ini agar
             * biodata tidak terduplikat, tetapi dulu kolom nama tetap wajib
             * sekaligus berada di langkah yang tersembunyi saat tombol Simpan
             * ditekan — peramban membatalkan pengiriman tanpa pesan apa pun,
             * dan siswa yang menuruti anjuran itu justru tidak pernah bisa
             * mengirim formulirnya.
             */
            'name' => [
                Rule::requiredIf(fn () => blank($request->input('student_id'))),
                'nullable',
                'string',
                'max:191',
            ],
            'nis' => ['nullable', 'string', 'max:50'],
            'nisn' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:191'],
            'tanggal_lahir' => ['nullable', 'date'],
            'agama' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'parent_phone' => ['required', 'string', 'max:20'],
            'nama_ayah' => ['nullable', 'string', 'max:191'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:191'],
            'nama_ibu' => ['nullable', 'string', 'max:191'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:191'],
            // Lima field di bawah dirender oleh student_biodata_form.blade.php tetapi
            // dulu tidak divalidasi, sehingga selalu terbuang dari $validated dan
            // tersimpan NULL meski orang tua sudah mengisinya.
            'nama_wali' => ['nullable', 'string', 'max:191'],
            'pekerjaan_wali' => ['nullable', 'string', 'max:191'],
            'anak_ke' => ['nullable', 'integer', 'min:1', 'max:50'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0', 'max:50'],
            'alamat_ortu' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'rt_rw' => ['nullable', 'string', 'max:20'],
            'kelurahan' => ['nullable', 'string', 'max:191'],
            'kecamatan' => ['nullable', 'string', 'max:191'],
            'jarak_rumah_km' => ['nullable', 'numeric'],
            'moda_transportasi' => ['nullable', 'string', 'max:191'],
            'golongan_darah' => ['nullable', 'string'],
            'tinggi_badan_cm' => ['nullable', 'integer'],
            'berat_badan_kg' => ['nullable', 'integer'],
            'asal_sekolah' => ['nullable', 'string', 'max:191'],
            'tahun_masuk' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'hobi' => ['nullable', 'string', 'max:191'],
            'cita_cita' => ['nullable', 'string', 'max:191'],
            'penerima_kip' => ['nullable', 'boolean'],
            'penerima_pkh' => ['nullable', 'boolean'],
        ]);

        $pilihanSiswa = $validated['student_id'] ?? null;
        unset($validated['student_id']);

        if (! empty($pilihanSiswa)) {
            $student = Student::where('class_id', $class->id)->findOrFail($pilihanSiswa);

            /*
             * Isian yang dikosongkan TIDAK menimpa data yang sudah ada.
             *
             * Formulir selalu mengirim seluruh input, termasuk yang dibiarkan
             * kosong. Dengan update apa adanya, siswa yang hanya melengkapi
             * dua kolom akan menghapus seluruh biodata lamanya — nama ayah,
             * alamat, hobi — tanpa peringatan apa pun. Mengisi bertahap adalah
             * pemakaian yang wajar untuk formulir mandiri seperti ini.
             */
            $student->update($this->buangIsianKosong($validated));

            return back()->with('success_public', 'Terima kasih! Biodata '.$student->name.' berhasil diperbarui.');
        }

        /*
         * Siswa yang mengetik namanya sendiri DICOCOKKAN, bukan ditolak.
         *
         * Daftar pilihan berbawaan "— Siswa Baru —", dan kenyataannya banyak
         * siswa melewatinya lalu langsung mengetik nama. Menolak mereka membuat
         * jalan buntu: sudah mengisi seluruh formulir, lalu tidak bisa
         * menyimpan sama sekali. Yang mereka maksud jelas — memperbarui data
         * dirinya — jadi itulah yang dilakukan.
         *
         * Penolakan disisakan hanya untuk kasus yang benar-benar rancu: dua
         * siswa berbeda dengan nama yang sama. Di situ hanya wali kelas yang
         * tahu mana yang benar.
         */
        $siswaKelas = $class->students()->get();

        /*
         * NIS/NISN dicocokkan lebih dulu, sebelum nama.
         *
         * Keduanya dikumpulkan formulir tetapi dulu sama sekali tidak dipakai
         * mencocokkan. Padahal itulah penanda yang pasti: siswa yang menuliskan
         * namanya sedikit berbeda dari data sekolah — "Renata Aulia" untuk
         * "Renata Aulia Priyanti" — lolos sebagai orang baru dan melahirkan
         * biodata kembar, walaupun NIS yang diisinya sudah jelas menunjuk
         * dirinya sendiri.
         */
        foreach (['nis', 'nisn'] as $penanda) {
            $nilai = trim((string) ($validated[$penanda] ?? ''));

            if ($nilai === '') {
                continue;
            }

            $cocokPenanda = $siswaKelas->filter(
                fn ($s) => trim((string) $s->{$penanda}) !== '' && trim((string) $s->{$penanda}) === $nilai
            );

            // Lebih dari satu berarti penandanya sendiri ganda di data sekolah;
            // di situ nama masih lebih bisa dipercaya daripada menebak.
            if ($cocokPenanda->count() === 1) {
                $student = $cocokPenanda->first();
                $student->update($this->buangIsianKosong($validated));

                return back()->with('success_public', 'Terima kasih! Biodata '.$student->name.' berhasil diperbarui.');
            }
        }

        $cocok = $siswaKelas
            ->filter(fn ($s) => $this->kunciNama($s->name) === $this->kunciNama($validated['name']));

        if ($cocok->count() > 1) {
            return back()
                ->withInput()
                ->withErrors([
                    'name' => 'Ada '.$cocok->count().' siswa bernama "'.$validated['name'].'" di kelas ini, '
                        .'jadi kami tidak bisa menentukan yang mana. Pilih nama Anda pada daftar "Pilih Nama" di atas, '
                        .'atau hubungi wali kelas.',
                ]);
        }

        if ($cocok->count() === 1) {
            $student = $cocok->first();
            $student->update($this->buangIsianKosong($validated));

            return back()->with('success_public', 'Terima kasih! Biodata '.$student->name.' berhasil diperbarui.');
        }

        $student = $class->students()->create($validated + [
            'user_id' => $class->user_id,
            'is_active' => true,
        ]);

        return back()->with('success_public', 'Terima kasih! Biodata '.$student->name.' berhasil disimpan.');
    }

    /**
     * Bentuk nama yang dipakai untuk mencocokkan, bukan untuk disimpan.
     *
     * Siswa menulis namanya dengan cara berbeda-beda setiap kali: "SALSA
     * VIRLYANA", "SALSA.VIRLYANA", "salsa  virlyana". Tanpa penyeragaman ini
     * ketiganya dianggap tiga orang berbeda, dan catatan gandanya lolos.
     */
    private function kunciNama(string $nama): string
    {
        $nama = mb_strtolower(trim($nama));
        // Tanda baca dijadikan spasi: titik dan koma lazim dipakai sebagai
        // pemisah kata pada nama yang diketik terburu-buru.
        $nama = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $nama);

        return trim(preg_replace('/\s+/', ' ', $nama));
    }

    /**
     * Sisakan hanya isian yang benar-benar diisi.
     *
     * null dan string kosong dianggap "tidak diisi". Angka 0 dan false TIDAK
     * termasuk — keduanya jawaban yang sah untuk jumlah saudara maupun
     * penerima KIP/PKH.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buangIsianKosong(array $data): array
    {
        return array_filter(
            $data,
            fn ($nilai) => $nilai !== null && $nilai !== '',
        );
    }

    /** Form refleksi karakter mandiri oleh siswa. */
    public function showReflectionForm(Classroom $class)
    {
        $students = $class->students()->where('is_active', true)->orderBy('name')->get();
        // Disaring ke pemilik kelas: halaman ini terbuka tanpa login, jadi
        // scope tenant pada model tidak punya pengguna untuk disandarkan.
        $dimensions = CharacterDimension::where('user_id', $class->user_id)
            ->where('is_active', true)->orderBy('sort_order')->get();

        return view('public.student_reflection_form', compact('class', 'students', 'dimensions'));
    }

    /** Simpan refleksi karakter mandiri siswa. */
    public function storeReflection(Request $request, Classroom $class)
    {
        $validated = $request->validate([
            /*
             * Disaring ke kelas & pemiliknya. Aturan exists polos menerima
             * siswa maupun dimensi milik sekolah lain, karena scope tenant
             * pada model tidak berlaku pada query mentah — dan halaman ini
             * terbuka tanpa login.
             */
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('class_id', $class->id),
            ],
            'character_dimension_id' => [
                'required',
                Rule::exists('character_dimensions', 'id')->where('user_id', $class->user_id),
            ],
            'self_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'what_went_well' => ['required', 'string'],
            'what_to_improve' => ['required', 'string'],
            'action_plan' => ['required', 'string'],
            'pesan_ortu' => ['nullable', 'string', 'max:1000'],
        ]);

        /*
         * forceFill, BUKAN create().
         *
         * user_id sengaja tidak masuk $fillable agar kepemilikan tidak bisa
         * dititipkan lewat request. Konsekuensinya create() membuangnya diam-
         * diam, dan trait BelongsToTenant tidak bisa menambalnya karena halaman
         * ini publik — tidak ada pengguna yang sedang login. Hasilnya refleksi
         * tersimpan dengan user_id kosong lalu tersaring habis oleh TenantScope:
         * siswa melihat "berhasil dikirim", wali kelas tidak pernah melihat
         * apa pun.
         */
        (new CharacterReflection)->forceFill([
            'user_id' => $class->user_id,
            'class_id' => $class->id,
            'student_id' => $validated['student_id'],
            'character_dimension_id' => $validated['character_dimension_id'],
            /*
             * Konstanta periode, BUKAN "Agustus 2026".
             *
             * Kolomnya varchar sehingga teks bebas tersimpan diam-diam, tetapi
             * seluruh sisa aplikasi — portal siswa maupun penyaring laporan —
             * memakai daily/weekly/monthly. Nilai bebas membuat refleksi dari
             * jalur publik ini tidak pernah cocok pada penyaringan mana pun.
             */
            'period' => CharacterReflection::PERIOD_MONTHLY,
            'reflection_date' => Carbon::now()->toDateString(),
            'self_rating' => $validated['self_rating'],
            'what_went_well' => $validated['what_went_well'],
            'what_to_improve' => $validated['what_to_improve'],
            'action_plan' => $validated['action_plan'],
            'pesan_ortu' => $validated['pesan_ortu'] ?? null,
            'status' => 'submitted',
            'submitted_at' => Carbon::now(),
        ])->save();

        return back()->with('success_public', 'Refleksi karakter Anda berhasil dikirim ke Wali Kelas!');
    }

    /** Kirim link form mandiri + kata-kata profesional langsung ke WhatsApp Grup. */
    public function shareBiodataWa(Request $request, Classroom $class)
    {
        $validated = $request->validate([
            'group_id' => ['nullable', 'string'],
            'custom_message' => ['nullable', 'string'],
        ]);

        $groupId = $validated['group_id'] ?? $class->parent_group_wa;

        if (blank($groupId)) {
            return back()->with('error', 'Grup WhatsApp belum dipilih atau belum terhubung ke kelas.');
        }

        $link = route('public.biodata.show', $class->tokenPublik());

        $defaultMessage = implode("\n", [
            "*PEMBERITAHUAN PENGISIAN BIODATA MANDIRI SISWA*",
            "Kelas: " . $class->name,
            "",
            "Assalamu'alaikum Wr. Wb. / Selamat Pagi Bapak/Ibu Orang Tua & Wali Siswa,",
            "",
            "Sehubungan dengan pembaruan data administrasi siswa Kelas " . $class->name . ", kami memohon kesediaan Bapak/Ibu atau siswa untuk melengkapi Form Biodata Mandiri melalui tautan resmi berikut:",
            "",
            "🔗 *Tautan Form Biodata Mandiri:*",
            $link,
            "",
            "Mohon data dapat diisi dengan teliti dan benar.",
            "Atas perhatian dan kerja samanya, kami ucapkan terima kasih.",
            "",
            "Hormat kami,",
            "Wali Kelas " . $class->name,
        ]);

        $message = !empty($validated['custom_message']) ? $validated['custom_message'] : $defaultMessage;

        try {
            SendWhatsAppMessage::dispatch($groupId, $message, $class->user_id, [
                'type' => 'biodata_share',
                'class_id' => $class->id,
            ]);
            return back()->with('success', 'Pesan pengisian biodata berhasil dikirimkan ke grup WhatsApp!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim ke WhatsApp: ' . $e->getMessage());
        }
    }
}
