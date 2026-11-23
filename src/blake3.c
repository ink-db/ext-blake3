/* Portable BLAKE3 implementation. */
#include "blake3.h"

#include <string.h>

#define BLOCK_LEN 64
#define CHUNK_LEN 1024
#define CHUNK_START 1
#define CHUNK_END 2
#define PARENT 4
#define ROOT 8
#define KEYED_HASH 16

static const uint32_t IV[8] = {
    0x6A09E667, 0xBB67AE85, 0x3C6EF372, 0xA54FF53A,
    0x510E527F, 0x9B05688C, 0x1F83D9AB, 0x5BE0CD19
};

static const uint8_t PERM[16] = {
    2, 6, 3, 10, 7, 0, 4, 13,
    1, 11, 12, 5, 9, 14, 15, 8
};

typedef struct {
    uint32_t cv[8];
    uint32_t block[16];
    uint64_t counter;
    uint32_t block_len;
    uint32_t flags;
} output_t;

static uint32_t rotr(uint32_t value, unsigned count) {
    return (value >> count) | (value << (32 - count));
}

static uint32_t load32(const uint8_t *bytes) {
    return (uint32_t) bytes[0]
        | ((uint32_t) bytes[1] << 8)
        | ((uint32_t) bytes[2] << 16)
        | ((uint32_t) bytes[3] << 24);
}

static void store32(uint8_t *bytes, uint32_t value) {
    bytes[0] = (uint8_t) value;
    bytes[1] = (uint8_t) (value >> 8);
    bytes[2] = (uint8_t) (value >> 16);
    bytes[3] = (uint8_t) (value >> 24);
}

static void g(uint32_t *state, int a, int b, int c, int d, uint32_t x, uint32_t y) {
    state[a] = state[a] + state[b] + x;
    state[d] = rotr(state[d] ^ state[a], 16);
    state[c] += state[d];
    state[b] = rotr(state[b] ^ state[c], 12);
    state[a] = state[a] + state[b] + y;
    state[d] = rotr(state[d] ^ state[a], 8);
    state[c] += state[d];
    state[b] = rotr(state[b] ^ state[c], 7);
}

static void round_fn(uint32_t *state, const uint32_t *message) {
    g(state, 0, 4, 8, 12, message[0], message[1]);
    g(state, 1, 5, 9, 13, message[2], message[3]);
    g(state, 2, 6, 10, 14, message[4], message[5]);
    g(state, 3, 7, 11, 15, message[6], message[7]);
    g(state, 0, 5, 10, 15, message[8], message[9]);
    g(state, 1, 6, 11, 12, message[10], message[11]);
    g(state, 2, 7, 8, 13, message[12], message[13]);
    g(state, 3, 4, 9, 14, message[14], message[15]);
}

static void permute(uint32_t *message) {
    uint32_t permuted[16];
    for (int i = 0; i < 16; i++) {
        permuted[i] = message[PERM[i]];
    }
    memcpy(message, permuted, sizeof(permuted));
}

static void compress(
    const uint32_t cv[8],
    const uint32_t block[16],
    uint64_t counter,
    uint32_t block_len,
    uint32_t flags,
    uint32_t out[16]
) {
    uint32_t state[16];
    uint32_t message[16];

    memcpy(state, cv, 8 * sizeof(uint32_t));
    memcpy(state + 8, IV, 4 * sizeof(uint32_t));
    state[12] = (uint32_t) counter;
    state[13] = (uint32_t) (counter >> 32);
    state[14] = block_len;
    state[15] = flags;
    memcpy(message, block, sizeof(message));

    for (int round = 0; round < 7; round++) {
        round_fn(state, message);
        if (round < 6) {
            permute(message);
        }
    }

    for (int i = 0; i < 8; i++) {
        out[i] = state[i] ^ state[i + 8];
        out[i + 8] = state[i + 8] ^ cv[i];
    }
}

static void words_from_bytes(const uint8_t *bytes, size_t len, uint32_t words[16]) {
    uint8_t block[BLOCK_LEN] = {0};

    if (len > 0) {
        memcpy(block, bytes, len);
    }
    for (int i = 0; i < 16; i++) {
        words[i] = load32(block + 4 * i);
    }
}

static void output_cv(const output_t *output, uint32_t cv[8]) {
    uint32_t words[16];
    compress(output->cv, output->block, output->counter, output->block_len, output->flags, words);
    memcpy(cv, words, 8 * sizeof(uint32_t));
}

static output_t chunk_output(
    const uint32_t key[8],
    const uint8_t *input,
    size_t len,
    uint64_t chunk_counter,
    uint32_t flags
) {
    uint32_t cv[8];
    memcpy(cv, key, sizeof(cv));

    size_t blocks = len ? (len + BLOCK_LEN - 1) / BLOCK_LEN : 1;
    for (size_t block_index = 0; block_index + 1 < blocks; block_index++) {
        uint32_t block[16];
        uint32_t compressed[16];
        words_from_bytes(input + block_index * BLOCK_LEN, BLOCK_LEN, block);
        compress(
            cv,
            block,
            chunk_counter,
            BLOCK_LEN,
            flags | (block_index == 0 ? CHUNK_START : 0),
            compressed
        );
        memcpy(cv, compressed, sizeof(cv));
    }

    size_t offset = (blocks - 1) * BLOCK_LEN;
    size_t last_len = len - offset;
    output_t output;
    memcpy(output.cv, cv, sizeof(cv));
    words_from_bytes(input + offset, last_len, output.block);
    output.counter = chunk_counter;
    output.block_len = (uint32_t) last_len;
    output.flags = flags | CHUNK_END | (blocks == 1 ? CHUNK_START : 0);
    return output;
}

static output_t parent_output(
    const uint32_t key[8],
    const uint32_t left[8],
    const uint32_t right[8],
    uint32_t flags
) {
    output_t output;
    memcpy(output.cv, key, sizeof(output.cv));
    memcpy(output.block, left, 8 * sizeof(uint32_t));
    memcpy(output.block + 8, right, 8 * sizeof(uint32_t));
    output.counter = 0;
    output.block_len = BLOCK_LEN;
    output.flags = flags | PARENT;
    return output;
}

static size_t left_subtree_chunks(size_t chunks) {
    size_t power_of_two = 1;
    while ((power_of_two << 1) < chunks) {
        power_of_two <<= 1;
    }
    return power_of_two;
}

static output_t subtree_output(
    const uint32_t key[8],
    const uint8_t *input,
    size_t len,
    uint64_t first_chunk,
    uint32_t flags
) {
    if (len <= CHUNK_LEN) {
        return chunk_output(key, input, len, first_chunk, flags);
    }

    size_t chunks = (len + CHUNK_LEN - 1) / CHUNK_LEN;
    size_t left_chunks = left_subtree_chunks(chunks);
    size_t left_len = left_chunks * CHUNK_LEN;
    output_t left = subtree_output(key, input, left_len, first_chunk, flags);
    output_t right = subtree_output(
        key,
        input + left_len,
        len - left_len,
        first_chunk + left_chunks,
        flags
    );
    uint32_t left_cv[8];
    uint32_t right_cv[8];
    output_cv(&left, left_cv);
    output_cv(&right, right_cv);
    return parent_output(key, left_cv, right_cv, flags);
}

static void hash_internal(
    const uint32_t key[8],
    uint32_t flags,
    const uint8_t *input,
    size_t input_len,
    uint8_t *output,
    size_t output_len
) {
    output_t root = subtree_output(key, input, input_len, 0, flags);
    size_t produced = 0;
    uint64_t block_counter = 0;

    while (produced < output_len) {
        uint32_t words[16];
        uint8_t block[BLOCK_LEN];
        compress(root.cv, root.block, block_counter++, root.block_len, root.flags | ROOT, words);
        for (int i = 0; i < 16; i++) {
            store32(block + 4 * i, words[i]);
        }

        size_t take = output_len - produced;
        if (take > BLOCK_LEN) {
            take = BLOCK_LEN;
        }
        memcpy(output + produced, block, take);
        produced += take;
    }
}

void b3_hash(const uint8_t *input, size_t input_len, uint8_t *output, size_t output_len) {
    hash_internal(IV, 0, input, input_len, output, output_len);
}

void b3_keyed_hash(
    const uint8_t key_bytes[32],
    const uint8_t *input,
    size_t input_len,
    uint8_t *output,
    size_t output_len
) {
    uint32_t key[8];
    for (int i = 0; i < 8; i++) {
        key[i] = load32(key_bytes + 4 * i);
    }
    hash_internal(key, KEYED_HASH, input, input_len, output, output_len);
}
