<?php
namespace Muvon\KISS;

/**
 * This method is faster implementation of beautify float numbers without E notation
 * Faster than sprintf
 * Faster than bcmath
 *
 * @param string $value
 * @param int $fraction
 * @return string
 *   String value of float representation with fraction applied
 */
function money_v2a(string $value, int $fraction, bool $trim_trailing = false): string {
  $l = strlen($value);
  $a = $l > $fraction ? $value : str_repeat('0', $fraction - $l + 1) . $value;
  $a = substr_replace($a, '.', -$fraction, 0);

  return $trim_trailing ? rtrim($a, '.0') : $a;
}

// Same function to fast up another way conversion
function money_a2v(string $amount, int $fraction): string {
  if (str_contains($amount, '.')) {
    $n = strtok($amount, '.');
    $f = substr(strtok('.'), 0, $fraction);
  } else {
    $n = $amount;
    $f = '';
  }

  $l = strlen($f);
  $v = $n . $f . ($fraction > $l ? str_repeat('0', $fraction - $l) : '');

  return ltrim($v, '0') ?: '0';
}