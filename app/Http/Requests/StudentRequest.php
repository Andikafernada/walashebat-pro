<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Satu FormRequest untuk seluruh data siswa.
 *
 * StudentProfileRequest yang terpisah sudah tidak dipakai lagi: dua request
 * untuk satu formulir berarti dua daftar aturan yang harus dijaga tetap sinkron,
 * dan salah satunya pasti tertinggal.
 */
class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Tenant scope + route binding sudah membatasi akses.
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'penerima_kip' => $this->boolean('penerima_kip'),
            'penerima_pkh' => $this->boolean('penerima_pkh'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    public function rules(): array
    {
        $classId = $this->route('class')?->id ?? $this->input('class_id');
        $studentId = $this->route('student')?->id;

        return [
            // Catatan: 'name' tidak divalidasi unik per-kelas karena nama siswa
            // bisa sama (misal: dua siswa bernama "Ahmad" di kelas yang sama).
            // Untuk identifikasi unik, gunakan NIS.
            'name' => ['required', 'string', 'max:255'],
            'nis' => [
                'nullable', 'string', 'max:50',
                Rule::unique('students', 'nis')
                    ->where('class_id', $classId)
                    ->whereNull('deleted_at') // NIS siswa terarsip boleh dipakai ulang
                    ->ignore($studentId),
            ],
            'nisn' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],

            // Data pribadi
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'agama' => ['nullable', Rule::in(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'])],
            'anak_ke' => ['nullable', 'integer', 'min:1', 'max:20'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0', 'max:20'],
            'golongan_darah' => ['nullable', Rule::in(['A', 'B', 'AB', 'O', 'Tidak Tahu'])],
            'tinggi_badan_cm' => ['nullable', 'integer', 'min:50', 'max:250'],
            'berat_badan_kg' => ['nullable', 'integer', 'min:10', 'max:200'],

            // Domisili
            'rt_rw' => ['nullable', 'string', 'max:10'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'jarak_rumah_km' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'moda_transportasi' => ['nullable', 'string', 'max:50'],

            // Orang tua / wali
            'nama_ayah' => ['nullable', 'string', 'max:100'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:100'],
            'nama_ibu' => ['nullable', 'string', 'max:100'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:100'],
            'nama_wali' => ['nullable', 'string', 'max:100'],
            'pekerjaan_wali' => ['nullable', 'string', 'max:100'],
            'alamat_ortu' => ['nullable', 'string', 'max:500'],

            // Riwayat sekolah
            'asal_sekolah' => ['nullable', 'string', 'max:150'],
            'tahun_masuk' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'kompetensi_keahlian' => ['nullable', 'string', 'max:100'],

            // Lain-lain
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hobi' => ['nullable', 'string', 'max:150'],
            'cita_cita' => ['nullable', 'string', 'max:150'],
            'penerima_kip' => ['boolean'],
            'penerima_pkh' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'parent_phone' => 'HP orang tua',
            'phone' => 'HP siswa',
            'address' => 'alamat',
            'tinggi_badan_cm' => 'tinggi badan',
            'berat_badan_kg' => 'berat badan',
            'foto' => 'foto siswa',
        ];
    }

    public function messages(): array
    {
        return [
            'nis.unique' => 'NIS ini sudah dipakai siswa lain di kelas yang sama.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}
