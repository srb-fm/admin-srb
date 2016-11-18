#!/bin/bash

# This script is for configuration firebird-sql-server.
#
# Testet with ubuntu-server 14.04 in 2016
#
# Dieses kleine Script uebernimmt die Configuration
# des Firebird-Servers Admin-SRB auf dem Server
# Die Einrichtung der DBs muss separat manuell vorgenommen werden
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2016-11-16
#


echo "Admin-SRB-Firebird Configuration..."
echo "Use this script carefully!"
echo "You can change the aliases.conf only for using with admin-srb"
echo "You can add a database user"
echo "You can create a empty database"
read -p "Are you sure to config firebird database server? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Configuration aborted"
	exit
fi

echo "Configuration..."
sudo dpkg-reconfigure firebird2.5-super

read -p "Are you sure to customize aliases for admin-srb? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Customizing Aliases aborted"
	exit
fi

echo "Customize Aliases..."
sudo cp /etc/firebird/2.5/aliases.conf /etc/firebird/2.5/aliases.conf.$(date +'%y-%m-%d-%H-%M-%S')
sudo bash -c "echo ""Admin_SRB_db = /var/lib/firebird/2.5/data/admin_srb_db.fdb"" >> /etc/firebird/2.5/aliases.conf"
sudo bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/admin_srb_db_log.fdb"" >> /etc/firebird/2.5/aliases.conf"

read -p "Are you sure to add firebird database user for admin-srb? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Adding User aborted"
	exit
fi

echo "Add Firebird-User:"
read -p 'Firebird Username for Admin-SRB: ' fb_user
read -sp 'Firebird Password for Admin-SRB: ' fb_pw
read -sp 'To add the new user, type in the firebird-master-password: ' fb_pw_master
sudo gsec -user sysdba -pass $fb_pw_master -add $fb_user -pw $fb_pw

echo ""
echo "To allow access the firebird server from the network, edit:"
echo "sudo nano /etc/firebird/2.5/firebird.conf"
echo "and change RemoteBindAddress = localhost to RemoteBindAddress = "
echo ""
echo "...finish"

exit
