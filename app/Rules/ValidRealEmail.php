<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validasi ketat kualitas email guru:
 * 1. Menolak domain email sementara / disposable / temp-mail.
 * 2. Menolak domain dummy / contoh (example.com, test.com, asdf.com, dll).
 * 3. Menolak format asal-asalan / repetitif (asdf@, test@, 123@, aaa@).
 * 4. Mendeteksi salah ketik (typo) umum domain besar (gamil.com -> gmail.com).
 * 5. Memeriksa keberadaan DNS MX record aktif pada domain email.
 */
class ValidRealEmail implements ValidationRule
{
    /**
     * Daftar domain email sementara (Disposable / Temp-mail).
     */
    private const DISPOSABLE_DOMAINS = [
        'mailinator.com', 'yopmail.com', '10minutemail.com', 'guerrillamail.com',
        'trashmail.com', 'sharklasers.com', 'dispostable.com', 'temp-mail.org',
        'tempmail.net', 'tempmail.com', 'fakemailgenerator.com', 'throwawaymail.com',
        'getairmail.com', 'maildrop.cc', 'inboxkitten.com', 'nada.ltd',
        'mohmal.com', 'crazymailing.com', 'dropmail.me', 'generator.email',
        'mytemp.email', 'tempmailaddress.com', 'tempinbox.com', 'binkmail.com',
        'bobmail.info', 'chacuo.net', 'disposablemail.com', 'emailondeck.com',
        'fastmail.fm', 'filzmail.com', 'fudgerub.com', 'getnada.com',
        'grr.la', 'harakirimail.com', 'incognitomail.org', 'jetable.org',
        'kasmail.com', 'mailcatch.com', 'mailnesia.com', 'mailnull.com',
        'meltmail.com', 'mintemail.com', 'mytrashmail.com', 'noclickemail.com',
        'nomail.xl.cx', 'oneoffmail.com', 'pookmail.com', 'spambox.us',
        'spamgourmet.com', 'trashymail.com', 'trbvm.com', 'tyldd.com',
        'wegwerfmail.de', 'whyspam.me', 'zoemail.org', 'burnermail.io',
        '10mail.org', 'tempail.com', 'fakemail.net', 'internxt.com',
    ];

    /**
     * Domain dummy / contoh yang dilarang untuk registrasi produksi.
     */
    private const DUMMY_DOMAINS = [
        'example.com', 'example.org', 'example.net', 'example.edu',
        'test.com', 'testing.com', 'dummy.com', 'fake.com',
        'asdf.com', 'sample.com', 'invalid.com', 'localhost',
        'contoh.com', 'contoh.id', 'coba.com', 'nyoba.com',
    ];

    /**
     * Pola typo domain yang sering salah diketik.
     */
    private const DOMAIN_TYPOS = [
        'gamil.com' => 'gmail.com',
        'gmai.com' => 'gmail.com',
        'gmaill.com' => 'gmail.com',
        'gmial.com' => 'gmail.com',
        'gmil.com' => 'gmail.com',
        'gmaul.com' => 'gmail.com',
        'yaho.com' => 'yahoo.com',
        'yahooo.com' => 'yahoo.com',
        'yaho.co.id' => 'yahoo.co.id',
        'hotmial.com' => 'hotmail.com',
        'outlok.com' => 'outlook.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $email = strtolower(trim((string) $value));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail('Format alamat email tidak valid.');
            return;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            $fail('Format alamat email tidak valid.');
            return;
        }

        [$username, $domain] = $parts;

        // 1. Periksa Typo Domain Umum
        if (isset(self::DOMAIN_TYPOS[$domain])) {
            $saran = self::DOMAIN_TYPOS[$domain];
            $fail("Sepertinya ada salah ketik pada domain email. Apakah maksud Anda @{$saran}?");
            return;
        }

        // 2. Tolak Domain Dummy / Contoh
        if (in_array($domain, self::DUMMY_DOMAINS, true)) {
            $fail('Mohon gunakan alamat email asli Anda yang aktif (bukan email dummy/contoh).');
            return;
        }

        // 3. Tolak Domain Sementara (Disposable / Temp-mail)
        if (in_array($domain, self::DISPOSABLE_DOMAINS, true) ||
            str_contains($domain, 'tempmail') ||
            str_contains($domain, 'disposable') ||
            str_contains($domain, 'throwaway') ||
            str_contains($domain, 'fakemail') ||
            str_contains($domain, 'trashmail')) {
            $fail('Email sementara / disposable tidak diizinkan. Mohon gunakan email resmi sekolah, Gmail, atau akun pribadi aktif Anda.');
            return;
        }

        // 4. Tolak Username Asal-asalan (Repeating / Bogus)
        $bogusUsernames = ['test', 'asdf', 'admin', 'fake', 'dummy', 'contoh', 'abc', '123', 'qwe', 'user', 'aaaa', 'zzzz'];
        if (in_array($username, $bogusUsernames, true) || preg_match('/^(.)\1{3,}$/', $username)) {
            $fail('Alamat email terdeteksi tidak wajar. Mohon gunakan alamat email resmi Anda.');
            return;
        }

        // 5. Validasi DNS MX Record Domain (Kecuali domain localhost/testing saat unit test)
        if (! app()->runningUnitTests() && ! in_array($domain, ['gmail.com', 'yahoo.com', 'belajar.id', 'outlook.com', 'icloud.com'], true)) {
            $hasMx = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            if (! $hasMx) {
                $fail("Domain email @{$domain} tidak memiliki server email aktif. Periksa kembali ejaan email Anda.");
            }
        }
    }
}
