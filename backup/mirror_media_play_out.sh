#!/bin/bash

# This script is for mirroring play-out-audiofiles 
#
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge at gmail.com
# 2013-06-19

# folders from config:
# notice fullpath to conf for using with cron and different workdir
source ~/srb-backup/mirror_media_play_out_conf.sh

running=$(date +'%Y-%m-%d-%H-%M-%S')
echo "running mirror_media_play_out.sh..."
echo $running

echo "Mirror from play_out Sendung"
rsync -r -t -v -s --delete --log-file=$path_file_log_sg $media_play_out_sendung $media_mirror_sendung

echo "Mirror aus play_out Infotime"
rsync -r -t -v -s --delete --log-file=$path_file_log_it $media_play_out_infotime $media_mirror_infotime

echo "Mirror aus play_out Layout"
rsync -r -t -v -s --delete --log-file=$path_file_log_layout $media_play_out_layout $media_mirror_layout

echo "Mirror aus play_out Rotation"
rsync -r -t -v -s --delete --log-file=$path_file_log_rotation $media_play_out_rotation $media_mirror_rotation

echo "finish mirror_media_play_out.sh..."
exit
