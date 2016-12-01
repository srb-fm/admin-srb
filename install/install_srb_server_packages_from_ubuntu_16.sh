#!/bin/bash

# This script is for installing server-packages.
# Some of them must be configured later.
# Testet with ubuntu-server 14.04 in 2015
#
# Dieses kleine Script uebernimmt die Instalaltion von Paketen
# fuer den srb-admin-server
# Bei einigen ist eine spaetere Configuration noetig
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2016-05-17
echo ""
echo "Admin-SRB-Server Packageinstall prepaere..."
echo ""
echo "PPA for mp3gain ..."
sudo add-apt-repository ppa:flexiondotorg/audio
sudo apt update

echo ""
echo "Admin-SRB-Server Packageinstall..."

# TODO: php7!?

sudo apt install \
git ntp php-cli php-dev php-pear \
phpmyadmin vsftpd mpc \
python python-tk python-mutagen python-setuptools \
lame mp3val libid3-tools mp3gain sox mp3info \
libsox-fmt-all darkice id3v2 \
curl gawk links libtranslate-bin

sudo pear install HTTP_Download

echo ""
echo "Load ez_setup.py..."
wget https://bootstrap.pypa.io/ez_setup.py -O - | sudo python

echo ""
echo "Install pip..."
sudo easy_install pip

echo ""
echo "Load Tweepy from git ..."
git clone https://github.com/tweepy/tweepy.git

echo ""
echo "Install Tweepy..."
cd "$(pwd)"/tweepy
sudo python setup.py install
cd ..

echo ""
echo "Clean up Tweepy-Temp..."
sudo rm -R "$(pwd)"/tweepy
sudo rm "$(pwd)"/setuptools*.zip

echo "...finish"
echo ""
exit
