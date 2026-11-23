--TEST--
BLAKE3 validates output length and keyed input
--SKIPIF--
<?php if (!extension_loaded('blake3')) die('skip blake3 extension not loaded'); ?>
--FILE--
<?php
try { blake3_hash('x', -1); } catch (ValueError $e) { echo "length rejected\n"; }
try { blake3_keyed_hash('x', 'short'); } catch (ValueError $e) { echo "key rejected\n"; }
echo strlen(blake3_keyed_hash('data', str_repeat("k", 32))), "\n";
?>
--EXPECT--
length rejected
key rejected
32
