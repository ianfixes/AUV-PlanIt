#!/bin/bash
# build.sh: build JAR and XPI files from source
# based on Nathan Yergler's build script
# Modified to work on OS X by Mike Chambers (http://mesh.typepad.com)

#### editable items (none of these can be blank)
APP_NAME=mybic    # short-name, jar and xpi files name.
HAS_DEFAULTS=0       # whether the ext. provides default values for user prefs etc.
HAS_COMPONENTS=0     # whether the ext. includes any components
HAS_LOCALE=0         # package APP_NAME.jar/locale/ ?
HAS_SKIN=0           # package APP_NAME.jar/skin/ ?
KEEP_JAR=1           # leave the jar when done?
ROOT_FILES="install.rdf chrome.manifest" # put these files in root of xpi
ROOTDIR="/Volumes/Crypto/Code/projects/debugger/mybic_extension"
#### editable items end

TMP_DIR=tmp_build

rm -rf $TMP_DIR

#uncomment to debug
#set -x

# remove any left-over files
rm $APP_NAME.jar
rm $APP_NAME.xpi
rm -rf $TMP_DIR

# create xpi directory layout and populate it
mkdir $TMP_DIR
mkdir $TMP_DIR/chrome

if [ $HAS_COMPONENTS = 1 ]; then
  mkdir $TMP_DIR/components
  cp components/* $TMP_DIR/components
fi

if [ $HAS_DEFAULTS = 1 ]; then
  DEFAULT_FILES="`find ./defaults -path '*DS_Store*' -prune -o -type f -print | grep -v \~`"
  cp --parents $DEFAULT_FILES $TMP_DIR
fi

# Copy other files to the root of future XPI.
cp $ROOT_FILES $TMP_DIR

cp -rf defaults $TMP_DIR

# generate the JAR file, excluding .DS_Store and temporary files
#zip -0 -r $TMP_DIR/chrome/$APP_NAME.jar `find content -path '*DS_Store*' -prune -o -type f -print | grep -v \~`

cd chrome
#zip -r $TMP_DIR/chrome/$APP_NAME.jar content
find . -name "*" -print | zip ../$TMP_DIR/chrome/$APP_NAME.jar -@
cd ..
mv $APP_NAME.jar $TMP_DIR/chrome/$APP_NAME.jar


if [ $HAS_LOCALE = 1 ]; then
  zip -0 -r $TMP_DIR/chrome/$APP_NAME.jar `find locale -path '*DS_Store*' -prune -o -type f -print | grep -v \~`
fi
if [ $HAS_SKIN = 1 ]; then
  zip -0 -r $TMP_DIR/chrome/$APP_NAME.jar `find skin -path '*DS_Store*' -prune -o -type f -print | grep -v \~`
fi
echo 'temp is' $TMP_DIR
# generate the XPI file
cd $TMP_DIR
#zip -r ../$APP_NAME.xpi *
zip -r $APP_NAME.xpi *
mv $APP_NAME.xpi ../$APP_NAME.xpi
cd ..

if [ $KEEP_JAR = 1 ]; then
  # save the jar file
  #mv $TMP_DIR/chrome/$APP_NAME.jar .
  echo $APP_NAME;
fi


killall firefox-bin

# copy new jar file into our extension directory
echo $ROOTDIR/$TMP_DIR
cp $ROOTDIR/tmp_build/chrome/mybic.jar "/Users/jimplush/Library/Application Support/Firefox/Profiles/i09g2ga9.TEMP/extensions/jiminoc@gmail.com/chrome"
# remove the working files
rm -rf $TMP_DIR
#copy new jar over


#  start new firefox 
/Applications/Firefox.app/Contents/MacOS/firefox -P "TEMP" http://localhost &

