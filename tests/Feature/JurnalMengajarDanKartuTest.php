<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\TeachingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurnalMengajarDanKartuTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_jurnal_mengajar_bisa_dibuka(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('classes.jurnal.index', $class));
        $response->assertOk();
        $response->assertSee('Jurnal Mengajar Guru');
    }

    public function test_ai_opencode_jurnal_menghasilkan_tp_dan_aktivitas(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('classes.jurnal.generate-ai', $class), [
            'subject' => 'Informatika',
            'topic' => 'Algoritma dan Pemrograman',
            'meeting_number' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'subject',
                'topic',
                'meeting_number',
                'learning_objective',
                'activity',
                'p5_dimension',
                'reflection',
            ],
        ]);
    }

    public function test_simpan_jurnal_mengajar_berhasil(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('classes.jurnal.store', $class), [
            'session_date' => '2026-08-27',
            'meeting_number' => 1,
            'subject' => 'Informatika',
            'topic' => 'Pengenalan Berpikir Komputasional',
            'learning_objective' => 'Siswa mampu memahami dekomposisi dan pola.',
            'activity' => 'Pendahuluan, Inti, Penutup.',
            'p5_dimension' => 'Bernalar Kritis',
            'attendance_summary' => '32 Hadir Lengkap',
        ]);

        $response->assertRedirect(route('classes.jurnal.index', $class));
        $this->assertDatabaseHas('teaching_journals', [
            'class_id' => $class->id,
            'topic' => 'Pengenalan Berpikir Komputasional',
        ]);
    }

    public function test_halaman_kartu_pelajar_dan_qr_presensi_bisa_dibuka(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('classes.students.cards', $class));
        $response->assertOk();
        $response->assertSee('Kartu Pelajar &amp; QR Presensi Digital', false);
    }
}
