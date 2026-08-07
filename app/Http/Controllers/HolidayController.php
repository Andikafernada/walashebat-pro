<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Kalender libur milik wali kelas.
 *
 * Pada tanggal yang tercakup di sini, scheduler TIDAK membuat sesi absensi
 * dan tidak mengirim WhatsApp. Baris libur nasional (user_id NULL) disiapkan
 * pengelola sistem dan hanya bisa dilihat, tidak bisa diubah pengguna.
 */
class HolidayController extends Controller
{
    public function index(): View
    {
        $mine = Holiday::where('user_id', Auth::id())
            ->orderBy('start_date')->get();

        $national = Holiday::whereNull('user_id')
            ->orderBy('start_date')->get();

        return view('holidays.index', compact('mine', 'national'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string', 'max:191'],
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        Holiday::create($data + ['user_id' => Auth::id()]);

        return back()->with('success', 'Libur ditambahkan. Absensi otomatis dilewati pada tanggal tersebut.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        // Libur nasional bukan milik pengguna; tidak boleh dihapus dari sini.
        abort_unless($holiday->user_id === Auth::id(), 403);

        $holiday->delete();

        return back()->with('success', 'Libur dihapus.');
    }
}
