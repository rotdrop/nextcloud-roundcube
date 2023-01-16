#!/bin/bash

if [ -z "${DRY+x}" ]; then
    DRY=echo
fi

APPDIR=$(realpath $(dirname $0)/..)

CORE_LOCK=${APPDIR}/../../3rdparty/composer.lock
LOCK=${APPDIR}/composer.lock
CONFIG=${APPDIR}/composer.json

{ ! [ -f $LOCK ] || ! [ -f $CORE_LOCK ] ; } && exit 0

WD=$(mktemp -d)

function cleanup() {
    rm -rf $WD
}

trap cleanup EXIT

function packageVersions() {
    local LOCK=$1
    local PREFIX=$2
    # echo '{'
    # PREV=''
    # while read LINE; do
    #     if [ -n "$PREV" ]; then
    #         echo $PREV,
    #     fi
    #     PREV=$LINE
    # done < <(jq '.packages[]|{(.name): .version}' < $LOCK|sed -e 's/[{}]//g' -e '/^$/d'|sort)
    # echo $PREV
    # echo '}'
    jq '."packages'"$PREFIX"'"[]|{(.name): .version}' < $LOCK|sed -e 's/[{}]//g' -e '/^$/d'|sort
}

function packageVersion() {
    local PKG=$1
    local LOCK=$2
    grep '"'$PKG'"' $LOCK|awk '{ print $2; }'|sed 's/"//g'
}

CORE_VERSIONS=$WD/core-versions
VERSIONS=$WD/versions

for PREFIX in '' '-dev'; do
    packageVersions $CORE_LOCK $PREFIX >> ${CORE_VERSIONS}
    packageVersions $LOCK $PREFIX > ${VERSIONS}${PREFIX}
done

#diff -u $CORE_VERSIONS $VERSIONS

OUTER_FIRST=true
VERSION_TWEAKS='{'
for PREFIX in '' '-dev'; do
    if ! $OUTER_FIRST; then
        VERSION_TWEAKS="$VERSION_TWEAKS,"
    fi
    OUTER_FIRST=false
    VERSION_TWEAKS="$VERSION_TWEAKS
  \"require${PREFIX}\" : {"
    FIRST=true
    while read PKG VERSION; do
        PKG=$(echo $PKG|sed 's/[":]//g')
        VERSION=$(echo $VERSION|sed 's/"//g')
        CORE_VERSION=$(packageVersion $PKG $CORE_VERSIONS)
        if [ -n "$CORE_VERSION" ] && ! [ "$VERSION" = "$CORE_VERSION" ]; then
            if ! $FIRST; then
                VERSION_TWEAKS="$VERSION_TWEAKS,"
            fi
            FIRST=false
            VERSION_TWEAKS="$VERSION_TWEAKS
    \"$PKG\": \"$CORE_VERSION\""
        fi
    done < ${VERSIONS}${PREFIX}
    VERSION_TWEAKS="$VERSION_TWEAKS
  }"
done
VERSION_TWEAKS="$VERSION_TWEAKS
}"

CONFIG_TWEAK=$WD/core-versions-tweaked.json
TWEAKED_CONFIG=$WD/composer.json
echo "$VERSION_TWEAKS" > $CONFIG_TWEAK

jq -s '.[0] * .[1]' $CONFIG $CONFIG_TWEAK > $TWEAKED_CONFIG
diff -u $CONFIG $TWEAKED_CONFIG
if ! cmp $CONFIG $TWEAKED_CONFIG; then
    $DRY cp $TWEAKED_CONFIG $CONFIG
    exit 1
fi
