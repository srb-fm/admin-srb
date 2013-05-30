#!/bin/bash

# Dieses kleine Script uebernimmt die Aktualisierung 
# des Intra fuer Admin-SRB auf dem Server
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2013-05-27
#


echo "SRB-Admin-Intra Update..."
echo "SRB-Admin von git laden..."

git clone https://github.com/srb-fm/admin-srb.git

echo "Intra kopieren" 
sudo cp -R ~/srb-tools/admin-srb/intra/* /var/www

echo "Libs verschieben"
# ohne admin_srb_conf.php
sudo mv /var/www/admin_srb_libs/lib*.php /var/cgi-bin/admin_srb_libs

echo "Admin-SRB aufraeumen"
rm -rf ~/srb-tools/admin-srb 

echo "...abgeschlossen"
# erstmal nur manuell um etwaige fehler zu sehen
read -p "Press Enter to finish..."
exit
