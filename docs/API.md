# API

`blake3_hash(string $data, int $length = 32): string` returns raw digest bytes. Set `$length` to request XOF output.

`blake3_keyed_hash(string $data, string $key, int $length = 32): string` computes keyed mode. `$key` must be exactly 32 bytes.

`blake3\Blake3::hash()` and `hashHex()` provide static helpers. `blake3\Hasher` supports `update()`, `digest()`, `digestHex()`, and `reset()`. The class currently buffers input in PHP memory until a digest is requested.

Invalid lengths and keys throw `ValueError`. The Composer polyfill runs a portable pure PHP implementation when the extension is not loaded. It supports hash, keyed mode, and XOF calls, with lower performance than the C extension.
