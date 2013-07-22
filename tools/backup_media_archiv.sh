#!/bin/bash

# Dieses kleine Script uebernimmt das Backup 
# der gesendeten, archivierten Mediadateien
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2013-06-19
#

# folders from config:
# notice fullpath to conf for using with cron and different workdir
source ~/srb-tools/backup_media_archiv_conf.sh



echo "Backup aus Archiv Infotime"
rsync -r -t -v -s --log-file=backup_media_archiv_sg.log $media_archiv_sendung $media_backup_sendung
echo "Backup aus Archiv Sendung"
rsync -r -t -v -s --log-file=backup_media_archiv_it.log $media_archiv_infotime $media_backup_infotime
echo "Genug fue heute..."
exit
