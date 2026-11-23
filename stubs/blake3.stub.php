<?php

declare(strict_types=1);

/** Compute a binary BLAKE3 digest, with optional extendable output length. */
namespace {
    function blake3_hash(string $data, int $length = 32): string {}

    /** Compute a keyed BLAKE3 digest. The key must be exactly 32 bytes. */
    function blake3_keyed_hash(string $data, string $key, int $length = 32): string {}
}

namespace blake3 {
    final class Blake3
    {
        public static function hash(string $data, int $length = 32): string {}

        public static function hashHex(string $data, int $length = 32): string {}

        public static function keyedHash(string $data, string $key, int $length = 32): string {}
    }

    final class Hasher
    {
        public function __construct(?string $key = null) {}

        public function update(string $data): self {}

        public function digest(int $length = 32): string {}

        public function digestHex(int $length = 32): string {}

        public function reset(): self {}
    }
}
