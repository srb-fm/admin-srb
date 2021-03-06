#!/bin/bash

# This script is for installing admin-srb-files.
# Some of them must be configured later.
# Testet with ubuntu-server 14.04 and 16.04 in 2016
#
# Dieses kleine Script uebernimmt die Installation 
# der Dateien fuer Admin-SRB auf dem Server
# Bei einigen ist eine spaetere Configuration noetig
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2016-11-15
#
echo ""
echo "Admin-SRB Installation..."
echo "Use this script carefully!"
echo "This script is for installing the admin-srb files for a fresh install!"
echo "It will install:"
echo "srb-tools"
echo "srb-intra"
echo "srb-backup"
echo "srb-backup-firebird"
echo ""
read -p "Are you sure to install admin-srb files? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Installation aborted"
	exit
fi

echo "Installing git..."
sudo apt-get install git

echo ""
echo "Load Admin-SRB from git..."
git clone https://github.com/srb-fm/admin-srb.git

echo ""
read -p "Are you sure to install srb-tools? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
	  echo "Install srb-tools aborted"
else
    echo "Make path ~/srb-tools"
    mkdir ~/srb-tools

    echo "Copy tools"
    cp "$(pwd)"/admin-srb/tools/*.py ~/srb-tools
    cp "$(pwd)"/admin-srb/tools/*.sh ~/srb-tools
    cp "$(pwd)"/admin-srb/tools/*.template ~/srb-tools
    echo "Make Tools executable"
    echo "This scripts makes only tools executable"
    echo "that are necessary for running on the main admin server"
    find srb-tools -type f ! -name "lib_*.py" ! -name "*.sh" ! -name "*.py.template" -exec chmod u+x {} +
    chmod o+x ~/srb-tools/play_out_loader*.py
    chmod o+x ~/srb-tools/audio_switch_controller.py
fi

echo ""
read -p "Are you sure to install srb-intra? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
	  echo "Install srb-intra aborted"
else
    echo "Make path ~/srb-intra..."
    sudo mkdir /var/www/srb-intra
    echo "Change User:Group for srb-intra..."
    sudo chown $USER:www-data /var/www/srb-intra
    echo "Make symbolic link from /var/www/srb-intra to home..."
    ln -s /var/www/srb-intra ~/
    mkdir ~/srb-intra/public_html
    mkdir ~/srb-intra/cgi-bin
    mkdir ~/srb-intra/cgi-bin/admin_srb_libs

    echo "Copy intra..." 
    cp -R "$(pwd)"/admin-srb/intra/* ~/srb-intra/public_html
    cp -R "$(pwd)"/admin-srb/intra/admin_srb_libs/ ~/srb-intra/cgi-bin/

    echo "Change user:group for srb-export..."
    sudo chown -R www-data:www-data ~/srb-intra/public_html/admin_srb_export
    echo "Be sure to have correct permissions..."
    cd ~/srb-intra/public_html
    find . -type f -exec chmod o-x {} +
    cd
    cd ~/srb-intra/cgi-bin/admin_srb_libs
    find . -type f -exec chmod o-x {} +
    cd
fi

echo ""
read -p "Are you sure to install srb-backup? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
	  echo "Install srb-backup aborted"
else
    echo "Make path ~/srb-backup..."
    mkdir ~/srb-backup
    mkdir ~/srb-backup/log
    echo "Copy backupscripts..."
    cp "$(pwd)"/admin-srb/backup/*.sh ~/srb-backup
fi

echo ""
read -p "Are you sure to install srb-backup-firebird? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
	  echo "Install srb-backup-firebird aborted"
else
    echo "Make path ~/srb-backup-firebird..."
    sudo mkdir ~/srb-backup-firebird
    sudo mkdir ~/srb-backup-firebird/log
    sudo chown -R firebird:firebird ~/srb-backup-firebird
    echo "Copy firebird backupscripts..."
    sudo cp "$(pwd)"/admin-srb/backup-firebird/*.sh ~/srb-backup-firebird
fi

echo ""
echo "Folder admin-srb will continue stay for later use of install-scripts..."
echo "To complete admin-srb setup continue with installscripts in admin-srb/install..."
echo "...finish"
echo ""
exit


