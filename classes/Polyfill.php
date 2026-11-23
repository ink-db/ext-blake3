<?php

declare(strict_types=1);

namespace blake3;

/** Portable, pure PHP implementation used when the native extension is absent. */
final class Polyfill
{
    private const IV = [
        0x6A09E667, 0xBB67AE85, 0x3C6EF372, 0xA54FF53A,
        0x510E527F, 0x9B05688C, 0x1F83D9AB, 0x5BE0CD19,
    ];
    private const PERMUTATION = [2, 6, 3, 10, 7, 0, 4, 13, 1, 11, 12, 5, 9, 14, 15, 8];
    private const CHUNK_START = 1;
    private const CHUNK_END = 2;
    private const PARENT = 4;
    private const ROOT = 8;
    private const KEYED_HASH = 16;

    public static function hash(string $input, int $length = 32, ?string $key = null): string
    {
        if ($length < 0) {
            throw new \ValueError('Output length must be non-negative');
        }
        if ($key !== null && strlen($key) !== 32) {
            throw new \ValueError('BLAKE3 key must be exactly 32 bytes');
        }

        $keyWords = $key === null ? self::IV : array_slice(self::words($key), 0, 8);
        $flags = $key === null ? 0 : self::KEYED_HASH;
        $root = self::subtreeOutput($keyWords, $input, 0, $flags);
        $result = '';
        $produced = 0;
        for ($counter = 0; $produced < $length; ++$counter) {
            $words = self::compress($root['cv'], $root['block'], $counter, $root['blockLen'], $root['flags'] | self::ROOT);
            $block = pack('V*', ...$words);
            $take = min(64, $length - $produced);
            $result .= substr($block, 0, $take);
            $produced += $take;
        }
        return $result;
    }

    private static function u32(int $value): int
    {
        return $value & 0xFFFFFFFF;
    }

    private static function rotateRight(int $value, int $count): int
    {
        return self::u32(($value >> $count) | ($value << (32 - $count)));
    }

    private static function words(string $bytes): array
    {
        $bytes = str_pad(substr($bytes, 0, 64), 64, "\0");
        return array_values(unpack('V16', $bytes));
    }

    private static function g(array &$state, int $a, int $b, int $c, int $d, int $x, int $y): void
    {
        $state[$a] = self::u32($state[$a] + $state[$b] + $x);
        $state[$d] = self::rotateRight($state[$d] ^ $state[$a], 16);
        $state[$c] = self::u32($state[$c] + $state[$d]);
        $state[$b] = self::rotateRight($state[$b] ^ $state[$c], 12);
        $state[$a] = self::u32($state[$a] + $state[$b] + $y);
        $state[$d] = self::rotateRight($state[$d] ^ $state[$a], 8);
        $state[$c] = self::u32($state[$c] + $state[$d]);
        $state[$b] = self::rotateRight($state[$b] ^ $state[$c], 7);
    }

    private static function compress(array $cv, array $block, int $counter, int $blockLen, int $flags): array
    {
        $state = array_merge($cv, array_slice(self::IV, 0, 4), [$counter & 0xFFFFFFFF, ($counter >> 32) & 0xFFFFFFFF, $blockLen, $flags]);
        for ($round = 0; $round < 7; ++$round) {
            self::g($state, 0, 4, 8, 12, $block[0], $block[1]);
            self::g($state, 1, 5, 9, 13, $block[2], $block[3]);
            self::g($state, 2, 6, 10, 14, $block[4], $block[5]);
            self::g($state, 3, 7, 11, 15, $block[6], $block[7]);
            self::g($state, 0, 5, 10, 15, $block[8], $block[9]);
            self::g($state, 1, 6, 11, 12, $block[10], $block[11]);
            self::g($state, 2, 7, 8, 13, $block[12], $block[13]);
            self::g($state, 3, 4, 9, 14, $block[14], $block[15]);
            if ($round < 6) {
                $permuted = [];
                foreach (self::PERMUTATION as $index) {
                    $permuted[] = $block[$index];
                }
                $block = $permuted;
            }
        }

        $output = [];
        for ($i = 0; $i < 8; ++$i) {
            $output[] = self::u32($state[$i] ^ $state[$i + 8]);
        }
        for ($i = 0; $i < 8; ++$i) {
            $output[] = self::u32($state[$i + 8] ^ $cv[$i]);
        }
        return $output;
    }

    private static function chunkOutput(array $key, string $input, int $chunkCounter, int $flags): array
    {
        $cv = $key;
        $length = strlen($input);
        $blockCount = max(1, intdiv($length + 63, 64));
        for ($blockIndex = 0; $blockIndex < $blockCount - 1; ++$blockIndex) {
            $block = self::words(substr($input, $blockIndex * 64, 64));
            $compressed = self::compress($cv, $block, $chunkCounter, 64, $flags | ($blockIndex === 0 ? self::CHUNK_START : 0));
            $cv = array_slice($compressed, 0, 8);
        }

        $offset = ($blockCount - 1) * 64;
        $last = substr($input, $offset);
        return [
            'cv' => $cv,
            'block' => self::words($last),
            'counter' => $chunkCounter,
            'blockLen' => strlen($last),
            'flags' => $flags | self::CHUNK_END | ($blockCount === 1 ? self::CHUNK_START : 0),
        ];
    }

    private static function subtreeOutput(array $key, string $input, int $firstChunk, int $flags): array
    {
        $length = strlen($input);
        if ($length <= 1024) {
            return self::chunkOutput($key, $input, $firstChunk, $flags);
        }

        $chunks = intdiv($length + 1023, 1024);
        $leftChunks = 1;
        while (($leftChunks << 1) < $chunks) {
            $leftChunks <<= 1;
        }
        $leftLength = $leftChunks * 1024;
        $left = self::subtreeOutput($key, substr($input, 0, $leftLength), $firstChunk, $flags);
        $right = self::subtreeOutput($key, substr($input, $leftLength), $firstChunk + $leftChunks, $flags);
        $leftCv = array_slice(self::compress($left['cv'], $left['block'], $left['counter'], $left['blockLen'], $left['flags']), 0, 8);
        $rightCv = array_slice(self::compress($right['cv'], $right['block'], $right['counter'], $right['blockLen'], $right['flags']), 0, 8);

        return [
            'cv' => $key,
            'block' => array_merge($leftCv, $rightCv),
            'counter' => 0,
            'blockLen' => 64,
            'flags' => $flags | self::PARENT,
        ];
    }
}
