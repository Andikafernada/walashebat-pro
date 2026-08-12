<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengumuman operator ke semua guru — lewat lonceng dalam-aplikasi, bukan WA.
 */
class AdminPengumumanTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_pengumuman_masuk_ke_setiap_guru(): void
    {
        User::factory()->count(3)->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($this->operator())
            ->post(route('admin.announcements.send'), [
                'title' => 'Pemeliharaan Sabtu',
                'body' => 'Aplikasi akan dimatikan 22.00–23.00.',
            ])
            ->assertRedirect();

        // Satu notifikasi per guru; operator sendiri tidak menerima apa pun.
        $this->assertSame(3, Notification::where('type', 'announcement')->count());
        foreach (User::where('role', User::ROLE_TEACHER)->pluck('id') as $id) {
            $this->assertDatabaseHas('notifications', ['user_id' => $id, 'title' => 'Pemeliharaan Sabtu']);
        }
    }

    public function test_guru_biasa_tidak_bisa_mengirim(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_TEACHER]))
            ->post(route('admin.announcements.send'), ['title' => 'x', 'body' => 'y'])
            ->assertForbidden();

        $this->assertSame(0, Notification::where('type', 'announcement')->count());
    }

    public function test_judul_dan_isi_wajib(): void
    {
        $this->actingAs($this->operator())
            ->post(route('admin.announcements.send'), ['title' => '', 'body' => ''])
            ->assertSessionHasErrors(['title', 'body']);
    }
}
