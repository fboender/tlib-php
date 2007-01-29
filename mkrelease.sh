#!/bin/sh

if [ -z $1 ]; then
    echo "Usage: mkrelease.sh <version>"
    exit;
fi

# Pre-clean
rm tlib-php-$1.tar.gz
rm tlib-php-$1-win.zip

# Documentation
doxygen

# Unix (src) release
mkdir tlib-php-$1
cp * tlib-php-$1
cp html tlib-php-$1 -r
cp examples tlib-php-$1 -r
rm tlib-php-$1/mkrelease.sh
rm tlib-php-$1/Doxyfile
tar -vczf tlib-php-$1.tar.gz tlib-php-$1

# Cleanup.
rm tlib-php-$1 -r
