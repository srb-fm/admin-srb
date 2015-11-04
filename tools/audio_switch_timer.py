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
2015-11-26

Dieses Script schaltet den Audio-Switch per ser. Schnittstelle.
This script is for switching an audio switch via rs 232.

Dateiname Script: audio_switch_timer.py
Schluesselwort fuer Einstellungen: audio_switch
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank


Fehlerliste:
E 0 Parameter-Typ oder Inhalt stimmt nicht
E 01 Fehler beim Lesen Parameter Times
E 03 Fehler beim Fade

Parameterliste:
Param 1: Serial Port Server A
Param 2: Serial Port Server B
Param 3: Serial baudrate
Param 4: Serial bytesize
Param 5: Serial parity
Param 6: Serial stopbits
Param 7: Serial timeout

Dieses Script wird per crontab ein Minute vor den Schaltzeiten aufgerufen

"""

import sys
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
        self.app_id = "025"
        self.app_desc = "Audio_Switch_Timer"
        self.app_errorfile = "error_audio_switch_timer.log"
        # key for config in db
        self.app_config = "audio_switch"
        # amount parameter
        self.app_config_params_range = 7
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append("Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append("Fehler beim Lesen Parameter Times")
        self.app_errorslist.append("Fehler beim Fade ")
        # using develop-params
        self.app_develop = "no"
        # display debugmessages on console yes or no: "no"
        # for normal usage set to no!!!!!!
        self.app_debug_mod = "no"
        # settings serial port


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
    # extern tools
    ext_params_ok = lib_cm.params_provide_server_settings(ac, db)
    if ext_params_ok is None:
        return None
    lib_cm.set_server(ac, db)

    # Times
    db.ac_config_times = db.params_load_1a(ac, db, "PO_Time_Config_1")
    if db.ac_config_times is not None:
        # Erweiterte Paramsliste anlegen
        app_params_type_list_times = []
        # Erweiterte Params-Type-List,
        # Typ entsprechend der Params-Liste in der Config
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        # check extend. params
        param_check_times = lib_cm.params_check_a(
                        ac, db, 8,
                        app_params_type_list_times,
                        db.ac_config_times)
        if param_check_times is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
            return None
    return ext_params_ok


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

    return log_data


def fade_switch(param):
    """fade_switch"""
    switch_status = ser.get_status(ac, db, "-s", "I")
    if switch_status is None:
        return
    switch_imput_old = ser.read_switch_respond(ac, db, switch_status)
    switch_imput_new = param
    switch_fade_out = switch_imput_old + "-G"
    switch_fade_in = switch_imput_new + "+G"
    switch_to_input = switch_imput_new + "!"
    if switch_imput_old == switch_imput_new:
        log_message = ("Kein fade noetig, Input "
                                + switch_imput_new + " bereits aktiv")
        db.write_log_to_db_a(ac, log_message, "e", "write_also_to_console")
        return
    port = ser.set_port(ac, db)
    if not port:
        return
    try:
        log_message = ("Fade from Input " + switch_imput_old
                        + " to Input " + switch_imput_new)
        db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
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
        time.sleep(0.2)
        port.close
        log_message = "Sendequelle " + switch_imput_new + " eingeblendet"
        db.write_log_to_db_a(ac, log_message, "n", "write_also_to_console")
    except Exception as e:
        db.write_log_to_db_a(ac, ac.app_errorslist[3] + str(e), "x",
            "write_also_to_console")
        port.close


def lets_rock():
    """main function """

    ac.timer = threading.Timer(1, lets_rock)
    ac.timer.start()
    time_now = datetime.datetime.now()

    if time_now.minute == 59:
        if time_now.second == 15:
            ac.switch_input_new = ac.switch_inputs[2][15:16]
            log_message = "Load Input for top of the hour"
            db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
    #if time_now.minute == 04:
    if time_now.minute == int(db.ac_config_times[4]) - 1:
        if time_now.second == 15:
            ac.switch_input_new = ac.switch_inputs[2][17:18]
            log_message = "Load Input for Switchtime 2"
            db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
    #if time_now.minute == 29:
    if time_now.minute == int(db.ac_config_times[5]) - 1:
        if time_now.second == 15:
            ac.switch_input_new = ac.switch_inputs[2][19:20]
            log_message = "Load Input for Switchtime 3"
            db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    if time_now.second == 35:
        log_message = "Input " + ac.switch_input_new + " vorgesehen"
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    if time_now.second == 57:
        fade_switch(ac.switch_input_new)

    if datetime.datetime.now() >= ac.time_forward:
        #print "finito"
        ac.timer.cancel()
        log_message = ac.app_desc + " gestoppt"
        db.write_log_to_db_a(ac, log_message, "s", "write_also_to_console")
        return


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    ser = lib_ser.mySERIAL()
    log_message = ac.app_desc + " gestartet"
    db.write_log_to_db_a(ac, log_message, "r", "write_also_to_console")
    # losgehts
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
         # alles ok: weiter
        if param_check is not None:
            # losgehts
            # extendet params
            load_extended_params_ok = load_extended_params()
            if load_extended_params_ok is None:
                log_message = ac.app_desc + " gestoppt"
                db.write_log_to_db_a(ac, log_message, "s",
                                            "write_also_to_console")
                sys.exit()
            # check if serial port is a port number or an string
            if ac.server_active == "A":
                if db.ac_config_1[1].isdigit():
                    ac.ser_port = int(db.ac_config_1[1])
                else:
                    ac.ser_port = db.ac_config_1[1]
            if ac.server_active == "B":
                if db.ac_config_1[2].isdigit():
                    ac.ser_port = int(db.ac_config_1[2])
                else:
                    ac.ser_port = db.ac_config_1[2]

            switch_status = ser.get_status(ac, db, "-s", "I")
            if not switch_status:
                log_message = ac.app_desc + " gestoppt"
                db.write_log_to_db_a(ac, log_message, "s",
                                            "write_also_to_console")
                sys.exit()
    ac.time_forward = datetime.datetime.now() + datetime.timedelta(seconds=+ 70)
    ac.switch_inputs = load_switch_inputs()
    db.write_log_to_db_a(ac, "Starting timer", "t", "write_also_to_console")
    lets_rock()
    sys.exit()
