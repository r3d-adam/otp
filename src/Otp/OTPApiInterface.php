<?php
namespace App\Otp;

interface OTPApiInterface
{
	public function sendCode(string $phone, string $code): bool;
}