<?php

declare(strict_types=1);

namespace blake3;

/** Buffered incremental API. Data is held in memory until digest() is called. */
final class Hasher
{
    private string $data = '';
    private ?string $key;

    public function __construct(?string $key = null)
    {
        if ($key !== null && strlen($key) !== 32) {
            throw new \ValueError('BLAKE3 key must be exactly 32 bytes');
        }
        $this->key = $key;
    }

    public function update(string $data): self
    {
        $this->data .= $data;
        return $this;
    }

    public function digest(int $length = 32): string
    {
        return $this->key === null
            ? Blake3::hash($this->data, $length)
            : Blake3::keyedHash($this->data, $this->key, $length);
    }

    public function digestHex(int $length = 32): string
    {
        return bin2hex($this->digest($length));
    }

    public function reset(): self
    {
        $this->data = '';
        return $this;
    }
}
