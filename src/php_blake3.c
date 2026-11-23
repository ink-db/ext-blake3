#ifdef HAVE_CONFIG_H
#    include "config.h"
#endif

#include "php.h"
#include "../php_blake3.h"
#include "blake3.h"

PHP_FUNCTION(blake3_hash)
{
    zend_string *data;
    zend_long length = 32;

    ZEND_PARSE_PARAMETERS_START(1, 2)
        Z_PARAM_STR(data)
        Z_PARAM_OPTIONAL
        Z_PARAM_LONG(length)
    ZEND_PARSE_PARAMETERS_END();

    if (length < 0) {
        zend_value_error("Output length must be non-negative");
        RETURN_THROWS();
    }

    zend_string *result = zend_string_alloc((size_t) length, 0);
    b3_hash(
        (const uint8_t *) ZSTR_VAL(data),
        ZSTR_LEN(data),
        (uint8_t *) ZSTR_VAL(result),
        (size_t) length
    );
    ZSTR_VAL(result)[length] = '\0';
    RETURN_STR(result);
}

PHP_FUNCTION(blake3_keyed_hash)
{
    zend_string *data;
    zend_string *key;
    zend_long length = 32;

    ZEND_PARSE_PARAMETERS_START(2, 3)
        Z_PARAM_STR(data)
        Z_PARAM_STR(key)
        Z_PARAM_OPTIONAL
        Z_PARAM_LONG(length)
    ZEND_PARSE_PARAMETERS_END();

    if (ZSTR_LEN(key) != 32) {
        zend_value_error("BLAKE3 key must be exactly 32 bytes");
        RETURN_THROWS();
    }
    if (length < 0) {
        zend_value_error("Output length must be non-negative");
        RETURN_THROWS();
    }

    zend_string *result = zend_string_alloc((size_t) length, 0);
    b3_keyed_hash(
        (const uint8_t *) ZSTR_VAL(key),
        (const uint8_t *) ZSTR_VAL(data),
        ZSTR_LEN(data),
        (uint8_t *) ZSTR_VAL(result),
        (size_t) length
    );
    ZSTR_VAL(result)[length] = '\0';
    RETURN_STR(result);
}

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_blake3_hash, 0, 1, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, data, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, length, IS_LONG, 0, "32")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_blake3_keyed_hash, 0, 2, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, data, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, key, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, length, IS_LONG, 0, "32")
ZEND_END_ARG_INFO()

static const zend_function_entry blake3_functions[] = {
    ZEND_FE(blake3_hash, arginfo_blake3_hash)
    ZEND_FE(blake3_keyed_hash, arginfo_blake3_keyed_hash)
    ZEND_FE_END
};

zend_module_entry blake3_module_entry = {
    STANDARD_MODULE_HEADER,
    "blake3",
    blake3_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_BLAKE3_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_BLAKE3
#    ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#    endif
ZEND_GET_MODULE(blake3)
#endif
