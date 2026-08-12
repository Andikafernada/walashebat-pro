<?php

namespace App\Support;

use App\Models\Classroom;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Poin kerajinan siswa dari kehadiran.
 *
 * Terpisah dari discipline_points (yang digerakkan pelanggaran): angka ini
 * murni akumulasi kehadiran, supaya "siswa terajin" adalah soal rajin masuk,
 * bukan sekadar bersih dari pelanggaran.
 *
 * Sumber kebenaran adalah tabel attendances. Kolom students.diligence_points
 * hanyalah cache yang SELALU dihitung ulang dari nol (bukan delta), sehingga
 * tidak bisa melenceng meski absensi diedit, dihapus, atau sesinya dibatalkan.
 * Kueri pakai raw DB agar lolos TenantScope di konteks publik (magic link
 * absensi tidak punya sesi login).
 */
class PoinKerajinan
{
    // ponytail: satu tempat menyetel aturan poin. Status lain (sakit, izin,
    // terlambat) sengaja bernilai 0 — ubah di sini kalau kebijakan berubah,
    // lalu jalankan `php artisan poin:hitung-ulang`.
    public const NILAI = ['hadir' => 5, 'alfa' => -10];

    /** SQL CASE penjumlah poin, dipakai bersama agar aturannya satu sumber. */
    private static function sqlCase(string $kolom = 'a.status'): string
    {
        // NILAI adalah literal tepercaya dari kode ini, bukan input pengguna.
        $cases = '';
        foreach (self::NILAI as $status => $poin) {
            $cases .= " WHEN '".$status."' THEN ".(int) $poin;
        }

        return "SUM(CASE {$kolom}{$cases} ELSE 0 END)";
    }

    /** Hitung ulang poin satu siswa dari SELURUH absensinya (sesi non-batal). */
    public static function hitungUlang(int $studentId): int
    {
        $row = DB::table('attendances as a')
            ->join('attendance_sessions as s', 'a.attendance_session_id', '=', 's.id')
            ->where('a.student_id', $studentId)
            ->where('s.status', '!=', 'cancelled')
            ->selectRaw(self::sqlCase().' as poin')
            ->first();

        $poin = (int) ($row->poin ?? 0);

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
     * Peringkat kerajinan satu kelas dalam rentang tanggal, untuk sertifikat
     * semester. Hanya siswa dengan absensi pada rentang itu yang muncul.
     *
     * @return Collection<int, object> objek berkolom student_id, name, poin
     */
    public static function peringkat(Classroom $class, Carbon $dari, Carbon $hingga): Collection
    {
        return DB::table('attendances as a')
            ->join('attendance_sessions as s', 'a.attendance_session_id', '=', 's.id')
            ->join('students as st', 'a.student_id', '=', 'st.id')
            ->where('st.class_id', $class->id)
            ->where('s.status', '!=', 'cancelled')
            ->whereBetween('s.session_date', [$dari->toDateString(), $hingga->toDateString()])
            ->groupBy('a.student_id', 'st.name')
            ->select('a.student_id', 'st.name')
            ->selectRaw(self::sqlCase().' as poin')
            ->orderByDesc('poin')
            ->orderBy('st.name')
            ->get();
    }
}
