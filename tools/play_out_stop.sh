#!/bin/bash
#
# This script is for stopping play-out and tools
# 
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2014-11-18
#

# config:

## do not edit below this line
source $stream_config
echo "Play-Out Jack-Apps-stopping..."

(	echo "10"

	echo "# Jamin stop.."
	sleep 1	
	killall jamin &
	
	echo "# EBU Meter stop.."
	sleep 1
	killall ebumeter &

	echo "# MPD stop.."
	sleep 1
	killall mpd &

	sleep 2
	echo "100"

)| zenity --progress \
           --title="SRB-Play-Out" --text="stop..." --width=500 --pulsate --auto-close

