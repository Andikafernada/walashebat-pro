<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return ['homeroom_wa.regex' => 'Nomor WhatsApp hanya boleh angka, contoh: 6281234567890.'];
    }

    /**
     * Buang kolom mapel yang dibiarkan kosong.
     *
     * Formulir selalu mengirim kedua kolomnya, termasuk yang tidak diisi.
     * Tanpa pembersihan ini daftar mapel berisi string kosong, dan pilihan
     * kosong itu ikut muncul di formulir presensi sebagai baris tanpa nama.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('mapel')) {
            return;
        }

        $this->merge([
            'mapel' => array_values(array_filter(
                array_map('trim', (array) $this->input('mapel', [])),
                fn ($m) => $m !== '',
            )),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'jenis' => ['sometimes', 'in:perwalian,ajar'],
            /*
             * Mapel yang diampu. Jumlahnya TIDAK dipatok dua meski formulir
             * menyediakan dua kolom: banyaknya mapel adalah keputusan jadwal
             * sekolah yang berubah tiap semester, dan batas keras di validasi
             * hanya akan menghalangi tanpa melindungi apa pun.
             */
            'mapel' => ['sometimes', 'array', 'max:10'],
            'mapel.*' => ['nullable', 'string', 'max:100'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'major' => ['nullable', 'string', 'max:100'],
            'homeroom_wa' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]*$/'],
            'auto_attendance' => ['sometimes', 'boolean'],
            'parent_group_wa' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
