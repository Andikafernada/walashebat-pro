<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_wali_kelas_hanya_melihat_kelasnya_sendiri(): void
    {
        $andi = User::factory()->create();
        $budi = User::factory()->create();

        Classroom::factory()->create(['user_id' => $andi->id, 'name' => 'Kelas Andi']);
        Classroom::factory()->create(['user_id' => $budi->id, 'name' => 'Kelas Budi']);

        $this->actingAs($andi);
        $names = Classroom::pluck('name');

        $this->assertTrue($names->contains('Kelas Andi'));
        $this->assertFalse($names->contains('Kelas Budi'));
    }

    public function test_tidak_bisa_membuka_kelas_milik_orang_lain(): void
    {
        $budi = User::factory()->create();
        $kelasBudi = Classroom::factory()->create(['user_id' => $budi->id]);

        $this->actingAs(User::factory()->create());

        $this->get(route('classes.show', $kelasBudi))->assertNotFound();
    }
}
