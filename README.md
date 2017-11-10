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
) );

$data->name === "John Doe";
$data->age === 45;
$data->address === null;
```
