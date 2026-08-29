<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CashBook;
use App\Models\CharacterDimension;
use App\Models\CharacterRecord;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Holiday;
use App\Models\OrganizationStructure;
use App\Models\PaymentProof;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\Student;
use App\Models\StudentExcuse;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Services\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterComprehensiveBacktestTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $admin;
    protected Classroom $class;
    protected Student $studentA;
    protected Student $studentB;
    protected AttendanceSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        // 1. Guru / Wali Kelas PRO
        $this->teacher = User::factory()->create([
            'name' => 'Pak Guru Andika',
            'email' => 'andika@sekolah.id',
            'role' => 'teacher',
            'whatsapp_number' => '6283817203455',
            'wa_session_status' => 'connected',
            'subscription_ends_at' => now()->addYear(),
            'is_active' => true,
        ]);

        // 2. Admin Operator
        $this->admin = User::factory()->create([
            'name' => 'Operator Sekolah',
            'email' => 'admin@sekolah.id',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 3. Kelas Perwalian
        $this->class = Classroom::factory()->create([
            'user_id' => $this->teacher->id,
            'name' => 'XII TKJ D',
            'academic_year' => '2025/2026',
            'major' => 'Teknik Komputer Jaringan',
            'parent_group_wa' => '120363321166050533@g.us',
            'public_token' => 'token-public-kelas-xii-tkj-d-999',
            'is_active' => true,
        ]);

        // 4. Siswa
        $this->studentA = Student::factory()->create([
            'user_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'name' => 'Ahmad Rendy Moerdany',
            'nis' => '2025001',
            'nisn' => '0012345678',
            'gender' => 'L',
            'parent_phone' => '082110554446',
            'discipline_points' => 100,
            'password' => bcrypt('password123'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $this->studentB = Student::factory()->create([
            'user_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'name' => 'Siti Harum Delisma',
            'nis' => '2025002',
            'nisn' => '0012345679',
            'gender' => 'P',
            'parent_phone' => '082120949446',
            'discipline_points' => 95,
            'password' => bcrypt('password123'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        // 5. Dimension P5
        CharacterDimension::create([
            'code' => 'BK',
            'name' => 'Bernalar Kritis',
            'description' => 'Menganalisis dan mengevaluasi penalaran',
        ]);
    }

    /**
     * TEST JOURNEY 1: Alur Sesi Presensi, Magic Link, Input Kehadiran & Rekap Otomatis Grup Orang Tua
     */
    public function test_journey_attendance_magic_link_and_auto_parent_recap(): void
    {
        $service = app(AttendanceSessionService::class);

        // 1. Buat Sesi Presensi
        $res = $service->create($this->class, title: 'Presensi Pagi XII TKJ D');
        $session = $res['session'];
        $pin = $res['pin'];

        $this->assertNotNull($session->token);
        $this->assertEquals('open', $session->status);

        // 2. Dispatch Magic Link ke Petugas
        $dispatched = $service->dispatchMagicLink($session, '628123456789', $pin);
        $this->assertTrue($dispatched);

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use ($session, $pin) {
            return $job->to === '628123456789'
                && str_contains($job->message, $pin)
                && str_contains($job->message, $session->magicLink());
        });

        // 3. Petugas Buka Halaman PIN
        $response = $this->get(route('magic.show', $session->token));
        $response->assertOk();

        // 4. Petugas Verifikasi PIN
        $response = $this->post(route('magic.verify', $session->token), ['pin' => $pin]);
        $response->assertRedirect(route('magic.roster', $session->token));

        // 5. Petugas Submit Absensi (Ahmad Terlambat, Siti Hadir)
        $response = $this->withSession(["magic_ok:{$session->token}" => true])
            ->post(route('magic.submit', $session->token), [
                'attendance' => [
                    $this->studentA->id => 'terlambat',
                    $this->studentB->id => 'hadir',
                ],
                'notes' => [
                    $this->studentA->id => 'Nganter nenek ke RS',
                ],
            ]);

        $response->assertRedirect(route('magic.done', $session->token));

        // 6. Verifikasi Data Absensi Tersimpan
        $this->assertDatabaseHas('attendances', [
            'attendance_session_id' => $session->id,
            'student_id' => $this->studentA->id,
            'status' => 'terlambat',
            'note' => 'Nganter nenek ke RS',
        ]);

        $this->assertDatabaseHas('attendances', [
            'attendance_session_id' => $session->id,
            'student_id' => $this->studentB->id,
            'status' => 'hadir',
        ]);

        // 7. Verifikasi Rekap Otomatis dikirimkan ke Grup Orang Tua
        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
            return $job->to === '120363321166050533@g.us'
                && str_contains($job->message, 'Rekap Absensi XII TKJ D')
                && str_contains($job->message, 'Ahmad Rendy Moerdany — Terlambat');
        });
    }

    /**
     * TEST JOURNEY 2: Link Publik Orang Tua & Siswa (Izin/Sakit, Biodata Mandiri, Refleksi P5)
     */
    public function test_journey_public_parent_and_student_links(): void
    {
        $c = $this->class;
        $st = $this->studentA;

        // A. FORM IZIN / SAKIT ORANG TUA
        // 1. Tampil form
        $res = $this->get(route('public.excuse.show', $c->public_token));
        $res->assertOk();

        // 2. Submit form izin dengan upload bukti
        $fakeFile = UploadedFile::fake()->image('surat_dokter.jpg');
        $res = $this->post(route('public.excuse.store', $c->public_token), [
            'student_id' => $st->id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'izin',
            'keterangan' => 'Acara keluarga mendesak',
            'parent_phone_last4' => '4446',
            'attachment' => $fakeFile,
        ]);

        $res->assertStatus(302);
        $res->assertSessionHas('success_public');

        $this->assertDatabaseHas('student_excuses', [
            'class_id' => $c->id,
            'student_id' => $st->id,
            'jenis' => 'izin',
            'keterangan' => 'Acara keluarga mendesak',
        ]);

        // B. FORM BIODATA MANDIRI SISWA
        // 1. Tampil form
        $res = $this->get(route('public.biodata.show', $c->public_token));
        $res->assertOk();

        // 2. Submit form biodata
        $res = $this->post(route('public.biodata.store', $c->public_token), [
            'student_id' => $st->id,
            'nis' => '2025001',
            'nisn' => '0012345678',
            'parent_phone' => '082110554446',
            'parent_phone_last4' => '4446',
            'address' => 'Jl. Merdeka No. 45',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2008-05-15',
        ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('students', [
            'id' => $st->id,
            'address' => 'Jl. Merdeka No. 45',
            'tempat_lahir' => 'Jakarta',
        ]);

        // C. FORM REFLEKSI KARAKTER P5 SISWA
        // 1. Tampil form
        $res = $this->get(route('public.reflection.show', $c->public_token));
        $res->assertOk();

        // 2. Submit form refleksi
        $dim = CharacterDimension::first();
        $res = $this->post(route('public.reflection.store', $c->public_token), [
            'student_id' => $st->id,
            'character_dimension_id' => $dim->id,
            'self_rating' => 5,
            'what_went_well' => 'Saya berhasil menyelesaikan tugas tepat waktu',
            'what_to_improve' => 'Tingkatkan kerja sama tim',
            'action_plan' => 'Lebih aktif berdiskusi dalam kelompok',
        ]);

        $res->assertStatus(302);
        $res->assertSessionHas('success_public');

        $this->assertDatabaseHas('character_reflections', [
            'student_id' => $st->id,
            'character_dimension_id' => $dim->id,
            'self_rating' => 5,
        ]);
    }

    /**
     * TEST JOURNEY 3: Modul Akademik & Administrasi Guru (Jurnal AI, Nilai, Kas, EWS, Pelanggaran, dsb)
     */
    public function test_journey_teacher_academic_modules(): void
    {
        $c = $this->class;
        $st = $this->studentA;

        // 1. Jurnal Mengajar
        $res = $this->actingAs($this->teacher)->post(route('classes.jurnal.store', $c), [
            'session_date' => now()->toDateString(),
            'meeting_number' => 1,
            'subject' => 'Teknologi Jaringan Berbasis Luas (WAN)',
            'topic' => 'Konfigurasi Jaringan Fiber Optic',
            'learning_objectives' => 'Memahami arsitektur kabel serat optik',
            'activities' => 'Praktik splicing dan OTDR',
            'p5_dimensions' => ['Bernalar Kritis', 'Mandiri'],
            'present_count' => 36,
            'absent_count' => 2,
        ]);
        $res->assertRedirect(route('classes.jurnal.index', $c));
        $this->assertDatabaseHas('teaching_journals', [
            'class_id' => $c->id,
            'subject' => 'Teknologi Jaringan Berbasis Luas (WAN)',
        ]);

        // 2. Penilaian Harian & Rekap
        $res = $this->actingAs($this->teacher)->post(route('classes.nilai.store', $c), [
            'mapel' => 'WAN',
            'jenis' => 'harian',
            'semester' => 1,
            'assessment_date' => now()->toDateString(),
            'capaian_pembelajaran' => 'Konfigurasi Fiber Optic',
            'nilai' => [
                $this->studentA->id => 90,
                $this->studentB->id => 88,
            ],
        ]);
        $res->assertStatus(302);
        $this->assertDatabaseHas('assessments', [
            'class_id' => $c->id,
            'mapel' => 'WAN',
            'jenis' => 'harian',
        ]);

        // 3. Buku Kas Masuk & Keluar
        $res = $this->actingAs($this->teacher)->post(route('classes.cashbook.store', $c), [
            'transaction_date' => now()->toDateString(),
            'type' => 'in',
            'amount' => 50000,
            'description' => 'Uang Kas Mingguan',
            'student_id' => $st->id,
        ]);
        $res->assertStatus(302);
        $this->assertDatabaseHas('cash_books', [
            'class_id' => $c->id,
            'type' => 'in',
            'amount' => 50000,
        ]);

        // 4. Catatan Pelanggaran
        $vtype = ViolationType::create([
            'user_id' => $this->teacher->id,
            'name' => 'Terlambat Upacara',
            'points' => 5,
            'category' => 'ringan',
        ]);

        $res = $this->actingAs($this->teacher)->post(route('classes.violations.store', $c), [
            'student_id' => $st->id,
            'violation_type_id' => $vtype->id,
            'occurred_on' => now()->toDateString(),
            'note' => 'Terlambat 10 menit',
        ]);
        $res->assertStatus(302);
        $this->assertDatabaseHas('violations', [
            'class_id' => $c->id,
            'student_id' => $st->id,
            'violation_type_id' => $vtype->id,
        ]);

        // 5. Poin Kerajinan Index
        $res = $this->actingAs($this->teacher)->get(route('classes.kerajinan.index', $c));
        $res->assertOk();

        // 6. Denah Meja Duduk
        $res = $this->actingAs($this->teacher)->post(route('classes.seating.save', $c), [
            'layout' => json_encode([
                ['row' => 1, 'col' => 1, 'student_id' => $st->id],
            ]),
        ]);
        $res->assertStatus(200);

        // 7. Struktur Organisasi
        $roles = array_keys(config('walikelas.student_roles'));
        $firstRole = $roles[0] ?? 'ketua_kelas';
        $res = $this->actingAs($this->teacher)->post(route('classes.organization.store', $c), [
            'role' => $firstRole,
            'student_id' => $st->id,
        ]);
        $res->assertStatus(302);
        $this->assertDatabaseHas('organization_structures', [
            'class_id' => $c->id,
            'student_id' => $st->id,
            'role' => $firstRole,
        ]);
    }

    /**
     * TEST JOURNEY 4: Seluruh Menu Utama Guru, Submenu Kelas, Portal Siswa, & Admin Panel
     */
    public function test_journey_all_routes_status_ok(): void
    {
        $c = $this->class;
        $st = $this->studentA;

        // Session dummy for attendance view
        $session = AttendanceSession::create([
            'user_id' => $this->teacher->id,
            'class_id' => $c->id,
            'session_date' => now()->toDateString(),
            'title' => 'Sesi Audit Final',
            'token' => 'token-audit-final-123',
            'pin_hash' => Hash::make('1234'),
            'expires_at' => now()->addHours(2),
            'status' => 'submitted',
        ]);

        $teacherRoutes = [
            'Dashboard' => route('dashboard'),
            'Classes Index' => route('classes.index'),
            'Classes Create' => route('classes.create'),
            'Class Show' => route('classes.show', $c),
            'Class Edit' => route('classes.edit', $c),
            'Class Attendance Index' => route('classes.attendance.index', $c),
            'Class Attendance Manual' => route('classes.attendance.manual.create', $c),
            'Class Attendance Show' => route('classes.attendance.show', [$c, $session]),
            'Class Students Index' => route('classes.students.index', $c),
            'Class Students Create' => route('classes.students.create', $c),
            'Class Student Detail FUT' => route('classes.students.show', [$c, $st]),
            'Class Student Edit' => route('classes.students.edit', [$c, $st]),
            'Class Student Cards' => route('classes.students.cards', $c),
            'Class Student Cards PDF' => route('classes.students.cards.pdf', $c),
            'Class Student QR Cards' => route('classes.students.qr-cards', $c),
            'Class EWS' => route('classes.ews.index', $c),
            'Class Jurnal Index' => route('classes.jurnal.index', $c),
            'Class Jurnal Create AI' => route('classes.jurnal.create', $c),
            'Class Nilai Index' => route('classes.nilai.index', $c),
            'Class Nilai Rekap' => route('classes.nilai.rekap', $c),
            'Class Schedules' => route('classes.schedules.index', $c),
            'Class Character' => route('classes.character-portfolio.index', $c),
            'Class Violations' => route('classes.violations.index', $c),
            'Class Kerajinan' => route('classes.kerajinan.index', $c),
            'Class Cashbook' => route('classes.cashbook.index', $c),
            'Class Cashbook Per Siswa' => route('classes.cashbook.per-siswa', $c),
            'Class Seating' => route('classes.seating.index', $c),
            'Class Organization' => route('classes.organization.index', $c),
            'Class Reports Attendance' => route('classes.reports.attendance', $c),
            'Class Reports Full' => route('classes.reports.full', $c),
            'Class Reports Analisis' => route('classes.reports.analisis', $c),
            'Analytics' => route('analytics.index'),
            'Holidays' => route('holidays.index'),
            'Notifications' => route('notifications.index'),
            'Notification Settings' => route('notifications.settings'),
            'WhatsApp' => route('whatsapp.index'),
            'Violation Types' => route('violation-types.index'),
            'Subscription' => route('subscription.index'),
            'Profile' => route('profile.edit'),
        ];

        foreach ($teacherRoutes as $name => $url) {
            $res = $this->actingAs($this->teacher)->get($url);
            $this->assertTrue(
                in_array($res->status(), [200, 302]),
                "Teacher Route '{$name}' ({$url}) failed with status: " . $res->status()
            );
        }

        // Student Portal
        $res = $this->actingAs($st, 'student')->get(route('student.dashboard'));
        $res->assertOk();

        $res = $this->actingAs($st, 'student')->get(route('student.biodata'));
        $res->assertOk();

        $res = $this->actingAs($st, 'student')->get(route('student.portfolio'));
        $res->assertOk();

        // Admin Operator Panel
        $adminRoutes = [
            'Admin Dashboard' => route('admin.dashboard'),
            'Admin Teachers' => route('admin.teachers.index'),
            'Admin Announcements' => route('admin.announcements.form'),
            'Admin Subscriptions' => route('admin.subscriptions.index'),
        ];

        foreach ($adminRoutes as $name => $url) {
            $res = $this->actingAs($this->admin)->get($url);
            $this->assertTrue(
                in_array($res->status(), [200, 302]),
                "Admin Route '{$name}' ({$url}) failed with status: " . $res->status()
            );
        }
    }
}
