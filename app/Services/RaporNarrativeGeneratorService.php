<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Student;
use App\Support\PoinKerajinan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * OpenCode AI Rapor Narrative Generator Service.
 *
 * Menghasilkan narasi deskripsi capaian pembelajaran & catatan wali kelas
 * resmi standar Kurikulum Merdeka secara cerdas tanpa biaya API tambahan.
 */
class RaporNarrativeGeneratorService
{
    /**
     * Generate narasi lengkap untuk seluruh siswa dalam satu kelas.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function generateForClassroom(Classroom $class, int $semester = 1): Collection
    {
        $students = $class->students()->where('is_active', true)->orderBy('name')->get();
        $scoresData = $this->loadClassScores($class, $semester);
        $attendanceData = $this->loadClassAttendance($class);
        $rolesData = $this->loadClassRoles($class);
        $reflectionsData = $this->loadClassReflections($class);

        return $students->map(function (Student $student) use ($scoresData, $attendanceData, $rolesData, $reflectionsData, $semester) {
            return $this->generateForStudent(
                $student,
                $scoresData->get($student->id, collect()),
                $attendanceData->get($student->id, ['persen' => 100, 'alfa' => 0, 'izin' => 0, 'sakit' => 0]),
                $rolesData->get($student->id),
                $reflectionsData->get($student->id, collect()),
                $semester
            );
        });
    }

    /**
     * Generate narasi untuk satu orang siswa.
     */
    public function generateForStudent(
        Student $student,
        Collection $scores,
        array $attendance,
        ?string $role = null,
        ?Collection $reflections = null,
        int $semester = 1
    ): array {
        $nama = $student->name;
        $genderPanggilan = $student->gender === 'P' ? 'siswi' : 'siswa';
        $kataGanti = 'Ananda ' . $this->namaPanggilan($nama);

        // 1. ANALISIS AKADEMIK (NILAI)
        $deskripsiAkademik = $this->buildAcademicNarrative($kataGanti, $scores);

        // 2. ANALISIS KARAKTER P5 & SIKAP
        $deskripsiKarakter = $this->buildCharacterNarrative($kataGanti, $student, $reflections, $role);

        // 3. CATATAN WALI KELAS & PRESENSI
        $catatanWali = $this->buildHomeroomNotes($kataGanti, $student, $attendance, $role, $semester);

        // 4. GABUNGAN TEKS LENGKAP (SIAP COPAS E-RAPOR)
        $fullText = "{$deskripsiAkademik}\n\n{$deskripsiKarakter}\n\nCatatan Wali Kelas:\n{$catatanWali}";

        return [
            'student_id' => $student->id,
            'name' => $student->name,
            'nis' => $student->nis,
            'gender' => $student->gender,
            'role' => $role,
            'attendance_rate' => $attendance['persen'] ?? 100,
            'discipline_points' => $student->discipline_points ?? 100,
            'diligence_points' => $student->diligence_points ?? 0,
            'academic_narrative' => $deskripsiAkademik,
            'character_narrative' => $deskripsiKarakter,
            'homeroom_notes' => $catatanWali,
            'full_text' => $fullText,
        ];
    }

    /**
     * Susun narasi capaian akademik.
     */
    private function buildAcademicNarrative(string $kataGanti, Collection $scores): string
    {
        if ($scores->isEmpty()) {
            return "{$kataGanti} telah mengikuti seluruh rangkaian pembelajaran semester ini dengan penuh kesungguhan dan menunjukkan potensi akademik yang baik.";
        }

        $sorted = $scores->sortByDesc('nilai');
        $tertinggi = $sorted->first();
        $terendah = $sorted->last();
        $rataRata = round($scores->avg('nilai'), 1);

        $teks = "";
        if ($tertinggi && $tertinggi['nilai'] >= 85) {
            $teks .= "{$kataGanti} menunjukkan penguasaan kompetensi yang sangat memuaskan pada mata pelajaran {$tertinggi['mapel']} (nilai {$tertinggi['nilai']}). ";
        } elseif ($tertinggi) {
            $teks .= "{$kataGanti} menunjukkan pemahaman yang baik pada mata pelajaran {$tertinggi['mapel']} dengan rerata nilai {$rataRata}. ";
        }

        if ($terendah && $terendah['nilai'] < 75 && $terendah['mapel'] !== ($tertinggi['mapel'] ?? '')) {
            $teks .= "Perlu bimbingan dan peningkatan fokus belajar lebih konsisten, khususnya pada mata pelajaran {$terendah['mapel']}.";
        } else {
            $teks .= "Pertahankan konsistensi semangat belajar dan eksplorasi minat akademik di semester berikutnya.";
        }

        return trim($teks);
    }

    /**
     * Susun narasi karakter P5.
     */
    private function buildCharacterNarrative(string $kataGanti, Student $student, ?Collection $reflections, ?string $role): string
    {
        $poinDisiplin = $student->discipline_points ?? 100;
        $poinKerajinan = $student->diligence_points ?? 0;

        $kekuatan = [];
        if ($role) {
            $kekuatan[] = "menunjukkan jiwa kepemimpinan yang bertanggung jawab sebagai {$role}";
        }
        if ($poinDisiplin >= 95) {
            $kekuatan[] = "memiliki integritas dan kedisiplinan diri yang sangat terpuji";
        }
        if ($poinKerajinan >= 30) {
            $kekuatan[] = "sangat rajin, aktif, dan berinisiatif tinggi dalam kegiatan kelas";
        }

        if (empty($kekuatan)) {
            $kekuatan[] = "mampu beradaptasi, bekerja sama dengan rekan sejawat, dan mematuhi tata tertib sekolah dengan baik";
        }

        $kalimatKekuatan = implode(', ', $kekuatan);

        return "Dalam dimensi Profil Pelajar Pancasila, {$kataGanti} {$kalimatKekuatan}. Selalu santun dalam bertutur kata serta menunjukkan sikap gotong royong dan kemandirian yang positif.";
    }

    /**
     * Susun catatan resmi wali kelas.
     */
    private function buildHomeroomNotes(string $kataGanti, Student $student, array $attendance, ?string $role, int $semester): string
    {
        $persen = $attendance['persen'] ?? 100;
        $alfa = $attendance['alfa'] ?? 0;

        $catatan = "";
        if ($persen >= 95 && $alfa === 0) {
            $catatan = "Selamat atas pencapaian prestasi dan tingkat kehadiran yang istimewa ({$persen}%). Pertahankan semangat belajar yang luar biasa ini di jenjang berikutnya!";
        } elseif ($persen >= 85) {
            $catatan = "Pencapaian belajar yang baik dan kehadiran terjaga ({$persen}%). Terus tingkatkan keaktifan serta kepercayaan diri dalam setiap aktivitas sekolah.";
        } else {
            $catatan = "Diharapkan dapat meningkatkan kedisiplinan kehadiran di semester mendatang (tercatat {$alfa} hari tanpa keterangan). Tetap semangat dan jangan ragu untuk berdiskusi dengan wali kelas.";
        }

        return $catatan;
    }

    private function namaPanggilan(string $fullName): string
    {
        $words = explode(' ', trim($fullName));
        return $words[0] ?? $fullName;
    }

    private function loadClassScores(Classroom $class, int $semester): Collection
    {
        $penilaian = DB::table('assessments as a')
            ->join('assessment_scores as sc', 'sc.assessment_id', '=', 'a.id')
            ->where('a.class_id', $class->id)
            ->where('a.semester', $semester)
            ->whereNotNull('sc.nilai')
            ->select('sc.student_id', 'a.mapel', 'sc.nilai')
            ->get();

        return $penilaian->groupBy('student_id')->map(function ($items) {
            return $items->groupBy('mapel')->map(function ($mapelItems, $mapel) {
                return [
                    'mapel' => $mapel,
                    'nilai' => round($mapelItems->avg('nilai'), 1),
                ];
            })->values();
        });
    }

    private function loadClassAttendance(Classroom $class): Collection
    {
        $records = DB::table('attendances as a')
            ->join('attendance_sessions as s', 'a.attendance_session_id', '=', 's.id')
            ->where('s.class_id', $class->id)
            ->where('s.status', '!=', 'cancelled')
            ->select('a.student_id', 'a.status')
            ->get();

        return $records->groupBy('student_id')->map(function ($items) {
            $total = $items->count();
            $masuk = $items->whereIn('status', ['hadir', 'terlambat'])->count();
            $alfa = $items->where('status', 'alfa')->count();
            $izin = $items->where('status', 'izin')->count();
            $sakit = $items->where('status', 'sakit')->count();

            return [
                'total' => $total,
                'persen' => $total > 0 ? (int) round(($masuk / $total) * 100) : 100,
                'alfa' => $alfa,
                'izin' => $izin,
                'sakit' => $sakit,
            ];
        });
    }

    private function loadClassRoles(Classroom $class): Collection
    {
        return DB::table('organization_structures')
            ->where('class_id', $class->id)
            ->whereNotNull('student_id')
            ->pluck('role', 'student_id');
    }

    private function loadClassReflections(Classroom $class): Collection
    {
        return DB::table('character_reflections')
            ->where('class_id', $class->id)
            ->get()
            ->groupBy('student_id');
    }
}
