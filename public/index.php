<?php

use App\Otp\OTPService;
use App\Otp\SMSAeroApi;

require __DIR__ . '/../bootstrap.php';

$apiClient = new SMSAeroApi($_ENV['API_KEY'], $_ENV['API_USERNAME'], 'SMS Aero');
$service = new OTPService($apiClient, [
	'code_life_time_minutes' => 5,
	'debug_mode' => false,
]);

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Оставьте заявку</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #1976d2;
            font-family: "Roboto", sans-serif;
            display: flex;
            min-height: 100vh;
        }

        .form-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .text-center {
            text-align: center;
        }

        fieldset {
            border: none;
        }

        legend {
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            text-align: center;
            color: #333;
        }

        .form-field {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
            color: #555;
        }

        input[type="text"],
        input[type="tel"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 1rem;
            transition: border 0.2s;
        }

        input[type="text"]:focus,
        input[type="tel"]:focus {
            border-color: #1976d2;
            outline: none;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .checkbox-group input {
            align-self: flex-start;
            margin-right: 0.5rem;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            border: none;
            background-color: #1976d2;
            color: #fff;
            font-size: 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #155cb0;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1.5rem 1rem;
            }

            h1 {
                font-size: 1.3rem;
            }
        }

        a {
            color: #1976d2;
        }

        a:hover, a:focus, a:active {
            color: #11518f;
            text-decoration: none;
        }

        body {
            margin: 0;
            background: #1976d2;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
            color: #333;
        }

        .form-container {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .phone {
            font-size: 1.2rem;
            color: #424242;
        }

        .phone-icon {
            font-size: 1.5rem;
            color: #1976d2;
            vertical-align: middle;
            display: inline-flex;
            width: 24px;
            margin-left: -24px;
        }

        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 1.5rem 0;
        }

        .otp-input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.5rem;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        .otp-inputs.code-inputs--error .otp-input {
            border: 2px solid #a11111;
        }

        .resend {
            margin-top: 1rem;
            font-size: 0.95rem;
            color: #777;
        }

        .resend a {
            color: #1976d2;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            display: none;
        }

        .change-number {
            margin-top: 1rem;
            display: inline-block;
            font-size: 0.9rem;
            color: #1976d2;
            text-decoration: underline;
            cursor: pointer;
        }

    </style>
</head>
<body>

<?php


if ($_SERVER["REQUEST_METHOD"] === "GET"):
	?>
    <form class="form-container js-form-validate" method="post">
        <input type="hidden" name="action" value="send_code">
        <fieldset>
            <legend>Форма заявки</legend>

            <div class="form-field">
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" autocomplete="name firstname" placeholder="Введите имя"
                />
            </div>

            <div class="form-field">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" autocomplete="phone tel" placeholder="+7 (___) ___-__-__"
                       required/>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="agree" required/>
                <label for="agree">Я <a href="#">согласен</a> на обработку <a href="#">персональных данных</a></label>
            </div>

            <button type="submit">Отправить</button>
        </fieldset>
    </form>

<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'):
	include __DIR__ . '/send_code.php';
endif; ?>

<script>

    const otpCode = (function () {
        const initializedForms = new WeakSet();
        const otpInputsContainerSelector = '.otp-inputs';


        function init(selector = 'form:has(.otp-inputs)') {
            const forms = document.querySelectorAll(selector);
            if (!forms.length) {
                return;
            }

            forms.forEach((form) => {
                if (initializedForms.has(form)) {
                    return;
                }

                initializeForm(form);
                initializedForms.add(form);
            });
        }

        function initializeForm(form) {

            const inputsContainer = form.querySelector(otpInputsContainerSelector);
            inputsContainer.innerHTML = `
            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" autocomplete="one-time-code" />
            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" autocomplete="one-time-code" />
            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" autocomplete="one-time-code" />
            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" autocomplete="one-time-code" />
        `;

            const otpInputs = inputsContainer.querySelectorAll('.otp-input');
            const otpHidden = form.querySelector('[name="code"]');

            if (!otpInputs.length || !otpHidden) {
                return;
            }

            otpInputs.forEach((input, index) => {
                input.addEventListener('keydown', (e) =>
                    handleKeypress(e, otpInputs, otpHidden, index),
                );
                input.addEventListener('keydown', (e) =>
                    handleBackspace(e, otpInputs, otpHidden, index),
                );
                input.addEventListener('input', (e) => updateHiddenInput(otpInputs, otpHidden));

                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = paste.replace(/\D/g, '').slice(0, otpInputs.length);

                    digits.split('').forEach((char, i) => {
                        if (otpInputs[i]) {
                            otpInputs[i].value = char;
                            updateHiddenInput(otpInputs, otpHidden);
                            if (validateOTP(otpHidden, otpInputs)) {
                                const form = e.target.closest('form');
                                form && sendForm(form);
                            }
                        }
                    });
                });
            });
        }

        function handleKeypress(e, otpInputs, otpHidden, index) {
            // Разрешаем Ctrl+V (или Cmd+V на Mac)
            if ((e.ctrlKey || e.metaKey) && e.code === 'KeyV') {
                return;
            }

            if (/^\d$/.test(e.key)) {
                e.target.value = e.key;

                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                updateHiddenInput(otpInputs, otpHidden);

                if (validateOTP(otpHidden, otpInputs)) {
                    const form = e.target.closest('form');
                    form && sendForm(form);
                }

                e.preventDefault();
                return;
            }

            if (e.key === 'Backspace') {
                return;
            }

            e.preventDefault();
        }


        function sendForm(form) {
            const btn = form && form.querySelector('[type=submit]');

            if (btn) {
                btn.click();
                form.reset();
            }
        }

        function validateOTP(otpHidden, otpInputs) {
            if (otpHidden.value.length !== otpInputs.length) {
                return false;
            }
            const otpValue = Array.from(otpInputs)
                .map((input) => input.value)
                .join('');
            return /^\d{4}$/.test(otpValue);
        }

        function handleBackspace(e, otpInputs, otpHidden, index) {
            if (e.key === 'Backspace') {
                if (e.target.value === '') {
                    if (!e.target.value && index > 0) {
                        otpInputs[index - 1].focus();
                        otpInputs[index - 1].dispatchEvent(new KeyboardEvent('keydown', {
                            key: e.key,
                            keyCode: e.keyCode,
                            charCode: e.charCode,
                            cancelable: true,
                            button: true,
                        }));
                        return;
                    }
                }
                setTimeout(() => {
                    updateHiddenInput(otpInputs, otpHidden);
                    if (!e.target.value && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            }
        }

        function updateHiddenInput(otpInputs, otpHidden) {
            const otpValue = Array.from(otpInputs)
                .map((input) => input.value)
                .join('');
            otpHidden.value = otpValue;
        }

        return {
            init,
        };
    })();
    window.otpCode = otpCode;
    otpCode.init();


    (function () {
        const initializedForms = new WeakSet();
        const containerSelector = '.otp-container';
        const timerSelector = '.timer';

        let timerDuration = 60;
        const timers = new Map();

        function init(selector = containerSelector) {
            const container = document.querySelector(selector);
            if (!container) {
                return;
            }

            startTimer(container.querySelector(timerSelector));
        }

        function changeTimerState(timerElement, state) {
            const resendBtn = timerElement?.closest(containerSelector)?.querySelector('.resend-btn');
            const timerText = timerElement?.closest(containerSelector)?.querySelector('.timer-text-wrap');
            if (!resendBtn || !timerText) return;

            if (state === 'running') {
                timerElement.classList.add('running');
                resendBtn.disabled = true;
                resendBtn.style.display = 'none';
                timerText.style.display = '';
            } else {
                timerElement.classList.remove('running');
                resendBtn.disabled = false;
                resendBtn.style.display = '';
                timerText.style.display = 'none';
            }
        }

        function startTimer(timerElement) {
            if (!timerElement) return;

            const oldTimer = timers.get(timerElement);
            clearInterval(oldTimer);

            if (timerElement.dataset.time) {
                timerDuration = parseInt(timerElement.dataset.time);
            }
            let timeLeft = timerDuration;
            updateTimer(timerElement, timeLeft);
            changeTimerState(timerElement, 'running');

            const timerInterval = setInterval(() => {
                timeLeft--;
                updateTimer(timerElement, timeLeft);

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    changeTimerState(timerElement, 'complete');
                }
            }, 1000);

            timers.set(timerElement, timerInterval);
        }

        function updateTimer(timerElement, seconds) {
            const timerText = timerElement.querySelector('.timer-text');
            if (timerText) {
                timerText.textContent = seconds;
            }
        }

        const otpAuth = {
            init
        };


        window.otpAuth = otpAuth;

        document.addEventListener('DOMContentLoaded', function () {
            otpAuth.init();
        });
    })();
</script>

<script src="https://unpkg.com/imask"></script>
<script>
    const maskList = [
        {
            mask: '+{7} (000) 000-00-00',
            startsWith: '7',
            country: 'Russia1',
        },
        {
            mask: '{8} (000) 000-00-00',
            startsWith: '8',
            country: 'Russia',
        },
        {
            mask: '+{375} (00) 000-00-00',
            startsWith: '375',
            country: 'Belarus',
        },
        {
            mask: '+{998} (00) 000-00-00',
            startsWith: '998',
            country: 'Uzbekistan',
        },
        {
            mask: '+{77} (000) 000-00-00',
            startsWith: '77',
            country: 'Kazakhstan',
        },
        {
            mask: '+{000} 0000000000',
            startsWith: '',
            country: 'Other',
        }
    ];

    let phoneMask = null;

    function setPhoneMaskByCountry(countryCode) {
        const phoneInput = document.getElementById('phone');
        if (!phoneInput) return;

        const code = countryCode.replace(/\D/g, '');

        const found = countryCode.length && maskList.find(m => m.startsWith === code);

        if (found) {
            phoneMask = new IMask(phoneInput, {
                mask: found.mask,
                lazy: false,
            });
        } else {
            phoneMask = new IMask(phoneInput, {
                mask: maskList,
                lazy: false,
                dispatch: function (appended, dynamicMasked) {
                    const number = (dynamicMasked.value + appended).replace(/\D/g, '');
                    return dynamicMasked.compiledMasks.find(m =>
                        number.indexOf(m.startsWith) === 0
                    );
                }
            });
        }

        phoneInput.addEventListener('blur', () => {
            if (!phoneMask.masked.isComplete) {
                phoneMask.value = '';
            }
        });
    }


    const form = document.querySelector('.js-form-validate');
    if (form) {
        fetch('https://ipwho.is/')
            .then(res => res.json())
            .then(data => {
                setPhoneMaskByCountry(data.calling_code);
            })
            .catch(() => {
                // fallback
                setPhoneMaskByCountry('');
            });

        form.addEventListener('submit', e => {
            if (!phoneMask || !phoneMask.masked.isComplete) {
                e.preventDefault();
                alert('Введите корректный номер телефона');
            }
        });
    }


</script>
<script>
    const params = new URLSearchParams(window.location.search);
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'].forEach(key => {
        const value = params.get(key);
        if (value) {
            document.cookie = `${key}=${encodeURIComponent(value)}; path=/; max-age=2592000`; // 30 дней
        }
    });
</script>
</body>
</html>
