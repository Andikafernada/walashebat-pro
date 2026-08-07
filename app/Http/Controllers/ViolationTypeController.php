<?php

namespace App\Http\Controllers;

use App\Models\ViolationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViolationTypeController extends Controller
{
    public function index(): View
    {
        $types = ViolationType::orderBy('name')->paginate(30);

        return view('violations.types', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'category' => ['nullable', 'in:ringan,sedang,berat,penghargaan'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        /*
         * Tandanya ditentukan di sini, bukan diserahkan kepada pengisi.
         *
         * Formulirnya meminta "Bobot Poin" dan hanya menerima angka positif
         * (min:1), sedangkan poin kedisiplinan siswa dimulai dari 100 lalu
         * DIKURANGI selisih pelanggaran. Selama tandanya tidak dibalik di sini,
         * setiap jenis yang dibuat lewat halaman ini justru MENAMBAH poin:
         * mencatat "Terlambat, bobot 5" menaikkan poin siswa dari 100 ke 105,
         * badge-nya tetap hijau, dan tak seorang pun pernah masuk daftar perlu
         * perhatian. Hanya kategori penghargaan yang memang menambah.
         */
        $data['points'] = ($data['category'] ?? null) === 'penghargaan'
            ? abs($data['points'])
            : -abs($data['points']);

        ViolationType::create($data);

        return back()->with('success', 'Jenis pelanggaran ditambahkan.');
    }

    public function destroy(ViolationType $type): RedirectResponse
    {
        $type->delete();

        return back()->with('success', 'Jenis pelanggaran dihapus.');
    }
}
