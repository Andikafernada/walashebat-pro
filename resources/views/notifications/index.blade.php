@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Notifikasi</span>
            </nav>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-slate-500">Semua pemberitahuan untuk Anda</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.settings') }}" class="btn-secondary btn-secondary--sm">Pengaturan</a>
            <button id="markAllRead" class="btn-ghost btn-ghost--sm">Tandai semua dibaca</button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-5 border-b border-slate-200 text-sm">
        @foreach (['all' => 'Semua', 'unread' => 'Belum Dibaca', 'read' => 'Dibaca'] as $key => $label)
            <a href="{{ route('notifications.index', ['filter' => $key]) }}"
               @if ($filter === $key) aria-current="page" @endif
               class="-mb-px border-b-2 pb-2 font-medium {{ $filter === $key ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Notifications List -->
    <div class="blok">
        <div class="divide-y divide-slate-200">
            @forelse($notifications as $notification)
            <div class="p-4 transition-colors hover:bg-slate-50 {{ $notification->isRead() ? 'opacity-75' : '' }}"
                 data-notification-id="{{ $notification->id }}">
                <div class="flex gap-3">
                    <!-- Kode margin: warna menandakan sifatnya, hurufnya jenisnya -->
                    <span class="kode mt-0.5 {{ ['emerald' => 'kode--hadir', 'rose' => 'kode--alfa', 'amber' => 'kode--izin'][$notification->color] ?? 'kode--aksen' }}"
                          aria-hidden="true">{{ ['alert' => '!', 'check' => '✓', 'bell' => '•'][$notification->icon] ?? 'i' }}</span>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-medium text-slate-900 {{ $notification->isRead() ? '' : 'font-semibold' }}">
                                    {{ $notification->title }}
                                </p>
                                <p class="text-sm text-slate-600 mt-1">{{ $notification->body }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs text-slate-400 whitespace-nowrap">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                                @if(!$notification->isRead())
                                <button onclick="markAsRead({{ $notification->id }})" class="btn-ghost btn-ghost--sm">Tandai dibaca</button>
                                @endif
                            </div>
                        </div>
                        @if($notification->action_url)
                        <a href="{{ $notification->action_url }}"
                           onclick="markAsRead({{ $notification->id }})"
                           class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Lihat detail &rarr;</a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state border-0">
                <p class="empty-state__title">Tidak ada notifikasi</p>
                <p class="empty-state__description">Notifikasi akan muncul di sini</p>
            </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/read`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const el = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (el) {
                el.classList.add('opacity-75');
                el.querySelector('p.font-semibold')?.classList.remove('font-semibold');
                el.querySelector('button[onclick*="markAsRead"]')?.remove();
            }
            updateNotificationCount();
        }
    });
}

document.getElementById('markAllRead')?.addEventListener('click', function() {
    fetch('/notifications/read-all', {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
});

function updateNotificationCount() {
    fetch('/notifications/count')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('#notification-bell .notification-badge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        });
}
</script>
@endpush
@endsection
