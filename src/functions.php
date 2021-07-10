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
  $a = str_pad($value, $fraction, '0', STR_PAD_LEFT);
  $l = strlen($a);

  $a[$l - $fraction] = '.';
  if ($a[0] === '.') {
    $a = '0' . $a;
  }

  return $trim_trailing ? rtrim($a, '.0') : $a;
}