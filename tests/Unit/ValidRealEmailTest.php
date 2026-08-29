<?php

namespace Tests\Unit;

use App\Rules\ValidRealEmail;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidRealEmailTest extends TestCase
{
    private function validateEmail(string $email): bool
    {
        $v = Validator::make(
            ['email' => $email],
            ['email' => ['required', new ValidRealEmail]]
        );

        return $v->passes();
    }

    private function getErrorMessage(string $email): string
    {
        $v = Validator::make(
            ['email' => $email],
            ['email' => ['required', new ValidRealEmail]]
        );

        return (string) ($v->errors()->first('email') ?? '');
    }

    public function test_email_asli_guru_berhasil_lolos(): void
    {
        $this->assertTrue($this->validateEmail('andika.guru@gmail.com'));
        $this->assertTrue($this->validateEmail('budi.santoso@guru.smp.belajar.id'));
        $this->assertTrue($this->validateEmail('siti.aminah@sman1kotabaru.sch.id'));
        $this->assertTrue($this->validateEmail('ahmad.fauzi@yahoo.com'));
    }

    public function test_email_sementara_disposable_ditolak(): void
    {
        $this->assertFalse($this->validateEmail('user123@mailinator.com'));
        $this->assertFalse($this->validateEmail('testguru@yopmail.com'));
        $this->assertFalse($this->validateEmail('random99@10minutemail.com'));
        $this->assertFalse($this->validateEmail('akunbaru@temp-mail.org'));
        $this->assertStringContainsString('Email sementara / disposable tidak diizinkan', $this->getErrorMessage('user123@mailinator.com'));
    }

    public function test_email_dummy_contoh_ditolak(): void
    {
        $this->assertFalse($this->validateEmail('john.doe@example.com'));
        $this->assertFalse($this->validateEmail('guru@test.com'));
        $this->assertFalse($this->validateEmail('coba@dummy.com'));
        $this->assertStringContainsString('bukan email dummy/contoh', $this->getErrorMessage('guru@example.com'));
    }

    public function test_email_asal_asalan_ditolak(): void
    {
        $this->assertFalse($this->validateEmail('test@gmail.com'));
        $this->assertFalse($this->validateEmail('asdf@gmail.com'));
        $this->assertFalse($this->validateEmail('aaaa@gmail.com'));
        $this->assertFalse($this->validateEmail('123@gmail.com'));
        $this->assertStringContainsString('tidak wajar', $this->getErrorMessage('asdf@gmail.com'));
    }

    public function test_typo_domain_dideteksi_dan_diberi_saran(): void
    {
        $this->assertFalse($this->validateEmail('andika.santoso@gamil.com'));
        $this->assertStringContainsString('Apakah maksud Anda @gmail.com?', $this->getErrorMessage('andika.santoso@gamil.com'));

        $this->assertFalse($this->validateEmail('guru.fisika@yaho.com'));
        $this->assertStringContainsString('Apakah maksud Anda @yahoo.com?', $this->getErrorMessage('guru.fisika@yaho.com'));
    }
}
