#!/bin/bash

# This script is for configuration firebird-sql-server.
#
# Testet with ubuntu-server 14.04 in 2016
#
# Dieses kleine Script uebernimmt die Configuration
# des Firebird-Servers Admin-SRB auf dem Server
# 
# Author: Joerg Sorge
# Distributed under the terms of GNU GPL version 2 or later
# Copyright (C) Joerg Sorge joergsorge@gmail.com
# 2016-11-16
#


echo "Admin-SRB-Firebird Configuration..."
echo "Use this script carefully!"
echo "It provides following steps:"
echo "- Reconfiguring firebird server"
echo "- Creating database"
echo "- Define aliases for using with admin-srb"
echo "- Add database user"
echo "- Config db credential for tools"
echo "- Config db credential for intra"
read -p "Are you sure to config firebird database server? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Configuration aborted"
	exit
fi

read -p "Are you sure to config the firebird server in general? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Configuration of the firebird server aborted"
else
	sudo dpkg-reconfigure firebird2.5-super
	read -p "Are you sure to allow firebird access from the network? (y/n) " -n 1
	echo ""
	if [[ ! $REPLY =~ ^[Yy]$ ]]; then
		echo ""
		echo "Access from the network aborted"
	else
		echo "Allow firebird access from the network:"
		sudo cp /etc/firebird/2.5/firebird.conf /etc/firebird/2.5/firebird.conf.$(date +'%y-%m-%d-%H-%M-%S')
		sed -i "s/RemoteBindAddress =/#RemoteBindAddress =/" /etc/firebird/2.5/firebird.conf
		sed -i "s/#RemoteBindAddress = localhost/RemoteBindAddress = localhost/" /etc/firebird/2.5/firebird.conf
		echo ""
	fi
fi

db_option=""

read -p "Are you sure to create a database? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Creating of database aborted"
else
	echo "1 - new empty db"
	echo "2 - use existing db"
	echo "any other input for aborting"
	echo -n "Enter number 1 or 2, other character for aborting: "
	read character
	case $character in
    		1 ) echo "Create empty db"
			db_option="create"
			read -sp 'To create the new db, type in the firebird-master-password: ' fb_pw_master
			sudo service firebird2.5-super stop
			if sudo test -f /var/lib/firebird/2.5/data/admin_srb_db.fdb ; then
				sudo mv /var/lib/firebird/2.5/data/admin_srb_db.fdb /var/lib/firebird/2.5/data/admin_srb_db_$(date +'%y-%m-%d-%H-%M-%S').fdb
			fi
			if sudo test -f /var/lib/firebird/2.5/data/admin_srb_db_log.fdb ; then
				sudo mv /var/lib/firebird/2.5/data/admin_srb_db_log.fdb /var/lib/firebird/2.5/data/admin_srb_db_log_$(date +'%y-%m-%d-%H-%M-%S').fdb
			fi
			#gfix -user SYSDBA -password $fb_pw_master -online Admin_SRB_db
			#gfix -user SYSDBA -password $fb_pw_master -shut full -tran 0 Admin_SRB_db
			#gfix -user SYSDBA -password $fb_pw_master -online Admin_SRB_db_log
			#gfix -user SYSDBA -password $fb_pw_master -shut full -tran 0 Admin_SRB_db_log
			sudo chown -R firebird:firebird "$(pwd)"/db
			sudo service firebird2.5-super start
			gbak -user SYSDBA -password $fb_pw_master -rep "$(pwd)"/db/admin_srb_db_meta.fbk /var/lib/firebird/2.5/data/admin_srb_db.fdb -meta_data
			gbak -user SYSDBA -password $fb_pw_master -rep "$(pwd)"/db/admin_srb_db_log_meta.fbk /var/lib/firebird/2.5/data/admin_srb_db_log.fdb -meta_data
        	;;
    		2 ) echo "Restore existing db"
			db_option="restore"
			read -sp 'To restore existing db, type in the firebird-master-password: ' fb_pw_master
			echo "This action will restore both, db and db_log"
			echo "db will taken from ~/srb-backup-firebird"
			read -p 'input db-name without extention: ' fb_db_name
			echo "logging-db can restore as empty db"
			echo "therefore, leave the input blank"
			read -p 'input log-db-name without extention: ' fb_db_name_log
			sudo service firebird2.5-super stop
			if sudo test -f /var/lib/firebird/2.5/data/$fb_db_name".fdb" ; then
				sudo mv /var/lib/firebird/2.5/data/$fb_db_name".fdb" /var/lib/firebird/2.5/data/$fb_db_name_$(date +'%y-%m-%d-%H-%M-%S')".fdb"
			fi
			if ! [ -z "$fb_db_name_log" ]; then
				if sudo test -f /var/lib/firebird/2.5/data/$fb_db_name_log".fdb" ; then
					sudo mv /var/lib/firebird/2.5/data/$fb_db_name_log".fdb" /var/lib/firebird/2.5/data/$fb_db_name_log_$(date +'%y-%m-%d-%H-%M-%S')".fdb"
				fi
			fi
			sudo chown -R firebird:firebird ~/srb-backup-firebird
			sudo service firebird2.5-super start
			gbak -user SYSDBA -password $fb_pw_master -rep ~/srb-backup-firebird/$fb_db_name".fbk" /var/lib/firebird/2.5/data/$fb_db_name".fdb"
			if [ -z "$fb_db_name_log" ]; then
				gbak -user SYSDBA -password $fb_pw_master -rep "$(pwd)"/db/admin_srb_db_log_meta.fbk /var/lib/firebird/2.5/data/admin_srb_db_log.fdb -meta_data
			else
				gbak -user SYSDBA -password $fb_pw_master -rep ~/srb-backup-firebird/$fb_db_name_log".fbk" /var/lib/firebird/2.5/data/$fb_db_name_log".fdb"
			fi
        	;;
    		* ) echo "You did not enter 1 or 2"
	esac
fi

read -p "Are you sure to customize aliases for admin-srb? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Customizing aliases aborted"
else
	echo "Customize aliases..."
	case $db_option in
    		create ) 
		sudo cp /etc/firebird/2.5/aliases.conf /etc/firebird/2.5/aliases.conf.$(date +'%y-%m-%d-%H-%M-%S')
		text="Admin_SRB_db ="
		if grep -Fwn "$text" /etc/firebird/2.5/aliases.conf; then
			line_nr=$(grep -Fwn "$text" /etc/firebird/2.5/aliases.conf | sed 's/^\([0-9]\+\):.*$/\1/')
			sudo sed -i "${line_nr}d" /etc/firebird/2.5/aliases.conf
		fi
		sudo bash -c "echo ""Admin_SRB_db = /var/lib/firebird/2.5/data/admin_srb_db.fdb"" >> /etc/firebird/2.5/aliases.conf"
		
		text="Admin_SRB_db_log ="
		if grep -Fwn "$text" /etc/firebird/2.5/aliases.conf; then
			line_nr=$(grep -Fwn "$text" /etc/firebird/2.5/aliases.conf | sed 's/^\([0-9]\+\):.*$/\1/')
			sudo sed -i "${line_nr}d" /etc/firebird/2.5/aliases.conf
		fi
		sudo bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/admin_srb_db_log.fdb"" >> /etc/firebird/2.5/aliases.conf"
		;;
	
		restore )
		sudo cp /etc/firebird/2.5/aliases.conf /etc/firebird/2.5/aliases.conf.$(date +'%y-%m-%d-%H-%M-%S')
		text="Admin_SRB_db ="
		if grep -Fwn "$text" /etc/firebird/2.5/aliases.conf; then
			line_nr=$(grep -Fwn "$text" /etc/firebird/2.5/aliases.conf | sed 's/^\([0-9]\+\):.*$/\1/')
			sudo sed -i "${line_nr}d" /etc/firebird/2.5/aliases.conf
		fi
		sudo bash -c "echo ""Admin_SRB_db = /var/lib/firebird/2.5/data/$fb_db_name.fdb"" >> /etc/firebird/2.5/aliases.conf"
		
		text="Admin_SRB_db_log ="
		if grep -Fwn "$text" /etc/firebird/2.5/aliases.conf; then
			line_nr=$(grep -Fwn "$text" /etc/firebird/2.5/aliases.conf | sed 's/^\([0-9]\+\):.*$/\1/')
			sudo sed -i "${line_nr}d" /etc/firebird/2.5/aliases.conf
		fi
		if [ -z "$fb_db_name_log" ]; then
			sudo bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/admin_srb_db_log.fdb"" >> /etc/firebird/2.5/aliases.conf"
		else
			sudo bash -c "echo ""Admin_SRB_db_log = /var/lib/firebird/2.5/data/$fb_db_name_log"" >> /etc/firebird/2.5/aliases.conf"
		fi
		;;
		* ) echo "No database created or restored, customize alias aborted"
	esac
fi

read -p "Are you sure to add firebird database user for admin-srb? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Adding user aborted"
else
	echo "Add Firebird-User:"
	read -p 'Firebird username for Admin-SRB: ' fb_user
	read -sp 'Firebird password for Admin-SRB: ' fb_pw
	read -sp 'To add the new user, type in the firebird-master-password: ' fb_pw_master
	sudo gsec -user sysdba -pass $fb_pw_master -add $fb_user -pw $fb_pw
	echo ""
fi

read -p "Are you sure to add db-config for admin-srb tools? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Adding config for tools aborted"
else
	echo "Add db-config for admin-srb tools:"
	if [ -z "$fb_user" ]; then
		echo "firebird user data:"
		read -p 'Firebird Username for Admin-SRB: ' fb_user
		read -sp 'Firebird Password for Admin-SRB: ' fb_pw
	fi
	config_filename=~/srb-tools/db_config.py
	bash -c "echo ""\#!/usr/bin/env python"" > $config_filename"
	bash -c "echo ""\# -*- coding: utf-8 -*-"" >> $config_filename"
	bash -c "echo ""\# db-config"" >> $config_filename"
	bash -c "echo ""db_name = \'Admin_SRB_db\'"" >> $config_filename"
	bash -c "echo ""db_user = \'$fb_user\'"" >> $config_filename"
	bash -c "echo ""db_pw = \'$fb_pw\'"" >> $config_filename"
	bash -c "echo ""db_log_name = \'Admin_SRB_db_log\'"" >> $config_filename"
	bash -c "echo ""db_log_user = \'$fb_user\'"" >> $config_filename"
	bash -c "echo ""db_log_pw = \'$fb_pw\'"" >> $config_filename"
	echo ""
fi

read -p "Are you sure to add db-config for admin-srb intra? (y/n) " -n 1
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
	echo ""
	echo "Adding config for intra aborted"
else
	echo "Add db-config for admin-srb intra:"
	if [ -z "$fb_user" ]; then
		echo "firebird user data:"
		read -p 'Firebird Username for Admin-SRB: ' fb_user
		read -sp 'Firebird Password for Admin-SRB: ' fb_pw
	fi
	config_filename=~/srb-intra/cgi-bin/admin_srb_libs/admin_srb_conf.php
	sed -i "s/\$db_host_db = \"\"/\$db_host_db = \"localhost:Admin_SRB_db\"/" $config_filename
	sed -i "s/\$db_user = \"\"/\$db_user = \"$fb_user\"/" $config_filename
	sed -i "s/\$db_pwd  = \"\"/\$db_pwd = \"$fb_pw\"/" $config_filename
	sed -i "s/\$db_log_host_db = \"\"/\$db_log_host_db = \"localhost:Admin_SRB_db_log\"/" $config_filename
	sed -i "s/\$db_log_user = \"\"/\$db_log_user = \"$fb_user\"/" $config_filename
	sed -i "s/\$db_log_pwd  = \"\"/\$db_log_pwd = \"$fb_pw\"/" $config_filename
	echo ""
fi

echo ""
echo "To allow access the firebird server from the network, edit:"
echo "sudo nano /etc/firebird/2.5/firebird.conf"
echo "and change RemoteBindAddress = localhost to RemoteBindAddress = "
echo ""
echo "...finish"

exit
