--TEST--
BLAKE3 PHP classes
--SKIPIF--
<?php if (!extension_loaded('blake3')) die('skip blake3 extension not loaded'); ?>
--FILE--
<?php
require __DIR__ . '/../classes/Blake3.php';
require __DIR__ . '/../classes/Hasher.php';
echo blake3\Blake3::hashHex('abc'), "\n";
$hasher = (new blake3\Hasher())->update('a')->update('bc');
echo $hasher->digestHex(), "\n";
echo $hasher->reset()->update('')->digestHex(), "\n";
?>
--EXPECT--
6437b3ac38465133ffb63b75273a8db548c558465d79db03fd359c6cd5bd9d85
6437b3ac38465133ffb63b75273a8db548c558465d79db03fd359c6cd5bd9d85
af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262
