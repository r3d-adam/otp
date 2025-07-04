<?php

namespace App\Otp;

use Exception;
use App\Otp\OTPApiInterface;

class VoicePasswordApi implements OTPApiInterface
{
	private string $apiKey;

	public function __construct(string $apiKey)
	{
		$this->apiKey = $apiKey;
	}


    /**
     * @throws Exception
     */
    public function sendCode(string $phone, string $code): bool
	{
		$body = json_encode([
			'number' => $phone,
			'flashcall' => ['code' => $code]
		]);

		list($httpCode, $responseBody, $curlError) = $this->sendPostRequest(
			$body,
			['Authorization' => $this->apiKey]
		);

		if ($curlError || $httpCode !== 200) {
			throw new Exception('Не удалось отправить код: ' . $curlError);
		}

		$data = json_decode($responseBody, true);


		if (empty($data['result']) || $data['result'] !== 'ok') {
			$log = '[' . date('Y-m-d H:i:s') . ']:  ' . print_r($responseBody, 1);
			file_put_contents(__DIR__ . '/error_log.log', $log . PHP_EOL, FILE_APPEND);
			$errorDescription = isset($data['error_code']) ? ': ' . $data['error_code'] : '';
			throw new Exception('Ошибка' . $errorDescription);
		}

		return true;
	}

	private function sendPostRequest($body, $headers = []): array
	{
		$ch = curl_init('https://vp.voicepassword.ru/api/voice-password/send/');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
			'Content-Type: application/json',
		], $this->formatHeaders($headers)));

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
