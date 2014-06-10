#!/bin/sh
#
# This script tries to set the volume
# Requirements:
# MacOSX:    osascript
# GNU/Linux: (amixer|aumix) && awk
#

for snd in amixer aumix osascript; do
	if [ -x "`which $snd 2>/dev/null`" ]; then
		SND=$snd
		break
	else
		continue
	fi
done


case $SND in
	amixer)
	# all devices: amixer scontrols | awk "{gsub(/'/,\"\"); print \$NF}"
	DEV=PCM
	if [ -z "$1" ]; then
		erg=`amixer sget "$DEV",0 | awk '/Mono: Playback/ {gsub(/[\[\]\%]/,""); print $4}'`
		if [ "$erg" != "" ]; then
			echo $erg
		else
			#amixer sget "$DEV",0 | awk '/Left: Playback/ {gsub(/[\[\]\%]/,""); print $4}'
			amixer sget "$DEV",0 | awk '/Left: Playback/ {gsub(/[\[\]\%]/,""); print $5}'
		fi
	else
		amixer -qc 0 set "$DEV" "$1"%
	fi
	;;

	aumix)
	if [ -z "$1" ]; then
		aumix -vq | awk '{sub(/\,/,""); print $2}'
 	else
		aumix -v "$1"
	fi
	;;
    
	osascript)
	if [ -z "$1" ]; then
		osascript -e "get output volume of (get volume settings)"
	else
		osascript -e "set volume output volume $1"
	fi
	;;
	
	*)
	echo "Please check $0"
  	echo ""
	echo "On GNU/Linux, you have to install aumix (OSS) or amix (ALSA) and"
	echo "give group audio permissions to the webserver user (for OSS only)."
	echo ""
	echo "On MacOSX, you have to install osascript (should be part of MacOSX anyway)."
	echo ""
	echo "Or change the volumeBin setting in config.php to a script of your own."
	exit 1
	;;
esac
