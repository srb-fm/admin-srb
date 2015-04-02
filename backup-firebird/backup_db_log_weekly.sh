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
# change "my-user" to your user in next line!!!

source /home/my-user/srb-backup-firebird/backup_db_log_weekly_conf.sh

running=$(date +'%Y-%m-%d-%H-%M-%S')
echo "running backup_db_log_weekly.sh..."
echo $running

if [ -f $fb_db_log_backup_log ]; then
	rm $fb_db_log_backup_log
fi

gbak -b -v -user $fb_db_log_user -pass $fb_db_log_pw $fb_db_location$fb_db_log $fb_db_log_backup -y $fb_db_log_backup_log

echo "finish backup_db_log_weekly.sh..."
exit
