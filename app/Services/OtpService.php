<?php

namespace App\Services;

use App\Support\Phone;

use App\Models\User;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Generate a 6-digit OTP
     */
    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via WhatsApp
     */
    public function sendViaWhatsApp(string $phoneNumber, string $otp): bool
    {
        // Normalize phone number
        $normalizedPhone = $this->normalizePhone($phoneNumber);

        // Clean OTP for message
        $formattedOtp = implode(' ', str_split($otp));

        // WhatsApp message
        $message = "🔐 *Kode Verifikasi WaliKelas Pro*\n\n";
        $message .= "Halo!\n\n";
        $message .= "Berikut adalah kode verifikasi Anda:\n\n";
        $message .= "*$formattedOtp*\n\n";
        $message .= "Kode ini berlaku selama *5 menit*. Jangan bagikan kode ini ke siapapun.\n\n";
        $message .= "Jika Anda tidak merasa mendaftar, abaikan pesan ini.";

        // For now, we'll log to file since we don't have WhatsApp API set up
        // In production, integrate with Fonnte, WaBlas, or similar
        $this->logWhatsAppMessage($normalizedPhone, $message);

        return true;
    }

    /**
     * Store OTP for a user (pending registration)
     */
    public function storePendingOtp(string $phone, string $name, string $email, string $password): User
    {
        $otp = $this->generate();
        $normalizedPhone = $this->normalizePhone($phone);

        // Find existing user by phone or email
        $user = User::where('whatsapp_number', $normalizedPhone)
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            // Create new user with required fields
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'whatsapp_number' => $normalizedPhone,
                'is_active' => true,
                'role' => User::ROLE_TEACHER,
            ]);
        }

        // Store pending OTP data
        $user->update([
            'pending_name' => $name,
            'pending_email' => $email,
            'pending_password' => bcrypt($password),
            'pending_otp' => $otp,
            'pending_otp_expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP
        $this->sendViaWhatsApp($phone, $otp);

        return $user;
    }

    /**
     * Verify OTP for pending user
     */
    public function verifyPendingOtp(string $phone, string $otp): array
    {
        $user = User::where('whatsapp_number', $this->normalizePhone($phone))->first();

        if (!$user) {
            return ['success' => false, 'message' => 'Nomor WhatsApp tidak ditemukan.'];
        }

        // Check if OTP is expired
        if (!$user->pending_otp_expires_at || $user->pending_otp_expires_at->isPast()) {
            return ['success' => false, 'message' => 'Kode OTP sudah kadaluarsa. Minta kode baru.'];
        }

        // Check if OTP matches
        if ($user->pending_otp !== $otp) {
            // Increment attempts (you could add this field)
            return ['success' => false, 'message' => 'Kode OTP tidak valid.'];
        }

        // Complete registration
        $user->update([
            'name' => $user->pending_name,
            'email' => $user->pending_email,
            'password' => $user->pending_password,
            'whatsapp_verified' => true,
            'is_active' => true,
            'pending_otp' => null,
            'pending_otp_expires_at' => null,
            'pending_name' => null,
            'pending_email' => null,
            'pending_password' => null,
        ]);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Resend OTP
     */
    public function resendOtp(string $phone): array
    {
        $user = User::where('whatsapp_number', $this->normalizePhone($phone))->first();

        if (!$user) {
            return ['success' => false, 'message' => 'Nomor WhatsApp tidak ditemukan.'];
        }

        $otp = $this->generate();

        $user->update([
            'pending_otp' => $otp,
            'pending_otp_expires_at' => now()->addMinutes(5),
        ]);

        $this->sendViaWhatsApp($phone, $otp);

        return ['success' => true, 'message' => 'Kode OTP baru telah dikirim.'];
    }

    /**
     * Normalisasi nomor — SATU sumber kebenaran, App\Support\Phone.
     *
     * Sebelumnya kelas ini punya algoritmanya sendiri, padahal nomor yang
     * dicarinya disimpan model User lewat Phone::normalize. Dua algoritma yang
     * berbeda saling dibandingkan: nomor yang tidak diawali 0/8/62 tersimpan
     * apa adanya tetapi dicari dengan awalan "62", sehingga penggunanya tidak
     * pernah ditemukan dan reset kata sandinya gagal tanpa sebab yang terlihat.
     */
    private function normalizePhone(string $phone): string
    {
        return Phone::normalize($phone) ?? '';
    }

    /**
     * Log WhatsApp message (for development/debugging)
     */
    private function logWhatsAppMessage(string $phone, string $message): void
    {
        $logPath = storage_path('logs/whatsapp-otp.log');
        $timestamp = now()->format('Y-m-d H:i:s');

        $logEntry = "[$timestamp] TO: $phone\nMESSAGE:\n$message\n\n---\n\n";

        file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
