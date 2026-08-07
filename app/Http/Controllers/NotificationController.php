<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Tampilkan semua notifikasi
     */
    public function index(Request $request): View
    {
        $query = auth()->user()->userNotifications();

        // Filter by read status
        if ($request->get('filter') === 'unread') {
            $query->unread();
        } elseif ($request->get('filter') === 'read') {
            $query->read();
        }

        $notifications = $query->paginate(20)->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $request->get('filter', 'all'),
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai dibaca
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Ensure notification belongs to current user
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Tandai semua notifikasi sebagai dibaca
     */
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Hapus notifikasi
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get unread count (for AJAX polling)
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'count' => auth()->user()->unreadCount(),
        ]);
    }

    /**
     * Get latest notifications (for dropdown)
     */
    public function latest(): JsonResponse
    {
        $notifications = auth()->user()->unreadNotifications()
            ->take(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'icon' => $n->icon,
                'color' => $n->color,
                'action_url' => $n->action_url,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => auth()->user()->unreadCount(),
        ]);
    }

    /**
     * Notification settings/preferences
     */
    public function settings(): View
    {
        $preference = auth()->user()->getOrCreateNotificationPreference();

        return view('notifications.settings', [
            'preference' => $preference,
        ]);
    }

    /**
     * Update notification preferences
     */
    /** Preferensi yang boleh diubah lewat endpoint ini. */
    private const PREFERENSI = [
        'push_enabled',
        'push_attendance_reminder',
        'push_new_violation',
        'push_low_cashbook',
        'push_daily_summary',
    ];

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate(
            array_fill_keys(self::PREFERENSI, ['nullable', 'boolean'])
        );

        /*
         * Hanya kunci yang BENAR-BENAR dikirim yang diperbarui.
         *
         * Checkbox yang tidak dicentang tidak ikut terkirim browser, jadi
         * formulir menyertakan input tersembunyi bernilai 0 untuk tiap
         * preferensi. Dengan begitu "matikan" sampai ke sini sebagai 0,
         * bukan sebagai ketiadaan — dulu preferensi hanya bisa dinyalakan,
         * tidak pernah bisa dimatikan.
         */
        $perubahan = [];

        foreach (self::PREFERENSI as $kunci) {
            if ($request->has($kunci)) {
                $perubahan[$kunci] = $request->boolean($kunci);
            }
        }

        /*
         * Permintaan tanpa satu pun preferensi dulu tetap dijawab
         * "berhasil disimpan" padahal tidak ada yang berubah — pesan sukses
         * yang berbohong. Lebih baik ditolak terang-terangan.
         */
        if ($perubahan === []) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada preferensi yang dikirim.',
            ], 422);
        }

        auth()->user()->getOrCreateNotificationPreference()->update($perubahan);

        return response()->json([
            'success' => true,
            'message' => 'Preferensi notifikasi berhasil disimpan.',
        ]);
    }

    /**
     * Save push subscription from browser
     */
    public function subscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys' => 'required|array',
        ]);

        $preference = auth()->user()->getOrCreateNotificationPreference();
        $preference->updatePushSubscription($validated['subscription']);

        return response()->json(['success' => true]);
    }

    /**
     * Unsubscribe push notifications
     */
    public function unsubscribePush(): JsonResponse
    {
        $preference = auth()->user()->getOrCreateNotificationPreference();
        $preference->removePushSubscription();

        return response()->json(['success' => true]);
    }
}
