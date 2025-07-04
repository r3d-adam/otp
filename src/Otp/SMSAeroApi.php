<?php

namespace App\Otp;
use Exception;
use App\Otp\OTPApiInterface;

class SMSAeroApi implements OTPApiInterface
{
	private string $apiKey;
	private string $username;
	private string $sign;

	public function __construct(string $apiKey, string $username, string $sign)
	{
		$this->apiKey = $apiKey;
		$this->username = $username;
		$this->sign = $sign;
	}

	/**
	 * @throws Exception
	 */
	public function sendCode(string $phone, string $code): bool
	{
		$body = [
			'number' => $phone,
			'text' => "Код: $code",
			'sign' => $this->sign
		];

		list($httpCode, $responseBody, $curlError) = $this->sendRequest($body);

		$log = '[' . date('Y-m-d H:i:s') . ']:  ' . print_r($responseBody, 1);
		file_put_contents(__DIR__ . '/error_log.log', $log . PHP_EOL, FILE_APPEND);

		if ($curlError || $httpCode !== 200) {
			$data = json_decode($responseBody, true);
			$errorDescription = isset($data['message']) ? ': ' . $data['message'] : '';
			throw new Exception('Не удалось отправить код' . $errorDescription);
		}

		$data = json_decode($responseBody, true);

		if (empty($data['success']) || $data['success'] !== true) {
			$errorDescription = isset($data['message']) ? ': ' . $data['message'] : '';
			throw new Exception('Ошибка' . $errorDescription);
		}
		
		return true;
	}



	private function sendRequest($query = []): array
	{
		$url = "https://gate.smsaero.ru/v2/sms/send";

		if (!empty($query)) {
			$url .= '?' . http_build_query($query);
		}

		$ch = curl_init($url);

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
			CURLOPT_USERPWD        => "{$this->username}:{$this->apiKey}",
			CURLOPT_SSL_VERIFYPEER => true,
		]);


		$responseBody = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		return [$httpCode, $responseBody, $error];
	}

	private function formatHeaders(array $headers): array
	{
		$formatted = [];
		foreach ($headers as $key => $value) {
			$formatted[] = "$key: $value";
		}
		return $formatted;
	}
}
