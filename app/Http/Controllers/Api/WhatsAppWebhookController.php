<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gateway memberitahu aplikasi saat sesi WhatsApp seorang guru berubah.
 *
 * Tanpa ini, aplikasi baru tahu sesi putus ketika pengiriman gagal — yaitu
 * tepat saat absensi dibutuhkan. Dengan dorongan status dari gateway, guru
 * bisa diperingatkan lebih dulu di dashboard.
 *
 * Diamankan dengan secret yang sama seperti webhook keluar. Dibandingkan
 * memakai hash_equals agar tidak bocor lewat perbedaan waktu.
 */
class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('walikelas.whatsapp.n8n.secret');
        $given = (string) $request->header('X-Webhook-Secret', '');

        abort_if($expected === '' || ! hash_equals($expected, $given), 403);

        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:disconnected,pairing,connected'],
            'error' => ['nullable', 'string', 'max:200'],
        ]);

        $user = User::where('wa_session_id', $data['session_id'])->first();

        if (! $user) {
            return response()->json(['ok' => false, 'reason' => 'unknown_session'], 404);
        }

        $user->catatStatusSesi($data['status'], $data['error'] ?? null);

        return response()->json(['ok' => true]);
    }
}
