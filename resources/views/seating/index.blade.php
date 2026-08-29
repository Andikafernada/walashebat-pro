@extends('layouts.app')

@section('title', 'Denah Tempat Duduk - ' . $classroom->name)

@section('content')
<div class="space-y-6 pb-12">

    {{-- ══════════ 1. HEADER ══════════ --}}
    <div>
        <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
            <a href="{{ route('classes.index') }}" class="hover:text-slate-600 transition-colors">Kelas</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600 transition-colors">{{ $classroom->name }}</a>
            <span aria-hidden="true">/</span>
            <span class="text-slate-500 font-medium">Denah Tempat Duduk</span>
        </nav>
        <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
            Denah Tempat Duduk Kelas {{ $classroom->name }}
        </h1>
        <p class="mt-0.5 text-xs sm:text-sm text-slate-500">
            Tata letak posisi bangku siswa secara visual interaktif sesuai denah ruang kelas nyata.
        </p>
    </div>

    {{-- NAVIGASI KELAS --}}
    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    @php
        $studentsArr = $students->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'gender' => $s->gender])->values();
        $seatsArr = $seats->map(fn($s) => [
            'row_index' => $s->row_index, 'col_index' => $s->col_index,
            'student_id' => $s->student_id, 'label' => $s->label,
        ])->values();
        $maxRow = ($seats->max('row_index') ?? 3);
        $maxCol = ($seats->max('col_index') ?? 5);
    @endphp

    {{-- ══════════ 2. INTERACTIVE SEATING STUDIO ══════════ --}}
    <div class="bg-white rounded-3xl border border-emerald-200/80 p-5 sm:p-7 shadow-xs space-y-6"
         x-data="seatingStudio({
            saveUrl: '{{ route('classes.seating.save', $classroom) }}',
            csrf: '{{ csrf_token() }}',
            students: {{ Illuminate\Support\Js::from($studentsArr) }},
            initial: {{ Illuminate\Support\Js::from($seatsArr) }},
            rows: {{ $maxRow + 1 }}, cols: {{ $maxCol + 1 }}
         })">

        {{-- Controls Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-emerald-100 pb-5">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 bg-emerald-50 px-3 py-1.5 rounded-2xl border border-emerald-200">
                    <label class="text-[11px] font-bold text-emerald-950 uppercase tracking-wider">Baris Meja:</label>
                    <input type="number" min="1" max="15" x-model.number="rows"
                           class="h-7 w-12 rounded-xl border border-emerald-300 bg-white text-center text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div class="flex items-center gap-2 bg-emerald-50 px-3 py-1.5 rounded-2xl border border-emerald-200">
                    <label class="text-[11px] font-bold text-emerald-950 uppercase tracking-wider">Kolom Meja:</label>
                    <input type="number" min="1" max="12" x-model.number="cols"
                           class="h-7 w-12 rounded-xl border border-emerald-300 bg-white text-center text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span x-show="savedMsg" x-cloak class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 px-3 py-1.5 text-xs font-bold shadow-xs">
                    <span>✓</span> <span x-text="savedMsg"></span>
                </span>
                <button type="button" @click="save()" :disabled="saving"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-200 transition-all flex items-center gap-1.5">
                    <template x-if="!saving">
                        <span>💾 Simpan Denah Kelas</span>
                    </template>
                    <template x-if="saving">
                        <span>Menyimpan...</span>
                    </template>
                </button>
            </div>
        </div>

        {{-- Teacher Whiteboard Podium Header (Front of Class) --}}
        <div class="mx-auto max-w-xl rounded-2xl bg-slate-900 py-2.5 px-4 text-center shadow-md">
            <div class="flex items-center justify-center gap-2 text-xs font-bold tracking-widest text-slate-200 uppercase">
                <span>👨‍🏫</span>
                <span>Papan Tulis &amp; Meja Guru (Bagian Depan Kelas)</span>
            </div>
        </div>

        {{-- Dynamic Seating Grid Canvas --}}
        <div class="overflow-x-auto py-2">
            <div class="inline-grid gap-3 min-w-full justify-center p-2"
                 :style="`grid-template-columns: repeat(${cols}, minmax(140px, 1fr));`">

                <template x-for="r in rows" :key="`r-${r}`">
                    <template x-for="c in cols" :key="`r-${r}-c-${c}`">
                        <div class="group relative flex flex-col justify-between rounded-2xl border p-3 transition-all"
                             :class="seatAt(r-1, c-1).student_id
                                ? 'border-emerald-300 bg-emerald-100/70 shadow-xs'
                                : 'border-emerald-200/80 bg-emerald-50/40 hover:border-emerald-300 hover:bg-white'">

                            {{-- Seat Header --}}
                            <div class="flex items-center justify-between text-[11px] mb-2">
                                <span class="font-mono font-bold text-slate-500" x-text="`B${r}-K${c}`"></span>
                                <template x-if="seatAt(r-1, c-1).student_id">
                                    <button type="button" @click="unassign(r-1, c-1)"
                                            class="w-5 h-5 rounded-md bg-emerald-200/80 hover:bg-slate-200 hover:text-slate-900 text-slate-700 flex items-center justify-center text-xs font-bold transition-colors"
                                            title="Kosongkan Meja">
                                        &times;
                                    </button>
                                </template>
                            </div>

                            {{-- Student Selector Dropdown --}}
                            <select :value="seatAt(r-1, c-1).student_id || ''"
                                    @change="assign(r-1, c-1, $event.target.value)"
                                    class="w-full text-xs font-bold rounded-xl border py-1.5 px-2 transition-colors focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    :class="seatAt(r-1, c-1).student_id
                                        ? 'border-emerald-300 bg-white text-emerald-950 font-extrabold'
                                        : 'border-emerald-200 bg-white text-slate-600'">
                                <option value="">-- Meja Kosong --</option>
                                <template x-for="st in students" :key="st.id">
                                    <option :value="st.id" :selected="st.id == seatAt(r-1, c-1).student_id" x-text="st.name"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                </template>

            </div>
        </div>

        {{-- Studio Legend --}}
        <div class="flex flex-wrap items-center justify-center gap-4 pt-3 border-t border-emerald-100 text-xs text-slate-600 font-bold">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-emerald-100 border border-emerald-300 inline-block"></span>
                <span>Meja Terisi Siswa</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-md bg-emerald-50 border border-emerald-200 inline-block"></span>
                <span>Meja Kosong</span>
            </span>
        </div>

    </div>

</div>

<script>
    function seatingStudio(cfg) {
        return {
            saveUrl: cfg.saveUrl,
            csrf: cfg.csrf,
            students: cfg.students,
            rows: cfg.rows,
            cols: cfg.cols,
            seats: cfg.initial || [],
            saving: false,
            savedMsg: '',

            seatAt(r, c) {
                let found = this.seats.find(s => s.row_index === r && s.col_index === c);
                if (!found) {
                    found = { row_index: r, col_index: c, student_id: null, label: null };
                    this.seats.push(found);
                }
                return found;
            },
            assign(r, c, sid) {
                let s = this.seatAt(r, c);
                s.student_id = sid ? parseInt(sid) : null;
            },
            unassign(r, c) {
                let s = this.seatAt(r, c);
                s.student_id = null;
            },
            async save() {
                this.saving = true;
                this.savedMsg = '';
                try {
                    const res = await fetch(this.saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ seats: this.seats })
                    });
                    if (res.ok) {
                        this.savedMsg = 'Denah Berhasil Disimpan!';
                        setTimeout(() => this.savedMsg = '', 3500);
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Denah berhasil disimpan!', type: 'success' } }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Gagal menyimpan denah tempat duduk.', type: 'error' } }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
                } finally {
                    this.saving = false;
                }
            }
        }
    }
</script>
@endsection
