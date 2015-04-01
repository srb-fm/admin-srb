#!/bin/bash

# This script is for backing up firebird-db
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com

# folders from config:
# notice fullpath to conf for using with cron and different workdir
source /home/myuser/srb-backup-firebird/backup_db_weekly_conf.sh

if [ -f $fb_db_backup_log ]; then 
	rm $fb_db_backup_log
fi

gbak -b -v -user $fb_db_user -pass $fb_db_pw $fb_db_location$fb_db_active $fb_db_backup -y $fb_db_backup_log

exit
