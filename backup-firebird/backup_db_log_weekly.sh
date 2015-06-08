#!/bin/bash

# This script is for backing up firebird-db
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com

# folders from config:
# notice fullpath to conf for using with cron and different workdir
#
#
fb_db_user=""
fb_db_pw=""
fb_db_active=""
fb_db_backup=""
fb_db_log_backup_log=""
fb_db_location="/var/lib/firebird/2.5/data/"

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
			echo "mounted: $mount_name" >> $fb_db_log_backup_log
		else
			echo "It's not mounted."
			mount "$mount_name"
			if [ $? -eq 0 ]; then
				echo "Mount success!"
				mount_available="Y"
				echo "now mounted: $mount_name" >> $fb_db_log_backup_log
			else
				echo "Something went wrong with the mount..."
				echo "$mount_name not mounted" >> $fb_db_log_backup_log
			fi
		fi
	else
		echo "Not available: $mount_ip"
		echo "$mount_ip not available" >> $fb_db_log_backup_log
	fi
}

running=$(date +'%Y-%m-%d-%H-%M-%S')
echo "$running running backup_db_log_weekly.sh..."
echo "$running running backup_db_log_weekly.sh..." >> $fb_db_log_backup_log

if [ $mount_check == "Y" ]; then
	f_check_mountz
else
	mount_available="Y"
fi

if [ $mount_available == "Y" ]; then

	if [ -f $fb_db_log_backup_log ]; then
		rm $fb_db_log_backup_log
	fi

	gbak -b -v -user $fb_db_user -pass $fb_db_pw $fb_db_location$fb_db_active $fb_db_backup -y $fb_db_log_backup_log

	echo "finish backup_db_log_weekly.sh..."
	echo "finish backup_db_log_weekly.sh..." >> $fb_db_log_backup_log
else
	echo "No backup possible" >> $fb_db_log_backup_log
fi
exit
