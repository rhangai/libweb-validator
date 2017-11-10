Validator
========================================

Validate objects against easily defined rules (See [API Reference](#api))

Installation
-------------------------------------
```sh
composer require renanhangai/validator
```

Getting Started
-------------------------------------
Simply validate some data
```php
use LibWeb\Validator as v;

// Data to be validated
$data = array(
    "name" => "John Doe",
    "age"  => "45",
);
// Validate the data against the rule
$data = v::validate( $data, array(
    "name" => v::s(), //< String validator
    "age"  => v::i(), //< Integer validator
    "address?" => v::s(), //< Optional string validator
));

$data->name === "John Doe";
$data->age === 45;
$data->address === null;
```

Another Example
-------------------------------------
Simply validate some data
```php
use LibWeb\Validator as v;

$data = array(
    "name" => "John Doe",
    "age"  => "45",
    "password" => "123456",
    "children" => array(
        array( "name" => "John Doe Jr", "age" => 15 ),
        array( "name" => "Mary Doe", "age" => 17 ),
    ),
);

$data = v::validate( $data, array(
    "name" => v::s(), //< String validator
    "age"  => v::i(), //< Integer validator
    "password" => v::s()->minlen( 6 ),
    "children" => v::arrayOf(array(
        "name" => v::s(),
        "age"  => v::i(),
    )),
));
```


Chainability
-------------------------------------
Every method can be chained by using `->`
```php
v::s()->str_replace( "foo", "bar" )->regex('/testbar$/')->minlen(10)
// Will pass on "mytestfoo", "another_testfoo", "testbar", "mytestbar"
```

<a name="api"></a>
API Reference
======================

Summary
----------------
- Type validators
  - [`any()`](#api-any)
  - [`arrayOf($rule)`](#api-array-of)
  - [`boolval()`](#api-boolval)
  - [`b()`](#api-boolval)
  - [`date($format = null, $out = null)`](#api-date)
  - [`decimal($digits, $decimal, $decimalSeparator = null, $thousandsSeparator = null)`](#api-decimal)
  - [`floatval($decimal = null, $thousands = null, $asString = false)`](#api-floatval)
  - [`f($decimal = null, $thousands = null, $asString = false)`](#api-floatval)
  - [`instanceOf($type)`](#api-instance-of)
  - [`intval()`](#api-intval)
  - [`i()`](#api-intval)
  - [`obj($definition)`](#api-obj)
  - [`strval($trim = true)`](#api-strval)
  - [`s($trim = true)`](#api-strval)
- String rules
  - [`blacklist($chars)`](#api-blacklist)
  - [`len($min, $max = null)`](#api-len)
  - [`minlen($min)`](#api-minlen)
  - [`preg_replace($search, $replace)`](#api-preg-replace)
  - [`regex($pattern)`](#api-regex)
  - [`str_replace($search, $replace)`](#api-str-replace)
  - [`whitelist($chars)`](#api-whitelist)
- Mixed
  - [`call($fn)`](#api-call)
- Locale rules
  - [`cnpj()`](#api-cnpj)
  - [`cpf()`](#api-cpf)
- Meta
  - [`dependsOn()`](#api-depends-on)
- Obligatoriness
  - [`optional()`](#api-optional)
  - [`required()`](#api-required)
  - [`skippable()`](#api-skippable)
- Numeric rules
  - [`range($min, $max)`](#api-range)

Methods
----------------

- <a name="api-any"></a> `any()`

  Validate all objects

- <a name="api-array-of"></a> `arrayOf($rule)`

  Validate every element on the array against the $rule

- <a name="api-blacklist"></a> `blacklist($chars)`

  Remove any char found in $chars from the string

- <a name="api-boolval"></a> `boolval()`

  Convert the value to a boolean

- <a name="api-call"></a> `call($fn)`

  Call the function $fn to validate the value

- <a name="api-cnpj"></a> `cnpj()`

  Brazilian CNPJ validator

- <a name="api-cpf"></a> `cpf()`

  Brazilian CPF validator

- <a name="api-date"></a> `date($format = null, $out = null)`

  Convert the value to a date using the format (or keep if alredy a \DateTime)

- <a name="api-decimal"></a> `decimal($digits, $decimal, $decimalSeparator = null, $thousandsSeparator = null)`

  Convert the value to a decimal with $digits and $decimal (needs rtlopes\Decimal)

- <a name="api-depends-on"></a> `dependsOn()`

  Add a rule dependency. (Only works for objects)

- <a name="api-floatval"></a> `floatval($decimal = null, $thousands = null, $asString = false)`

  Convert the value to a float

- <a name="api-instance-of"></a> `instanceOf($type)`

  Check if the object is an instance of the given type

- <a name="api-intval"></a> `intval()`

  Convert the value to an int and fails if cannot be safely convertible

- <a name="api-len"></a> `len($min, $max = null)`

  Check for string or array length

- <a name="api-minlen"></a> `minlen($min)`

  Check if string has at least $min length

- <a name="api-obj"></a> `obj($definition)`

  Convert the value to an object and validate its fields

- <a name="api-optional"></a> `optional()`

  Optional validator (Bypass if null or '')

- <a name="api-preg-replace"></a> `preg_replace($search, $replace)`

  Replace the $search pattern on the string using $replace (Callback or string)

- <a name="api-range"></a> `range($min, $max)`

  Range validator

- <a name="api-regex"></a> `regex($pattern)`

  Validate the value against the $pattern

- <a name="api-required"></a> `required()`

  Required validator (Fails if null or '')

- <a name="api-skippable"></a> `skippable()`

  Skippable validator (Bypass if null or '' and does not set the property)

- <a name="api-str-replace"></a> `str_replace($search, $replace)`

  Replace the characters on the string

- <a name="api-strval"></a> `strval($trim = true)`

  Convert the object to a string value

- <a name="api-whitelist"></a> `whitelist($chars)`

  Remove any char NOT found in $chars from the string

