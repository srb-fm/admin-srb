#!/bin/bash

# folders from config:
# notice fullpath to conf for using with cron and different workdir
source /home/srb-server-03/srb-firebird-backup/backup_db_weekly_conf.sh

if [ -f $fb_db_backup_log ]; then 
	rm $fb_db_backup_log
fi

gbak -b -v -user $fb_db_user -pass $fb_db_pw $fb_programm$fb_db $fb_db_backup -y $fb_db_backup_log

exit
