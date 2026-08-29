<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CashBook;
use App\Models\CharacterRecord;
use App\Models\Classroom;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\Student;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComprehensiveAuditAllMenusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Classroom $classroom;
    protected Student $student;
    protected AttendanceSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create standard Teacher
        $this->user = User::factory()->create([
            'email' => 'guru.audit@sekolah.id',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        // 2. Create Admin/Operator
        $this->admin = User::factory()->create([
            'email' => 'operator.audit@sekolah.id',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 3. Create Class & Students
        $this->classroom = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Kelas X-A Audit',
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);

        $this->student = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->classroom->id,
            'name' => 'Ahmad Siswa Audit',
            'nis' => '2025001',
            'nisn' => '0012345678',
            'gender' => 'L',
            'parent_phone' => '081234567890',
            'discipline_points' => 100,
            'password' => bcrypt('password123'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        // 4. Create Sample Data for all submodules
        $this->session = AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->classroom->id,
            'session_date' => now()->toDateString(),
            'title' => 'Sesi Presensi Audit',
            'token' => 'token-audit-123456',
            'pin_hash' => Hash::make('1234'),
            'expires_at' => now()->addHours(6),
            'status' => 'draft',
        ]);

        TeachingJournal::create([
            'user_id' => $this->user->id,
            'class_id' => $this->classroom->id,
            'session_date' => now()->toDateString(),
            'subject' => 'Informatika',
            'topic' => 'Algoritma Pemrograman',
            'learning_objectives' => 'Siswa mampu memahami algoritma',
            'activities' => 'Diskusi kelompok dan presentasi',
            'p5_dimensions' => ['Bernalar Kritis', 'Gotong Royong'],
            'present_count' => 30,
            'absent_count' => 0,
        ]);

        ViolationType::create([
            'user_id' => $this->user->id,
            'name' => 'Terlambat Masuk Kelas',
            'points' => 5,
            'category' => 'ringan',
        ]);

        CashBook::create([
            'user_id' => $this->user->id,
            'class_id' => $this->classroom->id,
            'transaction_date' => now()->toDateString(),
            'type' => 'in',
            'amount' => 50000,
            'description' => 'Iuran Kas Kelas',
        ]);
    }

    /**
     * Test Teacher Main Navigation Menus
     */
    public function test_teacher_main_menus_status(): void
    {
        $routes = [
            'Dashboard Beranda' => route('dashboard'),
            'Daftar Kelas' => route('classes.index'),
            'Buat Kelas Baru' => route('classes.create'),
            'Statistik Analitik' => route('analytics.index'),
            'Kalender Hari Libur' => route('holidays.index'),
            'Notifikasi Pengumuman' => route('notifications.index'),
            'Pengaturan Notifikasi' => route('notifications.settings'),
            'Integrasi WhatsApp' => route('whatsapp.index'),
            'Jenis Pelanggaran' => route('violation-types.index'),
            'Langganan Paket PRO' => route('subscription.index'),
            'Profil Akun & Sekolah' => route('profile.edit'),
        ];

        foreach ($routes as $label => $url) {
            $response = $this->actingAs($this->user)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302]),
                "Menu Teacher '{$label}' ({$url}) failed with status: " . $response->status()
            );
        }
    }

    /**
     * Test Classroom Specific Sub-Menus
     */
    public function test_classroom_submenus_status(): void
    {
        $c = $this->classroom;
        $s = $this->student;
        $a = $this->session;

        $routes = [
            'Detail Kelas' => route('classes.show', $c),
            'Ubah Kelas' => route('classes.edit', $c),
            'Rekap Presensi Kelas' => route('classes.attendance.index', $c),
            'Input Manual Presensi' => route('classes.attendance.manual.create', $c),
            'Detail Sesi Presensi' => route('classes.attendance.show', [$c, $a]),
            'Edit Sesi Presensi' => route('classes.attendance.edit', [$c, $a]),
            'Daftar Siswa' => route('classes.students.index', $c),
            'Tambah Siswa Baru' => route('classes.students.create', $c),
            'Detail Siswa & FUT Card' => route('classes.students.show', [$c, $s]),
            'Ubah Biodata Siswa' => route('classes.students.edit', [$c, $s]),
            'Impor Excel Siswa' => route('classes.students.import.form', $c),
            'Pratinjau Kartu Pelajar' => route('classes.students.cards', $c),
            'Cetak PDF Kartu Pelajar A4' => route('classes.students.cards.pdf', $c),
            'Kartu QR Presensi' => route('classes.students.qr-cards', $c),
            'EWS Risiko Siswa' => route('classes.ews.index', $c),
            'Daftar Jurnal Mengajar' => route('classes.jurnal.index', $c),
            'Buat Jurnal Mengajar AI' => route('classes.jurnal.create', $c),
            'Daftar Nilai Siswa' => route('classes.nilai.index', $c),
            'Input Nilai Baru' => route('classes.nilai.create', $c),
            'Rekap Nilai Rapor' => route('classes.nilai.rekap', $c),
            'Jadwal Pelajaran' => route('classes.schedules.index', $c),
            'Portofolio Karakter P5' => route('classes.character-portfolio.index', $c),
            'Catatan Pelanggaran' => route('classes.violations.index', $c),
            'Poin Kerajinan' => route('classes.kerajinan.index', $c),
            'Buku Kas Kelas' => route('classes.cashbook.index', $c),
            'Buku Kas Per Siswa' => route('classes.cashbook.per-siswa', $c),
            'Denah Meja Duduk' => route('classes.seating.index', $c),
            'Struktur Organisasi Kelas' => route('classes.organization.index', $c),
            'Laporan Presensi PDF' => route('classes.reports.attendance', $c),
            'Laporan Lengkap Wali Kelas' => route('classes.reports.full', $c),
            'Analisis Grafik Kehadiran' => route('classes.reports.analisis', $c),
        ];

        foreach ($routes as $label => $url) {
            $response = $this->actingAs($this->user)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302]),
                "Submenu '{$label}' ({$url}) failed with status: " . $response->status()
            );
        }
    }

    /**
     * Test Operator / Admin Menus
     */
    public function test_admin_operator_menus_status(): void
    {
        $routes = [
            'Admin Dashboard' => route('admin.dashboard'),
            'Admin Manajemen Guru' => route('admin.teachers.index'),
            'Admin Pengumuman' => route('admin.announcements.form'),
            'Admin Data Langganan' => route('admin.subscriptions.index'),
        ];

        foreach ($routes as $label => $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302]),
                "Admin Menu '{$label}' ({$url}) failed with status: " . $response->status()
            );
        }
    }

    /**
     * Test Student Portal Menus
     */
    public function test_student_portal_menus_status(): void
    {
        $student = $this->student;

        // Login page
        $response = $this->get(route('student.login'));
        $response->assertOk();

        // Student authenticated
        $response = $this->actingAs($student, 'student')->get(route('student.dashboard'));
        $response->assertOk();

        $response = $this->actingAs($student, 'student')->get(route('student.biodata'));
        $response->assertOk();

        $response = $this->actingAs($student, 'student')->get(route('student.portfolio'));
        $response->assertOk();
    }
}
