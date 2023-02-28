#!/bin/bash

# Exchange the OCA\RotDrop\Toolkit namespace prefix by a custom
# OCA\CUSTOMPREFIX\Toolkit prefix. This is in order decouple different
# apps using the toolkit from each other, a little bit in the spirit
# of PHP scoper, but much simpler as we have full control over our own
# sources ;)

SRC_DIR=$(realpath $(dirname $0)/..)
DST_DIR=$([ -n "$1" ] && realpath "$1")
NS_PREFIX=$2

if [ -z "$DST_DIR" ] || [ -z "$NS_PREFIX" ]; then
    cat <<EOF
Usage: $(basename $0) DST_DIR NAMESPACE_PREFIX
EOF
    exit 1
fi

rsync -a --delete $SRC_DIR/[A-Z]* $DST_DIR/.
find $DST_DIR -name '*.php' -exec sed -i 's/OCA\\RotDrop\\Toolkit/OCA\\'$NS_PREFIX'\\Toolkit/g'  {} \;
find $DST_DIR -exec touch {} \;
