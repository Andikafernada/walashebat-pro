<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppMessage;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentExcuse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PublicStudentFormController extends Controller
{
    /** Form pengisian biodata mandiri siswa oleh orang tua/siswa. */
    public function showBiodataForm(Classroom $class)
    {
        $students = $class->students()->where('is_active', true)->orderBy('name')->get();

        return view('public.student_biodata_form', compact('class', 'students'));
    }

    /** Simpan biodata mandiri siswa. */
    public function storeBiodata(Request $request, Classroom $class)
    {
        $hasStudentId = $request->filled('student_id');

        $validated = $request->validate([
            'student_id' => [
                $hasStudentId ? 'required' : 'nullable',
                Rule::exists('students', 'id')->where('class_id', $class->id),
            ],
            'name' => [$hasStudentId ? 'nullable' : 'required', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50'],
            'nisn' => ['nullable', 'string', 'max:50'],
            'gender' => [$hasStudentId ? 'nullable' : 'required', 'in:L,P'],
            'pob' => ['nullable', 'string', 'max:191'],
            'dob' => ['nullable', 'date'],
            'tempat_lahir' => ['nullable', 'string', 'max:191'],
            'tanggal_lahir' => ['nullable', 'date'],
            'nik' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'string', 'max:191'],
            'agama' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'rt_rw' => ['nullable', 'string', 'max:50'],
            'subdistrict' => ['nullable', 'string', 'max:191'],
            'kelurahan' => ['nullable', 'string', 'max:191'],
            'district' => ['nullable', 'string', 'max:191'],
            'kecamatan' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:191'],
            'nama_ayah' => ['nullable', 'string', 'max:191'],
            'father_nik' => ['nullable', 'string', 'max:50'],
            'father_job' => ['nullable', 'string', 'max:191'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:191'],
            'father_income' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:191'],
            'nama_ibu' => ['nullable', 'string', 'max:191'],
            'mother_nik' => ['nullable', 'string', 'max:50'],
            'mother_job' => ['nullable', 'string', 'max:191'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:191'],
            'mother_income' => ['nullable', 'string', 'max:100'],
            'guardian_name' => ['nullable', 'string', 'max:191'],
            'nama_wali' => ['nullable', 'string', 'max:191'],
            'guardian_job' => ['nullable', 'string', 'max:191'],
            'pekerjaan_wali' => ['nullable', 'string', 'max:191'],
            'parent_phone' => ['nullable', 'string', 'max:50'],
            'family_status' => ['nullable', 'string', 'max:50'],
            'child_number' => ['nullable', 'integer', 'min:1'],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0'],
            'school_origin' => ['nullable', 'string', 'max:191'],
            'asal_sekolah' => ['nullable', 'string', 'max:191'],
            'height' => ['nullable', 'integer', 'min:50', 'max:250'],
            'tinggi_badan_cm' => ['nullable', 'integer', 'min:50', 'max:250'],
            'weight' => ['nullable', 'integer', 'min:10', 'max:200'],
            'berat_badan_kg' => ['nullable', 'integer', 'min:10', 'max:200'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'jarak_rumah_km' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'moda_transportasi' => ['nullable', 'string', 'max:191'],
            'golongan_darah' => ['nullable', 'string', 'max:20'],
            'hobi' => ['nullable', 'string', 'max:191'],
            'cita_cita' => ['nullable', 'string', 'max:191'],
            'penerima_kip' => ['nullable', 'boolean'],
            'penerima_pkh' => ['nullable', 'boolean'],
            'alamat_ortu' => ['nullable', 'string', 'max:500'],
            'tahun_masuk' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'travel_time_minutes' => ['nullable', 'integer', 'min:0', 'max:999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($hasStudentId) {
            $student = Student::where('class_id', $class->id)->findOrFail($validated['student_id']);
        } else {
            $student = new Student();
            $student->class_id = $class->id;
            $student->user_id = $class->user_id;
            $student->name = $validated['name'];
            $student->gender = $validated['gender'] ?? 'L';
            $student->is_active = true;
        }

        $fillable = array_filter($validated, fn($val) => !is_null($val) && $val !== '');
        unset($fillable['student_id']);
        if (!$hasStudentId) {
            unset($fillable['name']);
        }

        $student->fill($fillable);
        $student->save();

        return back()->with('success_public', 'Terima kasih! Biodata ' . $student->name . ' berhasil ' . ($hasStudentId ? 'diperbarui' : 'disimpan') . '.');
    }

    /** Bagikan tautan form biodata mandiri via WhatsApp. */
    public function shareBiodataWa(Request $request, Classroom $class)
    {
        $validated = $request->validate([
            'group_id' => ['required', 'string'],
        ]);

        $url = route('public.biodata.show', $class->tokenPublik());
        $pesan = "📢 *Form Biodata Mandiri Siswa - {$class->name}*\n\n"
            . "Kepada Bapak/Ibu Orang Tua/Wali Siswa,\n"
            . "Mohon untuk melengkapi data biodata siswa melalui tautan resmi berikut:\n\n"
            . "👉 {$url}\n\n"
            . "Terima kasih.";

        SendWhatsAppMessage::dispatch(
            to: $validated['group_id'],
            message: $pesan,
            userId: (int) auth()->id(),
        );

        return back()->with('success', 'Tautan formulir biodata mandiri berhasil dikirim ke grup WhatsApp.');
    }

    /** Form refleksi karakter P5 oleh siswa. */
    public function showReflectionForm(Classroom $class)
    {
        $students = $class->students()->where('is_active', true)->orderBy('name')->get();
        $dimensions = \App\Models\CharacterDimension::orderBy('name')->get();

        return view('public.character_reflection_form', compact('class', 'students', 'dimensions'));
    }

    /** Simpan refleksi karakter P5. */
    public function storeReflection(Request $request, Classroom $class)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('class_id', $class->id),
            ],
            'character_dimension_id' => [
                'required',
                Rule::exists('character_dimensions', 'id'),
            ],
            'self_rating' => ['required', 'integer', 'between:1,5'],
            'what_went_well' => ['required', 'string', 'max:1000'],
            'what_to_improve' => ['required', 'string', 'max:1000'],
            'action_plan' => ['required', 'string', 'max:1000'],
            'pesan_ortu' => ['nullable', 'string', 'max:1000'],
            'kesan_teman' => ['nullable', 'string', 'max:1000'],
        ]);

        (new CharacterReflection)->forceFill([
            'user_id' => $class->user_id,
            'class_id' => $class->id,
            'student_id' => $validated['student_id'],
            'character_dimension_id' => $validated['character_dimension_id'],
            'period' => CharacterReflection::PERIOD_MONTHLY,
            'reflection_date' => Carbon::now()->toDateString(),
            'self_rating' => $validated['self_rating'],
            'what_went_well' => $validated['what_went_well'],
            'what_to_improve' => $validated['what_to_improve'],
            'action_plan' => $validated['action_plan'],
            'pesan_ortu' => $validated['pesan_ortu'] ?? null,
            'kesan_teman' => $validated['kesan_teman'] ?? null,
            'status' => 'submitted',
            'submitted_at' => Carbon::now(),
        ])->save();

        return back()->with('success_public', 'Refleksi karakter Anda berhasil dikirim ke Wali Kelas!');
    }

    /** Form laporan izin/sakit oleh orang tua. */
    public function showExcuseForm(Classroom $class)
    {
        $students = $class->students()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'parent_phone'])
            ->map(function ($s) {
                $rawPhone = preg_replace('/\D/', '', $s->parent_phone ?? '');
                $last4 = strlen($rawPhone) >= 4 ? substr($rawPhone, -4) : null;
                $masked = strlen($rawPhone) >= 7 
                    ? substr($rawPhone, 0, 4) . '-xxxx-' . $last4 
                    : ($last4 ? 'xxxx-' . $last4 : 'Belum Terdaftar');

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nis' => $s->nis ?: '-',
                    'has_phone' => !empty($last4),
                    'masked_phone' => $masked,
                    'last4' => $last4,
                ];
            });

        return view('public.student_excuse_form', compact('class', 'students'));
    }

    /** Simpan laporan izin/sakit. */
    public function storeExcuse(Request $request, Classroom $class)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('class_id', $class->id),
            ],
            'tanggal' => [
                'required', 'date',
                'after_or_equal:'.now()->subDays(2)->toDateString(),
                'before_or_equal:'.now()->addDays(3)->toDateString(),
            ],
            'jenis' => ['required', 'in:izin,sakit'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'parent_phone_last4' => ['nullable', 'string', 'max:4'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'tanggal.after_or_equal' => 'Tanggal terlalu lama berlalu. Hubungi wali kelas langsung untuk tanggal itu.',
            'tanggal.before_or_equal' => 'Tanggal terlalu jauh di depan.',
            'attachment.max' => 'Ukuran foto bukti maksimal 5 MB.',
            'attachment.mimes' => 'Format foto bukti harus JPG, PNG, WEBP, atau PDF.',
        ]);

        $siswa = Student::where('class_id', $class->id)->findOrFail($validated['student_id']);

        if ($siswa->parent_phone) {
            $rawPhone = preg_replace('/\D/', '', $siswa->parent_phone);
            $realLast4 = strlen($rawPhone) >= 4 ? substr($rawPhone, -4) : null;
            if ($realLast4 && $validated['parent_phone_last4'] !== $realLast4) {
                return back()->withErrors(['parent_phone_last4' => '4 digit terakhir nomor WhatsApp tidak sesuai dengan data sekolah.'])->withInput();
            }
        }

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('excuses', 'public');
        }

        $excuse = StudentExcuse::create([
            'user_id' => $class->user_id,
            'class_id' => $class->id,
            'student_id' => $siswa->id,
            'tanggal' => $validated['tanggal'],
            'jenis' => $validated['jenis'],
            'keterangan' => $validated['keterangan'] ?? null,
            'attachment_path' => $path,
            'parent_phone_verified' => true,
        ]);

        $guru = $class->user;
        if ($guru && $guru->whatsapp_number) {
            $pesan = "📢 *Laporan Izin/Sakit Siswa Baru*\n\n"
                . "Kelas: {$class->name}\n"
                . "Siswa: {$siswa->name}\n"
                . "Tanggal: " . Carbon::parse($validated['tanggal'])->translatedFormat('d M Y') . "\n"
                . "Jenis: " . ucfirst($validated['jenis']) . "\n"
                . "Alasan: " . ($validated['keterangan'] ?: '-') . "\n\n"
                . "Silakan periksa di menu Presensi > Laporan Orang Tua.";

            SendWhatsAppMessage::dispatch(
                to: (string) $guru->whatsapp_number,
                message: $pesan,
                userId: (int) $guru->id,
            );
        }

        return back()->with('success_public', 'Laporan ' . $validated['jenis'] . ' untuk ' . $siswa->name . ' telah berhasil dikirim ke Wali Kelas.');
    }
}
