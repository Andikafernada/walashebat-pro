@extends('layouts.app')

@section('title', 'Pengaturan Notifikasi')

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('notifications.index') }}" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Pengaturan Notifikasi</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola preferensi notifikasi Anda</p>
        </div>
    </div>

    <form id="notification-form" class="space-y-6">
        @csrf

        <!-- Push Notifications -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-emerald-100">
                <h3 class="text-sm font-extrabold text-slate-900">Push Notifications</h3>
                <p class="text-xs text-slate-500 mt-0.5">Notifikasi browser untuk memberi tahu Anda secara real-time</p>
            </div>
            <div class="p-5 space-y-5">
                <!-- Enable Push -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-900">Aktifkan Push Notifications</p>
                        <p class="text-xs text-slate-500">Terima notifikasi di browser</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="push_enabled" value="0">
                        <input type="checkbox" name="push_enabled" value="1" {{ $preference->push_enabled ? 'checked' : '' }}
                               class="sr-only peer" onchange="togglePushSettings(this.checked)">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div id="push-settings" class="{{ $preference->push_enabled ? '' : 'opacity-50 pointer-events-none' }}">
                    <!-- Browser Push Subscription -->
                    <div class="p-4 bg-emerald-50/40 rounded-2xl border border-emerald-100 mb-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-900">Status Browser</p>
                                <p class="text-xs text-slate-500 mt-0.5" id="push-status">
                                    @if($preference->hasPushSubscription())
                                        <span class="text-emerald-700 font-bold">● Terhubung</span>
                                    @else
                                        <span class="text-slate-400">Belum terhubung</span>
                                    @endif
                                </p>
                            </div>
                            @if($preference->hasPushSubscription())
                                <button type="button" onclick="unsubscribePush()" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                                    Putuskan
                                </button>
                            @else
                                <button type="button" onclick="subscribePush()" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                                    Aktifkan
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Individual Settings -->
                    <div class="divide-y divide-slate-100">
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="text-xs font-bold text-slate-900">Siswa Alfa</p>
                                <p class="text-[11px] text-slate-500">Notifikasi saat ada siswa alfa</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="push_student_alpha" value="0">
                                <input type="checkbox" name="push_student_alpha" value="1" {{ $preference->push_student_alpha ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="text-xs font-bold text-slate-900">Pelanggaran Berat</p>
                                <p class="text-[11px] text-slate-500">Notifikasi untuk pelanggaran kategori berat</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="push_violation_heavy" value="0">
                                <input type="checkbox" name="push_violation_heavy" value="1" {{ $preference->push_violation_heavy ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="text-xs font-bold text-slate-900">Peringatan Kas Rendah</p>
                                <p class="text-[11px] text-slate-500">Notifikasi saat kas kelas hampir habis</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="push_low_cashbook" value="0">
                                <input type="checkbox" name="push_low_cashbook" value="1" {{ $preference->push_low_cashbook ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="text-xs font-bold text-slate-900">Ringkasan Harian</p>
                                <p class="text-[11px] text-slate-500">Kirim ringkasan setiap sore</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="push_daily_summary" value="0">
                                <input type="checkbox" name="push_daily_summary" value="1" {{ $preference->push_daily_summary ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('notifications.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('notification-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('{{ route("notifications.settings.update") }}', {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(response => response.json().then(data => ({ ok: response.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.success) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Preferensi berhasil disimpan!', type: 'success' } }));
        } else {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Preferensi gagal disimpan', type: 'error' } }));
        }
    })
    .catch(error => {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Terjadi kesalahan koneksi.', type: 'error' } }));
    });
});

function togglePushSettings(enabled) {
    const settings = document.getElementById('push-settings');
    if (enabled) {
        settings.classList.remove('opacity-50', 'pointer-events-none');
    } else {
        settings.classList.add('opacity-50', 'pointer-events-none');
    }
}

async function subscribePush() {
    if (!('Notification' in window)) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Browser ini tidak mendukung push notifications.', type: 'error' } }));
        return;
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Izin notifikasi diperlukan.', type: 'error' } }));
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array('{{ config('services.webpush.vapid_public_key') }}')
        });

        await fetch('{{ route("notifications.push.subscribe") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ subscription: subscription.toJSON() })
        });

        document.getElementById('push-status').innerHTML = '<span class="text-emerald-700 font-bold">● Terhubung</span>';
        document.querySelector('#push-settings button[onclick*="subscribePush"]').outerHTML =
            '<button type="button" onclick="unsubscribePush()" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">Putuskan</button>';

        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Push notification berhasil diaktifkan!', type: 'success' } }));
    } catch (error) {
        console.error('Push subscription error:', error);
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Gagal mengaktifkan push notification.', type: 'error' } }));
    }
}

async function unsubscribePush() {
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await subscription.unsubscribe();
        }

        await fetch('{{ route("notifications.push.unsubscribe") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        });

        document.getElementById('push-status').innerHTML = '<span class="text-slate-400">Belum terhubung</span>';
        document.querySelector('#push-settings button[onclick*="unsubscribePush"]').outerHTML =
            '<button type="button" onclick="subscribePush()" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">Aktifkan</button>';

        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Push notification dinonaktifkan.', type: 'success' } }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Gagal menonaktifkan push notification.', type: 'error' } }));
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
</script>
@endpush
@endsection
