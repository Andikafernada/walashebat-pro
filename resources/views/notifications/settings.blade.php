@extends('layouts.app')

@section('title', 'Pengaturan Notifikasi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('notifications.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Pengaturan Notifikasi</h2>
            <p class="text-slate-500 mt-1">Kelola preferensi notifikasi Anda</p>
        </div>
    </div>

    <form id="notification-form" class="space-y-6">
        @csrf

        <!-- Push Notifications -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="font-semibold text-slate-900">Push Notifications</h3>
                <p class="text-sm text-slate-500 mt-1">Notifikasi browser untuk memberi tahu Anda secara real-time</p>
            </div>
            <div class="p-6 space-y-6">
                <!-- Enable Push -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">Aktifkan Push Notifications</p>
                        <p class="text-sm text-slate-500">Terima notifikasi di browser</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        {{-- Pasangan tersembunyi: checkbox yang tidak dicentang tidak dikirim
     browser, sehingga preferensi mustahil dimatikan tanpa ini. --}}
<input type="hidden" name="push_enabled" value="0">
<input type="checkbox" name="push_enabled" value="1" {{ $preference->push_enabled ? 'checked' : '' }}
                               class="sr-only peer" onchange="togglePushSettings(this.checked)">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div id="push-settings" class="{{ $preference->push_enabled ? '' : 'opacity-50 pointer-events-none' }}">
                    <!-- Browser Push Subscription -->
                    <div class="p-4 bg-slate-50 rounded mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-slate-900">Status Browser</p>
                                <p class="text-sm text-slate-500" id="push-status">
                                    @if($preference->hasPushSubscription())
                                        <span class="text-emerald-600">● Terhubung</span>
                                    @else
                                        <span class="text-slate-500">Belum terhubung</span>
                                    @endif
                                </p>
                            </div>
                            @if($preference->hasPushSubscription())
                                <button type="button" onclick="unsubscribePush()"
                                        class="px-3 py-1.5 text-sm text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                    Putuskan
                                </button>
                            @else
                                <button type="button" onclick="subscribePush()"
                                        class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                    Aktifkan
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Notification Types -->
                    <div class="space-y-4">
                        <p class="text-sm font-medium text-slate-700">Jenis Notifikasi</p>

                        <div class="flex items-center justify-between py-3 border-b border-slate-200">
                            <div>
                                <p class="font-medium text-slate-900">Pengingat Absensi</p>
                                <p class="text-sm text-slate-500">Notifikasi untuk mengisi absensi</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                {{-- Pasangan tersembunyi: checkbox yang tidak dicentang tidak dikirim
     browser, sehingga preferensi mustahil dimatikan tanpa ini. --}}
<input type="hidden" name="push_attendance_reminder" value="0">
<input type="checkbox" name="push_attendance_reminder" value="1" {{ $preference->push_attendance_reminder ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between py-3 border-b border-slate-200">
                            <div>
                                <p class="font-medium text-slate-900">Pelanggaran Baru</p>
                                <p class="text-sm text-slate-500">Notifikasi saat ada pelanggaran siswa</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                {{-- Pasangan tersembunyi: checkbox yang tidak dicentang tidak dikirim
     browser, sehingga preferensi mustahil dimatikan tanpa ini. --}}
<input type="hidden" name="push_new_violation" value="0">
<input type="checkbox" name="push_new_violation" value="1" {{ $preference->push_new_violation ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between py-3 border-b border-slate-200">
                            <div>
                                <p class="font-medium text-slate-900">Kas Kelas Rendah</p>
                                <p class="text-sm text-slate-500">Notifikasi saat kas kelas hampir habis</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                {{-- Pasangan tersembunyi: checkbox yang tidak dicentang tidak dikirim
     browser, sehingga preferensi mustahil dimatikan tanpa ini. --}}
<input type="hidden" name="push_low_cashbook" value="0">
<input type="checkbox" name="push_low_cashbook" value="1" {{ $preference->push_low_cashbook ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="font-medium text-slate-900">Ringkasan Harian</p>
                                <p class="text-sm text-slate-500">Kirim ringkasan setiap sore</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                {{-- Pasangan tersembunyi: checkbox yang tidak dicentang tidak dikirim
     browser, sehingga preferensi mustahil dimatikan tanpa ini. --}}
<input type="hidden" name="push_daily_summary" value="0">
<input type="checkbox" name="push_daily_summary" value="1" {{ $preference->push_daily_summary ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-1 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-colors peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('notifications.index') }}" class="px-6 py-2.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded transition-colors">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded hover:bg-indigo-700 transition-colors">
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
        // Jangan diam saat ditolak: dulu cabang gagal tidak menampilkan apa pun,
        // sehingga penyimpanan yang gagal terlihat sama dengan tidak terjadi apa-apa.
        if (ok && data.success) {
            showToast('Preferensi berhasil disimpan!', 'success');
        } else {
            showToast(data.message || 'Preferensi gagal disimpan', 'error');
        }
    })
    .catch(error => {
        showToast('Terjadi kesalahan', 'error');
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
        showToast('Browser ini tidak mendukung push notifications', 'error');
        return;
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        showToast('Izin notifikasi diperlukan', 'error');
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

        document.getElementById('push-status').innerHTML = '<span class="text-emerald-600">● Terhubung</span>';
        document.querySelector('#push-settings button[onclick*="subscribePush"]').outerHTML =
            '<button type="button" onclick="unsubscribePush()" class="px-3 py-1.5 text-sm text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">Putuskan</button>';

        showToast('Push notification berhasil diaktifkan!', 'success');
    } catch (error) {
        console.error('Push subscription error:', error);
        showToast('Gagal mengaktifkan push notification', 'error');
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

        document.getElementById('push-status').innerHTML = '<span class="text-slate-500">Belum terhubung</span>';
        document.querySelector('#push-settings button[onclick*="unsubscribePush"]').outerHTML =
            '<button type="button" onclick="subscribePush()" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Aktifkan</button>';

        showToast('Push notification dinonaktifkan', 'success');
    } catch (error) {
        showToast('Gagal menonaktifkan push notification', 'error');
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

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded  z-50  ${
        type === 'success' ? 'bg-emerald-600 text-white' :
        type === 'error' ? 'bg-rose-600 text-white' :
        'bg-slate-800 text-white'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>
@endpush
@endsection
