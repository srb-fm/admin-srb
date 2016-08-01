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

echo "Play-Out Apps-stopping..."

(	echo "10"

	echo "# Jamin stop.."
	sleep 1	
	killall jamin &

	echo "# Calfjackhost stop.."
	sleep 1	
	killall calfjackhost &
	
	echo "# EBU Meter stop.."
	sleep 1
	killall ebumeter &

	echo "# MPD stop.."
	sleep 1
	killall mpd &

	echo "# Logging stop.."
	sleep 1
	pkill -1 -f play_out_logging.py &

	echo "# Scheduling stop.."
	sleep 1
	pkill -1 -f play_out_scheduler.py &

	sleep 2
	echo "100"

)| zenity --progress \
           --title="SRB-Play-Out" --text="stop..." --width=500 --pulsate --auto-close

