#!/bin/bash

# This script is for mirroring play-out-audiofiles
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2013-06-19

# Config for mirror
# Source and destinationfolders
path_file_log_sg="destpath/mirror_media_play_out_sg.log"
path_file_log_it="destpath/mirror_media_play_out_it.log"
path_file_log_layout="destpath/mirror_media_play_out_layout.log"
path_file_log_rotation="destpath/mirror_media_play_out_rotation.log"
path_file_log_reg_form="destpath/mirror_doc_reg_form.log"

media_play_out_sendung="sourcepath/Sendung"
media_play_out_infotime="sourcepath/Infotime"
media_play_out_layout="sourcepath/Layout"
media_play_out_rotation="sourcepath/Rotation"
doc_reg_form="sourcepath/Sendeanmeldungen"

media_mirror_sendung="mirrorpath/Sendung"
media_mirror_infotime="mirrorpath/Infotime"
media_mirror_layout="mirrorpath/Layout"
media_mirror_rotation="mirrorpath/Rotation"
doc_mirror_reg_form="mirrorpath/Sendeanmeldungen"

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
echo "$running running backup_media_play_out_archiv.sh..."
echo "$running running backup_media_play_out_archiv.sh..." >> $path_file_log_sg

if [ $mount_check == "Y" ]; then
	f_check_mountz
else
	mount_available="Y"
fi

if [ $mount_available == "Y" ]; then

	echo "Mirror from play_out Sendung"
	rsync -r -t -v -s --delete --log-file=$path_file_log_sg $media_play_out_sendung $media_mirror_sendung

	echo "Now running Mirror-Backup Infotime..." >> $path_file_log_sg
	echo "Mirror aus play_out Infotime"
	rsync -r -t -v -s --delete --log-file=$path_file_log_it $media_play_out_infotime $media_mirror_infotime

	echo "Now running Mirror-Backup Layout..." >> $path_file_log_it
	echo "Mirror aus play_out Layout"
	rsync -r -t -v -s --delete --log-file=$path_file_log_layout $media_play_out_layout $media_mirror_layout

	echo "Now running Mirror-Backup Rotation..." >> $path_file_log_layout
	echo "Mirror aus play_out Rotation"
	rsync -r -t -v -s --delete --log-file=$path_file_log_rotation $media_play_out_rotation $media_mirror_rotation

	echo "Now running Mirror-Backup Reg-Form..." >> $path_file_log_rotation
	echo "Mirror aus play_out Reg-Form"
	rsync -r -t -v -s --delete --log-file=$path_file_log_reg_form $doc_reg_form $doc_mirror_reg_form

	echo "finish mirror_media_play_out.sh..."
	echo "finish mirror_media_play_out.sh..." >> $path_file_log_reg_form
else
	echo "No mirror-backup possible" >> $path_file_log_sg
fi
exit
