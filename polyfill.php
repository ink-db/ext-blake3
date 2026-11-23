<?php

declare(strict_types=1);

require_once __DIR__ . '/classes/Polyfill.php';

if (!function_exists('blake3_hash')) {
    /** Return a binary BLAKE3 digest (or XOF output) for the given data. */
    function blake3_hash(string $data, int $length = 32): string
    {
        if ($length < 0) {
            throw new ValueError('Output length must be non-negative');
        }
        return \blake3\Polyfill::hash($data, $length);
    }
}

if (!function_exists('blake3_keyed_hash')) {
    /** Return a keyed BLAKE3 digest; keys must contain exactly 32 bytes. */
    function blake3_keyed_hash(string $data, string $key, int $length = 32): string
    {
        if (strlen($key) !== 32) {
            throw new ValueError('BLAKE3 key must be exactly 32 bytes');
        }
        if ($length < 0) {
            throw new ValueError('Output length must be non-negative');
        }
        return \blake3\Polyfill::hash($data, $length, $key);
    }
}
