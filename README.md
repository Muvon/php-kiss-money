# php-kiss-money

KISS implementation of Money manipulation with configuring currencies byself

## Simple usage

```php
use Muvon\KISS\Money;
Money::init([
  'USD' => [
    'fraction' => 2
  ]
]);

$Money = Money::fromAmount('0.01', 'USD');

// If we want to create from minor amount
// $Money = Money::fromMinor('1', 'USD');
// Get amount
var_dump($Money->getAmount());

// Get minor amount aka Value
var_dump($Money->getValue());
```

## Methods

### Initialize 

Before usage of Money object you need to configure it with your currencies.

To do this just invoke Money::init() method with passed config to it before startup of your application.

Example of initializing the object:

```php
use Muvon\KISS\Money;
$config = [
  'USD' => [
    'fraction' => 2
  ],
  'EUR' => [
    'fraction' => 2
  ]
];
Money::init($config);
```

### Create from amount of currency

You can create object using amount or value (aka minor amount) as single instance and also as from list of instances (array);

Look at example usage below to understand how you can create objects.

```php
$list = Money::fromAmounts(['1.01', '2.03'], 'USD');
// Dumps array of Money objects created from amounts
var_dump($list);

// Dumps single object from amount
var_dump(Money::fromAmount('1.01', 'USD'));

// You can do almost the same just from minor amount
// Using method fromValue and fromValues
// But passing minor amounts to them
```

### Mathematic operations

Available operations with 2 objects of money - add and sub.

Example:

```php
$one = Money::fromAmount('1.5', 'USD');
$two = Money::fromAmount('2.0', 'USD');

// Dumps "3.50"
var_dump($one->add($two));

// Dumps "0.50"
var_dump($two->sub($one));
```

You also can multiply or divide using methods mul and div.

Example:

```php
$usd = Money::fromAmount('1.5', 'USD');

// Dumps "3.00"
var_dump($usd->mul('2'));

// Dumps "0.75"
var_dump($usd->div('2'));
```

### Convert to another country

The library supports conversion from one to another country in rate of presented by another country.

To convert just call cnv methods as follows in example.

Example:

```php
$xrp = Money::fromAmount('23.00243', 'XRP');
$rate = Money::fromAmount('0.51', 'USD'); // How much for 1 XRP

// Dumps "11.73"
var_dump($xrp->cnv($rate));
```

To make conversions easier you can also create your rate using 2 same currency objects.

Example:

```php
$Source = Money::fromAmount('0.5', 'USD');
$Target = Money::fromAount('10', 'USD');

// Dumps object with rate "20"
var_dump(Money::rate($Source, $Target, 'XRP'));
```

### Comparing operations

All comparing operations are named as bash-like style.

| Method | Description |
|-|-|
| eq | Equals |
| ne | Not equals |
| gt | Greater than |
| ge | Greater or equal |
| lt | Less than |
| le | Less than or equal |

All methods has only 1 arg should be passed – another object of Money to compare with current one.

Example:

```php
$one = Money::fromAmount('1.5', 'USD');
$two = Money::fromAmount('2', 'USD');

// Dumps false
var_dump($one->ge($two));

// Dumps true
var_dump($one->lt($two));
```

### Self checking methods

You can check current value with methods – isPositive, isNegative, isZero.

Example:

```php
$usd = Money::fromAmount('1.5', 'USD');

// Dumps true
var_dump($usd->isPositive());

// Dumps false
var_dump($usd->isNegative());

// Dump false
var_dump($usd->isZero());
```

### Getting information about current object

You can use next method to get current state of object and its value. All methods have no arguments

| Method | Arguments | Return | Description |
|-|-|-|-|
| getAmount | - | string |Return current amount presentation of value with configured fraction as a string |
| getValue | - | string | Return current minor amount (called value) of current object |
| getCurrency | - | string | Return current currency of of this object |

### Use as string

You can use object as string. By default it returns Money::getAmount() method call.

## Dependecies

This library requires you to have bcmath and gmp extension and PHP 8+ version.

This library has no other code dependencies and kept as simple as possible.

## Test coverage

- [x] Can create single
- [x] Can create multiple
- [x] Can create zero
- [x] Cannot create no config
- [x] Add sub operations
- [x] Mul div operations
- [x] Rate calculation
- [x] Convert to another currency
- [x] Cannot convert same currency
- [x] Result is immutable
- [x] Cmp operations
- [x] Cannot add sub different currency
- [x] Cannot compare different currency
- [x] Is positive
- [x] Is negative
- [x] Is zero
- [x] Has currency
- [x] Can use as string
