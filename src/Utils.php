<?php

function formatPhoneNumber(string $number, array $mask = []): string
{
    $digits = preg_replace('/\D+/', '', $number);

    if (strlen($digits) < 11) {
        return $number;
    }

    $countryCodeLength = strlen($digits) - 10;
    $countryCode = substr($digits, 0, $countryCodeLength);

    $mainPart = substr($digits, $countryCodeLength);

    $parts = [
        'area' => substr($mainPart, 0, 3),
        'first' => substr($mainPart, 3, 3),
        'second' => substr($mainPart, 6, 2),
        'third' => substr($mainPart, 8, 2),
    ];

    foreach ($mask as $key => $value) {
        if (isset($parts[$key])) {
            $length = strlen($parts[$key]);
            $parts[$key] = str_repeat($value, $length);
        }
    }

    return sprintf(
        '+%s (%s) %s - %s - %s',
        $countryCode,
        $parts['area'],
        $parts['first'],
        $parts['second'],
        $parts['third']
    );
}
