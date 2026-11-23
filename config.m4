PHP_ARG_ENABLE([blake3], [whether to enable BLAKE3 support],
  [AS_HELP_STRING([--enable-blake3], [Enable BLAKE3 support])], [no])

if test "$PHP_BLAKE3" != "no"; then
  PHP_NEW_EXTENSION([blake3], [src/blake3.c src/php_blake3.c], [$ext_shared])
  PHP_ADD_INCLUDE([$ext_srcdir/src])
fi
