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
# 2012-09-17

echo "Admin-SRB-Server Packageinstall..."

sudo apt-get install \
git ntp php5-cli php5-dev php-pear \
phpmyadmin vsftpd mpc \
python-tk python-mutagen python-setuptools \
lame mp3val libid3-tools mp3gain sox mp3info \
libsox-fmt-all darkice id3v2 \
curl gawk links libtranslate-bin

sudo pear install HTTP_Download

echo "Load ez_setup.py.."
wget https://bootstrap.pypa.io/ez_setup.py -O - | sudo python

echo "Install pip"
sudo easy_install pip

echo "Load Tweepy from git ..."
git clone https://github.com/tweepy/tweepy.git

echo "Install Tweepy"
cd "$(pwd)"/tweepy
sudo python setup.py install

echo "Clean up Tweepy-Temp"
cd ..
sudo rm -R "$(pwd)"/tweepy
sudo rm "$(pwd)"/setuptools-14.0.zip

echo "...finish"
exit
