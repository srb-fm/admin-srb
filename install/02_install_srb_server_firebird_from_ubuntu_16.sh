#!/bin/bash

# This script is for installing server-packages.
# Some of them must be configured later.
# Testet with ubuntu-server 16.04 in 2016
# It's referring to https://help.ubuntu.com/community/Firebird2.5
#
# Dieses kleine Script uebernimmt die Installation
# des Firebird-Servers Admin-SRB auf dem Server
# Bei einigen ist eine spaetere Configuration noetig
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2016-12-02
#

echo ""
echo "Admin-SRB-Firebird Installation..."
echo "Use this script only for a fresh firebird-install!"
echo ""
read -p "Are you sure to install? (y/n) " -n 1
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
	echo "Adding the ppa..."
	sudo add-apt-repository ppa:mapopa
	sudo apt-get update
	echo "Install..."
	sudo apt-get install \
	firebird2.5-super firebird2.5-examples firebird2.5-dev \
	php-interbase python-kinterbasdb

	echo "Configuration..."
	sudo dpkg-reconfigure firebird2.5-super
	sudo phpenmod interbase

	echo "Restart Apache ..."
	sudo service apache2 restart
	echo "...finish"
else
	echo ""
	echo "Install aborted"
fi

echo ""
exit
