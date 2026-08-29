<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Student;
use App\Support\PeriodeLaporan;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EarlyWarningSystemService
{
    /**
     * Menganalisis seluruh siswa di kelas dan memberikan skor risiko serta diagnosa.
     */
    public function analyzeClassroom(Classroom $classroom, ?Request $request = null): Collection
    {
        $periodeData = PeriodeLaporan::resolve($request ?? request());
        $start = $periodeData['awal'];
        $end = $periodeData['akhir'];

        // Eager load seluruh relasi yang dibutuhkan untuk performa tinggi
        $students = $classroom->students()
            ->with([
                'attendances' => function ($q) use ($start, $end) {
                    $q->whereHas('session', function ($sq) use ($start, $end) {
                        $sq->whereBetween('session_date', [$start, $end]);
                    })->with('session');
                },
                'violations' => function ($q) use ($start, $end) {
                    $q->whereBetween('occurred_on', [$start, $end])
                      ->with('type');
                },
                'assessmentScores' => function ($q) use ($start, $end) {
                    $q->whereHas('assessment', function ($sq) use ($start, $end) {
                        $sq->whereBetween('assessment_date', [$start, $end]);
                    })->with('assessment');
                },
                'characterReflections' => function ($q) use ($start, $end) {
                    $q->whereBetween('reflection_date', [$start, $end]);
                },
                'seat',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $results = $students->map(function ($student) use ($classroom, $periodeData) {
            return $this->evaluateStudent($student, $classroom, $periodeData);
        });

        // Urutkan dari risiko tertinggi ke terendah
        return $results->sortByDesc('risk_score')->values();
    }

    /**
     * Evaluasi multi-dimensi untuk satu siswa.
     */
    public function evaluateStudent(Student $student, Classroom $classroom, array $periodeData): array
    {
        // 1. DIMENSI PRESENSI & KEHADIRAN
        $attendances = $student->attendances;
        $totalSessions = $attendances->count();
        $hadir = $attendances->where('status', 'hadir')->count();
        $terlambat = $attendances->where('status', 'terlambat')->count();
        $sakit = $attendances->where('status', 'sakit')->count();
        $izin = $attendances->where('status', 'izin')->count();
        $alfa = $attendances->where('status', 'alfa')->count();

        $persenHadir = $totalSessions > 0
            ? round((($hadir + $terlambat) / $totalSessions) * 100, 1)
            : 100;

        // 2. DIMENSI AKADEMIK / NILAI HARIAN
        $scores = $student->assessmentScores->whereNotNull('nilai');
        $avgScore = $scores->isNotEmpty() ? round($scores->avg('nilai'), 1) : null;
        $scoresBelowKKM = $scores->where('nilai', '<', 75)->count();

        // 3. DIMENSI KEDISIPLINAN & POIN
        $violations = $student->violations;
        $poinPengurang = $violations->sum(fn ($v) => $v->points ?: 5);
        $sisaPoin = max(0, 100 - $poinPengurang);
        $violationCount = $violations->count();

        // 4. DIMENSI KARAKTER & REFLEKSI P5
        $reflectionsCount = $student->characterReflections->count();

        // 5. SOSIO-EKONOMI & BIODATA
        $hasParentPhone = filled($student->parent_phone);
        $isOrphan = in_array(strtolower($student->family_status ?? ''), ['yatim', 'piatu', 'yatim piatu']);

        // ══════════ ENGINE KALKULASI SKOR RISIKO (0 - 100) ══════════
        $riskScore = 0;
        $triggers = [];

        // Evaluasi Alfa
        if ($alfa >= 3) {
            $riskScore += 35;
            $triggers[] = "{$alfa}× Alfa (Tanpa Keterangan)";
        } elseif ($alfa > 0) {
            $riskScore += ($alfa * 10);
            $triggers[] = "{$alfa}× Alfa";
        }

        // Evaluasi Terlambat
        if ($terlambat >= 4) {
            $riskScore += 20;
            $triggers[] = "Pola terlambat berulang ({$terlambat}×)";
        } elseif ($terlambat >= 2) {
            $riskScore += 10;
            $triggers[] = "{$terlambat}× Terlambat";
        }

        // Evaluasi Persentase Kehadiran
        if ($totalSessions >= 5) {
            if ($persenHadir < 75) {
                $riskScore += 25;
                $triggers[] = "Tingkat kehadiran kritis ({$persenHadir}%)";
            } elseif ($persenHadir < 85) {
                $riskScore += 12;
                $triggers[] = "Kehadiran di bawah standar ({$persenHadir}%)";
            }
        }

        // Evaluasi Nilai Akademik
        if ($avgScore !== null) {
            if ($avgScore < 60) {
                $riskScore += 25;
                $triggers[] = "Rata-rata nilai rendah ({$avgScore})";
            } elseif ($avgScore < 75) {
                $riskScore += 15;
                $triggers[] = "Rata-rata di bawah KKM ({$avgScore})";
            }
            if ($scoresBelowKKM >= 2) {
                $riskScore += 10;
                $triggers[] = "{$scoresBelowKKM} tugas belum tuntas";
            }
        }

        // Evaluasi Kedisiplinan
        if ($sisaPoin < 60) {
            $riskScore += 30;
            $triggers[] = "Poin disiplin kritis ({$sisaPoin}/100)";
        } elseif ($sisaPoin < 80) {
            $riskScore += 15;
            $triggers[] = "Poin kedisiplinan ({$sisaPoin}/100)";
        }

        // Batasi risk score maksimal 100
        $riskScore = min(100, max(0, $riskScore));

        // Tentukan Kategori Risiko
        if ($riskScore >= 70) {
            $riskLevel = 'critical';
            $levelLabel = 'Kritis';
            $badgeClass = 'bg-rose-100 text-rose-800 border-rose-300';
            $icon = '🔴';
        } elseif ($riskScore >= 45) {
            $riskLevel = 'warning';
            $levelLabel = 'Peringatan Dini';
            $badgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
            $icon = '🟠';
        } elseif ($riskScore >= 20) {
            $riskLevel = 'attention';
            $levelLabel = 'Perhatian';
            $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            $icon = '🟡';
        } else {
            $riskLevel = 'safe';
            $levelLabel = 'Aman';
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $icon = '🟢';
        }

        // ══════════ AI SYNTHESIZER: DIAGNOSA, LANGKAH GURU, DRAF WA ══════════
        $aiAnalysis = $this->generateAiDiagnosis(
            $student,
            $classroom,
            $riskLevel,
            $triggers,
            $persenHadir,
            $avgScore,
            $sisaPoin,
            $alfa,
            $terlambat
        );

        return [
            'student' => $student,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'level_label' => $levelLabel,
            'badge_class' => $badgeClass,
            'icon' => $icon,
            'triggers' => $triggers,
            'metrics' => [
                'total_sessions' => $totalSessions,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'sakit' => $sakit,
                'izin' => $izin,
                'alfa' => $alfa,
                'attendance_percent' => $persenHadir,
                'avg_score' => $avgScore,
                'scores_below_kkm' => $scoresBelowKKM,
                'sisa_poin' => $sisaPoin,
                'violations_count' => $violationCount,
                'reflections_count' => $reflectionsCount,
                'has_parent_phone' => $hasParentPhone,
            ],
            'ai' => $aiAnalysis,
        ];
    }

    /**
     * Menghasilkan sintesis diagnosa AI, panduan aksi guru, dan draf WhatsApp orang tua.
     */
    protected function generateAiDiagnosis(
        Student $student,
        Classroom $classroom,
        string $riskLevel,
        array $triggers,
        float $persenHadir,
        ?float $avgScore,
        int $sisaPoin,
        int $alfa,
        int $terlambat
    ): array {
        $namaPanggilan = explode(' ', trim($student->name))[0];
        $namaLengkap = $student->name;
        $namaKelas = $classroom->name;
        $namaWali = auth()->user()?->name ?? 'Wali Kelas';

        if ($riskLevel === 'critical') {
            $diagnosis = "Terdeteksi indikasi krisis akademik & kedisiplinan yang signifikan pada ananda {$namaPanggilan}. Akumulasi ketidakhadiran ({$alfa}× Alfa) yang disertai sisa poin kedisiplinan ({$sisaPoin}/100) berpotensi memicu ketertinggalan materi semester dan sanksi pemanggilan orang tua tingkat lanjut.";
            
            $recommendations = [
                "Lakukan panggilan telepon langsung atau jadwalkan pertemuan tatap muka segera dengan orang tua/wali murid.",
                "Terbitkan lembar rujukan kasus ke Guru Bimbingan Konseling (BK) untuk pendampingan psikologis/sosial.",
                "Berikan kontrak komitmen kehadiran khusus dan dispensasi remedial tugas yang tertinggal.",
            ];

            $waDraft = "Selamat pagi/siang Bapak/Ibu Wali Murid dari ananda {$namaLengkap}.\n\nSaya {$namaWali}, Wali Kelas {$namaKelas}. Kami ingin bersilaturahmi dan berkoordinasi secara khusus terkait perkembangan ananda di sekolah minggu ini. Berdasarkan catatan kami, ada beberapa hal terkait kehadiran ({$alfa} hari tanpa keterangan) yang perlu kami diskusikan bersama demi kebaikan belajar ananda.\n\nApakah Bapak/Ibu memiliki waktu luang hari ini/besok untuk kami hubungi atau bertemu di sekolah? Terima kasih banyak atas perhatian dan kerja samanya.";

            $bkDraft = "Rujukan Kasus Siswa: Ananda {$namaLengkap} ({$namaKelas}) memerlukan pendampingan intensif dari Tim BK sehubungan dengan akumulasi ketidakhadiran {$alfa}× Alfa dan penurunan indikator kedisiplinan ({$sisaPoin} poin). Mohon dijadwalkan sesi konseling mendalam.";

        } elseif ($riskLevel === 'warning') {
            $diagnosis = "Terdeteksi tren penurunan konsistensi belajar pada {$namaPanggilan}. Muncul anomali pada kehadiran/keterlambatan yang mulai berimbas pada ketuntasan asesmen. Diperlukan tindakan preventif sebelum memasuki zona kritis.";

            $recommendations = [
                "Lakukan sesi bicara empat mata (1-on-1) santai dengan {$namaPanggilan} setelah jam istirahat untuk mendengarkan kendala pribadinya.",
                "Kirimkan pesan pengingat yang ramah dan suportif kepada orang tua via WhatsApp.",
                "Pantau perkembangan kehadiran dan pengumpulan tugasnya secara ketat selama 7 hari ke depan.",
            ];

            $waDraft = "Selamat pagi/siang Bapak/Ibu orang tua {$namaLengkap}.\n\nSaya {$namaWali}, Wali Kelas {$namaKelas}. Sekadar ingin mengabarkan bahwa ananda {$namaPanggilan} dalam kondisi baik, namun tercatat sempat terlambat/tidak hadir beberapa kali belakangan ini. Kami ingin memastikan apakah ananda memiliki kendala kesehatan atau transportasi dari rumah? Semoga kita bisa saling mendukung agar semangat belajar ananda tetap terjaga. Terima kasih Bapak/Ibu.";

            $bkDraft = "Catatan Pemantauan: Ananda {$namaLengkap} ({$namaKelas}) menunjukkan tren penurunan kehadiran/nilai. Disarankan observasi berkala.";

        } elseif ($riskLevel === 'attention') {
            $diagnosis = "Kondisi ananda {$namaPanggilan} secara umum stabil, namun terdapat sedikit anomali ringan yang perlu diperhatikan agar tidak berkembang menjadi kebiasaan.";

            $recommendations = [
                "Berikan apresiasi saat siswa hadir tepat waktu di kelas.",
                "Ingatkan siswa untuk segera mengumpulkan tugas atau mengonfirmasi surat izin jika berhalangan.",
            ];

            $waDraft = "Selamat pagi/siang Bapak/Ibu orang tua {$namaLengkap}.\n\nSemoga Bapak/Ibu sekeluarga sehat selalu. Kami dari pihak wali kelas {$namaKelas} menginformasikan ananda {$namaPanggilan} belajar dengan baik di sekolah. Mohon bantuan untuk tetap memantau jam istirahat ananda di rumah agar senantiasa bersemangat ke sekolah. Terima kasih.";

            $bkDraft = "Status: Aman terkendali dengan pantauan ringan.";

        } else {
            $diagnosis = "Performa ananda {$namaPanggilan} berada pada tingkat optimal. Kehadiran sangat baik ({$persenHadir}%), kedisiplinan terjaga, dan tidak terdeteksi risiko akademik.";

            $recommendations = [
                "Pertahankan konsistensi motivasi belajar siswa.",
                "Pertimbangkan siswa untuk mendapatkan apresiasi / sertifikat siswa terajin.",
            ];

            $waDraft = "Selamat pagi/siang Bapak/Ibu orang tua {$namaLengkap}.\n\nKami mengapresiasi kedisiplinan dan semangat belajar ananda {$namaPanggilan} di kelas {$namaKelas} yang sangat baik dan konsisten. Terima kasih atas bimbingan luar biasa dari Bapak/Ibu di rumah.";

            $bkDraft = "Status: Sangat Baik / Teladan.";
        }

        return [
            'diagnosis' => $diagnosis,
            'recommendations' => $recommendations,
            'whatsapp_draft' => $waDraft,
            'bk_draft' => $bkDraft,
        ];
    }
}
