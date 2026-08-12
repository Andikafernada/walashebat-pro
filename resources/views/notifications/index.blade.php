@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Notifikasi</h2>
            <p class="text-slate-500 mt-1">Semua pemberitahuan untuk Anda</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('notifications.settings') }}" class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pengaturan
            </a>
            <button id="markAllRead" class="inline-flex items-center gap-2 px-4 py-2 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Tandai semua dibaca
            </button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-xl border border-slate-100 p-1 inline-flex">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filter === 'all' ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:text-slate-900' }}">
            Semua
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filter === 'unread' ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:text-slate-900' }}">
            Belum Dibaca
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'read']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filter === 'read' ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:text-slate-900' }}">
            Dibaca
        </a>
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
            <div class="p-4 sm:p-6 hover:bg-slate-50 transition-colors {{ $notification->isRead() ? 'opacity-75' : '' }}"
                 data-notification-id="{{ $notification->id }}">
                <div class="flex gap-4">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center @if($notification->color === 'emerald') bg-emerald-100 text-emerald-600 @elseif($notification->color === 'rose') bg-rose-100 text-rose-600 @elseif($notification->color === 'amber') bg-amber-100 text-amber-600 @else bg-indigo-100 text-indigo-600 @endif">
                            @if($notification->icon === 'bell')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            @elseif($notification->icon === 'alert')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @elseif($notification->icon === 'check')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                    </div>

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
                                <button onclick="markAsRead({{ $notification->id }})"
                                        class="p-1 text-slate-400 hover:text-indigo-600 rounded transition-colors"
                                        title="Tandai dibaca">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </div>
                        @if($notification->action_url)
                        <a href="{{ $notification->action_url }}"
                           onclick="markAsRead({{ $notification->id }})"
                           class="inline-flex items-center gap-1 mt-2 text-sm text-indigo-600 hover:text-indigo-700">
                            Lihat detail
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <h3 class="text-lg font-medium text-slate-700 mb-1">Tidak ada notifikasi</h3>
                <p class="text-slate-500">Notifikasi akan muncul di sini</p>
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
