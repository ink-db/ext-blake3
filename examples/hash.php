<?php

require dirname(__DIR__) . '/vendor/autoload.php';

echo blake3\Blake3::hashHex('BLAKE3 from PHP'), PHP_EOL;

$stream = (new blake3\Hasher())
    ->update('first part')
    ->update(' and second part');

echo $stream->digestHex(), PHP_EOL;
