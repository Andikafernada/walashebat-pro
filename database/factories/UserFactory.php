<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
            // Sama seperti pendaftar sungguhan: masa otomasi masih berjalan.
            // Test yang perlu keadaan kedaluwarsa memakai state kedaluwarsa().
            'subscription_tier' => User::TIER_TRIAL,
            'subscription_ends_at' => User::akhirMasaGratis(),
            'wa_session_status' => 'disconnected',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Masa gratis tiga bulan sudah lewat: otomasi WhatsApp harus berhenti. */
    public function kedaluwarsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => User::TIER_TRIAL,
            'subscription_ends_at' => now()->subDay(),
        ]);
    }

    /** Sudah membayar: otomasi berjalan sampai tanggal yang ditentukan. */
    public function pro(?\DateTimeInterface $sampai = null): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => User::TIER_PRO,
            'subscription_ends_at' => $sampai ?? now()->addYear(),
        ]);
    }
}
