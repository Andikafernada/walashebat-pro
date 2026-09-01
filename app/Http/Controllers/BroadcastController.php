<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BroadcastController extends Controller
{
    public function __construct(
        protected WhatsAppService $waService
    ) {}

    /**
     * Halaman Utama WhatsApp Broadcast Pengumuman Kelas.
     */
    public function index(Request $request, Classroom $class): View
    {
        $students = $class->students()
            ->where('is_active', true)
            ->with(['attendanceLogs'])
            ->orderBy('name')
            ->get();

        // Hitung persentase kehadiran masing-masing siswa
        $studentList = $students->map(function (Student $st) {
            $total = $st->attendanceLogs->count();
            $hadir = $st->attendanceLogs->where('status', 'hadir')->count();
            $persen = $total > 0 ? round(($hadir / $total) * 100) : 100;

            return [
                'id' => $st->id,
                'name' => $st->name,
                'nis' => $st->nis ?: '—',
                'parent_name' => $st->parent_name ?: 'Orang Tua / Wali',
                'parent_phone' => $st->parent_phone ?: $st->phone,
                'persen_kehadiran' => $persen . '%',
            ];
        });

        // Template Bawaan yang Sangat Disukai Guru
        $templates = [
            [
                'judul' => '📢 Pengumuman Rapat Wali Murid',
                'pesan' => "Yth. Bapak/Ibu {{nama_ortu}} (Wali dari ananda {{nama_siswa}}),\n\nKami mengundang Bapak/Ibu untuk menghadiri pertemuan wali murid kelas {{nama_kelas}} yang akan diadakan pada:\n🗓️ Hari/Tgl: Sabtu, [Tentukan Tanggal]\n⏰ Pukul: 08.30 WIB - Selesai\n📍 Tempat: Ruang Kelas {{nama_kelas}}\n\nKehadiran Bapak/Ibu sangat kami harapkan demi kemajuan belajar ananda. Terima kasih.\n\nSalam hormat,\nWali Kelas {{nama_kelas}}",
            ],
            [
                'judul' => '📝 Jadwal Asesmen / Penilaian Sumatif',
                'pesan' => "Yth. Orang tua dari ananda {{nama_siswa}},\n\nMemberitahukan bahwa pelaksanaan Asesmen Sumatif (STS/SAS) Semester ini akan dimulai pada [Tentukan Tanggal]. Mohon bimbingan dan pendampingan belajar ananda di rumah agar dapat mengikuti ujian dengan optimal.\n\nCatatan: Persentase kehadiran ananda saat ini: {{persen_kehadiran}}.\n\nTerima kasih atas kerja samanya.",
            ],
            [
                'judul' => '🏖️ Informasi Hari Libur Sekolah',
                'pesan' => "Yth. Bapak/Ibu Wali dari {{nama_siswa}},\n\nSehubungan dengan libur nasional, kegiatan pembelajaran kelas {{nama_kelas}} diliburkan mulai tanggal [Tgl Mulai] sampai [Tgl Selesai]. Siswa kembali masuk sekolah pada tanggal [Tgl Masuk].\n\nSelamat berlibur dan tetap jaga kesehatan ananda.",
            ],
            [
                'judul' => '⚠️ Pengingat Evaluasi Kehadiran',
                'pesan' => "Yth. Bapak/Ibu {{nama_ortu}},\n\nKami menginformasikan rekap presensi ananda {{nama_siswa}} di kelas {{nama_kelas}} saat ini mencapai {{persen_kehadiran}}.\n\nMohon pastikan ananda dapat hadir tepat waktu pada jam pelajaran. Jika ananda berhalangan hadir karena sakit/izin, mohon mengisi formulir izin resmi kelas kita. Terima kasih.",
            ],
        ];

        return view('broadcast.index', [
            'classroom' => $class,
            'students' => $studentList,
            'templates' => $templates,
            'waSession' => $request->user()->wa_session_status ?? 'disconnected',
        ]);
    }

    /**
     * Kirim Pesan Broadcast via WhatsApp Gateway secara Otomatis.
     */
    public function send(Request $request, Classroom $class)
    {
        $request->validate([
            'message_template' => 'required|string|min:5',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
        ]);

        $students = $class->students()->whereIn('id', $request->student_ids)->get();
        $template = $request->message_template;
        $sentCount = 0;
        $failedCount = 0;

        foreach ($students as $student) {
            $phone = $student->parent_phone ?: $student->phone;
            if (empty($phone)) {
                $failedCount++;
                continue;
            }

            // Hitung kehadiran
            $total = $student->attendanceLogs->count();
            $hadir = $student->attendanceLogs->where('status', 'hadir')->count();
            $persen = $total > 0 ? round(($hadir / $total) * 100) : 100;

            // Ganti Merge Tags
            $pesanPersonal = str_replace(
                [
                    '{{nama_siswa}}',
                    '{{nama_ortu}}',
                    '{{nis}}',
                    '{{nama_kelas}}',
                    '{{persen_kehadiran}}',
                ],
                [
                    $student->name,
                    $student->parent_name ?: 'Orang Tua/Wali',
                    $student->nis ?: '—',
                    $class->name,
                    $persen . '%',
                ],
                $template
            );

            // Kirim via WA Gateway jika terhubung
            try {
                $this->waService->sendMessage($phone, $pesanPersonal, $request->user());
                $sentCount++;
            } catch (\Throwable $e) {
                $failedCount++;
            }
        }

        return back()->with('success', "Pesan broadcast berhasil diproses! ({$sentCount} terkirim, {$failedCount} gagal/tanpa nomor WA).");
    }
}
