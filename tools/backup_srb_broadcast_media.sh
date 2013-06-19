#!/bin/bash

# Dieses kleine Script uebernimmt das Backup 
# der gesendeten, archivierten Mediadateien
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2013-06-19
#

media_archiv_sendung="/mnt/Data_Server_03/Play_Out_Archiv/Archiv_HF_Sendung"
media_archiv_infotime="/mnt/Data_Server_03/Play_Out_Archiv/Archiv_HF_Infotime"
media_backup_sendung="/home/srb-server-03/srb-net/backup/Backup_Media_HF_Sendungen/Backup_HF_Sendung"
media_backup_infotime="/home/srb-server-03/srb-net/backup/Backup_Media_HF_Sendungen/Backup_HF_Infotime"

echo "Backup aus Archiv Infotime"
rsync -r -t -v -s --log-file=backup-srb-media-sg.log $media_archiv_sendung $media_backup_sendung
echo "Backup aus Archiv Sendung"
rsync -r -t -v -s --log-file=backup-srb-media-it.log $media_archiv_infotime $media_backup_infotime
echo "Genug fue heute..."
exit
