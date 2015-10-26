#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Controller Audio Switch
Autor: Jenz Loeffler
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Jenz Loeffler
2015-03-31

Dieses Script ermittelt den Status des Audio-Switches
und uebertraegt sie in die Web-Datenbank.
Dort werden diese Datensaetze zur Programmvorschau angezeigt

Dateiname Script: play_out_preview.py
Schluesselwort fuer Einstellungen: PO_Preview_Config_1
Benoetigt: ib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank
Arbeitet zusammen mit: srb_tools_load_preview.php auf dem Webserver

Fehlerliste:
E 0 Parameter-Typ oder Inhalt stimmt nicht
E 01 Fehler bei Parameteruebergabe an Script

Parameterliste:
Param 1:

Dieses Script wird durch das Intra-Web-Frontend aufgerufen


"""

import sys
import getopt
import serial
import time
#import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "023"
        self.app_desc = u"Controller_Audio_Switch"
        self.app_errorfile = "error_controller_audio_switch.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append("Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append("Fehler beim Verbinden zum Switch")
        # using develop-params
        self.app_develop = "no"
        # display debugmessages on console yes or no: "no"
        # for normal usage set to no!!!!!!
        self.app_debug_mod = "no"
        # settings serial port
        self.app_ser_port = 0
        self.app_ser_baudrate = 9600
        self.app_ser_bytesize = 8
        self.app_ser_parity = 'N'
        self.app_ser_stopbits = 1
        self.app_ser_timeout = 1


def usage_help():
    """pull out some help"""
    print "controller for audioswitch"
    print "usage:"
    print "controller_audio_switch.py -option value"
    print "valid options are:"
    print "-h --help"
    print "-s --status"
    print "-p n --push n"
    print "-f n --fade n"


def set_port():
    """setting port"""
    try:
        ser_port = serial.Serial(port=ac.app_ser_port,
                         baudrate=ac.app_ser_baudrate,
                         bytesize=ac.app_ser_bytesize,
                         parity=ac.app_ser_parity,
                         stopbits=ac.app_ser_stopbits,
                         timeout=ac.app_ser_timeout)
        if not ser_port.isOpen():
            ser_port.open()
            print "opening port"
    except Exception as e:
        print ("Fehler beim Port-Setting..: " + str(e))
        ser_port = False
    return ser_port


def get_status(param):
    """get status"""
    print "status " + param
    switch_status_audio = None
    port = set_port()
    if not port:
        return

    try:
        #print "write"
        port.write('I')
        time.sleep(0.5)
        switch_respond = port.read(10)
        time.sleep(0.5)
        port.close
        switch_status = switch_respond.split()
        print switch_status
        #switch_status_audio = switch_status[1]
        #print switch_status[1]
    except Exception as e:
        print ("Fehler beim lesen des ser. status..: " + str(e))
        port.close
    return switch_status


def read_switch_respond(switch_status):
    """read active input"""
    print "read resp"
    print switch_status[0]
    switch_respond = None
    if switch_status[0][:2] == "In":
        switch_respond = switch_status[0][2:4]
    if switch_status[0] == "Vx":
        switch_respond = switch_status[1][2:3]
    if switch_status[0] == "Amt1":
        switch_respond = "muted"
    if switch_status[0] == "Amt0":
        switch_respond = "unmuted"
    if switch_status[0] == "Exe1":
        switch_respond = "locked"
    if switch_status[0] == "Exe0":
        switch_respond = "unlocked"
    if switch_status[0] == "F1":
        switch_respond = "normal"
    if switch_status[0] == "F2":
        switch_respond = "auto"
    if switch_status[0] == "Zpa":
        switch_respond = "reset audio"
    if switch_status[0] == "Zpx":
        switch_respond = "reset system"
    print switch_respond
    return switch_respond


def push_switch_(param):
    """push switch"""
    print "push " + param
    port = set_port()
    if not port:
        return
    switch_cmd = param + "!"
    try:
        #print "write"
        port.write(switch_cmd)
        time.sleep(0.5)
        switch_status = get_status("-s")
        if switch_status is None:
            print "Fehler bei Statusabfrage bei push"
        return
        #print switch_status
        port.close
    except Exception as e:
        print ("Fehler beim push..: " + str(e))
        port.close


def fade_switch_(param):
    """fade_switch"""
    print "fade " + param
    switch_status = get_status("-s")
    if switch_status is None:
        print "Fehler bei Statusabfrage fuer fade"
        return
    switch_imput = read_switch_respond(switch_status)
    print "input"
    print switch_imput
    switch_fade_out = switch_imput + "-G"
    switch_fade_in = param + "+G"
    port = set_port()
    if not port:
        return
    try:
        #print "write"
        x = 1
        for x in range(18):
            port.write(switch_fade_out)
        time.sleep(0.5)
        x = 1
        for x in range(18):
            port.write(switch_fade_in)
        time.sleep(0.5)
        switch_status = get_status("-s")
        if switch_status is None:
            print "Fehler bei Statusabfrage fuer fade"
        return
        print switch_status
        port.close
    except Exception as e:
        print "xy1"
        print ("Fehler beim lesen..: " + str(e))
        port.close


def lets_rock(argv):
    """Hauptfunktion """
    print "lets_rock "
    valid_param = None

    try:
        opts, args = getopt.getopt(argv,
                            "hsp:f:",
                            ["help", "status", "push=", "fade="])
    except getopt.GetoptError:
        usage_help()
        sys.exit(2)
    for opt, arg in opts:
        if opt in ("-h", "--help"):
            valid_param = True
            usage_help()
            sys.exit()
        elif opt in ("-s", "--status"):
            valid_param = True
            get_status(arg)
            print arg
        elif opt in ("-p", "--push"):
            if arg != "":
                print arg
                valid_param = True
                push_switch_(arg)

        elif opt in ("-f", "--fade="):
            if arg != "":
                print arg
                valid_param = True
                fade_switch_(arg)

    # it seems, that no valid arg is given
    if valid_param is None:
        usage_help()


if __name__ == "__main__":
    #db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # losgehts
    #db.write_log_to_db(ac, ac.app_desc + " gestartet", "r")
    lets_rock(sys.argv[1:])

    # fertsch
    #db.write_log_to_db(ac, ac.app_desc + " gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
