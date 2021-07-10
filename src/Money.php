<?php declare(strict_types=1);

namespace Muvon\KISS;

use Exception;

final class Money {
  /**
   * @property string $value
   *  Current value (aka minor amount) of inited object
   * @property string $currency
   *  Current currency that we set for inited amount
   * @property string $value_per_amount
   *  How much cents we have in 1 amount
   * @property int $fraction
   *  Current currency fraction
   * @property array $currency_map
   *  Config for supported currency that we set on static init method call
   */
  protected string $value, $currency, $value_per_amount;
  protected int $fraction;
  protected static array $currency_map;

  /**
   * We close constructor to init it with static methods only
   *
   * @param string $amount
   * @param string $currency
   * @param int $fraction
   */
  final protected function __construct(string $amount, string $currency, int $fraction) {
    if (!isset(static::$currency_map[$currency])) {
      throw new \Exception('Unknown currency: ' . $currency);
    }
    assert(isset(static::$currency_map[$currency]['fraction']));
    assert(is_int(static::$currency_map[$currency]['fraction']));
    assert(static::$currency_map[$currency]['fraction'] >= 0);

    $this->currency = $currency;
    $this->fraction = $fraction;
    $this->value_per_amount = gmp_strval(gmp_pow(10, $fraction));
    $this->value = $this->amountToValue($amount);
  }

  /**
   * This method is should be call with configuration before any usage of class
   * Currency map is array where key = currency and value = assoc array with struct
   *   int fraction - config precision for current currency
   *   string name - name of currency
   *
   * @param array $currency_map
   * @return void
   */
  public static function init(array $currency_map): void {
    static::$currency_map = $currency_map;
  }

  /**
   * Create object using amount and currency
   *
   * @param string $amount
   * @param string $currency
   * @return static
   */
  public static function fromAmount(string $amount, string $currency): self {
    $config = static::getCurrencyConfig($currency);
    return new Money($amount, $currency, $config['fraction']);
  }

  /**
   * Create list of Money objects from list of amounts
   *
   * @param array $amounts
   * @param string $currency
   * @return array
   */
  public static function fromAmounts(array $amounts, string $currency): array {
    return array_map(function($amount) use ($currency) {
      return static::fromAmount($amount, $currency);
    }, $amounts);
  }

  /**
   * Create object from minor amount (aka value)
   *
   * @param string|int $value
   * @param string $currency
   * @return self
   */
  public static function fromValue(string|int $value, string $currency): self {
    $Money = static::zero($currency);
    $Money->value = strval($value);
    return $Money;
  }

  /**
   * Create zero value object
   *
   * @param string $currency
   * @return self
   */
  public static function zero(string $currency): self {
    $config = static::getCurrencyConfig($currency);
    return new Money('0', $currency, $config['fraction']);
  }

  /**
   * Create list of Money objects from list of minor amounts (values)
   *
   * @param array $values
   * @param string $currency
   * @return array
   */
  public static function fromValues(array $values, string $currency): array {
    return array_map(function($value) use ($currency) {
      return static::fromValue($value, $currency);
    }, $values);  }

  /**
   * Get currency config that we set with init method
   * This method used only for internal logic
   *
   * @param string $currency
   * @return array
   */
  protected static function getCurrencyConfig(string $currency): array {
    if (!isset(static::$currency_map[$currency])) {
      throw new \Exception('Cannot find currency in config: ' . $currency);
    }

    return static::$currency_map[$currency];
  }

  /**
   * Run addiition on 2 objects
   *
   * @param self $Money
   * @return static
   */
  public function add(self $Money): self {
    $this->validateCurrency($Money);
    return static::fromValue(gmp_strval(gmp_add($this->value, $Money->getValue())), $this->currency);
  }

  /**
   * Run substraction on 2 objects
   *
   * @param self $Money
   * @return self
   */
  public function sub(self $Money): self {
    $this->validateCurrency($Money);
    return static::fromValue(gmp_strval(gmp_sub($this->value, $Money->getValue())), $this->currency);
  }

  /**
   * Run multiplication by factor
   *
   * @param string $factor
   * @return self
   */
   public function mul(string|self $factor): self {
    return static::fromValue(
      bcmul($this->value, $this->adaptFactor($factor)->getAmount(), 0),
      $this->currency
    );
  }

  /**
   * Run division by factor
   *
   * @param string $factor
   * @return self
   */
   public function div(string|self $factor): self {
    return static::fromValue(
      bcdiv($this->value, $this->adaptFactor($factor)->getAmount(), 0),
      $this->currency
    );
  }

  // We use bash like syntax for comparing number

  /**
   * Greater than another Money object
   *
   * @param self $Money
   * @return bool
   */
  public function gt(self $Money): bool {
    $this->validateCurrency($Money);
    return gmp_cmp($this->value, $Money->getValue()) > 0;
  }

  /**
   * Greater than or equals another Money object
   *
   * @param self $Money
   * @return bool
   */
  public function ge(self $Money): bool {
    $this->validateCurrency($Money);
    return gmp_cmp($this->value, $Money->getValue()) >= 0;
  }

  /**
   * Equals to another Money object
   *
   * @param self $Money
   * @return bool
   */
  public function eq(self $Money): bool {
    $this->validateCurrency($Money);
    return gmp_cmp($this->value, $Money->getValue()) === 0;
  }

  /**
   * Not equals to another Money object
   *
   * @param self $Money
   * @return bool
   */
  public function ne(self $Money): bool {
    $this->validateCurrency($Money);
    return gmp_cmp($this->value, $Money->getValue()) !== 0;
  }

  /**
   * Lower than another Money object
   *
   * @param self $Money
   * @return bool
   */
  public function lt(self $Money): bool {
    $this->validateCurrency($Money);
    return gmp_cmp($this->value, $Money->getValue()) < 0;
  }

  /**
   * Lower than or equals to another Money object
   *
   * @param self $Money
   * @return bool
   */
  public function le(self $Money): bool {
    $this->validateCurrency($Money);
    return gmp_cmp($this->value, $Money->getValue()) <= 0;
  }

  /**
   * The current value is greater or equals to zero
   *
   * @return bool
   */
  public function isPositive(): bool {
    return gmp_cmp($this->value, 0) >= 0;
  }

  /**
   * Current value is lower than zero
   *
   * @return bool
   */
  public function isNegative(): bool {
    return gmp_cmp($this->value, 0) < 0;
  }

  /**
   * Current value is equal to zero with all fraction
   *
   * @return bool
   */
  public function isZero(): bool {
    return gmp_cmp($this->value, 0) === 0;
  }

  /**
  * Detect rate using total price and unit price to wanted currency
  *
  * @param self $Source
  * @param self $Target
  * @param string $currency
  * @return self
  */
  public static function rate(self $Source, self $Target, string $currency): self {
    if ($Source->getCurrency() !== $Target->getCurrency()) {
      throw new Exception('Cannot get rate using different currencies');
    }

    if ($Source->getCurrency() === $currency) {
      throw new Exception('Cannot get rate to itself');
    }

    return Money::fromAmount(
      bcdiv($Source->getValue(), $Target->getValue(), static::$currency_map[$currency]['fraction']),
      $currency
    );
  }

  /**
   * Convert current currency value to another one
   * It creates new Money objectu using rate provides as argument
   *
   * @param self $Rate
   *  Another currency value per one current currency (rate)
   * @return self New money object
   */
  public function cnv(self $Rate): self {
    if ($this->currency === $Rate->getCurrency()) {
      throw new Exception('Cannot convert value of same currency to itself');
    }
    return static::fromAmount(
      bcmul($this->getAmount(), $Rate->getAmount(), $Rate->fraction),
      $Rate->getCurrency()
    );
  }

  /**
   * Get current amount of money with configured fraction for currency
   *
   * @return string
   */
  public function getAmount(): string {
    return $this->valueToAmount($this->value);
  }

  /**
   * Get current value of money with zero fraction (minor amount)
   *
   * @return string
   */
  public function getValue(): string {
    return $this->value;
  }

  /**
   * Get current currency of this object
   *
   * @return string
   */
  public function getCurrency(): string {
    return $this->currency;
  }

  /**
   * Check if we have support for that currency
   *
   * @param string $currency
   * @return bool
   */
  public static function hasCurrency(string $currency): bool {
    return isset(static::$currency_map[$currency]);
  }

  // Magic methods
  public function __toString(): string {
    return $this->getAmount() . ' ' . $this->currency;
  }

  /**
   * Validation of currency on second object. This one used for protect
   *   from operations on 2 different currencies that actually should be impossible
   *
   * @param self $Money
   * @return void
   */
  protected function validateCurrency(self $Money) {
    if ($Money->getCurrency() !== $this->currency) {
      throw new \Exception('Trying to make operation on 2 different currencies');
    }
  }

  /**
   * Convert amount to value (minor amount)
   * Used only for internal logic
   *
   * @param string $amount
   * @return string
   */
  protected function amountToValue(string $amount): string {
    return money_a2v($amount, $this->fraction);
  }

  /**
   * Convert value (minor amount) to amount
   * Used only for internal logic
   *
   * @param string $value
   * @return string
   */
  protected function valueToAmount(string $value): string {
    return money_v2a($value, $this->fraction);
  }

  protected function adaptFactor(string|self $factor): self {
    if (!$factor instanceof self) {
      $factor = static::fromAmount($factor, $this->currency);
    } else {
      $this->validateCurrency($factor);
    }

    return $factor;
  }
}