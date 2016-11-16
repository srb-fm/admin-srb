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
echo "Use this script only for a fresh firebird-install!"
echo "It will change the aliases.conf only for using with admin-srb!"
read -p "Are you sure to install? (y/n) " -n 1
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
  echo "Configuration..."
	sudo dpkg-reconfigure firebird2.5-super

	echo "Customize Aliases ..."
	sudo cp /etc/firebird/2.5/aliases.conf /etc/firebird/2.5/aliases.conf.$(date +'%y-%m-%d-%H-%M-%S')
	sudo bash -c "echo ""Admin_SRB_db = /var/lib/firebird/2.5/data/admin_srb_db.fdb"" >> /etc/firebird/2.5/aliases.conf"
	sudo bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/admin_srb_db_log.fdb"" >> /etc/firebird/2.5/aliases.conf"

  echo "To allow access the firebird server from the network, edit:"
  echo "sudo nano /etc/firebird/2.5/firebird.conf"
  echo "and change RemoteBindAddress = localhost to RemoteBindAddress = "
  echo ""
	echo "...finish"
else
	echo ""
	echo "Configuration aborted"
fi

exit
