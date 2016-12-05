#!/bin/bash

# This script is for configuration apache.
#
# Testet with ubuntu-server 16.04 in 2016
#
# Dieses kleine Script uebernimmt die Configuration
# des Webservers apache fuer Admin-SRB auf dem Server
# 
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2016-12-02
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
echo ""

read -p "Are you sure to config apache? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Configuration aborted"
	echo ""
	exit
fi

echo ""
echo "The standard path for media files is: /mnt/data_server"
echo "Leave the input blank if you using this path"
echo "Otherwise type in a new path"
echo "It must be the same as used in 04_config_paths.sh"
echo ""
read -p 'input data path for media files: ' path_media
if [ -z "$path_media" ]; then
  path_media=/mnt/data_server
fi

echo ""
echo "Change permissions of document root..."
sudo chmod -R 755 /var/www

echo ""
echo "Add www-data to group users for apache fileaccess of media files..."
sudo usermod -aG users www-data

echo ""
echo "Activate some additionally mods..."
sudo a2enmod expires
sudo a2enmod include
sudo a2enmod ssl

echo ""
echo "Install and activate php-mcrypt..."
sudo phpenmod mcrypt

echo ""
read -p "Are you sure to create htaccess files in the media path? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Creating of htaccess files aborted"
	echo ""
else
  sudo bash -c "echo ""Options -Indexes"" > ${path_media}/Play_Out_Archiv/.htaccess"
  sudo bash -c "echo ""Options -Indexes"" > ${path_media}/Play_Out_Server/.htaccess"
fi

sudo service apache2 restart
sudo service apache2 force-reload

echo ""
echo "To finalize apache config, you have a few tasks manually to do:"
echo "Create a ssl key"
echo "Edit this files:"
echo "/etc/apache2/sites-available/000-default.conf"
echo "/etc/apache2/sites-available/default-ssl.conf"
echo "and restart apache"
echo "...finish"
echo ""
exit
