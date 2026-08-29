@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header -->
    <div class="page-header">
        <div>
            <nav class="text-xs font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Notifikasi</span>
            </nav>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-xs text-slate-500">Semua pemberitahuan untuk Anda</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.settings') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Pengaturan</a>
            <button id="markAllRead" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Tandai semua dibaca</button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-5 border-b border-slate-200 text-xs font-bold">
        @foreach (['all' => 'Semua', 'unread' => 'Belum Dibaca', 'read' => 'Dibaca'] as $key => $label)
            <a href="{{ route('notifications.index', ['filter' => $key]) }}"
               @if ($filter === $key) aria-current="page" @endif
               class="-mb-px border-b-2 pb-2.5 transition-colors {{ $filter === $key ? 'border-emerald-600 text-emerald-800 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
            <div class="p-4 transition-colors hover:bg-slate-50/50 {{ $notification->isRead() ? 'opacity-70' : '' }}"
                 data-notification-id="{{ $notification->id }}">
                <div class="flex gap-3">
                    <span class="flex h-7 w-7 items-center justify-center rounded-xl font-bold text-xs shrink-0 {{ ['emerald' => 'bg-emerald-100 text-emerald-800', 'rose' => 'bg-rose-100 text-rose-800', 'amber' => 'bg-amber-100 text-amber-800'][$notification->color] ?? 'bg-emerald-100 text-emerald-800' }}"
                          aria-hidden="true">{{ ['alert' => '!', 'check' => '✓', 'bell' => '•'][$notification->icon] ?? 'i' }}</span>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold text-slate-900 {{ $notification->isRead() ? '' : 'font-extrabold text-emerald-950' }}">
                                    {{ $notification->title }}
                                </p>
                                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ $notification->body }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-[11px] text-slate-400 font-mono whitespace-nowrap">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                                @if(!$notification->isRead())
                                <button onclick="markAsRead({{ $notification->id }})" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Tandai dibaca</button>
                                @endif
                            </div>
                        </div>
                        @if($notification->action_url)
                        <a href="{{ $notification->action_url }}"
                           onclick="markAsRead({{ $notification->id }})"
                           class="mt-1.5 inline-block text-xs font-bold text-emerald-700 hover:underline">Lihat detail &rarr;</a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <p class="text-sm font-bold text-slate-800">Tidak ada notifikasi</p>
                <p class="text-xs text-slate-500 mt-1">Notifikasi akan muncul di sini</p>
            </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
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
                el.classList.add('opacity-70');
                el.querySelector('p.font-extrabold')?.classList.remove('font-extrabold', 'text-emerald-950');
                el.querySelector('button[onclick*="markAsRead"]')?.remove();
            }
            updateNotificationCount();
        }
    })
    .catch(() => {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Tidak dapat menandai sudah dibaca.', type: 'error' } }));
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
    })
    .catch(() => {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Tidak dapat menandai semua sudah dibaca.', type: 'error' } }));
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
        })
        .catch(() => { });
}
</script>
@endpush
@endsection
