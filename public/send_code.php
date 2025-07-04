<?php

/** @global $service */

const ACTION_SEND_CODE = 'send_code';
const ACTION_VERIFY_CODE = 'verify_code';

$action = $_POST['action'] ?? null;
$result = ['success' => false];

$shouldRenderCodeForm = false;

if ($action === ACTION_SEND_CODE) {
	if (!session_id()) {
		session_start();
	}
	$_SESSION['phone'] = $_POST['phone'] ?? '';
	$_SESSION['name'] = $_POST['name'] ?? '';

	$phone = $_POST['phone'] ?? $service->getSessionPhone();
	try {
		$service->sendOtpCode($phone);
		$result = ['success' => true];
		$shouldRenderCodeForm = true;
	} catch (Exception $e) {
		$result = ['success' => false, 'message' => $e->getMessage()];
	}
} elseif ($action === ACTION_VERIFY_CODE) {
	$phone = $service->getSessionPhone();
	if (!empty($_POST['code']) && !empty($phone)) {
		try {
			$service->verifyOtpCode($phone, htmlspecialchars($_POST['code']));
			$result = [
				'success' => true,
			];
		} catch (Exception $e) {
			$shouldRenderCodeForm = true;
			$result = [
				'success' => false,
				'message' => $e->getMessage(),
			];
		}
	} else {
		$result = [
			'success' => false,
			'message' => 'Введите код и телефон',
		];
	}
}


$isCodeSent = $action === ACTION_SEND_CODE && $result['success'];
$isCodeVerified = $action === ACTION_VERIFY_CODE && $result['success'];

if ($shouldRenderCodeForm && (($action === ACTION_SEND_CODE && $isCodeSent) ||
		($action === ACTION_VERIFY_CODE && !$isCodeVerified))):
	?>
    <div class="form-container otp-container text-center">

        <h2>Вам придет SMS-сообщение</h2>
        <div class="phone">
            <span class="phone-icon">
<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path
            d="M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12ZM241-600l66-66-17-94h-89q5 41 14 81t26 79Zm358 358q39 17 79.5 27t81.5 13v-88l-94-19-67 67ZM241-600Zm358 358Z"/></svg></span>
			  <?php
			  $phone = $service->getSessionPhone();
			  $formattedPhone = formatPhoneNumber($phone, ['first' => '*', 'second' => '*']);
			  ?>
            <strong><?php echo $formattedPhone; ?></strong>
        </div>

        <a href="index.php" class="change-number">Изменить номер</a>

        <p>Введите код из SMS:</p>
        <form method="post">
            <input type="hidden" name="action" value="verify_code">
            <input type="hidden" name="code" pattern="\d{4}" required>
            <div class="code-inputs otp-inputs<?php echo ($action === ACTION_VERIFY_CODE && !$isCodeVerified) ? ' code-inputs--error' : '' ?>">
                <input type="text" maxlength="1" class="otp-input" name="digit1" inputmode="numeric"
                       pattern="\d+">
                <input type="text" maxlength="1" class="otp-input" name="digit2" inputmode="numeric"
                       pattern="\d+">
                <input type="text" maxlength="1" class="otp-input" name="digit3" inputmode="numeric"
                       pattern="\d+">
                <input type="text" maxlength="1" class="otp-input" name="digit4" inputmode="numeric"
                       pattern="\d+">
            </div>
            <button type="submit" style="display: none">Подтвердить</button>
        </form>

		 <?php
		 $expiringInSeconds = $service->getTtl();
		 ?>
        <div class="timer-text-wrap">
            <div class="resend timer"
                 data-time="<?php echo $expiringInSeconds ?: 0; ?>">
					<?php

					if ($expiringInSeconds) {
						?>
                   Получить код ещё раз можно через <span
                           class="timer-text"><?php echo $expiringInSeconds; ?></span> секунд.
						<?php
					}
					?>
            </div>
        </div>
        <form method="POST" tabindex="-1">
            <input type="hidden" name="action" value="<?php echo ACTION_SEND_CODE; ?>">
            <button type="submit" class="link resend-btn" disabled style="display: none">
                Запросить код повторно
            </button>
        </form>
    </div>
<?php elseif ($isCodeVerified): ?>
	<?php
    $response = sendWebhook([
            'phone' => $service->getSessionPhone(),
            'name' => $_SESSION['name'],
    ]);
	$service->clearSession();
	unset($_SESSION['phone']);
	unset($_SESSION['name']);
	?>
    <div class="form-container text-center">
        <h2>Сообщение отправлено</h2>
    </div>
<?php else: ?>
    <div class="form-container text-center">
        <h2>Ошибка</h2>
        <div class="error"><?php echo $result['message'] ?? ''; ?></div>
        <a href="index.php" class="change-number">Назад</a>
    </div>
<?php endif; ?>

