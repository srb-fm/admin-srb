#!/bin/bash

# This script is for syncing the play-out-files from old to new server
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2015-04-01

# folders from config:
# notice fullpath to conf for using with cron and different workdir
source ~/srb-backup/sync_media_play_out_from_old_server_conf.sh

running=$(date +'%Y-%m-%d-%H-%M-%S')
echo "running sync_media_play_out_from_old_server.sh..."
echo $running

echo "Sync from old to new: Play_Out_Sendung"
rsync -r -t -v -s --delete --log-file=$path_file_log_sg $media_old_sendung $media_new_sendung

echo "Sync from old to new: Play_Out_Infotime"
rsync -r -t -v -s --delete --log-file=$path_file_log_it $media_old_infotime $media_new_infotime

echo "Sync from old to new: Play_Out_Layout"
rsync -r -t -v -s --delete --log-file=$path_file_log_layout $media_old_layout $media_new_layout

echo "Sync from old to new: Play_Out_Rotation"
rsync -r -t -v -s --delete --log-file=$path_file_log_rotation $media_old_rotation $media_new_rotation

echo "finish sync_media_play_out_from_old_server..."
exit
