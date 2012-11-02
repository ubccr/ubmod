#!/bin/sh
ASSET_DIR=$(dirname $0)

EXT_VERSION="4.1.1a"
EXT_ARCHIVE="ext-$EXT_VERSION-gpl.zip"
EXT_DIR="ext-$EXT_VERSION"

ZF_VERSION="1.12.0-minimal"
ZF_ARCHIVE="ZendFramework-$ZF_VERSION.tar.gz"
ZF_DIR="ZendFramework-$ZF_VERSION"

PCHART_VERSION="2.1.3"
PCHART_ARCHIVE="pChart$PCHART_VERSION.tar"
PCHART_DIR="pChart$PCHART_VERSION"

OLE_VERSION="1.0.0RC2"
OLE_ARCHIVE="OLE-$OLE_VERSION.tgz"
OLE_DIR="OLE-$OLE_VERSION"

EXCEL_WRITER_VERSION="0.9.3"
EXCEL_WRITER_ARCHIVE="Spreadsheet_Excel_Writer-$EXCEL_WRITER_VERSION.tgz"
EXCEL_WRITER_DIR="Spreadsheet_Excel_Writer-$EXCEL_WRITER_VERSION"

cd $ASSET_DIR

unzip $EXT_ARCHIVE
cp -rf $EXT_DIR/ext-all.js ../html/js
rm -rf ../html/resources
cp -rf $EXT_DIR/resources ../html
rm -rf $EXT_DIR
find ../html/resources -type f | xargs chmod -x

tar zxvf $ZF_ARCHIVE
rm -rf ../lib/Zend
cp -rf $ZF_DIR/library/Zend ../lib
rm -rf $ZF_DIR
find ../lib/Zend -type f | xargs chmod -x

tar xvf $PCHART_ARCHIVE
rm -rf ../lib/pChart
mkdir ../lib/pChart
cp -rf $PCHART_DIR/class ../lib/pChart
cp -rf $PCHART_DIR/fonts ../lib/pChart
rm -rf $PCHART_DIR
find ../lib/pChart -type f | xargs chmod -x

tar xvf $OLE_ARCHIVE
rm -rf ../lib/OLE*
cp -rf $OLE_DIR/OLE* ../lib
rm -rf $OLE_DIR package.xml
chmod -x ../lib/OLE.php
find ../lib/OLE -type f | xargs chmod -x

tar xvf $EXCEL_WRITER_ARCHIVE
rm -rf ../lib/Spreadsheet
cp -rf $EXCEL_WRITER_DIR/Spreadsheet ../lib
rm -rf $EXCEL_WRITER_DIR package.xml

exit 0
