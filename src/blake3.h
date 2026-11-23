#ifndef BLAKE3_PORTABLE_H
#define BLAKE3_PORTABLE_H
#include <stddef.h>
#include <stdint.h>

void b3_hash(const uint8_t *input, size_t input_len, uint8_t *output, size_t output_len);
void b3_keyed_hash(const uint8_t key[32], const uint8_t *input, size_t input_len, uint8_t *output, size_t output_len);

#endif
