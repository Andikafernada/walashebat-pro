<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentExcuse;
use App\Models\User;
use App\Services\AttendanceSessionService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Lapor izin/sakit lewat formulir publik, bukan chat bebas grup WhatsApp.
 *
 * Yang dijaga: laporan orang tua sampai ke wali kelas sebagai catatan yang
 * terlihat saat roster diisi, TANPA otomatis mencentang status kehadiran
 * siswa — roster sengaja tidak punya nilai bawaan per baris (lihat komentar
 * di roster.blade.php), dan itu berlaku sama untuk laporan yang belum tentu
 * benar ini.
 */
class LaporIzinSakitTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    private Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Queue::fake();

        $this->guru = User::factory()->create();
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id, 'parent_group_wa' => '628111@g.us']);
        $this->siswa = Student::factory()->create(['user_id' => $this->guru->id, 'class_id' => $this->kelas->id, 'is_active' => true]);
    }

    public function test_formulir_menampilkan_daftar_siswa_kelas(): void
    {
        $this->get(route('public.excuse.show', $this->kelas->tokenPublik()))
            ->assertOk()
            ->assertSee($this->siswa->name);
    }

    public function test_laporan_tersimpan_dan_terkait_ke_siswa_yang_benar(): void
    {
        $this->post(route('public.excuse.store', $this->kelas->tokenPublik()), [
            'student_id' => $this->siswa->id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'sakit',
            'keterangan' => 'Demam sejak semalam.',
        ])->assertSessionHasNoErrors();

        $lapor = StudentExcuse::withoutTenant()->firstOrFail();
        $this->assertSame($this->siswa->id, $lapor->student_id);
        $this->assertSame($this->kelas->id, $lapor->class_id);
        $this->assertSame($this->guru->id, $lapor->user_id);
        $this->assertSame('sakit', $lapor->jenis);
    }

    /** exists rule polos menerima siswa kelas mana pun; harus disaring ke kelas ini. */
    public function test_siswa_kelas_lain_ditolak(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => User::factory()->create()->id]);
        $siswaLain = Student::factory()->create(['user_id' => $kelasLain->user_id, 'class_id' => $kelasLain->id]);

        $this->post(route('public.excuse.store', $this->kelas->tokenPublik()), [
            'student_id' => $siswaLain->id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'izin',
        ])->assertSessionHasErrors('student_id');

        $this->assertSame(0, StudentExcuse::withoutTenant()->count());
    }

    public function test_tanggal_terlalu_lama_berlalu_ditolak(): void
    {
        $this->post(route('public.excuse.store', $this->kelas->tokenPublik()), [
            'student_id' => $this->siswa->id,
            'tanggal' => now()->subDays(10)->toDateString(),
            'jenis' => 'izin',
        ])->assertSessionHasErrors('tanggal');
    }

    public function test_roster_menampilkan_catatan_laporan_tanpa_mencentang_status(): void
    {
        (new StudentExcuse)->forceFill([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id, 'student_id' => $this->siswa->id,
            'tanggal' => now()->toDateString(), 'jenis' => 'sakit', 'keterangan' => 'Demam.',
        ])->save();

        $this->actingAs($this->guru);
        ['session' => $session, 'pin' => $pin] = app(AttendanceSessionService::class)->create($this->kelas);
        auth()->logout();

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);

        $html = $this->get(route('magic.roster', $session->token))->assertOk()->getContent();

        $this->assertStringContainsString('Orang tua lapor', $html);
        $this->assertStringContainsString('Demam.', $html);

        // Catatan, bukan pra-pilihan: tidak ada radio yang sudah checked untuk siswa ini.
        $this->assertStringNotContainsString('name="attendance['.$this->siswa->id.']" value="sakit" required checked', $html);
    }

    public function test_roster_tidak_menampilkan_laporan_tanggal_lain(): void
    {
        (new StudentExcuse)->forceFill([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id, 'student_id' => $this->siswa->id,
            'tanggal' => now()->addDays(2)->toDateString(), 'jenis' => 'izin',
        ])->save();

        $this->actingAs($this->guru);
        ['session' => $session, 'pin' => $pin] = app(AttendanceSessionService::class)->create($this->kelas);
        auth()->logout();

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);

        $this->get(route('magic.roster', $session->token))
            ->assertOk()
            ->assertDontSee('Orang tua lapor');
    }

    public function test_wali_kelas_bisa_membagikan_link_ke_grup(): void
    {
        $this->actingAs($this->guru)
            ->post(route('classes.share-excuse-wa', $this->kelas))
            ->assertRedirect();

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
            return $job->to === '628111@g.us'
                && str_contains($job->message, route('public.excuse.show', $this->kelas->tokenPublik()))
                && str_contains($job->message, 'WAJIB');
        });
    }

    public function test_tanpa_grup_wa_ditolak_dengan_pesan_jelas(): void
    {
        $this->kelas->update(['parent_group_wa' => null]);

        $this->actingAs($this->guru)
            ->post(route('classes.share-excuse-wa', $this->kelas))
            ->assertRedirect();

        Queue::assertNotPushed(SendWhatsAppMessage::class);
    }
}
