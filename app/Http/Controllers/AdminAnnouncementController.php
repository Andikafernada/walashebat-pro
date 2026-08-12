<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengumuman operator ke semua guru — mis. jadwal maintenance atau perubahan
 * harga.
 *
 * Sengaja lewat notifikasi dalam-aplikasi (lonceng), BUKAN WhatsApp: satu blast
 * WA ke seluruh guru berbiaya nyata dan tak bisa ditarik kembali. Notifikasi
 * dalam-aplikasi gratis, muncul saat guru membuka aplikasi, dan tidak
 * mengganggu nomor pribadinya.
 */
class AdminAnnouncementController extends Controller
{
    public function form(): View
    {
        // Tabel users ter-scope tenant; tanpa lintasSeluruhTenant hitungannya 0.
        $jumlahGuru = TenantScope::lintasSeluruhTenant(
            fn () => User::where('role', User::ROLE_TEACHER)->count(),
        );

        return view('admin.announcements.form', compact('jumlahGuru'));
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $now = now();
        $terkirim = 0;

        /*
         * Bulk insert per potongan, bukan satu createForUser() per guru: pada
         * ribuan guru, ribuan INSERT terpisah membuat satu request ini timeout.
         * insert() melewati cast, jadi 'data' di-json_encode manual dan
         * timestamp diisi tangan.
         *
         * ponytail: potongan 500 baris; naikkan bila jumlah guru menembus puluhan ribu.
         */
        TenantScope::lintasSeluruhTenant(function () use ($data, $now, &$terkirim) {
            User::where('role', User::ROLE_TEACHER)
                ->select('id')
                ->chunkById(500, function ($guru) use ($data, $now, &$terkirim) {
                    $rows = $guru->map(fn ($g) => [
                        'user_id' => $g->id,
                        'type' => 'announcement',
                        'title' => $data['title'],
                        'body' => $data['body'],
                        'icon' => 'megaphone',
                        'color' => 'amber',
                        'action_url' => null,
                        'data' => json_encode([]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    Notification::insert($rows);
                    $terkirim += count($rows);
                });
        });

        return back()->with('success', "Pengumuman terkirim ke {$terkirim} guru.");
    }
}
