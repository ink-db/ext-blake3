--TEST--
BLAKE3 empty and abc known-answer tests
--SKIPIF--
<?php if (!extension_loaded('blake3')) die('skip blake3 extension not loaded'); ?>
--FILE--
<?php
echo bin2hex(blake3_hash('')), "\n";
echo bin2hex(blake3_hash('abc')), "\n";
echo strlen(blake3_hash('abc', 64)), "\n";
echo substr(blake3_hash('abc', 64), 0, 32) === blake3_hash('abc') ? "prefix ok\n" : "prefix failed\n";
?>
--EXPECT--
af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262
6437b3ac38465133ffb63b75273a8db548c558465d79db03fd359c6cd5bd9d85
64
prefix ok
