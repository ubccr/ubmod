#!/bin/sh
ASSET_DIR=$(dirname $0)
EXT_VERSION="4.0.2"
EXT_ARCHIVE="ext-$EXT_VERSION-gpl.zip"
EXT_DIR="ext-$EXT_VERSION"

cd $ASSET_DIR

unzip $EXT_ARCHIVE
cp -r $EXT_DIR/ext-all.js       ../html/js
cp -r $EXT_DIR/ext-all-debug.js ../html/js
cp -r $EXT_DIR/resources        ../html

exit 0
