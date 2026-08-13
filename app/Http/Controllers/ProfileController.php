<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\NomorWhatsApp;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // NIP wali kelas dipakai pada blok tanda tangan laporan resmi.
            'nip' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'whatsapp_number' => ['nullable', 'string', 'max:20', new NomorWhatsApp],
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:255'],
            // Kota untuk baris "Bandung, 25 Juli 2026" di atas tanda tangan.
            'school_city' => ['nullable', 'string', 'max:100'],
            'school_npsn' => ['nullable', 'string', 'max:20'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'principal_nip' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profil diperbarui. Laporan cetak akan memakai data terbaru.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini salah.',
            ]);
        }

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }
}
