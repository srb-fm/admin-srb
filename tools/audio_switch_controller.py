#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Controller Audio Switch
Autor: Joerg Sorge, based on SKS-Server from Jenz Loeffler
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge
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
import time
import lib_common_1 as lib_cm
import lib_serial as lib_ser


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "023"
        self.app_desc = "Audio_Switch_Controller"
        self.app_errorfile = "error_audio_switch_controller.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append("Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append("Fehler beim Push ")
        self.app_errorslist.append("Fehler beim Fade ")
        # using develop-params
        self.app_develop = "no"
        # display debugmessages on console yes or no: "no"
        # for normal usage set to no!!!!!!
        self.app_debug_mod = "no"
        # settings serial port


def usage_help():
    """pull out some help"""
    print "controller for audioswitch"
    print "usage:"
    print "controller_audio_switch.py -option value"
    print "valid options are:"
    print "-h --help"
    print "-s --status to display status"
    print "-l n --level n to display level for input n"
    print "-p n --push n to switch to input n"
    print "-f n --fade n to fade to input n"
    print "-g n --gain n to set gain to 0dB for input n"


def read_audio_input():
    """read audio input"""
    switch_status = ser.get_status(ac, db, "I", "I")
    status_audio = ser.read_switch_respond(ac, db, switch_status)
    log_message = "Audio Input active: " + status_audio
    print log_message
    db.write_log_to_db_a(ac, log_message, "p",
                                                "write_also_to_console")


def push_switch(param):
    """push switch"""
    port = ser.set_port(ac, db)
    if not port:
        return
    switch_cmd = param + "!"
    try:
        db.write_log_to_db_a(ac, "Switch to Input " + param, "e",
                                                "write_also_to_console")
        port.write(switch_cmd)
        time.sleep(0.1)
        read_audio_input()
        port.close
    except Exception as e:
        db.write_log_to_db_a(ac, ac.app_errorslist[1] + str(e), "x",
            "write_also_to_console")
        port.close


def fade_switch(param):
    """fade_switch"""
    #print "fade " + param
    switch_status = ser.get_status(ac, db, "-s", "I")
    if switch_status is None:
        return
    switch_imput_old = ser.read_switch_respond(ac, db, switch_status)
    switch_imput_new = param
    #print "input"
    #print switch_imput_old
    switch_fade_out = switch_imput_old + "-G"
    switch_fade_in = switch_imput_new + "+G"
    switch_to_input = switch_imput_new + "!"
    port = ser.set_port(ac, db)
    if not port:
        return
    try:
        log_message = ("Fade from Input " + switch_imput_old
                        + " to Input " + switch_imput_new)
        db.write_log_to_db_a(ac, log_message, "e", "write_also_to_console")
        # fade old input out
        x = 1
        for x in range(18):
            port.write(switch_fade_out)
            time.sleep(0.1)
        #print "gain switch_imput_old"
        ser.get_status(ac, db, switch_imput_old, "V" + switch_imput_old + "G")
        time.sleep(0.1)
        # reduce gain for new input
        port.write(switch_imput_new + "*-18g")
        time.sleep(0.1)
        #print "gain switch_imput_new"
        ser.get_status(ac, db, switch_imput_new, "V" + switch_imput_new + "G")
        # switch to new input
        port.write(switch_to_input)
        time.sleep(0.1)
        # fade new input in
        x = 1
        for x in range(18):
            port.write(switch_fade_in)
            time.sleep(0.1)
        # reset old input to 0dB
        ser.reset_gain(ac, db, switch_imput_old)
        switch_status = ser.get_status(ac, db, "-s", "I")
        if switch_status is None:
            return
        #print switch_status
        read_audio_input()
        port.close
    except Exception as e:
        db.write_log_to_db_a(ac, ac.app_errorslist[2] + str(e), "x",
            "write_also_to_console")
        port.close


def lets_rock(argv):
    """Hauptfunktion """
    #db.write_log_to_db_a(ac, "lets_rock ", "t", "write_also_to_console")
    valid_param = None

    try:
        opts, args = getopt.getopt(argv, "hsal:p:f:g:",
            ["help", "status", "audio", "level=", "push=", "fade=", "gain="])
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
            switch_status = ser.get_status(ac, db, arg, "I")
            log_message = "Status: " + ', '.join(switch_status)
            print log_message
            db.write_log_to_db_a(ac, log_message, "p",
                                             "write_also_to_console")

        elif opt in ("-a", "--audio"):
            valid_param = True
            read_audio_input()

        elif opt in ("-l", "--level="):
            if arg != "":
                log_message = "Level for Input: " + arg
                db.write_log_to_db_a(ac, log_message, "t",
                                                "write_also_to_console")
                print log_message
                valid_param = True
                ser.get_status(ac, db, arg, "V" + arg + "G")

        elif opt in ("-p", "--push"):
            if arg != "":
                valid_param = True
                push_switch(arg)

        elif opt in ("-f", "--fade="):
            if arg != "":
                valid_param = True
                fade_switch(arg)

        elif opt in ("-g", "--gain="):
            if arg != "":
                valid_param = True
                switch_status = ser.reset_gain(ac, db, arg)
                log_message = "Gain for Input: " + ', '.join(switch_status)
                print log_message
                db.write_log_to_db_a(ac, log_message, "p",
                                                "write_also_to_console")
    # it seems, that no valid arg is given
    if valid_param is None:
        usage_help()


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    ser = lib_ser.mySERIAL()
    log_message = ac.app_desc + " gestartet"
    db.write_log_to_db_a(ac, log_message, "r", "write_also_to_console")
    # losgehts
    lets_rock(sys.argv[1:])

    # fertsch
    log_message = ac.app_desc + " gestoppt"
    db.write_log_to_db_a(ac, log_message, "s", "write_also_to_console")
    sys.exit()
