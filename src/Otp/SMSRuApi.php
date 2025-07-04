<?php

namespace App\Otp;

use Exception;
use App\Otp\OTPApiInterface;

class SMSRuApi implements OTPApiInterface
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
		$body = [
			'api_id' => $this->apiKey,
			'to' => $phone,
			'msg' => "Код: $code",
			'json' => 1
		];

		list($httpCode, $responseBody, $curlError) = $this->sendPostRequest(
			$body,
		);

		if ($curlError || $httpCode !== 200) {
			throw new Exception('Не удалось отправить код: ' . $curlError);
		}

		$data = json_decode($responseBody, true);

		$log = '[' . date('Y-m-d H:i:s') . ']:  ' . print_r($responseBody, 1);
		file_put_contents(__DIR__ . '/error_log.log', $log . PHP_EOL, FILE_APPEND);

		if (empty($data['status']) || $data['status'] !== 'OK') {
			$errorDescription = isset($data['status_text']) ? ': ' . $data['status_text'] : '';
			throw new Exception('Ошибка' . $errorDescription);
		} else {
			foreach ($data['sms'] as $sms) {
				if ($sms['status'] === 'OK') {
					return true;
				} else {
					$errorDescription = isset($sms['status_text']) ? ': ' . $sms['status_text'] : '';
					throw new Exception('Ошибка' . $errorDescription);
				}
			}
		}
		
		return true;
	}



	private function sendPostRequest($body, $headers = []): array
	{
		$ch = curl_init('https://sms.ru/sms/send');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->formatHeaders($headers));

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
