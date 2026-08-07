<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Audit05ViewFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);
    }

    public function test_halaman_portofolio_siswa_dengan_refleksi(): void
    {
        $s = Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $this->class->id]);
        $d = CharacterDimension::create([
            'user_id' => $this->user->id, 'code' => 'iman', 'name' => 'Keimanan',
            'is_active' => true, 'sort_order' => 1,
        ]);
        CharacterReflection::create([
            'user_id' => $this->user->id,
            'student_id' => $s->id, 'class_id' => $this->class->id,
            'character_dimension_id' => $d->id, 'period' => 'weekly',
            'reflection_date' => now()->toDateString(), 'self_rating' => 4,
            'what_went_well' => 'baik', 'what_to_improve' => 'lebih rajin',
            'action_plan' => 'belajar', 'status' => 'submitted', 'submitted_at' => now(),
        ]);

        $r = $this->get(route('classes.character-portfolio.student', [$this->class, $s]));
        dump('STATUS: '.$r->getStatusCode());
        $r->assertOk();
    }

    public function test_notifikasi_settings_patch_kosong_tetap_sukses(): void
    {
        $pref = \App\Models\UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'push_enabled' => true, 'push_attendance_reminder' => true,
            'push_new_violation' => true, 'push_low_cashbook' => true,
            'push_daily_summary' => true,
        ]);

        // (a) Persis kondisi produksi: PATCH tiba TANPA input sama sekali.
        $r = $this->patchJson(route('notifications.settings.update'), []);
        dump('A) status='.$r->getStatusCode().' body='.$r->getContent());
        $pref->refresh();
        dump('A) setelah PATCH kosong: '.json_encode($pref->only([
            'push_enabled','push_attendance_reminder','push_new_violation',
            'push_low_cashbook','push_daily_summary',
        ])));

        // (b) Checkbox tak tercentang tidak ikut terkirim -> mematikan preferensi mustahil.
        $r2 = $this->patchJson(route('notifications.settings.update'), ['push_enabled' => '1']);
        $pref->refresh();
        dump('B) status='.$r2->getStatusCode().' -> '.json_encode($pref->only([
            'push_enabled','push_attendance_reminder','push_new_violation',
            'push_low_cashbook','push_daily_summary',
        ])));
        $this->assertTrue(true);
    }

    public function test_cashbook_student_id_diabaikan(): void
    {
        $st = \App\Models\Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $this->class->id]);

        $html = $this->get(route('classes.cashbook.index', $this->class))->assertOk()->getContent();
        dump('select student_id ada di halaman: '.(str_contains($html, 'name="student_id"') ? 'YA' : 'TIDAK'));

        $r = $this->post(route('classes.cashbook.store', $this->class), [
            'transaction_date' => '2026-08-04', 'type' => 'in', 'amount' => 5000,
            'description' => 'Kas mingguan', 'student_id' => $st->id,
        ]);
        dump('POST status='.$r->getStatusCode().' session errors='.json_encode(session('errors') ? session('errors')->all() : []));
        $row = \App\Models\CashBook::withoutTenant()->latest('id')->first();
        dump('baris tersimpan: '.json_encode($row->getAttributes()));
        dump('kolom tabel cash_books: '.json_encode(\Illuminate\Support\Facades\Schema::getColumnListing('cash_books')));
        $this->assertTrue(true);
    }

    public function test_biodata_publik_membuang_lima_field(): void
    {
        $html = $this->get(route('public.biodata.show', $this->class))->assertOk()->getContent();
        foreach (['tahun_masuk','nama_wali','pekerjaan_wali','anak_ke','jumlah_saudara'] as $f) {
            dump("blade merender name=\"$f\": ".(str_contains($html, 'name="'.$f.'"') ? 'YA' : 'TIDAK'));
        }

        $this->post(route('public.biodata.store', $this->class), [
            'name' => 'Budi Santoso', 'gender' => 'L', 'parent_phone' => '081234567890',
            'nis' => '12345', 'tahun_masuk' => 2024, 'nama_wali' => 'Pak Karto',
            'pekerjaan_wali' => 'Petani', 'anak_ke' => 2, 'jumlah_saudara' => 3,
            'hobi' => 'Sepak bola',
        ])->assertSessionHasNoErrors();

        $s = \App\Models\Student::withoutTenant()->where('name', 'Budi Santoso')->firstOrFail();
        dump('TERSIMPAN: '.json_encode($s->only([
            'name','nis','hobi','tahun_masuk','nama_wali','pekerjaan_wali','anak_ke','jumlah_saudara',
        ])));
        $this->assertTrue(true);
    }

    public function test_modal_refleksi_harian_portal_siswa_gagal_senyap(): void
    {
        $siswa = \App\Models\Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'must_change_password' => false,
        ]);
        \App\Models\CharacterDimension::create([
            'user_id' => $this->user->id, 'code' => 'iman', 'name' => 'Keimanan',
            'is_active' => true, 'sort_order' => 1,
        ]);

        $html = $this->actingAs($siswa, 'student')
            ->get(route('student.portfolio'))->assertOk()->getContent();
        dump('modal refleksi punya input reflection_date: '.(str_contains($html, 'name="reflection_date"') ? 'YA' : 'TIDAK'));
        dump('modal refleksi punya input period:          '.(str_contains($html, 'name="period"') ? 'YA' : 'TIDAK'));

        // Persis payload yang dikirim modal "Refleksi Harian".
        $r = $this->actingAs($siswa, 'student')->post(route('student.reflection.store'), [
            'what_went_well' => 'Hari ini saya piket kelas',
            'what_to_improve' => 'Kurang teliti mengerjakan PR',
        ]);
        dump('status='.$r->getStatusCode().' redirect='.$r->headers->get('Location'));
        dump('errors='.json_encode(session('errors') ? session('errors')->getBag('default')->all() : []));
        dump('jumlah refleksi tersimpan: '.\App\Models\CharacterReflection::withoutTenant()->count());
        $this->assertTrue(true);
    }

    public function test_form_catat_pelanggaran_selalu_gagal(): void
    {
        $st = \App\Models\Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id, 'discipline_points' => 100,
        ]);
        $vt = \App\Models\ViolationType::create([
            'user_id' => $this->user->id, 'name' => 'Terlambat', 'category' => 'ringan', 'points' => -5,
        ]);

        $html = $this->get(route('classes.violations.index', $this->class))->assertOk()->getContent();
        dump('form punya input name="points": '.(str_contains($html, 'name="points"') ? 'YA' : 'TIDAK'));

        // Persis payload form "Catat Pelanggaran" (student_id, violation_type_id, occurred_on, note).
        $r = $this->post(route('classes.violations.store', $this->class), [
            'student_id' => $st->id, 'violation_type_id' => $vt->id,
            'occurred_on' => '2026-08-04', 'note' => 'Datang jam 08.15',
        ]);
        dump('status='.$r->getStatusCode());
        dump('errors='.json_encode(session('errors') ? session('errors')->getBag('default')->all() : []));
        dump('pelanggaran tersimpan: '.\App\Models\Violation::withoutTenant()->count());
        dump('poin siswa: '.$st->fresh()->discipline_points);
        $this->assertTrue(true);
    }

}
