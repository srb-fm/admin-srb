#!/bin/bash

# Dieses kleine Script uebernimmt die Instalaltion 
# des Firebird-Servers Admin-SRB auf dem Server
# Bei einigen ist eine spaetere Configuration noetig
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2013-05-27
#
# ToDo 

echo "SRB-Admin-Firebird Installation..."

sudo apt-get install \
firebird2.5-super firebird2.5-examples firebird2.5-dev \
flamerobin php5-interbase python-kinterbasdb

echo "Konfiguration"
sudo dpkg-reconfigure firebird2.5-super

echo "Backup-Verzeichnis einrichten"
sudo mkdir ~/srb-firebird-backup
sudo chown -R firebird:firebird ~/srb-firebird-backup


echo "Alias anpassen.."
sudo cp /etc/firebird/2.5/aliases.conf /etc/firebird/2.5/aliases.conf.$(date +'%y-%m-%d-%H-%M-%S')
sudo bash -c "echo ""Admin_SRB_db = /var/lib/firebird/2.5/data/admin_srb_db.fdb"" >> /etc/firebird/2.5/aliases.conf"
sudo bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/admin_srb_db_log.fdb"" >> /etc/firebird/2.5/aliases.conf"

echo "...abgeschlossen"
read -p "Press Enter to finish..."
exit
