@extends('layouts.app')

@section('title', 'Denah Tempat Duduk - ' . $classroom->name)

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
                <span class="text-slate-700 font-semibold">Denah Tempat Duduk</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Denah Tempat Duduk Kelas {{ $classroom->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Atur posisi meja dan bangku siswa secara visual interaktif.</p>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    @php
        $studentsArr = $students->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values();
        $seatsArr = $seats->map(fn($s) => [
            'row_index' => $s->row_index, 'col_index' => $s->col_index,
            'student_id' => $s->student_id, 'label' => $s->label,
        ])->values();
        $maxRow = ($seats->max('row_index') ?? 3);
        $maxCol = ($seats->max('col_index') ?? 5);
    @endphp

    <!-- Seating Grid Canvas Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-5"
         x-data="seating({
            saveUrl: '{{ route('classes.seating.save', $classroom) }}',
            csrf: '{{ csrf_token() }}',
            students: {{ Illuminate\Support\Js::from($studentsArr) }},
            initial: {{ Illuminate\Support\Js::from($seatsArr) }},
            rows: {{ $maxRow + 1 }}, cols: {{ $maxCol + 1 }}
         })">
        
        <!-- Controls Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Baris Meja:</label>
                    <input type="number" min="1" max="20" x-model.number="rows" @change="resize()"
                           class="h-8 w-16 rounded-xl border border-slate-200 bg-slate-50 text-center text-xs font-bold text-slate-900 focus:border-indigo-500 focus:outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Kolom Meja:</label>
                    <input type="number" min="1" max="20" x-model.number="cols" @change="resize()"
                           class="h-8 w-16 rounded-xl border border-slate-200 bg-slate-50 text-center text-xs font-bold text-slate-900 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span x-show="savedMsg" x-cloak class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                    ✓ <span x-text="savedMsg"></span>
                </span>
                <button type="button" @click="save()" :disabled="saving"
                        class="h-9 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-bold px-4 transition-all shadow-md shadow-indigo-500/20 flex items-center gap-1.5">
                    <template x-if="!saving">
                        <span>Simpan Denah Meja</span>
                    </template>
                    <template x-if="saving">
                        <span class="flex items-center gap-1">
                            <span class="h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            Menyimpan...
                        </span>
                    </template>
                </button>
            </div>
        </div>

        <!-- Teacher's Blackboard Representation Header -->
        <div class="mx-auto w-3/4 rounded-xl bg-slate-800 py-2 text-center text-xs font-bold uppercase tracking-widest text-slate-200 shadow-inner">
             papan tulis / meja guru 
        </div>

        <!-- Dynamic Seating Grid -->
        <div class="overflow-x-auto py-4">
            <div class="inline-grid gap-3 min-w-full justify-center"
                 :style="`grid-template-columns: repeat(${cols}, minmax(130px, 1fr));`">
                
                <template x-for="r in rows" :key="`r-${r}`">
                    <template x-for="c in cols" :key="`r-${r}-c-${c}`">
                        <div class="group relative flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50/60 p-2.5 transition-all hover:border-indigo-300 hover:bg-white hover:shadow-sm"
                             :class="{ 'border-indigo-400 bg-indigo-50/30': seatAt(r-1, c-1).student_id }">
                            
                            <div class="flex items-center justify-between text-[10px] text-slate-400 mb-1">
                                <span class="font-mono font-semibold" x-text="`R${r}C${c}`"></span>
                                <template x-if="seatAt(r-1, c-1).student_id">
                                    <button @click="unassign(r-1, c-1)" class="text-rose-500 hover:text-rose-700 font-bold">×</button>
                                </template>
                            </div>

                            <select :value="seatAt(r-1, c-1).student_id || ''"
                                    @change="assign(r-1, c-1, $event.target.value)"
                                    class="w-full rounded-lg border border-slate-200 bg-white p-1.5 text-xs font-semibold text-slate-800 focus:border-indigo-500 focus:outline-none">
                                <option value="">-- Kosong --</option>
                                <template x-for="st in students" :key="st.id">
                                    <option :value="st.id" :selected="st.id == seatAt(r-1, c-1).student_id" x-text="st.name"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                </template>

            </div>
        </div>

    </div>
</div>

<script>
    function seating(cfg) {
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
            resize() {
                // Keep grid reactive
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
                        this.savedMsg = 'Tersimpan!';
                        setTimeout(() => this.savedMsg = '', 3000);
                    }
                } catch (e) {
                    alert('Gagal menyimpan denah tempat duduk.');
                } finally {
                    this.saving = false;
                }
            }
        }
    }
</script>
@endsection
