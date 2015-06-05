#!/bin/bash

# This script is for syncing firebird-db
# from active server to redundant server
#
# It must be running on redundant server
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
#
# db access user and pw for redundant server needed
#
# folders from config:
# notice fullpath to conf for using with cron and different workdir!
fb_db_user=""
fb_db_pw=""
fb_db_backup=""
fb_db_sync_pref="admin_srb_db_"
fb_db_sync_log="/home/my-user/srb-backup-firebird/log/sync_db_daily.log"
fb_db_location="/var/lib/firebird/2.5/data/"
fb_alias_location="/etc/firebird/2.5/aliases.conf"

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
			echo "mounted: $mount_name" >> $fb_db_sync_log
		else
			echo "It's not mounted."
			mount "$mount_name"
			if [ $? -eq 0 ]; then
				echo "Mount success!"
				mount_available="Y"
				echo "now mounted: $mount_name" >> $fb_db_sync_log
			else
				echo "Something went wrong with the mount..."
				echo "$mount_name not mounted" >> $fb_db_sync_log
			fi
		fi
	else
		echo "Not available: $mount_ip"
		echo "$mount_ip not available" >> $fb_db_sync_log
	fi
}

running=$(date +'%Y-%m-%d-%H-%M-%S')
echo "$running running sync_db_daily.sh..."
echo "$running running sync_db_daily.sh..." >> $fb_db_sync_log

if [ $mount_check == "Y" ]; then
	f_check_mountz
else
	mount_available="Y"
fi

if [ $mount_available == "Y" ]; then

	cdate=$(date +"%Y_%m_%d")
	fb_db_sync="$fb_db_sync_pref$cdate.fdb"

	if [ -f $fb_db_sync_log ]; then
		rm $fb_db_sync_log
	fi

	echo "Syncing db..."
	gbak -c -v -user $fb_db_user -password $fb_db_pw $fb_db_backup $fb_db_location$fb_db_sync -y $fb_db_sync_log

	echo "Set owner"
	chown firebird:firebird $fb_db_location$fb_db_sync

	echo "Customize Aliases ..."
	mv $fb_alias_location $fb_alias_location.$(date +'%Y-%m-%d-%H-%M-%S')
	bash -c "echo ""Admin_SRB_db = $fb_db_location$fb_db_sync"" >> /etc/firebird/2.5/aliases.conf"
	bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/admin_srb_db_log.fdb"" >> /etc/firebird/2.5/aliases.conf"

	echo "finish sync_db_daily.sh"
	echo "finish sync_db_daily.sh" >> $fb_db_sync_log
else	
	echo "No sync possible" >> $fb_db_sync_log
fi
exit
