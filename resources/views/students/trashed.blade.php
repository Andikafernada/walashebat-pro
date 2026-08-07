@extends('layouts.app')
@section('title', 'Arsip Siswa')
@section('content')
    @include('partials.class-nav')

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <p class="text-sm text-slate-500">{{ $students->total() }} siswa diarsipkan</p>
        </div>
        <a href="{{ route('classes.students.index', $classroom) }}" class="btn-ghost btn-ghost--sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke daftar siswa
        </a>
    </div>

    @if ($students->isEmpty())
        <!-- Empty State -->
        <div class="empty-state animate-in">
            <div class="empty-state__icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                         d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <p class="empty-state__title">Arsip kosong</p>
            <p class="empty-state__description">
                Siswa yang dihapus akan muncul di sini dan bisa dipulihkan kapan saja.
            </p>
        </div>
    @else
        <div class="table-wrapper animate-in">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">NIS</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Dihapus</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $s)
                        <tr>
                            <td class="td--mono">{{ $s->nis ?? '—' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                        {{ strtoupper(substr($s->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $s->name }}</span>
                                </div>
                            </td>
                            <td class="td--secondary">{{ $s->deleted_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('classes.students.restore', [$classroom, $s->id]) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-ghost btn-ghost--sm text-emerald-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Pulihkan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="mt-6">{{ $students->links() }}</div>
        @endif
    @endif
@endsection
