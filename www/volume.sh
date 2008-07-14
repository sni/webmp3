#!/bin/bash
#
# this script tries to set the volume
# 
# requirements:
#  on osx:   osascript
#  on linux: aumix, awk
#

hasAumix=0
hasOsascript=0

if [ -n "$(which aumix)" ]; then
  hasAumix=1
fi
if [ -n "$(which osascript)" ]; then
  hasOsascript=1
fi

if [ $hasOsascript -eq 0 -a $hasAumix -eq 0 ]; then
  echo "please check volume.sh"
  echo ""
  echo "on linux, you have to install aumix and give group audio permissions to the webserver user"
  echo ""
  echo "on osx, you have to install osascript"
  echo ""
  echo "or change the volumeBin setting in config.php to a script of your own"
  exit 1
fi

# without arguments, return the current volume
if [ $# == 0 ]; then
  if [ $hasAumix -eq 1 ]; then
    vol=`aumix -vq | awk '{ print $2 }' | tr -d ','`
    echo $vol
    exit
  fi

  if [ $hasOsascript -eq 1 ]; then
    osxvol=`osascript -e "get output volume of (get volume settings)"`
    echo $osxvol
    exit
  fi
else
  # with argument, set the current volume
  if [ $1 -gt 0 -a $1 -lt 100 ]; then 
    if [ $hasOsascript -eq 1 ]; then
      `osascript -e "set volume output volume $1"`
    fi
    if [ $hasAumix -eq 1 ]; then
      aumix -v $1
    fi
  else
    echo "not a valid volume!"
    exit;
  fi
fi
