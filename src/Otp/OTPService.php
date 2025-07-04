<?php
namespace App\Otp;

use Exception;
use App\Otp\OTPApiInterface;
use RuntimeException;

class OTPService
{
	const OTP_SENT_TRANSIENT_PREFIX = 'otp_sent_';
	private string $defaultCode = '9999';
	const DEFAULT_CODE_LIFE_TIME_MINUTES = 5;
	const DEFAULT_RETRY_TIMEOUT_SECONDS = 60;
	private OTPCodeManager $codeManager;
	private OTPApiInterface $apiClient;
	private array $options = [];

	/**
	 * @param $options array{
	 *      code_life_time_minutes?: int,
	 *      debug_mode?: bool,
	 *      retry_timeout_seconds?: int
	 *  }
	 */
	public function __construct(OTPApiInterface $apiClient, array $options = [])
	{
		$this->apiClient = $apiClient;

		$defaults = [
			'code_life_time_minutes' => self::DEFAULT_CODE_LIFE_TIME_MINUTES,
			'debug_mode' => false,
			'retry_timeout_seconds' => self::DEFAULT_RETRY_TIMEOUT_SECONDS,
		];

		$this->codeManager = new OTPCodeManager();
		$this->options = array_merge($defaults, $options);
		$this->startSession();
	}

    public function renderCodeField($field, $index, $formInstance)
    {

        if ($field['customId'] === 'code') {
            echo '<script>window.otpCode && (setTimeout(window.otpCode.init, 100));</script>';
        }
        return $field;
    }

	/**
	 * @throws Exception
	 */
	public function handleResendOtpCode(): bool
	{
		$phone = $this->getSessionPhone();
		if (!$phone) {
			throw new Exception('Номер телефона не найден в сессии');
		}

		try {
			$result = $this->sendOtpCode($phone);
		} catch (Exception $e) {
			throw new Exception('Ошибка отправки: ' . $e->getMessage());
		}

		return $result;
	}

	public function startSession(): void
	{
		if (!session_id()) {
			session_start();
		}
	}

	public function getSettings(): array
	{
		return $this->options;
	}

	public function getCodeLifetime()
	{
		$options = $this->getSettings();
		return $options['code_life_time_minutes'] ?? self::DEFAULT_CODE_LIFE_TIME_MINUTES;
	}

	/**
	 * @throws Exception
	 */
	public function getApiKey()
	{
		$options = $this->getSettings();
		if (empty($options['api_key'])) {
			throw new Exception('Api key not set');
		}
		return $options['api_key'];
	}

	public function isDebugMode(): bool
	{
		$options = $this->getSettings();
		return !empty($options['debug_mode']);
	}

	public function getRetryTimeout(): int
	{
		$options = $this->getSettings();
		return $options['retry_timeout_seconds'];
	}

	public function getDefaultCode(): string
	{
		return $this->defaultCode;
	}

	public function setSessionPhone($phone): void
	{
		$_SESSION['otp_phone'] = $phone;
	}

	public function getSessionPhone()
	{
		return $_SESSION['otp_phone'] ?? null;
	}

	public function clearSession(): void
	{
		unset($_SESSION['otp_phone']);
	}

	public function getTtl(): int|null {
		$phone = $this->sanitizePhone($this->getSessionPhone());

		return TransientManager::getTtl(self::OTP_SENT_TRANSIENT_PREFIX . md5($phone));
	}
	/**
	 * @throws Exception
	 */
	public function sendOtpCode($phone): bool
	{
		$phone = $this->sanitizePhone($phone);

		$expiringIn = TransientManager::getTtl(self::OTP_SENT_TRANSIENT_PREFIX . md5($phone));
		if ($expiringIn) {
			throw new Exception('Код уже отправлен. Пожалуйста, подождите ' . $expiringIn . ' секунд');
		}

		$code = $this->codeManager->generateAndStore($phone, $this->getCodeLifetime());

		if (!$this->isDebugMode()) {
			try {
				$this->apiClient->sendCode($phone, $code);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}


		TransientManager::delete(self::OTP_SENT_TRANSIENT_PREFIX . md5($phone));
		TransientManager::set(self::OTP_SENT_TRANSIENT_PREFIX . md5($phone), true, $this->getRetryTimeout());
		

		$this->setSessionPhone($phone);

		return true;
	}


	public function verifyOtpCode(string $phone, string|int $code): bool
	{
		$phone = $this->sanitizePhone($phone);

		if ($this->isDebugMode()) {
			if ($code !== $this->getDefaultCode()) {
				throw new RuntimeException('Неправильный код (debug mode)');
			}
		} else {
			if (!$this->codeManager->verify($phone, $code)) {
				throw new RuntimeException('Неправильный код');
			}
			$this->codeManager->delete($phone);
		}

		return true;
	}

	public function sanitizePhone(string $phone): string
	{
		$phone = preg_replace('/\D/', '', $phone);

		if (strlen($phone) === 11 && $phone[0] === '8') {
			$phone[0] = '7';
		}

		return $phone;
	}

	public function markOtpVerified(): void
	{
		$_SESSION['otp_verified'] = true;
	}

	public function isOtpVerified(): bool
	{
		return !empty($_SESSION['otp_verified']);
	}

	public function clearOtpVerified(): void
	{
		unset($_SESSION['otp_verified']);
	}
}

