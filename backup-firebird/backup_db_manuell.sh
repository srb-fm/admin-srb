#!/bin/bash

# This script is for backing up firebird-db
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com

fb_db="mydb.fdb"
fb_db_backup="my.fbk"
fb_db_backup_log="/home/my-home/srb-backup-firebird/log/backup_db_manuell.log"
fb_db_location="/var/lib/firebird/2.5/data/"

if [ -f $fb_db_backup_log ]; then 
	rm $fb_db_backup_log
fi
gbak -b -v -user myuser -pass mypw $fb_db_location$fb_db $fb_db_backup -y $fb_db_backup_log

exit

