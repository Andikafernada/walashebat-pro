<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $otp,
        public int $expiryMinutes = 10
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔐 {$this->otp} adalah Kode Verifikasi Akun WaliKelas Pro Anda",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration_otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
