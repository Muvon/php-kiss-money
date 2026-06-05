<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Muvon\KISS\Money;
use function Muvon\KISS\money_a2v;
use function Muvon\KISS\money_v2a;
use function Muvon\KISS\money_normalize;

final class MoneyTest extends TestCase
{
  protected $add_sub_ops = [
    'add' => [
      [['1.03', 'USD'], ['3.05', 'USD'], ['4.08', '408']],
      [['1.004', 'XRP'], ['0.000001', 'XRP'], ['1.004001', '1004001']],
      [['100', 'JPY'], ['200', 'JPY'], ['300', '300']],
    ],
    'sub' => [
      [['1.03', 'USD'], ['3.05', 'USD'], ['-2.02', '-202']],
      [['1.004', 'XRP'], ['0.000001', 'XRP'], ['1.003999', '1003999']],
      [['2.343', 'XRP'], ['5.44444', 'XRP'], ['-3.101440', '-3101440']],
      [['300', 'JPY'], ['200', 'JPY'], ['100', '100']],
    ]
  ];

  protected $mul_div_ops = [
    'mul' => [
      [['1.03', 'USD'], '1.3', ['1.33', '133']],
      [['1.004', 'XRP'], '1.24', ['1.244960', '1244960']],
      [['1.004', 'XRP'], '3', ['3.012000', '3012000']],
      [['1.004', 'XRP'], '1000', ['1004.000000', '1004000000']],
      // Factor must keep full precision; only the final result truncates.
      [['100.00', 'USD'], '1.005', ['100.50', '10050']],
      [['10.00', 'USD'], '0.001', ['0.01', '1']],
      [['1.000000', 'XRP'], '1.0000015', ['1.000001', '1000001']],
      [['100', 'JPY'], '1.5', ['150', '150']],
      [['100', 'JPY'], '2.9', ['290', '290']],
    ],
    'div' => [
      [['1.03', 'USD'], '1.43', ['0.72', '72']],
      [['1.004', 'XRP'], '1.22', ['0.822950', '822950']],
      [['1.004', 'XRP'], '2', ['0.502000', '502000']],
      [['1.004', 'XRP'], '100000', ['0.000010', '10']],
      [['1.004', 'XRP'], '10000000', ['0.000000', '0']],
      // Sub-fraction divisor must not be truncated to zero (used to crash).
      [['10.00', 'USD'], '0.001', ['10000.00', '1000000']],
      [['100', 'JPY'], '1.5', ['66', '66']],
    ]
  ];

  protected $cnv_ops = [
    [['2.04', 'USD'], ['10.3', 'XRP'], ['21.012000', 'XRP']],
    [['0.5', 'XRP'], ['0.34', 'USD'], ['0.17', 'USD']],
    [['20.5345', 'XRP'], ['0.34', 'USD'], ['6.98', 'USD']],
    [['3.000231', 'XRP'], ['0.43', 'USD'], ['1.29', 'USD']],
    [['10', 'USD'], ['150', 'JPY'], ['1500', 'JPY']],
  ];

  protected $cmp_ops = [
    'eq' => [
      [['1.03', 'USD'], ['1.03', 'USD']],
      [['1.004', 'XRP'], ['1.004', 'XRP']],
      [['1.004', 'XRP'], ['1.004', 'XRP']],
    ],
    'ne' => [
      [['1.03', 'USD'], ['1', 'USD']],
      [['1.004', 'XRP'], ['534', 'XRP']],
      [['1.004', 'XRP'], ['0.00001', 'XRP']],
    ],
    'gt' => [
      [['1.13', 'USD'], ['1.03', 'USD']],
      [['3004', 'XRP'], ['1.004', 'XRP']],
      [['1.404', 'XRP'], ['1.004', 'XRP']],
    ],
    'ge' => [
      [['1.13', 'USD'], ['1.03', 'USD']],
      [['1.004', 'XRP'], ['1.004', 'XRP']],
      [['1.005', 'XRP'], ['1.004', 'XRP']],
    ],
    'lt' => [
      [['0.9', 'USD'], ['1.03', 'USD']],
      [['1.003', 'XRP'], ['1.004', 'XRP']],
      [['1', 'XRP'], ['1.004', 'XRP']],
    ],
    'le' => [
      [['0.9', 'USD'], ['1.03', 'USD']],
      [['1.004', 'XRP'], ['1.004', 'XRP']],
      [['1', 'XRP'], ['1.004', 'XRP']],
    ],
  ];

  // Same fixtures but every operator must now return FALSE.
  protected $cmp_false_ops = [
    'eq' => [[['1.03', 'USD'], ['1.13', 'USD']]],
    'ne' => [[['1.03', 'USD'], ['1.03', 'USD']]],
    'gt' => [[['1.03', 'USD'], ['1.13', 'USD']], [['1.03', 'USD'], ['1.03', 'USD']]],
    'ge' => [[['1.03', 'USD'], ['1.13', 'USD']]],
    'lt' => [[['1.13', 'USD'], ['1.03', 'USD']], [['1.03', 'USD'], ['1.03', 'USD']]],
    'le' => [[['1.13', 'USD'], ['1.03', 'USD']]],
  ];

  public function setUp(): void {
    parent::setUp();
    Money::init([
      'USD' => [
        'fraction' => 2,
      ],
      'XRP' => [
        'fraction' => 6
      ],
      'JPY' => [
        'fraction' => 0
      ],
    ]);
  }

  public function testCanCreateSingle() {
    $Money = Money::fromAmount('1.05', 'USD');
    $this->assertInstanceOf(Money::class, $Money);
    $this->assertEquals('USD', $Money->getCurrency());
    $this->assertEquals('1.05', $Money->getAmount());

    $Money = Money::fromValue(100, 'USD');
    $this->assertInstanceOf(Money::class, $Money);
    $this->assertEquals('USD', $Money->getCurrency());
    $this->assertEquals('100', $Money->getValue());
  }


  public function testCanCreateMultiple() {
    $amounts = ['1.05', '10'];
    $moneys = $Money = Money::fromAmounts($amounts, 'USD');
    $this->assertEquals(sizeof($amounts), sizeof($moneys));
    foreach ($moneys as $k => $Money) {
      $this->assertEquals(Money::fromAmount($amounts[$k], 'USD'), $Money);
      $this->assertInstanceOf(Money::class, $Money);
    }

    $values = ['100', '350'];
    $moneys = $Money = Money::fromValues($values, 'USD');
    $this->assertEquals(sizeof($values), sizeof($moneys));
    foreach ($moneys as $k => $Money) {
      $this->assertEquals(Money::fromValue($values[$k], 'USD'), $Money);
      $this->assertInstanceOf(Money::class, $Money);
    }

  }

  public function testCanCreateZero() {
    $money1 = Money::fromAmount('0', 'USD');
    $money2 = Money::zero('USD');
    $this->assertInstanceOf(Money::class, $money2);
    $this->assertEquals($money1, $money2);
    $this->assertSame('0', $money2->getValue());
    $this->assertSame('0.00', $money2->getAmount());
  }

  public function testCannotCreateFromAmountNoConfig() {
    $this->expectException(Exception::class);
    Money::fromAmount('2.03', 'EUR');
  }

  public function testCannotCreateFromValueNoConfig() {
    $this->expectException(Exception::class);
    Money::fromValue('403', 'EUR');
  }

  public function testAddSubOperations() {
    foreach ($this->add_sub_ops as $operation => $tests) {
      foreach ($tests as $test) {
        $left = Money::fromAmount(...$test[0]);
        $right = Money::fromAmount(...$test[1]);
        $result = $left->$operation($right);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals($test[2][0], $result->getAmount());
        $this->assertEquals($test[2][1], $result->getValue());
      }
    }
  }

  public function testMulDivOperations() {
    foreach ($this->mul_div_ops as $operation => $tests) {
      foreach ($tests as $test) {
        $left = Money::fromAmount(...$test[0]);
        $result = $left->$operation($test[1]);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals($test[2][0], $result->getAmount());
        $this->assertEquals($test[2][1], $result->getValue());
      }
    }
  }

  public function testRateCalculation() {
    $source = Money::fromAmount('0.5', 'USD');
    $target = Money::fromAmount('400', 'USD');
    $rate = Money::rate($source, $target, 'XRP');

    $this->assertEquals('XRP', $rate->getCurrency());
    $this->assertEquals('0.001250', $rate->getAmount());
  }

  public function testConvertToAnotherCurrency() {
    foreach ($this->cnv_ops as $test) {
      $money = Money::fromAmount(...$test[0]);
      $rate = Money::fromAmount(...$test[1]);
      $result = $money->cnv($rate);

      $this->assertInstanceOf(Money::class, $result);
      $this->assertNotEquals($money, $result);
      $this->assertNotEquals($rate, $result);

      $expect = Money::fromAmount(...$test[2]);
      $this->assertEquals($expect->getValue(), $result->getValue());
      $this->assertEquals($expect->getAmount(), $result->getAmount());
      $this->assertEquals($expect->getCurrency(), $result->getCurrency());
    }
  }

  public function testCannotConvertSameCurrency() {
    $money = Money::fromAmount('1.23', 'USD');
    $rate = Money::fromAmount('0.3', 'USD');
    $this->expectException(Exception::class);
    $money->cnv($rate);
  }

  public function testResultIsImmutable() {
    $left_args = ['1.043200', 'XRP'];
    $right_args = ['1.432400', 'XRP'];
    $left = Money::fromAmount(...$left_args);
    $right = Money::fromAmount(...$right_args);

    foreach (['mul', 'div', 'add', 'sub'] as $op) {
      $result = $left->$op($right);
      $this->assertEquals($left_args[0], $left->getAmount());
      $this->assertEquals($left_args[1], $left->getCurrency());
      $this->assertEquals($right_args[0], $right->getAmount());
      $this->assertEquals($right_args[1], $right->getCurrency());
    }
  }

  public function testCnvIsImmutable() {
    $usd = Money::fromAmount('10.00', 'USD');
    $rate = Money::fromAmount('150', 'JPY');
    $usd->cnv($rate);

    $this->assertSame('10.00', $usd->getAmount());
    $this->assertSame('1000', $usd->getValue());
    $this->assertSame('USD', $usd->getCurrency());
    $this->assertSame('150', $rate->getAmount());
    $this->assertSame('JPY', $rate->getCurrency());
  }

  public function testCmpOperations() {
    foreach ($this->cmp_ops as $operation => $tests) {
      foreach ($tests as $test) {
        $left = Money::fromAmount(...$test[0]);
        $right = Money::fromAmount(...$test[1]);
        $result = $left->$operation($right);

        $this->assertIsBool($result);
        $this->assertTrue($result, "$operation should be true for " . json_encode($test));
      }
    }
  }

  public function testCmpOperationsFalseBranch() {
    foreach ($this->cmp_false_ops as $operation => $tests) {
      foreach ($tests as $test) {
        $left = Money::fromAmount(...$test[0]);
        $right = Money::fromAmount(...$test[1]);
        $result = $left->$operation($right);

        $this->assertIsBool($result);
        $this->assertFalse($result, "$operation should be false for " . json_encode($test));
      }
    }
  }

  public static function addSubProvider(): array {
    return [['add'], ['sub']];
  }

  #[DataProvider('addSubProvider')]
  public function testCannotAddSubDifferentCurrency(string $op) {
    $usd = Money::fromAmount('1.03', 'USD');
    $xrp = Money::fromAmount('0.001', 'XRP');
    $this->expectException(Exception::class);
    $usd->$op($xrp);
  }

  public static function cmpProvider(): array {
    return [['eq'], ['ne'], ['ge'], ['gt'], ['le'], ['lt']];
  }

  #[DataProvider('cmpProvider')]
  public function testCannotCompareDifferentCurrency(string $op) {
    $usd = Money::fromAmount('1.03', 'USD');
    $xrp = Money::fromAmount('0.001', 'XRP');
    $this->expectException(Exception::class);
    $usd->$op($xrp);
  }

  public function testIsPositive() {
    $usd = Money::fromAmount('1.03', 'USD');
    $this->assertEquals(true, $usd->isPositive());

    $usd = Money::fromAmount('-1.03', 'USD');
    $this->assertEquals(false, $usd->isPositive());

    $usd = Money::fromAmount('0', 'USD');
    $this->assertEquals(true, $usd->isPositive());
  }

  public function testIsNegative() {
    $usd = Money::fromAmount('1.03', 'USD');
    $this->assertEquals(false, $usd->isNegative());

    $usd = Money::fromAmount('-1.03', 'USD');
    $this->assertEquals(true, $usd->isNegative());

    $usd = Money::fromAmount('0', 'USD');
    $this->assertEquals(false, $usd->isNegative());
  }

  public function testIsZero() {
    $usd = Money::fromAmount('1.03', 'USD');
    $this->assertEquals(false, $usd->isZero());

    $usd = Money::fromAmount('-1.03', 'USD');
    $this->assertEquals(false, $usd->isZero());

    $usd = Money::fromAmount('0', 'USD');
    $this->assertEquals(true, $usd->isZero());

    $usd = Money::fromAmount('0.00', 'USD');
    $this->assertEquals(true, $usd->isZero());

    $usd = Money::fromAmount('0.001', 'USD');
    $this->assertEquals(true, $usd->isZero());
  }

  public function testHasCurrency() {
    $this->assertTrue(Money::hasCurrency('USD'));
    $this->assertTrue(Money::hasCurrency('JPY'));
    $this->assertFalse(Money::hasCurrency('TTT'));
    $this->assertFalse(Money::hasCurrency(''));
  }

  public function testGetCurrency() {
    $this->assertSame('USD', Money::fromAmount('1.00', 'USD')->getCurrency());
    $this->assertSame('XRP', Money::zero('XRP')->getCurrency());
    $this->assertSame('JPY', Money::fromValue('5', 'JPY')->getCurrency());
  }

  public function testCanUseAsString() {
    $usd = Money::fromAmount('0.01', 'USD');
    $this->assertEquals('0.01 USD', (string) $usd);

    $this->assertSame('-1.03 USD', (string) Money::fromAmount('-1.03', 'USD'));
    $this->assertSame('1500 JPY', (string) Money::fromAmount('1500', 'JPY'));
    $this->assertSame('0 JPY', (string) Money::zero('JPY'));
  }

  // --- Zero-fraction currencies (e.g. JPY) ---

  public function testZeroFractionGetAmountRoundTrip() {
    $this->assertSame('100', Money::fromAmount('100', 'JPY')->getAmount());
    $this->assertSame('1500', Money::fromAmount('1500', 'JPY')->getAmount());
    $this->assertSame('5', Money::fromValue('5', 'JPY')->getAmount());
    $this->assertSame('100', Money::fromAmount('100', 'JPY')->getValue());
    // Lossless value <-> amount round-trip at fraction 0.
    $this->assertSame('1500', money_v2a(money_a2v('1500', 0), 0));
  }

  public function testZeroFractionDirectConversion() {
    $this->assertSame('100', money_v2a('100', 0));
    $this->assertSame('0', money_v2a('0', 0));
    $this->assertSame('-7', money_v2a('-7', 0));
    $this->assertSame('100', money_a2v('100', 0));
    $this->assertSame('-7', money_a2v('-7', 0));
    // A fractional amount is truncated to the integer part for a 0-fraction currency.
    $this->assertSame('5', money_a2v('5.99', 0));
  }

  public function testCnvIntoZeroFractionCurrency() {
    $result = Money::fromAmount('10', 'USD')->cnv(Money::fromAmount('150', 'JPY'));
    $this->assertSame('1500', $result->getValue());
    $this->assertSame('1500', $result->getAmount());
    $this->assertSame('JPY', $result->getCurrency());
  }

  // --- mul/div factor precision (must not truncate the scalar to currency fraction) ---

  public function testMulFactorKeepsPrecisionBeyondCurrencyFraction() {
    $r = Money::fromAmount('100.00', 'USD')->mul('1.005');
    $this->assertSame('100.50', $r->getAmount());
    $this->assertSame('10050', $r->getValue());
  }

  public function testMulBySubFractionFactor() {
    $r = Money::fromAmount('10.00', 'USD')->mul('0.001');
    $this->assertSame('0.01', $r->getAmount());
    $this->assertSame('1', $r->getValue());
  }

  public function testDivBySubFractionFactor() {
    $r = Money::fromAmount('10.00', 'USD')->div('0.001');
    $this->assertSame('10000.00', $r->getAmount());
    $this->assertSame('1000000', $r->getValue());
  }

  public function testMulWithMoneyFactorSharesCurrency() {
    $r = Money::fromAmount('2.00', 'USD')->mul(Money::fromAmount('1.50', 'USD'));
    $this->assertSame('3.00', $r->getAmount());
  }

  public function testMulWithMoneyFactorDifferentCurrencyThrows() {
    $this->expectException(Exception::class);
    Money::fromAmount('2.00', 'USD')->mul(Money::fromAmount('1.50', 'XRP'));
  }

  // --- Division / rate by zero must throw a catchable library Exception ---

  public static function zeroDivisorProvider(): array {
    return [['0'], ['0.00'], ['0.000'], ['-0']];
  }

  #[DataProvider('zeroDivisorProvider')]
  public function testDivByZeroStringThrowsException(string $divisor) {
    $this->expectException(Exception::class);
    Money::fromAmount('100.00', 'USD')->div($divisor);
  }

  public function testDivByZeroMoneyThrowsException() {
    $this->expectException(Exception::class);
    Money::fromAmount('100.00', 'USD')->div(Money::zero('USD'));
  }

  public function testRateByZeroTargetThrowsException() {
    $this->expectException(Exception::class);
    Money::rate(Money::fromAmount('100', 'USD'), Money::fromAmount('0', 'USD'), 'XRP');
  }

  public function testRateDifferentSourceTargetCurrencyThrows() {
    $this->expectException(Exception::class);
    Money::rate(Money::fromAmount('1', 'USD'), Money::fromAmount('1', 'XRP'), 'JPY');
  }

  public function testRateToItselfThrows() {
    $this->expectException(Exception::class);
    Money::rate(Money::fromAmount('1', 'USD'), Money::fromAmount('2', 'USD'), 'USD');
  }

  // --- Leading-dot amounts must equal their zero-prefixed form ---

  public function testLeadingDotAmount() {
    $this->assertSame('50', Money::fromAmount('.5', 'USD')->getValue());
    $this->assertSame('0.50', Money::fromAmount('.5', 'USD')->getAmount());
    $this->assertTrue(Money::fromAmount('.5', 'USD')->eq(Money::fromAmount('0.5', 'USD')));

    $this->assertSame('50', money_a2v('.5', 2));
    $this->assertSame('-50', money_a2v('-.5', 2));
    $this->assertSame('25', money_a2v('.25', 2));
    $this->assertSame('99', money_a2v('.99', 2));
  }

  // --- Negative zero must canonicalize to "0" ---

  public function testNegativeZeroNormalizedOnTruncation() {
    $m = Money::fromAmount('-0.001', 'USD');
    $this->assertSame('0', $m->getValue());
    $this->assertSame('0.00', $m->getAmount());
    $this->assertSame('0.00 USD', (string) $m);
    $this->assertSame(Money::zero('USD')->getValue(), $m->getValue());
  }

  public function testNegativeZeroPredicateConsistency() {
    $m = Money::fromAmount('-0.001', 'USD');
    $this->assertFalse($m->isNegative());
    $this->assertTrue($m->isZero());
    $this->assertTrue($m->isPositive());
    // Display must agree with the predicates (no "-0.00").
    $this->assertSame('0.00 USD', (string) $m);
  }

  public function testNegativeZeroDirectFunctions() {
    $this->assertSame('0', money_a2v('-0.001', 2));
    $this->assertSame('0', money_a2v('-0.00', 2));
    $this->assertSame('0', money_a2v('0.001', 2));
    $this->assertSame('0.00', money_v2a('-0', 2));
    $this->assertSame('0', money_v2a('-0', 0));
  }

  // --- fromValue must canonicalize (gmp reads leading zeros as octal) ---

  public function testFromValueCanonicalizesLeadingZeros() {
    $m = Money::fromValue('0100', 'USD');
    $this->assertSame('100', $m->getValue());
    $this->assertSame('1.00', $m->getAmount());
    $this->assertSame('100', $m->add(Money::zero('USD'))->getValue());
    $this->assertSame('200', $m->mul('2')->getValue());
  }

  public function testFromValueNegativeLeadingZeros() {
    $m = Money::fromValue('-0100', 'USD');
    $this->assertSame('-100', $m->getValue());
    $this->assertSame('-1.00', $m->getAmount());
  }

  public function testFromValueOctalInvalidDigitsNoCrash() {
    $m = Money::fromValue('08', 'USD');
    $this->assertSame('8', $m->getValue());
    $this->assertSame('8', $m->add(Money::zero('USD'))->getValue());
    $this->assertSame('0.08', $m->getAmount());
  }

  public function testEqualityAcrossFactoryPaths() {
    $a = Money::fromAmount('1.00', 'USD');
    $b = Money::fromValue('0100', 'USD');
    $this->assertTrue($a->eq($b));
    $this->assertSame($a->getValue(), $b->getValue());
    $this->assertEquals($a, $b);
  }

  // --- Malformed/empty input must be handled without emitting PHP warnings ---

  public function testDegenerateInputEmitsNoWarning() {
    set_error_handler(function ($n, $s) { throw new ErrorException($s); });
    try {
      $this->assertSame('0', money_a2v('', 2));
      $this->assertSame('0.00', money_v2a('', 2));
      $this->assertSame('0', money_a2v('.', 2));
      $this->assertSame('0.00 USD', (string) Money::fromAmount('', 'USD'));
    } finally {
      restore_error_handler();
    }
  }

  // --- Arbitrary precision (gmp/bcmath) must not overflow ---

  public function testLargeNumberArithmetic() {
    $sum = Money::fromValue('99999999999999999999', 'USD')->add(Money::fromValue('1', 'USD'));
    $this->assertSame('100000000000000000000', $sum->getValue());
    $this->assertSame(
      '999999999999999999.99',
      Money::fromValue('99999999999999999999', 'USD')->getAmount()
    );
  }

  public function testLargeNumberMul() {
    $r = Money::fromValue('100000000000000000000', 'USD')->mul('3');
    $this->assertSame('300000000000000000000', $r->getValue());
  }

  // --- Conversion truncates (never rounds), symmetrically for negatives ---

  public function testTruncationNotRounding() {
    $this->assertSame('199', Money::fromAmount('1.999', 'USD')->getValue());
    $this->assertSame('1.99', Money::fromAmount('1.999', 'USD')->getAmount());
    $this->assertSame('100', Money::fromAmount('1.005', 'USD')->getValue());
    $this->assertSame('-199', Money::fromAmount('-1.999', 'USD')->getValue());
    $this->assertSame('-1.99', Money::fromAmount('-1.999', 'USD')->getAmount());
    // Excess fraction digits are truncated to the currency fraction.
    $this->assertSame('199', money_a2v('1.999999', 2));
  }

  // --- money_normalize helper ---

  // --- money_v2a trim_trailing flag drops redundant fractional zeros ---

  public function testMoneyV2aTrimTrailing() {
    $this->assertSame('100.5', money_v2a('10050', 2, true));
    $this->assertSame('100', money_v2a('10000', 2, true));
    $this->assertSame('1', money_v2a('100', 2, true));
    $this->assertSame('0.05', money_v2a('5', 2, true));
    $this->assertSame('0', money_v2a('0', 2, true));
    $this->assertSame('-100.5', money_v2a('-10050', 2, true));
    $this->assertSame('150', money_v2a('150', 0, true));
  }

  public function testMoneyNormalize() {
    $this->assertSame('100', money_normalize('0100'));
    $this->assertSame('-100', money_normalize('-0100'));
    $this->assertSame('0', money_normalize('0'));
    $this->assertSame('0', money_normalize('-0'));
    $this->assertSame('0', money_normalize('000'));
    $this->assertSame('0', money_normalize(''));
    $this->assertSame('8', money_normalize('08'));
    $this->assertSame('123', money_normalize('123'));
  }
}
