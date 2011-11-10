#!/bin/sh
ASSET_DIR=$(dirname $0)

EXT_VERSION="4.0.2a"
EXT_ARCHIVE="ext-$EXT_VERSION-gpl.zip"
EXT_DIR="ext-$EXT_VERSION"

ZF_VERSION="1.11.6"
ZF_ARCHIVE="ZendFramework-$ZF_VERSION.tar.gz"
ZF_DIR="ZendFramework-$ZF_VERSION"

PCHART_VERSION="2.1.3"
PCHART_ARCHIVE="pChart$PCHART_VERSION.tar"
PCHART_DIR="pChart$PCHART_VERSION"

cd $ASSET_DIR

unzip $EXT_ARCHIVE
cp -rf $EXT_DIR/ext-all-debug.js ../html/js
rm -rf ../html/resources
cp -rf $EXT_DIR/resources ../html
rm -rf $EXT_DIR

tar zxvf $ZF_ARCHIVE
rm -rf ../lib/Zend
cp -rf $ZF_DIR/library/Zend ../lib
rm -rf $ZF_DIR

tar xvf $PCHART_ARCHIVE
rm -rf ../lib/pChart
cp -rf $PCHART_DIR ../lib/pChart
rm -rf $PCHART_DIR

exit 0
