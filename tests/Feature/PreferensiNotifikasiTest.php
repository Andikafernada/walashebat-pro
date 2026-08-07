<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Preferensi notifikasi dulu hanya bisa DINYALAKAN.
 *
 * Checkbox yang tidak dicentang tidak ikut terkirim browser, dan controller
 * hanya memperbarui kunci yang ada di payload — jadi "matikan" tidak pernah
 * sampai ke database. Lebih buruk lagi, permintaan tanpa perubahan apa pun
 * tetap dijawab "berhasil disimpan".
 */
class PreferensiNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function preferensi(bool $semuaMenyala = true): UserNotificationPreference
    {
        return UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'push_enabled' => $semuaMenyala,
            'push_attendance_reminder' => $semuaMenyala,
            'push_new_violation' => $semuaMenyala,
            'push_low_cashbook' => $semuaMenyala,
            'push_daily_summary' => $semuaMenyala,
        ]);
    }

    public function test_preferensi_bisa_dimatikan(): void
    {
        $pref = $this->preferensi(true);

        $this->patchJson(route('notifications.settings.update'), [
            'push_enabled' => '1',
            'push_attendance_reminder' => '0',
            'push_new_violation' => '0',
            'push_low_cashbook' => '0',
            'push_daily_summary' => '0',
        ])->assertOk()->assertJson(['success' => true]);

        $pref->refresh();

        $this->assertTrue((bool) $pref->push_enabled);
        $this->assertFalse((bool) $pref->push_attendance_reminder);
        $this->assertFalse((bool) $pref->push_new_violation);
        $this->assertFalse((bool) $pref->push_low_cashbook);
        $this->assertFalse((bool) $pref->push_daily_summary);
    }

    public function test_semua_preferensi_bisa_dimatikan_sekaligus(): void
    {
        $pref = $this->preferensi(true);

        $this->patchJson(route('notifications.settings.update'), [
            'push_enabled' => '0',
            'push_attendance_reminder' => '0',
            'push_new_violation' => '0',
            'push_low_cashbook' => '0',
            'push_daily_summary' => '0',
        ])->assertOk();

        $pref->refresh();

        foreach (['push_enabled', 'push_attendance_reminder', 'push_new_violation',
            'push_low_cashbook', 'push_daily_summary'] as $kunci) {
            $this->assertFalse((bool) $pref->{$kunci}, "{$kunci} seharusnya mati");
        }
    }

    public function test_preferensi_bisa_dinyalakan_kembali(): void
    {
        $pref = $this->preferensi(false);

        $this->patchJson(route('notifications.settings.update'), [
            'push_enabled' => '1',
            'push_daily_summary' => '1',
        ])->assertOk();

        $pref->refresh();

        $this->assertTrue((bool) $pref->push_enabled);
        $this->assertTrue((bool) $pref->push_daily_summary);
    }

    /** Kunci yang tidak dikirim tidak boleh ikut berubah. */
    public function test_kunci_yang_tidak_dikirim_dibiarkan_apa_adanya(): void
    {
        $pref = $this->preferensi(true);

        $this->patchJson(route('notifications.settings.update'), [
            'push_daily_summary' => '0',
        ])->assertOk();

        $pref->refresh();

        $this->assertFalse((bool) $pref->push_daily_summary);
        $this->assertTrue((bool) $pref->push_enabled, 'Kunci lain tidak boleh ikut direset');
        $this->assertTrue((bool) $pref->push_new_violation);
    }

    /** Sukses yang berbohong lebih buruk daripada galat yang jujur. */
    public function test_permintaan_kosong_ditolak_bukan_dijawab_sukses(): void
    {
        $pref = $this->preferensi(true);

        $this->patchJson(route('notifications.settings.update'), [])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $pref->refresh();
        $this->assertTrue((bool) $pref->push_enabled, 'Tidak ada yang boleh berubah');
    }

    public function test_nilai_bukan_boolean_ditolak(): void
    {
        $this->preferensi(true);

        $this->patchJson(route('notifications.settings.update'), [
            'push_enabled' => 'mungkin',
        ])->assertStatus(422);
    }

    // -- Formulir di halaman pengaturan -------------------------------------

    /**
     * Tanpa pasangan tersembunyi bernilai 0, checkbox yang dimatikan tidak
     * terkirim sama sekali dan preferensi mustahil dimatikan lewat UI.
     */
    public function test_formulir_menyertakan_pasangan_tersembunyi_tiap_preferensi(): void
    {
        $this->preferensi(true);

        $html = $this->get(route('notifications.settings'))->assertOk()->getContent();

        foreach (['push_enabled', 'push_attendance_reminder', 'push_new_violation',
            'push_low_cashbook', 'push_daily_summary'] as $kunci) {
            $this->assertStringContainsString(
                '<input type="hidden" name="'.$kunci.'" value="0">',
                $html,
                "Preferensi {$kunci} tidak punya pasangan tersembunyi — tidak akan bisa dimatikan"
            );
        }
    }

    /** Pasangan tersembunyi harus mendahului checkbox agar centang yang menang. */
    public function test_tersembunyi_ditempatkan_sebelum_checkbox(): void
    {
        $this->preferensi(true);

        $html = $this->get(route('notifications.settings'))->assertOk()->getContent();

        $posisiTersembunyi = strpos($html, '<input type="hidden" name="push_enabled" value="0">');
        $posisiCheckbox = strpos($html, '<input type="checkbox" name="push_enabled" value="1"');

        $this->assertNotFalse($posisiTersembunyi);
        $this->assertNotFalse($posisiCheckbox);
        $this->assertLessThan(
            $posisiCheckbox,
            $posisiTersembunyi,
            'Nilai terakhir yang menang: tersembunyi harus lebih dulu, bukan sesudah checkbox'
        );
    }
}
