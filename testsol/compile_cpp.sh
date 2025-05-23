#!/bin/bash

# C++ compile wrapper-script for 'test_solution.sh'.
# See that script for syntax and more info.

SOURCE="$1"
DEST="$2"

# -Wall:	Report all warnings
# -O2:		Level 2 optimizations (default for speed)
# -lm:		Link with math-library
# -static:	Static link with all libraries
g++ -Wall -O2 -lm -static -o $DEST $SOURCE
exit $?
