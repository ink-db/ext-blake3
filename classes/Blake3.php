<?php

declare(strict_types=1);

namespace blake3;

final class Blake3
{
    public static function hash(string $data, int $length = 32): string
    {
        return \blake3_hash($data, $length);
    }

    public static function hashHex(string $data, int $length = 32): string
    {
        return bin2hex(self::hash($data, $length));
    }

    public static function keyedHash(string $data, string $key, int $length = 32): string
    {
        return \blake3_keyed_hash($data, $key, $length);
    }
}
