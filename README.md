Validator
========================================

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

API Reference
========================================

### Summary

- [`strval($trim = true)`](#strval) or [`s($trim = false)`](#strval)
- [`intval()`](#intval) or [`i()`](#intval)

### Reference

- <a name="strval"></a> `v::strval( $trim = true )`

  Validate and convert to string
  
- <a name="intval"></a> `v::intval()`

  Validate and convert to an integer
