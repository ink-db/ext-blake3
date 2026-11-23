<?php

declare(strict_types=1);

require dirname(__DIR__) . '/polyfill.php';
require dirname(__DIR__) . '/classes/Blake3.php';
require dirname(__DIR__) . '/classes/Hasher.php';

$cases = [
    [blake3_hash(''), 'af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262'],
    [blake3_hash('abc'), '6437b3ac38465133ffb63b75273a8db548c558465d79db03fd359c6cd5bd9d85'],
];
$input = '';
for ($i = 0; $i < 1025; ++$i) {
    $input .= chr($i % 251);
}
$cases[] = [blake3_hash($input), 'd00278ae47eb27b34faecf67b4fe263f82d5412916c1ffd97c8cb7fb814b8444'];
$cases[] = [
    blake3_keyed_hash($input, 'whats the Elvish word for friend'),
    '357dc55de0c7e382c900fd6e320acc04146be01db6a8ce7210b7189bd664ea69',
];
$input = '';
for ($i = 0; $i < 2049; ++$i) {
    $input .= chr($i % 251);
}
$cases[] = [blake3_hash($input), '5f4d72f40d7a5f82b15ca2b2e44b1de3c2ef86c426c95c1af0b6879522563030'];
$cases[] = [
    blake3_keyed_hash($input, 'whats the Elvish word for friend'),
    '9f29700902f7c86e514ddc4df1e3049f258b2472b6dd5267f61bf13983b78dd5',
];

foreach ($cases as [$actual, $expected]) {
    if (bin2hex($actual) !== $expected) {
        throw new RuntimeException('BLAKE3 polyfill known-answer test failed.');
    }
}

if (\blake3\Blake3::hashHex('abc') !== $cases[1][1]) {
    throw new RuntimeException('Lowercase blake3 namespace API test failed.');
}

echo "BLAKE3 polyfill vectors passed.\n";
