<?php

/**
 * Trims the string and then only removes spaces and/or a dash (or en-dash)
 * before the last four of the ten digits.
 *
 * @param string $value
 * @return string
 */
function cleanKennitalaCareful($value) {
    return preg_replace('/^(\d{6})\s?[-–]?\s?(\d{4})$/', '$1$2', trim($value));
}

/**
 * Aggressively strips away ALL spaces and dashes (or en-dashes) from the
 * string, as well as any trailing and leading non-digit gunk.
 *
 * @param string $value
 * @return string
 */
function cleanKennitalaAggressive($value) {
    return preg_replace('/[\s-–]/', '', preg_replace('/^\D+|\D+$/', '', $value));
}

/**
 * Runs minimal cleanup on the input string and if it looks like a kennitala
 * then inserts a nice separator (`'-'` by default) before the last four
 * digits.
 *
 * Falls back to returning the input untouched, if it isn't roughly "kennitala-shaped".
 *
 * @param string $value
 * @param string $separator
 * @return string
 */
function formatKennitala($value, $separator = '-') {
    $cleaned = cleanIfKtShaped($value);
    if (!$cleaned) {
        return $value;
    }
    return substr($cleaned, 0, 6) . $separator . substr($cleaned, 6);
}

/**
 * Returns the (UTC) birth-date (or founding-date) of a roughly
 * "kennitala-shaped" string — **without** checking if it is a valid
 * `Kennitala`.
 *
 * It returns `null` for malformed (non-kennitala shaped) strings,
 * temporary "kerfiskennitalas" and kennitalas with nonsensical dates,
 * even if they're otherwise numerically valid.
 *
 * @param string $value
 * @return DateTime|null
 */
function getKennitalaBirthDate($value) {
    $cleaned = cleanIfKtShaped($value);
    if (!$cleaned || preg_match('/^[89]/', $cleaned)) {
        return null;
    }
    return _getBirthDateFromCleaned($cleaned);
}

/**
 * Parses a string value to see if it may be a technically valid kennitala,
 * and if so, it returns a data array with the cleaned up value
 * along with some meta-data and pretty-formatted version.
 *
 * If the parsing/validation fails, it simply returns `null`.
 *
 * @param string $value
 * @param array $opts
 * @return array|null
 */
function parseKennitala($value, $opts = []) {
    $opts = $opts ?: [];
    if (!$value) {
        return null;
    }
    $value = isset($opts['clean']) && ($opts['clean'] === 'none' || $opts['clean'] === false)
        ? $value
        : (isset($opts['clean']) && $opts['clean'] === 'aggressive'
            ? cleanKennitalaAggressive($value)
            : cleanKennitalaCareful($value));

    if (strlen($value) !== 10 || preg_match('/\D/', $value)) {
        return null;
    }
    if (preg_match('/^[89]/', $value) && !isset($opts['rejectTemporary']) && (!isset($opts['type']) || $opts['type'] !== 'company')) {
        return [
            'value' => $value,
            'type' => 'person',
            'robot' => false,
            'temporary' => true,
            'formatted' => formatKennitala($value),
        ];
    }

    $type = $value[0] > '3' ? 'company' : 'person';
    $optType = isset($opts['type']) ? $opts['type'] : null;
    if ($optType && in_array($optType, ['person', 'company']) && $optType !== $type) {
        return null;
    }

    $robot = isRobotKt($value);
    if ($robot && (!isset($opts['robot']) || !$opts['robot'])) {
        return null;
    }
    $magic = [3, 2, 7, 6, 5, 4, 3, 2, 1];
    $checkSum = 0;
    for ($i = 0, $len = count($magic); $i < $len; $i++) {
        $checkSum += $magic[$i] * intval($value[$i]);
    }
    if ($checkSum % 11) {
        return null;
    }

    $badDate = !isset($opts['strictDate']) || !$opts['strictDate']
        ? !preg_match('/^(?:[012456]\d|[37][01])(?:0\d|1[012]).+[890]/', $value)
        : !_getBirthDateFromCleaned($value);

    if ($badDate) {
        return null;
    }

    return [
        'value' => $value,
        'type' => $type,
        'robot' => $robot,
        'formatted' => formatKennitala($value),
    ];
}

/**
 * Runs the input through `parseKennitala` and returns `true` if the parsing
 * was successful.
 *
 * Options are the same as for `parseKennitala`, except that `clean` option
 * defaults to `"none"`.
 *
 * @param string $value
 * @param array $opts
 * @return bool
 */
function isValidKennitala($value, $opts = []) {
    $opts = array_merge($opts, ['clean' => isset($opts['clean']) ? $opts['clean'] : false]);
    return parseKennitala($value, $opts) !== null;
}

/**
 * Quickly detects if an already parsed input `Kennitala` is `KennitalaPerson`.
 *
 * @param string $kennitala
 * @return bool
 */
function isPersonKennitala($kennitala) {
    return preg_match('/^[012389]/', $kennitala);
}

/**
 * Quickly detects if an already parsed input `Kennitala` is `KennitalaCompany`.
 *
 * @param string $kennitala
 * @return bool
 */
function isCompanyKennitala($kennitala) {
    return preg_match('/^[4567]/', $kennitala);
}

/**
 * Quickly detects if an already parsed input `Kennitala` is a (temporary)
 * "kerfiskennitala" (a subset of valid `KennitalaPerson`s).
 *
 * @param string $kennitala
 * @return bool
 */
function isTempKennitala($kennitala) {
    return preg_match('/^[89]/', $kennitala);
}

/**
 * Generates a technically valid `Kennitala`. (Possibly a real one!)
 *
 * @param array $opts
 * @return string
 */
function generateKennitala($opts = []) {
    $isCompany = isset($opts['type']) && $opts['type'] === 'company';
    if (!$isCompany) {
        if (isset($opts['temporary']) && $opts['temporary']) {
            $Head = rand(0, 1) ? '9' : '8';
            $Tail = str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            return $Head . $Tail;
        }
        if (isset($opts['robot']) && $opts['robot']) {
            $robotKtNums = [212, 220, 239, 247, 255, 263, 271, 298, 301, 336, 433, 492, 506, 778];
            $RRR = $robotKtNums[array_rand($robotKtNums)];
            return "010130{$RRR}9";
        }
    }

    $bDay = isset($opts['birthDate']) ? $opts['birthDate'] : null;
    if (!$bDay || !($bDay instanceof DateTime) ||
        $bDay < new DateTime($isCompany ? '1969-01-01' : '1800-01-01') ||
        $bDay >= new DateTime('2100-01-01')
    ) {
        $maxAge = ($isCompany ? 50 : 100) * 365 * 24 * 60 * 60;
        $bDay = new DateTime('@' . (time() - rand(0, $maxAge)));
    }

    $dateModifier = $isCompany ? 40 : 0;
    $DDMMYY = str_pad($bDay->format('d') + $dateModifier, 2, '0', STR_PAD_LEFT) .
        str_pad($bDay->format('m'), 2, '0', STR_PAD_LEFT) .
        substr($bDay->format('Y'), -2);
    $C = substr($bDay->format('Y'), 1, 1);

    while (true) {
        $x = 0;
        $RR = $isCompany
            ? str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT)
            : str_pad(rand(20, 99), 2, '0', STR_PAD_LEFT);
        while ($x < 10) {
            $kt = $DDMMYY . $RR . $x . $C;
            if (isValidKennitala($kt, ['type' => isset($opts['type']) ? $opts['type'] : null])) {
                return $kt;
            }
            $x++;
        }
    }
}

// Helper functions

function cleanIfKtShaped($value) {
    $cleaned = cleanKennitalaCareful($value);
    return strlen($cleaned) === 10 && !preg_match('/\D/', $cleaned) ? $cleaned : null;
}

function _getBirthDateFromCleaned($cleaned) {
    $D = intval(substr($cleaned, 0, 2)) % 40;
    $M = intval(substr($cleaned, 2, 2)) - 1;
    $C = 18 + ((intval(substr($cleaned, 9, 1)) + 2) % 10);
    $Y = $C * 100 + intval(substr($cleaned, 4, 2));
    $birthDate = new DateTime(sprintf('%04d-%02d-%02d', $Y, $M + 1, $D));
    if ($birthDate->format('d') !== str_pad($D, 2, '0', STR_PAD_LEFT) ||
        $birthDate->format('m') !== str_pad($M + 1, 2, '0', STR_PAD_LEFT) ||
        $birthDate->format('Y') !== str_pad($Y, 4, '0', STR_PAD_LEFT)
    ) {
        return null;
    }
    return $birthDate;
}

function isRobotKt($value) {
    static $robotKtRe;
    if (!$robotKtRe) {
        $robotKtNums = [212, 220, 239, 247, 255, 263, 271, 298, 301, 336, 433, 492, 506, 778];
        $robotKtRe = '/^010130(?:' . implode('|', $robotKtNums) . ')9/';
    }
    return preg_match($robotKtRe, $value);
}
