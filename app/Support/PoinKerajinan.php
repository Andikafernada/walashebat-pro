<?php

namespace App\Support;

use App\Models\Classroom;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Poin kerajinan & keaktifan siswa.
 *
 * Terpisah dari discipline_points (yang digerakkan pelanggaran): angka ini
 * murni akumulasi kehadiran dan keaktifan (pengurus kelas), supaya "siswa terajin"
 * adalah soal rajin masuk dan berkontribusi di kelas, bukan sekadar bersih dari pelanggaran.
 *
 * Sumber kebenaran adalah tabel attendances & organization_structures.
 * Kolom students.diligence_points hanyalah cache yang SELALU dihitung ulang dari nol (bukan delta),
 * sehingga tidak bisa melenceng meski absensi diedit, dihapus, atau sesinya dibatalkan.
 */
class PoinKerajinan
{
    // Aturan baku poin:
    // - Hadir: +5
    // - Alfa: -10
    // - Izin: -3
    // - Sakit: -3
    public const NILAI = [
        'hadir' => 5,
        'alfa' => -10,
        'izin' => -3,
        'sakit' => -3,
    ];

    /** SQL CASE penjumlah poin kehadiran */
    private static function sqlCase(string $kolom = 'a.status'): string
    {
        $cases = '';
        foreach (self::NILAI as $status => $poin) {
            $cases .= " WHEN '".$status."' THEN ".(int) $poin;
        }

        return "SUM(CASE {$kolom}{$cases} ELSE 0 END)";
    }

    /** Hitung ulang poin satu siswa dari SELURUH absensinya + bonus struktur organisasi. */
    public static function hitungUlang(int $studentId): int
    {
        $row = DB::table('attendances as a')
            ->join('attendance_sessions as s', 'a.attendance_session_id', '=', 's.id')
            ->where('a.student_id', $studentId)
            ->where('s.status', '!=', 'cancelled')
            ->selectRaw(self::sqlCase().' as poin')
            ->first();

        $poin = (int) ($row->poin ?? 0);

        // Bonus struktur organisasi (+2 poin keaktifan jika menjabat)
        $punyaStruktur = DB::table('organization_structures')
            ->where('student_id', $studentId)
            ->exists();
        if ($punyaStruktur) {
            $poin += 2;
        }

        DB::table('students')->where('id', $studentId)->update(['diligence_points' => $poin]);

        return $poin;
    }

    /** Hitung ulang semua siswa (retroaktif / setelah aturan berubah). */
    public static function hitungUlangSemua(): int
    {
        $ids = DB::table('students')->pluck('id');

        foreach ($ids as $id) {
            self::hitungUlang((int) $id);
        }

        return $ids->count();
    }

    /**
     * Peringkat kerajinan satu kelas dalam rentang tanggal, untuk sertifikat semester.
     *
     * @return Collection<int, object> objek berkolom student_id, name, poin
     */
    public static function peringkat(Classroom $class, Carbon $dari, Carbon $hingga): Collection
    {
        $rows = DB::table('attendances as a')
            ->join('attendance_sessions as s', 'a.attendance_session_id', '=', 's.id')
            ->join('students as st', 'a.student_id', '=', 'st.id')
            ->where('st.class_id', $class->id)
            ->where('s.status', '!=', 'cancelled')
            ->whereBetween('s.session_date', [$dari->toDateString(), $hingga->toDateString()])
            ->groupBy('a.student_id', 'st.name')
            ->select('a.student_id', 'st.name')
            ->selectRaw(self::sqlCase().' as poin_absen')
            ->get();

        $pengurusIds = DB::table('organization_structures')
            ->where('class_id', $class->id)
            ->whereNotNull('student_id')
            ->pluck('student_id')
            ->flip()
            ->all();

        return $rows->map(function ($s) use ($pengurusIds) {
            $bonus = isset($pengurusIds[$s->student_id]) ? 2 : 0;
            $s->poin = ((int) $s->poin_absen) + $bonus;
            return $s;
        })->sortByDesc('poin')->values();
    }
}
