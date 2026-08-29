@extends('layouts.app')

@section('title', 'Analitik & Statistik')

@push('styles')
<style>
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    .heatmap-cell {
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        line-height: 1rem;
        font-weight: 600;
        transition: all 0.15s ease;
    }
    .heatmap-cell.today {
        outline: 2px solid #059669;
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
            <nav class="text-xs font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Analitik &amp; Statistik</span>
            </nav>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">
                Analitik &amp; Statistik
            </h1>
            <p class="mt-1 text-xs text-slate-500">Visualisasi data kehadiran, pelanggaran, dan karakter siswa.</p>
        </div>
        <div>
            <form method="GET" class="flex items-center gap-2">
                <label for="class_id" class="text-xs font-bold text-slate-700">Kelas:</label>
                <select name="class_id" id="class_id" onchange="this.form.submit()" 
                        class="block rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
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
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Siswa</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $summaryStats['student_count'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Kehadiran</p>
            <p class="text-2xl font-extrabold text-emerald-600">{{ $summaryStats['attendance_rate'] }}%</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Absensi</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $summaryStats['total_attendance'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-200 shadow-xs p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Pelanggaran</p>
            <p class="text-2xl font-extrabold text-rose-600">{{ $summaryStats['violations_count'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Rata-rata Poin</p>
            <p class="text-2xl font-extrabold text-emerald-700">{{ $summaryStats['avg_discipline_points'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-200 shadow-xs p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Poin Rendah</p>
            <p class="text-2xl font-extrabold text-amber-600">{{ $summaryStats['low_points_students'] }}</p>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Attendance Chart -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h3 class="text-sm font-extrabold text-slate-900">Kehadiran Bulanan (12 Bulan)</h3>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Hadir
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Izin
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Alfa
                    </span>
                </div>
            </div>
            <div class="p-4">
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Violations by Category -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h3 class="text-sm font-extrabold text-slate-900">Pelanggaran per Kategori (Bulan Ini)</h3>
            </div>
            <div class="p-4">
                @if(count($violationsByCategory) > 0)
                    <div class="chart-container">
                        <canvas id="violationsChart"></canvas>
                    </div>
                @else
                    <div class="p-8 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-800">Tidak ada pelanggaran bulan ini</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Heatmap & Attendance Rate Trend -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Heatmap Kehadiran -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h3 class="text-sm font-extrabold text-slate-900">Heatmap Kehadiran (4 Minggu Terakhir)</h3>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <!-- Day names header -->
                    <div class="grid grid-cols-8 gap-2 mb-2">
                        <div></div>
                        @foreach($heatmapData['day_names'] as $day)
                            <div class="text-center text-xs font-bold text-slate-500">{{ $day }}</div>
                        @endforeach
                    </div>
                    <!-- Weeks -->
                    @foreach($heatmapData['weeks'] as $weekIndex => $week)
                        <div class="grid grid-cols-8 gap-2">
                            <div class="text-xs font-semibold text-slate-400 flex items-center">
                                @if($week[0]['date'])
                                    {{ \Carbon\Carbon::parse($week[0]['date'])->locale('id')->shortDayName }}
                                @endif
                            </div>
                            @foreach($week as $day)
                                <div class="heatmap-cell {{ $day['is_today'] ? 'today' : '' }} {{ $day['is_future'] ? 'future' : '' }} {{ !$day['is_future'] && $day['rate'] !== null ? ($day['rate'] >= 90 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($day['rate'] >= 75 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-rose-100 text-rose-800 border border-rose-200')) : '' }}"
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
                <div class="flex items-center justify-center gap-6 mt-4 pt-4 border-t border-slate-100">
                    <span class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <span class="w-3.5 h-3.5 rounded bg-emerald-100 border border-emerald-200"></span> ≥90%
                    </span>
                    <span class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <span class="w-3.5 h-3.5 rounded bg-amber-100 border border-amber-200"></span> 75-89%
                    </span>
                    <span class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <span class="w-3.5 h-3.5 rounded bg-rose-100 border border-rose-200"></span> &lt;75%
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h3 class="text-sm font-extrabold text-slate-900">Butuh Perhatian</h3>
            </div>
            <div class="p-4 space-y-3">
                @if($summaryStats['repeat_alpha_students'] > 0)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3 flex gap-2">
                        <div>
                            <p class="text-xs font-bold text-rose-900">{{ $summaryStats['repeat_alpha_students'] }} siswa</p>
                            <p class="text-xs text-rose-700 mt-0.5">Alfa &ge;3&times; sebulan</p>
                        </div>
                    </div>
                @endif
                @if($summaryStats['low_points_students'] > 0)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3 flex gap-2">
                        <div>
                            <p class="text-xs font-bold text-amber-900">{{ $summaryStats['low_points_students'] }} siswa</p>
                            <p class="text-xs text-amber-700 mt-0.5">Poin karakter &lt; 75</p>
                        </div>
                    </div>
                @endif
                @if($summaryStats['repeat_alpha_students'] == 0 && $summaryStats['low_points_students'] == 0)
                    <div class="p-4 text-center">
                        <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-800">Semua berjalan dengan baik!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-10 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <p class="mt-3 text-sm font-bold text-slate-900">Belum Ada Kelas</p>
        <p class="mt-1 text-xs text-slate-500">Silakan buat kelas terlebih dahulu untuk melihat analitik.</p>
        <div class="mt-4">
            <a href="{{ route('classes.create') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors">Buat Kelas Baru</a>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="/vendor/chart.umd.min.js?v=4.4.0"></script>
@if($selectedClass && count($monthlyAttendance) > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
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
                        borderRadius: 6,
                    },
                    {
                        label: 'Izin',
                        data: {!! json_encode(array_column($monthlyAttendance, 'permission')) !!},
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                    },
                    {
                        label: 'Alfa',
                        data: {!! json_encode(array_column($monthlyAttendance, 'alpha')) !!},
                        backgroundColor: '#f43f5e',
                        borderRadius: 6,
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
