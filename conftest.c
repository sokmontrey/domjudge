/* confdefs.h */
#define PACKAGE_NAME "DOMjudge"
#define PACKAGE_TARNAME "domjudge"
#define PACKAGE_VERSION "9.0.0DEV/3a04f9030"
#define PACKAGE_STRING "DOMjudge 9.0.0DEV/3a04f9030"
#define PACKAGE_BUGREPORT "domjudge-devel@domjudge.org"
#define PACKAGE_URL ""
#define DOMJUDGE_VERSION "9.0.0DEV/3a04f9030"
#define _POSIX_C_SOURCE 200809L
#define _XOPEN_SOURCE 500
#define HAVE_STDIO_H 1
#define HAVE_STDLIB_H 1
#define HAVE_STRING_H 1
#define HAVE_INTTYPES_H 1
#define HAVE_STDINT_H 1
#define HAVE_STRINGS_H 1
#define HAVE_SYS_STAT_H 1
#define HAVE_SYS_TYPES_H 1
#define HAVE_UNISTD_H 1
#define STDC_HEADERS 1
#define HAVE__BOOL 1
#define HAVE_STDBOOL_H 1
#define HAVE_FCNTL_H 1
#define HAVE_STDLIB_H 1
#define HAVE_STRING_H 1
#define HAVE_SYS_PARAM_H 1
#define HAVE_SYS_TIME_H 1
#define HAVE_SYSLOG_H 1
#define HAVE_TERMIOS_H 1
#define HAVE_UNISTD_H 1
#define HAVE_MAGIC_H 1
#define HAVE_LIBCGROUP_H 1
#define HAVE_FORK 1
#define HAVE_VFORK 1
#define HAVE_WORKING_VFORK 1
#define HAVE_WORKING_FORK 1
#define HAVE_MALLOC 1
#define HAVE_REALLOC 1
#define HAVE_ATEXIT 1
#define HAVE_DUP2 1
#define HAVE_GETCWD 1
#define HAVE_GETTIMEOFDAY 1
#define HAVE_MEMSET 1
#define HAVE_MKDIR 1
#define HAVE_REALPATH 1
#define HAVE_SETENV 1
/* end confdefs.h.  */
/* Define socket to an innocuous variant, in case <limits.h> declares socket.
   For example, HP-UX 11i <limits.h> declares gettimeofday.  */
#define socket innocuous_socket

/* System header to define __stub macros and hopefully few prototypes,
   which can conflict with char socket (); below.  */

#include <limits.h>
#undef socket

/* Override any GCC internal prototype to avoid an error.
   Use char because int might match the return type of a GCC
   builtin and then its argument prototype would still apply.  */
#ifdef __cplusplus
extern "C"
#endif
char socket ();
/* The GNU C library defines this for functions which it implements
    to always fail with ENOSYS.  Some functions are actually named
    something starting with __ and the normal name is an alias.  */
#if defined __stub_socket || defined __stub___socket
choke me
#endif

int
main (void)
{
return socket ();
  ;
  return 0;
}
