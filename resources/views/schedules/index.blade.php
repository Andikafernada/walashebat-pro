@extends('layouts.app')

@section('title', 'Jadwal Pelajaran - ' . $classroom->name)

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-indigo-600 transition-colors">{{ $classroom->name }}</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Jadwal Pelajaran</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Jadwal Pelajaran Kelas {{ $classroom->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola susunan mata pelajaran harian, waktu jam pelajaran, dan nama guru pengampu.</p>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- LEFT COLUMN: Schedules List by Day (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            @foreach (\App\Models\Schedule::DAYS as $num => $day)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm space-y-3">
                    
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-xs font-bold text-indigo-700">
                                {{ $loop->iteration }}
                            </div>
                            <h3 class="font-bold text-sm text-slate-900">{{ $day }}</h3>
                        </div>
                        @if(!empty($schedules[$num]))
                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 border border-indigo-200">
                                {{ count($schedules[$num]) }} Mata Pelajaran
                            </span>
                        @else
                            <span class="text-xs text-slate-400">Tidak Ada Jadwal</span>
                        @endif
                    </div>

                    @if(!empty($schedules[$num]))
                        <div class="divide-y divide-slate-100">
                            @foreach ($schedules[$num] as $s)
                                <div class="flex items-center justify-between gap-4 py-2.5 hover:bg-slate-50/80 px-2 rounded-xl transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-mono font-bold text-slate-700 shrink-0">
                                            {{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}
                                        </span>
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-900">{{ $s->subject }}</h4>
                                            <p class="text-xs text-slate-500">Guru: {{ $s->teacher_name ?: '—' }}</p>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('classes.schedules.destroy', [$classroom, $s]) }}" 
                                          onsubmit="return confirm('Hapus {{ $s->subject }} hari {{ $day }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="h-8 w-8 rounded-lg border border-slate-200 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center text-slate-400 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic text-center py-2">Belum ada mata pelajaran diisi untuk hari {{ $day }}.</p>
                    @endif

                </div>
            @endforeach
        </div>

        <!-- RIGHT COLUMN: Add Form (1/3 width) -->
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
                
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-500/20">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Tambah Jam Pelajaran</h3>
                        <p class="text-xs text-slate-500">Input Mata Pelajaran &amp; Guru</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('classes.schedules.store', $classroom) }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="day_of_week" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Hari</label>
                        <select id="day_of_week" name="day_of_week" required class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-800 focus:border-indigo-500 focus:outline-none">
                            @foreach (\App\Models\Schedule::DAYS as $num => $day)
                                <option value="{{ $num }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="start_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jam Mulai</label>
                            <input type="time" id="start_time" name="start_time" required class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label for="end_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jam Selesai</label>
                            <input type="time" id="end_time" name="end_time" required class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Mata Pelajaran</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" placeholder="cth: Matematika, Bahasa Indonesia..." required class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="teacher_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Guru Pengampu <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                        <input type="text" id="teacher_name" name="teacher_name" value="{{ old('teacher_name') }}" placeholder="cth: Drs. Supriadi, M.Pd" class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                    </div>

                    <button type="submit" :disabled="loading"
                            class="h-10 w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <template x-if="!loading">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Simpan Jadwal
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-1.5">
                                <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Menyimpan...
                            </span>
                        </template>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
