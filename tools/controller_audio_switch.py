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


def get_status(param):
    """get status"""
    print "status " + param


def push_switch_(param):
    """push switch"""
    print "push " + param


def fade_switch_(param):
    """fade_switch"""
    print "fade " + param


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
