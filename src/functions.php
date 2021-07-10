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
function money_format(string $value, int $fraction, bool $trim_trailing = false): string {
  $l = strlen($value);
  $a = $l > $fraction ? $value : str_repeat('0', $fraction - $l + 1) . $value;
  $a = substr_replace($a, '.', -$fraction, 0);

  return $trim_trailing ? rtrim($a, '.0') : $a;
}