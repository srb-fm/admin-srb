#!/bin/bash

# Dieses kleine Script uebernimmt die Instalaltion von Paketen
# fuer den srb-admin-server
# Bei einigen ist eine spaetere Configuration noetig
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2012-09-17

echo "SRB-Admin-Server Paketinstallation..."

sudo apt-get install \
ntp php5-cli php5-dev php-pear \
phpmyadmin vsftpd pgadmin3 \
python-tk python-mutagen python-setuptools \
lame mp3val libid3-tools mp3gain sox ffmpeg mp3info \
darkice id3v2 \
curl gawk links libtranslate-bin \
synaptic gnome-schedule git

sudo pear install HTTP_Download

echo "Tweepy von git laden..."
git clone https://github.com/tweepy/tweepy.git

echo "Tweepy installieren"
cd ~/tweepy
sudo python setup.py install

echo "Tweepy aufraeumen"
sudo rm -rf ~/tweepy

echo "...abgeschlossen"
exit
