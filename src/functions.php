<?php
namespace Muvon\KISS;

/**
 * Normalize an integer string (minor amount / value) to its canonical form:
 * strip a leading sign, drop leading zeros, and never emit a signed zero.
 * This keeps every value the library stores comparable as a plain string and
 * safe to feed to gmp_* (which would otherwise read leading zeros as octal).
 *
 * @param string $value
 * @return string
 */
function money_normalize(string $value): string {
  $is_negative = isset($value[0]) && $value[0] === '-';
  if ($is_negative) {
    $value = substr($value, 1);
  }

  $value = ltrim($value, '0');
  if ($value === '') {
    return '0';
  }

  return ($is_negative ? '-' : '') . $value;
}

/**
 * This method is faster implementation of beautify float numbers without E notation
 * Faster than sprintf
 * Faster than bcmath
 *
 * @param string $value
 * @param int $fraction
 * @param bool $trim_trailing
 * @return string
 *   String value of float representation with fraction applied
 */
function money_v2a(string $value, int $fraction, bool $trim_trailing = false): string {
  $value = money_normalize($value);
  $is_negative = $value[0] === '-';
  if ($is_negative) {
    $value = substr($value, 1);
  }

  // Zero-fraction currencies (e.g. JPY) have no decimal part at all.
  // substr_replace with offset -0 would otherwise inject a leading dot.
  if ($fraction === 0) {
    return ($is_negative ? '-' : '') . $value;
  }

  $l = strlen($value);
  $a = $l > $fraction ? $value : str_repeat('0', $fraction - $l + 1) . $value;
  $a = substr_replace($a, '.', -$fraction, 0);
  if ($trim_trailing) {
    $a = rtrim(rtrim($a, '0'), '.') ?: '0';
  }

  return ($is_negative ? '-' : '') . $a;
}

// Same function to fast up another way conversion
function money_a2v(string $amount, int $fraction): string {
  $is_negative = isset($amount[0]) && $amount[0] === '-';
  if ($is_negative) {
    $amount = substr($amount, 1);
  }

  // strpos/substr instead of strtok: strtok skips a leading '.', so ".5"
  // would be parsed as the integer part "5" (a 10^fraction inflation).
  $dot = strpos($amount, '.');
  if ($dot === false) {
    $int = $amount;
    $frac = '';
  } else {
    $int = substr($amount, 0, $dot);
    $frac = substr($amount, $dot + 1, $fraction);
  }

  $l = strlen($frac);
  $value = $int . $frac . ($fraction > $l ? str_repeat('0', $fraction - $l) : '');
  return money_normalize($is_negative ? '-' . $value : $value);
}
