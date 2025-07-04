<?php
namespace App\Otp;
class TransientManager
{
	public static function startSession(): void
	{
		if (!session_id()) {
			session_start();
		}
	}

	public static function set($key, $value, $expirationSeconds): void
	{
		self::startSession();
		$_SESSION['_transient'][$key] = [
			'value' => $value,
			'expires_at' => time() + $expirationSeconds,
		];
	}

	public static function get($key)
	{
		self::startSession();
		if (isset($_SESSION['_transient'][$key])) {
			$data = $_SESSION['_transient'][$key];
			if ($data['expires_at'] > time()) {
				return $data['value'];
			} else {
				unset($_SESSION['_transient'][$key]);
			}
		}
		return null;
	}

	public static function delete($key): void
	{
		self::startSession();
		unset($_SESSION['_transient'][$key]);
	}

	/**
	 * Возвращает timestamp истечения срока действия, либо null
	 */
	public static function getExpiration($key): ?int
	{
		self::startSession();
		if (isset($_SESSION['_transient'][$key])) {
			$data = $_SESSION['_transient'][$key];
			if ($data['expires_at'] > time()) {
				return $data['expires_at'];
			}
		}
		return null;
	}

	/**
	 * Возвращает TTL (в секундах), либо null если истёк или не существует
	 */
	public static function getTtl($key): ?int
	{
		$expiration = self::getExpiration($key);
		if ($expiration !== null) {
			return $expiration - time();
		}
		return null;
	}
}
