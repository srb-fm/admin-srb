#!/bin/bash

# This script is for backing up archived play-out-audiofiles
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2013-06-19
#
# Config fuer backup
# Source and destinationfolders
path_file_log_sg="Destpath/backup_media_archiv_sg.log"
path_file_log_it="Destpath/backup_media_archiv_it.log"
media_archive_sendung="Sourcepath/Sendung"
media_archive_infotime="Sourcepath/Infotime"
media_backup_sendung="Backuppath/Sendung"
media_backup_infotime="Backuppath/Infotime"
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
echo "$running running backup_media_play_out_archive.sh..."
echo "$running running backup_media_play_out_archive.sh..." >> $path_file_log_sg

if [ $mount_check == "Y" ]; then
	f_check_mountz
else
	mount_available="Y"
fi

if [ $mount_available == "Y" ]; then

	echo "Backup from Archive Sendung"
	echo "Backup from Archive Sendung" >> $path_file_log_sg
	rsync -r -t -v -s --log-file=$path_file_log_sg $media_archive_sendung $media_backup_sendung

	echo "Now running Backup Archive Infotime..." >> $path_file_log_sg

	echo "Backup from Archive Infotime"
	rsync -r -t -v -s --log-file=$path_file_log_it $media_archive_infotime $media_backup_infotime

	echo "finish backup_media_play_out_archive.sh..."
	echo "finish backup_media_play_out_archive.sh..." >> $path_file_log_it
else
	echo "No backup possible" >> $path_file_log_it
fi

exit
