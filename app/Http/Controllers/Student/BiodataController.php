<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class BiodataController extends Controller
{
    public function show()
    {
        $student = Auth::guard('student')->user();
        return view('student.biodata.show', ['student' => $student]);
    }

    public function edit()
    {
        $student = Auth::guard('student')->user();
        return view('student.biodata.edit', ['student' => $student]);
    }

    public function update(Request $request)
    {
        $student = Auth::guard('student')->user();
        $data = $request->validate([
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'agama' => ['nullable', Rule::in(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'])],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0'],
            'golongan_darah' => ['nullable', Rule::in(['A', 'B', 'AB', 'O', 'Tidak Tahu'])],
            'tinggi_badan_cm' => ['nullable', 'integer', 'min:50', 'max:250'],
            'berat_badan_kg' => ['nullable', 'integer', 'min:20', 'max:300'],
            // Kolomnya bernama `address`. `alamat` tidak pernah ada di tabel,
            // sehingga lolos validasi lalu dibuang mass-assignment tanpa jejak.
            'address' => ['nullable', 'string', 'max:500'],
            'rt_rw' => ['nullable', 'string', 'max:10'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'jarak_rumah_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'moda_transportasi' => ['nullable', 'string', 'max:100'],
            'nama_ayah' => ['nullable', 'string', 'max:100'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:100'],
            'nama_ibu' => ['nullable', 'string', 'max:100'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:100'],
            'alamat_ortu' => ['nullable', 'string', 'max:500'],
            'parent_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'hobi' => ['nullable', 'string', 'max:100'],
            'cita_cita' => ['nullable', 'string', 'max:100'],
            'asal_sekolah' => ['nullable', 'string', 'max:191'],
        ]);

        $student->update($data);
        return redirect()->back()->with('success', 'Biodata berhasil diperbarui.');
    }

    public function showPassword()
    {
        return view('student.biodata.password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:student'],
            'password' => ['required', 'confirmed'],
        ]);

        $student = Auth::guard('student')->user();
        $student->update(['password' => Hash::make($data['password'])]);
        return redirect()->route('student.dashboard')->with('success', 'Password berhasil diubah.');
    }
}
