#!/bin/bash

# Exchange the OCA\RotDrop\Toolkit namespace prefix by a custom
# OCA\CUSTOMPREFIX\Toolkit prefix. This is in order decouple different
# apps using the toolkit from each other, a little bit in the spirit
# of PHP scoper, but much simpler as we have full control over our own
# sources ;)

SRC_DIR=$(realpath "$(dirname "$0")"/..)
DST_DIR=$([ -n "$1" ] && realpath "$1")
NS_PREFIX=$2

WRAPPER_NS=$(echo "$3"|sed -E -e 's/([^\\])\\([^\\])/\1\\\\\2/g')

WRAPPED_NAMESPACES=(
    Doctrine
    Gedmo
)
WRAPPER_REPLACEMENTS=()
if [ -n "$WRAPPER_NS" ]; then
    for NS in "${WRAPPED_NAMESPACES[@]}"; do
        WRAPPER_REPLACEMENTS+=('s/use '"$NS"'/use OCA\\'"$NS_PREFIX"'\\'"$WRAPPER_NS"'\\'"$NS"'/g')
    done
fi

if [ -z "$DST_DIR" ] || [ -z "$NS_PREFIX" ]; then
    cat <<EOF
Usage: $(basename "$0") DST_DIR NAMESPACE_PREFIX [WRAPPER_NAMESPACE]
EOF
    exit 1
fi

mkdir -p "$DST_DIR"/.
rsync -a --delete "$SRC_DIR"/[A-Z]* "$DST_DIR"/.
find "$DST_DIR" -name '*.php' -exec sed -Ei\
 -e 's/([\]?)OCA\\RotDrop\\Toolkit/\1OCA\\'"$NS_PREFIX"'\\Toolkit/g'\
 "${WRAPPER_REPLACEMENTS[@]/#/-e}"\
 {} \;
find "$DST_DIR" -exec touch {} \;
