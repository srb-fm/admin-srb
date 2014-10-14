#!/bin/bash

# Dieses kleine Script uebernimmt das Backup 
# der aktuell gesendeten Mediadateien
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2013-06-19

# folders from config:
# notice fullpath to conf for using with cron and different workdir
source ~/srb-tools/backup_media_play_out_conf.sh

echo "Backup aus play_out Sendung"
#rsync -r -t -v -s --delete --log-file=backup_media_play_out_sg.log $media_play_out_sendung $media_backup_sendung
rsync -r -t -v -s --delete --log-file=$path_file_log_sg $media_play_out_sendung $media_backup_sendung
echo "Backup aus play_out Infotime"
#rsync -r -t -v -s --delete --log-file=backup_media_play_out_it.log $media_play_out_infotime $media_backup_infotime
rsync -r -t -v -s --delete --log-file=$path_file_log_it $media_play_out_infotime $media_backup_infotime
echo "Genug fuer heute..."
exit
