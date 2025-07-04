<?php
namespace App\Otp;

class OTPCodeManager
{
	private string $prefix = 'otp_code_';

	public function generateAndStore($phone, $lifetimeMinutes): string
	{
		$code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
		$_SESSION[$this->getKey($phone)] = [
			'code' => $code,
			'expires_at' => time() + ($lifetimeMinutes * 60),
		];
		return $code;
	}

	public function getCode($phone)
	{
		$data = $_SESSION[$this->getKey($phone)] ?? null;
		if ($data && isset($data['expires_at']) && $data['expires_at'] >= time()) {
			return $data['code'];
		}
		return null;
	}

	public function verify($phone, $input_code): bool
	{
		$stored_code = $this->getCode($phone);
		return $stored_code && $stored_code === $input_code;
	}

	public function delete($phone): void
	{
		unset($_SESSION[$this->getKey($phone)]);
	}

	private function getKey($phone): string
	{
		$clean = preg_replace('/\D/', '', $phone);
		return $this->prefix . md5($clean);
	}
}
