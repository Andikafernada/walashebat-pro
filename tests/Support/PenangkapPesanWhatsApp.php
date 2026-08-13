<?php

namespace Tests\Support;

use App\Support\Contracts\NotificationChannel;

/** Gateway palsu yang menyimpan pesannya alih-alih mengirim. */
class PenangkapPesanWhatsApp implements NotificationChannel
{
    /** @var list<array{to: string, message: string, meta: array<string, mixed>}> */
    public array $pesan = [];

    public function __construct(public bool $berhasil = true) {}

    public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
    {
        if (! $this->berhasil) {
            return false;
        }

        $this->pesan[] = ['to' => $to, 'message' => $message, 'meta' => $meta];

        return true;
    }

    /** Kode enam digit dari pesan terakhir. */
    public function kodeTerakhir(): ?string
    {
        $terakhir = end($this->pesan);

        if (! $terakhir || ! preg_match('/\*(\d{6})\*/', $terakhir['message'], $m)) {
            return null;
        }

        return $m[1];
    }
}
