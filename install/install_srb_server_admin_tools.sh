#!/bin/bash

# Dieses kleine Script uebernimmt die Instalaltion 
# der SRB-Tools fuer Admin-SRB auf dem Server
# Bei einigen ist eine spaetere Configuration noetig
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2013-05-27
#


echo "SRB-Admin-Tools Installation..."
echo "SRB-Admin von git laden..."

git clone https://github.com/srb-fm/admin-srb.git

echo "Verzeichnis einrichten"
echo "~/srb-tools"
mkdir ~/srb-tools

echo "Tools kopieren"
cp ~/admin-srb/tools/*.py ~/srb-tools
echo "Tools ausfuehrbar machen"
cd ~/srb-tools
chmod u+x *

echo "Intra kopieren" 
sudo cp -R ~/admin-srb/intra/* /var/www

echo "Gruppe aendern"
sudo groupadd www
sudo chgrp -R www /var/www
sudo usermod -aG users www-data

echo "Export-Verzeichnis einrichten"
sudo mkdir /var/www/admin_srb_export
sudo chown -R www-data:www-data /var/www/admin_srb_export


echo "Libs verschieben"
sudo mkdir /var/cgi-bin
sudo mv /var/www/admin_srb_libs /var/cgi-bin/admin_srb_libs

echo "Admin-SRB aufraeumen"
rm -rf ~/admin-srb 




echo "...abgeschlossen"
read -p "Press Enter to finish..."
exit
