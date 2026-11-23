# ext-blake3

PHP extension in C that implements BLAKE3 algorithm with a portable version.

## Requirements

- PHP 8.1 or higher (CLI).

## Compiler

```sh
phpize
./configure --enable-blake3
make
make test
make install
```

Add `extension=blake3` into `php.ini` and test it with `php --ri blake3`.

## Usage

```php
<?php
echo bin2hex(blake3_hash('abc'));
$xof = blake3_hash('abc', 64); // bytes
$mac = blake3_keyed_hash('message', random_bytes(32));

require 'vendor/autoload.php';
echo Blake3\Blake3::hashHex('abc');
$hasher = (new Blake3\Hasher())->update('part 1')->update('part 2');
echo $hasher->digestHex();
```

### See more examples [here](./examples/hash.php)

## Polyfill

Composer loads `polyfill.php` only when the native function is not found.

# [LICENSE](./LICENSE)