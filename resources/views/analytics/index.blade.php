@extends('layouts.app')

@section('title', 'Analitik & Statistik')

@push('styles')
<style>
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    /*
     * CSS biasa, bukan direktif Tailwind.
     *
     * Direktif seperti "at-apply" diproses Tailwind saat build. Di dalam blok
     * style yang dikirim ke browser saat halaman dibuka, ia bukan CSS yang sah
     * dan dibuang begitu saja — sel kalender kehadiran ini tampil tanpa bentuk,
     * tanpa perataan, dan tanda "hari ini" tidak terlihat sama sekali.
     */
    .heatmap-cell {
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        line-height: 1rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }
    .heatmap-cell.today {
        outline: 2px solid #6366f1;
        outline-offset: 2px;
    }
    .heatmap-cell.future {
        background-color: #f8fafc;
        color: #cbd5e1;
    }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="page-header">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Analitik &amp; Statistik</span>
            </nav>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                Analitik &amp; Statistik
            </h1>
            <p class="mt-1 text-sm text-slate-500">Visualisasi data kehadiran, pelanggaran, dan karakter siswa.</p>
        </div>
        <div>
            <form method="GET" class="flex items-center gap-2">
                <label for="class_id" class="form-label">Kelas</label>
                <select name="class_id" id="class_id" onchange="this.form.submit()" class="form-input form-input--sm">
                    @foreach($classrooms as $class)
                        <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($selectedClass)
    <!-- Summary Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="card">
            <p class="text-xs text-slate-500 mb-1">Total Siswa</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $summaryStats['student_count'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs text-slate-500 mb-1">Kehadiran</p>
            <p class="text-2xl font-semibold text-emerald-600">{{ $summaryStats['attendance_rate'] }}%</p>
        </div>
        <div class="card">
            <p class="text-xs text-slate-500 mb-1">Total Absensi</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $summaryStats['total_attendance'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs text-slate-500 mb-1">Pelanggaran</p>
            <p class="text-2xl font-semibold text-rose-600">{{ $summaryStats['violations_count'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs text-slate-500 mb-1">Rata-rata Poin</p>
            <p class="text-2xl font-semibold text-indigo-600">{{ $summaryStats['avg_discipline_points'] }}</p>
        </div>
        <div class="card">
            <p class="text-xs text-slate-500 mb-1">Poin Rendah</p>
            <p class="text-2xl font-semibold text-amber-600">{{ $summaryStats['low_points_students'] }}</p>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Attendance Chart -->
        <div class="blok">
            <div class="blok__kepala">
                <h3 class="blok__judul">Kehadiran Bulanan (12 Bulan)</h3>
                <div class="flex items-center gap-4 text-xs">
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Hadir
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> Izin
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Alfa
                    </span>
                </div>
            </div>
            <div class="blok__isi">
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Violations by Category -->
        <div class="blok">
            <div class="blok__kepala">
                <h3 class="blok__judul">Pelanggaran per Kategori (Bulan Ini)</h3>
            </div>
            <div class="blok__isi">
                @if(count($violationsByCategory) > 0)
                    <div class="chart-container">
                        <canvas id="violationsChart"></canvas>
                    </div>
                @else
                    <div class="empty-state empty-state--compact">
                        <div class="empty-state__icon">
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="empty-state__title">Tidak ada pelanggaran bulan ini</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Heatmap & Attendance Rate Trend -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Heatmap Kehadiran -->
        <div class="lg:col-span-2 blok">
            <div class="blok__kepala">
                <h3 class="blok__judul">Heatmap Kehadiran (4 Minggu Terakhir)</h3>
            </div>
            <div class="blok__isi">
                <div class="space-y-2">
                    <!-- Day names header -->
                    <div class="grid grid-cols-8 gap-2 mb-2">
                        <div></div>
                        @foreach($heatmapData['day_names'] as $day)
                            <div class="text-center text-xs font-medium text-slate-500">{{ $day }}</div>
                        @endforeach
                    </div>
                    <!-- Weeks -->
                    @foreach($heatmapData['weeks'] as $weekIndex => $week)
                        <div class="grid grid-cols-8 gap-2">
                            <div class="text-xs text-slate-500 flex items-center">
                                @if($week[0]['date'])
                                    {{ \Carbon\Carbon::parse($week[0]['date'])->locale('id')->shortDayName }}
                                @endif
                            </div>
                            @foreach($week as $day)
                                <div class="heatmap-cell {{ $day['is_today'] ? 'today' : '' }} {{ $day['is_future'] ? 'future' : '' }} {{ !$day['is_future'] && $day['rate'] !== null ? ($day['rate'] >= 90 ? 'bg-emerald-100 text-emerald-700' : ($day['rate'] >= 75 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')) : '' }}"
                                     title="{{ $day['formatted'] }}: {{ $day['rate'] !== null ? $day['rate'] . '%' : 'Tidak ada data' }}">
                                    @if(!$day['is_future'] && $day['total'] > 0)
                                        {{ $day['present'] }}/{{ $day['total'] }}
                                    @elseif($day['is_future'])
                                        -
                                    @else
                                        -
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <!-- Legend -->
                <div class="flex items-center justify-center gap-6 mt-4 pt-4 border-t border-slate-200">
                    <span class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="w-4 h-4 rounded bg-emerald-100"></span> ≥90%
                    </span>
                    <span class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="w-4 h-4 rounded bg-amber-100"></span> 75-89%
                    </span>
                    <span class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="w-4 h-4 rounded bg-rose-100"></span> <75%
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="blok">
            <div class="blok__kepala">
                <h3 class="blok__judul">Butuh Perhatian</h3>
            </div>
            <div class="blok__isi space-y-3">
                @if($summaryStats['repeat_alpha_students'] > 0)
                    <div class="alert alert--danger">
                        <div>
                            <p class="alert__title">{{ $summaryStats['repeat_alpha_students'] }} siswa</p>
                            <p class="alert__body">Alfa &ge;3&times; sebulan</p>
                        </div>
                    </div>
                @endif
                @if($summaryStats['low_points_students'] > 0)
                    <div class="alert alert--warning">
                        <div>
                            <p class="alert__title">{{ $summaryStats['low_points_students'] }} siswa</p>
                            <p class="alert__body">Poin karakter &lt; 75</p>
                        </div>
                    </div>
                @endif
                @if($summaryStats['repeat_alpha_students'] == 0 && $summaryStats['low_points_students'] == 0)
                    <div class="empty-state empty-state--compact">
                        <p class="empty-state__title">Semua berjalan dengan baik!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="empty-state">
        <div class="empty-state__icon">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <p class="empty-state__title">Belum Ada Kelas</p>
        <p class="empty-state__description">Silakan buat kelas terlebih dahulu untuk melihat analitik.</p>
        <div class="empty-state__action">
            <a href="{{ route('classes.create') }}" class="btn-primary btn-primary--sm">Buat Kelas Baru</a>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@if($selectedClass && count($monthlyAttendance) > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart');
    if (attendanceCtx) {
        new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_column($monthlyAttendance, 'month')) !!},
                datasets: [
                    {
                        label: 'Hadir',
                        data: {!! json_encode(array_column($monthlyAttendance, 'present')) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    },
                    {
                        label: 'Izin',
                        data: {!! json_encode(array_column($monthlyAttendance, 'permission')) !!},
                        backgroundColor: '#f59e0b',
                        borderRadius: 4,
                    },
                    {
                        label: 'Alfa',
                        data: {!! json_encode(array_column($monthlyAttendance, 'alpha')) !!},
                        backgroundColor: '#f43f5e',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const idx = context[0].dataIndex;
                                const data = {!! json_encode($monthlyAttendance) !!};
                                return 'Total: ' + data[idx].total + ' | Rate: ' + data[idx].rate + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Violations Chart
    const violationsCtx = document.getElementById('violationsChart');
    if (violationsCtx) {
        const violationsData = {!! json_encode($violationsByCategory) !!};
        new Chart(violationsCtx, {
            type: 'doughnut',
            data: {
                labels: violationsData.map(v => v.name),
                datasets: [{
                    data: violationsData.map(v => v.count),
                    backgroundColor: [
                        '#f43f5e', '#f59e0b', '#8b5cf6', '#3b82f6', '#10b981', '#ec4899'
                    ],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' kasus';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>
@endif
@endpush
@endsection
