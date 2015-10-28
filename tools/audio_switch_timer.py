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
#import getopt
import time
import datetime
import threading
import lib_common_1 as lib_cm
import lib_serial as lib_ser


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "023"
        self.app_desc = "Audio_Switch_Timer"
        self.app_errorfile = "error_audio_switch_timer.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append("Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append("Fehler beim Fade ")
        # using develop-params
        self.app_develop = "no"
        # display debugmessages on console yes or no: "no"
        # for normal usage set to no!!!!!!
        self.app_debug_mod = "no"
        # settings serial port


def load_switch_inputs():
    """load items for transmitting from db"""
    time_back = (datetime.datetime.now()
            + datetime.timedelta(seconds=- 3000))

    c_time_back = time_back.strftime("%Y-%m-%d %H:%M:%S")

    db_tbl = "USER_LOGS A "
    db_tbl_fields = ("A.USER_LOG_ID, A.USER_LOG_TIME, A.USER_LOG_ACTION, "
        "A.USER_LOG_ICON, A.USER_LOG_MODUL_ID ")
    db_tbl_condition = (
            "SUBSTRING( A.USER_LOG_TIME FROM 1 FOR 19) >= '"
            + c_time_back
            + "' AND A.USER_LOG_ACTION LIKE 'Sendequellen: %' "
            + " ORDER BY A.USER_LOG_ID")

    log_data = db.read_tbl_row_with_cond_log(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    #lib_cm.message_write_to_console(ac, log_data)
    return log_data


def read_audio_input():
    """read audio input"""
    switch_status = ser.get_status(ac, db, "I", "I")
    status_audio = ser.read_switch_respond(ac, db, switch_status)
    log_message = "Audio Input active: " + status_audio
    print log_message
    db.write_log_to_db_a(ac, log_message, "p",
                                                "write_also_to_console")


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
        time.sleep(0.1)
        # reset old input to 0dB
        ser.reset_gain(ac, db, switch_imput_old)
        #switch_status = ser.get_status(ac, db, "-s", "I")
        #if switch_status is None:
        #    return
        #print switch_status
        time.sleep(0.2)
        read_audio_input()
        port.close
    except Exception as e:
        db.write_log_to_db_a(ac, ac.app_errorslist[2] + str(e), "x",
            "write_also_to_console")
        port.close


def lets_rock():
    """Hauptfunktion """
    #db.write_log_to_db_a(ac, "lets_rock ", "t", "write_also_to_console")
    switch_inputs = load_switch_inputs()
    print switch_inputs[2][14:]
    return
    ac.timer = threading.Timer(1, lets_rock)
    ac.timer.start()
    time_now = datetime.datetime.now()
    print datetime.datetime.now()
    #if time_now.minute == 59:
    if time_now.second == 5:
        ac.switch_input = switch_inputs[2][14:16]
    #if time_now.minute == 04:
    if time_now.second == 5:
        ac.switch_input = switch_inputs[2][16:18]
    #if time_now.minute == 29:
    if time_now.second == 5:
        ac.switch_input = switch_inputs[2][18:20]


        print "finito"
        ac.timer.cancel()
        sys.exit()


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    ser = lib_ser.mySERIAL()
    log_message = ac.app_desc + " gestartet"
    #db.write_log_to_db_a(ac, log_message, "r", "write_also_to_console")
    # losgehts
    lets_rock()

    # fertsch
    #log_message = ac.app_desc + " gestoppt"
    #db.write_log_to_db_a(ac, log_message, "s", "write_also_to_console")
    sys.exit()
