--TEST--
BLAKE3 official vectors across a chunk boundary and keyed mode
--SKIPIF--
<?php if (!extension_loaded('blake3')) die('skip blake3 extension not loaded'); ?>
--FILE--
<?php
$input = '';
for ($i = 0; $i < 1025; ++$i) {
    $input .= chr($i % 251);
}
$key = 'whats the Elvish word for friend';
echo bin2hex(blake3_hash($input)), "\n";
echo bin2hex(blake3_keyed_hash($input, $key)), "\n";
$input = '';
for ($i = 0; $i < 2049; ++$i) {
    $input .= chr($i % 251);
}
echo bin2hex(blake3_hash($input)), "\n";
echo bin2hex(blake3_keyed_hash($input, $key)), "\n";
?>
--EXPECT--
d00278ae47eb27b34faecf67b4fe263f82d5412916c1ffd97c8cb7fb814b8444
357dc55de0c7e382c900fd6e320acc04146be01db6a8ce7210b7189bd664ea69
5f4d72f40d7a5f82b15ca2b2e44b1de3c2ef86c426c95c1af0b6879522563030
9f29700902f7c86e514ddc4df1e3049f258b2472b6dd5267f61bf13983b78dd5
