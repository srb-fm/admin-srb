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
# notice fullpath to conf for using with cron and different workdir

source /home/my-user/srb-backup-firebird/sync_db_daily_conf.sh


cdate=$(date +"%Y_%m_%d")
fb_db_sync="$fb_db_sync_pref$cdate.fdb"

if [ -f $fb_db_sync_log ]; then 
	rm $fb_db_sync_log
fi
echo "running sync_db_daily.sh..."
echo "Syncing db..."
sudo gbak -c -v -user $fb_db_user -password $fb_db_pw $fb_db_backup $fb_db_location$fb_db_sync -y $fb_db_sync_log

echo "Set owner"
chown firebird:firebird $fb_db_location$fb_db_sync

echo "Customize Aliases ..."
mv $fb_alias_location $fb_alias_location.$(date +'%y-%m-%d-%H-%M-%S')
bash -c "echo ""Admin_SRB_db = $fb_db_location$fb_db_sync"" >> /etc/firebird/2.5/aliases.conf"
bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/admin_srb_db_log.fdb"" >> /etc/firebird/2.5/aliases.conf"

echo "finish sync_db_daily.sh"
exit
