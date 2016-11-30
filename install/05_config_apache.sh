#!/bin/bash

# This script is for configuration apache.
#
# Testet with ubuntu-server 14.04 in 2016
#
# Dieses kleine Script uebernimmt die Configuration
# des Webservers apache fuer Admin-SRB auf dem Server
# 
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2016-11-29
#
echo ""
echo "Admin-SRB Apache Configuration..."
echo "Use this script only for a fresh install!"
echo "Run this script not with sudo!"
echo "It provides following steps:"
echo "- Set permissions for main path"
echo "- Add www-data to group users for apache fileaccess of media files"
echo "- Activate some additionally mods"
echo "- Install and activate php5-mcrypt"
echo "- Creating of htaccess files"

read -p "Are you sure to config apache? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Configuration aborted"
	echo ""
	exit
fi

echo "The standard path for media files is: /mnt/data_server"
echo "Leave the input blank if you using this path"
echo "Otherwise type in a new path"
echo "It must be the same as used in 04_config_paths.sh"
read -p 'input data path for media files: ' path_media
if [ -z "$path_media" ]; then
  path_media=/mnt/data_server
fi

echo "Change permissions of document root..."
sudo chmod -R 755 /var/www

echo "Add www-data to group users for apache fileaccess of media files..."
sudo usermod -aG users www-data

echo "Activate some additionally mods..."
sudo a2enmod expires
sudo a2enmod include
sudo a2enmod ssl

echo "Install and activate php5-mcrypt..."
sudo apt-get install php5-mcrypt
sudo php5enmod mcrypt

read -p "Are you sure to create htaccess files in the media path? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Creating of htaccess files aborted"
else
  sudo bash -c "echo ""Options -Indexes"" > ${path_media}/play_out_archiv/.htaccess"
  sudo bash -c "echo ""Options -Indexes"" > ${path_media}/play_out_server/.htaccess"
fi

sudo service apache2 restart
sudo service apache2 force-reload

echo "To finalize apache config, you have a few tasks manually to do:"
echo "Create a ssl key"
echo "Edit this files:"
echo "/etc/apache2/sites-available/000-default.conf"
echo "and:"
echo "/etc/apache2/sites-available/default-ssl.conf"
echo "and restart apache"
echo "...finish"
echo ""
exit
