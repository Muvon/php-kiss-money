<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Muvon\KISS\Money;

final class MoneyTest extends TestCase
{
  protected $add_sub_ops = [
    'add' => [
      [['1.03', 'USD'], ['3.05', 'USD'], ['4.08', '408']],
      [['1.004', 'XRP'], ['0.000001', 'XRP'], ['1.004001', '1004001']],
    ],
    'sub' => [
      [['1.03', 'USD'], ['3.05', 'USD'], ['-2.02', '-202']],
      [['1.004', 'XRP'], ['0.000001', 'XRP'], ['1.003999', '1003999']],
      [['2.343', 'XRP'], ['5.44444', 'XRP'], ['-3.101440', '-3101440']],
    ]
  ];

  protected $mul_div_ops = [
    'mul' => [
      [['1.03', 'USD'], '1.3', ['1.33', '133']],
      [['1.004', 'XRP'], '1.24', ['1.244960', '1244960']],
      [['1.004', 'XRP'], '3', ['3.012000', '3012000']],
      [['1.004', 'XRP'], '1000', ['1004.000000', '1004000000']],
    ],
    'div' => [
      [['1.03', 'USD'], '1.43', ['0.72', '72']],
      [['1.004', 'XRP'], '1.22', ['0.822950', '822950']],
      [['1.004', 'XRP'], '2', ['0.502000', '502000']],
      [['1.004', 'XRP'], '100000', ['0.000010', '10']],
      [['1.004', 'XRP'], '10000000', ['0.000000', '0']],
    ]
  ];

  protected $cnv_ops = [
    [['2.04', 'USD'], ['10.3', 'XRP'], ['21.012000', 'XRP']],
    [['0.5', 'XRP'], ['0.34', 'USD'], ['0.17', 'USD']],
    [['20.5345', 'XRP'], ['0.34', 'USD'], ['6.98', 'USD']],
    [['3.000231', 'XRP'], ['0.43', 'USD'], ['1.29', 'USD']],
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

  public function setUp(): void {
    parent::setUp();
    Money::init([
      'USD' => [
        'fraction' => 2,
      ],
      'XRP' => [
        'fraction' => 6
      ]
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
  }



  public function testCannotCreateNoConfig() {
    $this->expectException('Exception');
    Money::fromAmount('2.03', 'EUR');

    $this->expectException('Exception');
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
      // $this->assertEquals($expect->getCurrency(), $result->getCurrency());
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

  public function testCmpOperations() {
    foreach ($this->cmp_ops as $operation => $tests) {
      foreach ($tests as $test) {
        $left = Money::fromAmount(...$test[0]);
        $right = Money::fromAmount(...$test[1]);
        $result = $left->$operation($right);

        $this->assertIsBool($result);
        $this->assertEquals(true, $result);
      }
    }
  }

  public function testCannotAddSubDifferentCurrency() {
    $usd = Money::fromAmount('1.03', 'USD');
    $xrp = Money::fromAmount('0.001', 'XRP');

    foreach (['add', 'sub'] as $op) {
      $this->expectException(Exception::class);
      $usd->$op($xrp);
    }
  }

  public function testCannotCompareDifferentCurrency() {
    $usd = Money::fromAmount('1.03', 'USD');
    $xrp = Money::fromAmount('0.001', 'XRP');
    $this->expectException(Exception::class);
    $usd->eq($xrp);

    foreach (['eq', 'ne', 'ge', 'gt', 'le', 'lt'] as $op) {
      $this->expectException(Exception::class);
      $usd->$op($xrp);
    }
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
    $this->assertEquals(true, Money::hasCurrency('XRP'));
    $this->assertEquals(false, Money::hasCurrency('TTT'));
  }

  public function testCanUseAsString() {
    $usd = Money::fromAmount('0.01', 'USD');
    $this->assertEquals('0.01 USD', (string) $usd);
  }
}


