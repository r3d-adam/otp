<?php

function sendWebhook($payload): array
{
	$name = $payload['name'] ?? '';
	$phone = $payload['phone'] ?? null;

	if (!$phone) {
		return [
			'error' => true,
			'message' => 'Phone number is required'
		];
	}

	$utm = [
		'utm_source' => $_COOKIE['utm_source'] ?? ($_GET['utm_source'] ?? ''),
		'utm_medium' => $_COOKIE['utm_medium'] ?? ($_GET['utm_medium'] ?? ''),
		'utm_campaign' => $_COOKIE['utm_campaign'] ?? ($_GET['utm_campaign'] ?? ''),
		'utm_content' => $_COOKIE['utm_content'] ?? ($_GET['utm_content'] ?? ''),
		'utm_term' => $_COOKIE['utm_term'] ?? ($_GET['utm_term'] ?? ''),
	];

	$cookies = [];
	foreach ($_COOKIE as $key => $val) {
		$cookies[] = "$key=$val";
	}
	$cookiesString = implode(';', $cookies);

	$data = [
		'Name' => $name,
		'Phone' => $phone,
		'cookies' => $cookiesString,
		'utm_source' => $utm['utm_source'],
		'utm_medium' => $utm['utm_medium'],
		'utm_campaign' => $utm['utm_campaign'],
		'utm_content' => $utm['utm_content'],
		'utm_term' => $utm['utm_term'],
	];

	$ch = curl_init($_ENV['WEBHOOK_URL']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);


	return [
		'response' => $response,
		'httpCode' => $httpCode,
		'data' => json_encode($data)
	];
}
