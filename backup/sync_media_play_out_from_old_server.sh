#!/bin/bash

# This script is for syncing the play-out-files from old to new server
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2015-04-01

# Config for sync
# Source and destinationfolders
path_file_log_sg="/home/my-user/srb-backup/log/sync_media_play_out_sg.log"
path_file_log_it="/home/my-user/srb-backup/log/sync_media_play_out_it.log"
path_file_log_layout="/home/my-user/srb-backup/log/sync_media_play_out_layout.log"
path_file_log_rotation="/home/my-user/srb-backup/log/sync_media_play_out_rotation.log"

media_old_sendung="source/play_out_server_old/Play_Out_Sendung/"
media_old_infotime="source/play_out_server_old/Play_Out_Infotime/"
media_old_layout="source/play_out_server_old/Play_Out_Layout/"
media_old_rotation="source/play_out_server_old/Play_Out_Rotation/"

media_new_sendung="dest/play_out_server/Play_Out_Sendung"
media_new_infotime="dest/play_out_server/Play_Out_Infotime"
media_new_layout="dest/play_out_server/Play_Out_Layout"
media_new_rotation="dest/play_out_server/Play_Out_Rotation"

# mountz
# check if mount is mounted (Y or N)
mount_check="Y"
mount_name="/home/my-home/my-backup"
mount_ip="my-backup-ip"

# do not edit below this line
mount_available="N"

function f_check_mountz () {
	echo "check if backup-bucket is mounted..."
	if ping -c 1 $mount_ip &> /dev/null; then
		if grep -qs "$mount_name" /proc/mounts; then
			echo "It's mounted."
			mount_available="Y"
			echo "mounted: $mount_name" >> $path_file_log_sg
		else
			echo "It's not mounted."
			mount "$mount_name"
			if [ $? -eq 0 ]; then
				echo "Mount success!"
				mount_available="Y"
				echo "now mounted: $mount_name" >> $path_file_log_sg
			else
				echo "Something went wrong with the mount..."
				echo "$mount_name not mounted" >> $path_file_log_sg
			fi
		fi
	else
		echo "Not available: $mount_ip"
		echo "$mount_ip not available" >> $path_file_log_sg
	fi
}

running=$(date +'%Y-%m-%d-%H-%M-%S')
echo "$running running sync_media_play_out_from_old_server.sh..."
echo "$running running sync_media_play_out_from_old_server.sh..." >> $path_file_log_sg

if [ $mount_check == "Y" ]; then
	f_check_mountz
else
	mount_available="Y"
fi

if [ $mount_available == "Y" ]; then
	echo "Sync from old to new: Play_Out_Sendung"
	rsync -r -t -v -s --delete --log-file=$path_file_log_sg $media_old_sendung $media_new_sendung

	echo "Now running sync Infotime..." >> $path_file_log_sg
	echo "Sync from old to new: Play_Out_Infotime"
	rsync -r -t -v -s --delete --log-file=$path_file_log_it $media_old_infotime $media_new_infotime

	echo "Now running sync Layout..." >> $path_file_log_it
	echo "Sync from old to new: Play_Out_Layout"
	rsync -r -t -v -s --delete --log-file=$path_file_log_layout $media_old_layout $media_new_layout

	echo "Now running sync Rotation..." >> $path_file_log_layout
	echo "Sync from old to new: Play_Out_Rotation"
	rsync -r -t -v -s --delete --log-file=$path_file_log_rotation $media_old_rotation $media_new_rotation

	echo "finish sync_media_play_out_from_old_server..."
	echo "finish sync_media_play_out_from_old_server..." >> $path_file_log_rotation
else	
	echo "No sync possible" >> $path_file_log_sg
fi

exit
