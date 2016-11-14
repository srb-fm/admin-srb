#!/bin/bash

# This script is for installing server-packages.
# Some of them must be configured later.
# Testet with ubuntu-server 14.04 in 2015
#
# Dieses kleine Script uebernimmt die Installation 
# des Firebird-Servers Admin-SRB auf dem Server
# Bei einigen ist eine spaetere Configuration noetig
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2013-05-27
#


echo "Admin-SRB-Firebird Installation..."
echo "Use this script only for a fresh firebird-install!"
read -p "Are you sure to install? (y/n) " -n 1
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
	echo "Install..."
	sudo apt-get install \
	firebird2.5-super firebird2.5-examples firebird2.5-dev \ 
	php5-interbase python-kinterbasdb

	echo "Configuration"
	sudo dpkg-reconfigure firebird2.5-super

	echo "SetUp Backup-Path"
	sudo mkdir ~/srb-backup-firebird
	sudo chown -R firebird:firebird ~/srb-backup-firebird


	echo "Restart Apache ..."
	sudo service apache2 restart
	echo "...finish"
else
	echo ""
	echo "Install aborted"
fi

exit
