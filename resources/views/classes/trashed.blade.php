@extends('layouts.app')
@section('title', 'Arsip Kelas')
@section('content')

    <!-- Page Header -->
    <div class="page-header mb-5">
        <div>
            <p class="text-sm text-slate-500">{{ $classes->total() }} kelas diarsipkan</p>
        </div>
        <a href="{{ route('classes.index') }}" class="btn-ghost btn-ghost--sm">Kembali ke daftar kelas
        </a>
    </div>

    @if ($classes->isEmpty())
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state__icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                         d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <p class="empty-state__title">Arsip kosong</p>
            <p class="empty-state__description">
                Kelas yang dihapus akan muncul di sini. Anda bisa memulihkan atau menghapusnya permanen.
            </p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Kelas</th>
                        <th scope="col">Jurusan</th>
                        <th scope="col">Dihapus</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($classes as $class)
                        <tr>
                            <td class="font-semibold text-slate-900">{{ $class->name }}</td>
                            <td class="td--secondary">{{ $class->major ?? '—' }}</td>
                            <td class="td--secondary">{{ $class->deleted_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('classes.restore', $class->id) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-ghost btn-ghost--sm text-emerald-600">Pulihkan
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('classes.force-delete', $class->id) }}"
                                          class="inline"
                                          onsubmit="return confirm('Hapus PERMANEN kelas {{ $class->name }}? Seluruh data siswa, absensi, dan kas kelas akan hilang dan TIDAK BISA dikembalikan.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-ghost btn-danger-ghost--sm">
                                            Hapus permanen
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($classes->hasPages())
            <div class="mt-6">{{ $classes->links() }}</div>
        @endif
    @endif
@endsection
