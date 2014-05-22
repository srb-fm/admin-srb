#!/bin/bash
#
# This script is for starting jack_capture
# to record live-transmittings
#
# Prerequisite is a correct running jack/Qjackctrl
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2014-03-27
#
# config:
record_config="/home/srb-stream/stream-srb/stream-log/live_recording_conf.sh"
record_log="/home/srb-stream/stream-srb/stream-log/live_recording.log"



## do not edit below this line


function f_check_configfile () {
	if [ ! -f $record_config ]
	then
		echo "Configfile not found:">> $record_log
		echo $record_config>> $record_log
		sleep 2
		exit
	else
		source $record_config
		waiting=$r_wait
		duration=$r_duration
		filename=$r_filename
		echo "Deleting configfile..."
		rm $record_config
	fi
}


function f_check_jack () {
	echo "Checking Jack.."
	sleep 1
	jack_pid=$(ps aux | grep '[q]jackctl' | awk '{print $2}')
	if [ "$jack_pid" == "" ]; then
		echo "Jack/Qjackctl is not running!"
		echo "Please start it befor running this script!"
		echo "Let's lay down.."
		exit		
	fi
}


function f_start_jack_capture () {
	echo "Starting Jack_Capture..">> $record_log
	sleep $waiting
	jack_capture --port jamin:out_L --port jamin:out_R -d $duration -fn $filename -dc 
}


echo `date +%Y-%m-%d-%M`> $record_log
echo "Starting Live-Recording..."

f_check_configfile
f_start_jack_capture
sleep 2

exit

