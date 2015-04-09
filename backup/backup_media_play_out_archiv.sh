#!/bin/bash

# This script is for backing up archived play-out-audiofiles 
# 
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2013-06-19
#

# folders from config:
# notice fullpath to conf for using with cron and different workdir
source ~/srb-backup/backup_media_play_out_archiv_conf.sh


running=$(date +'%Y-%m-%d-%H-%M-%S')
echo "running backup_media_play_out_archiv.sh..."
echo $running

echo "Backup from Archive Sendung"
rsync -r -t -v -s --log-file=$path_file_log_sg $media_archiv_sendung $media_backup_sendung

echo "Backup from Archive Infotime"
rsync -r -t -v -s --log-file=$path_file_log_it $media_archiv_infotime $media_backup_infotime

echo "finish backup_media_play_out_archiv.sh..."
exit
