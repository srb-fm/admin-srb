#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Live Recorder
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at guugell
2011-10-06

Dieses Script ermittelt Live-Sendungen
und registriert sie in einer Config-Datei,
sodass der Live-Recorder einen Mitschnitt anfertigen kann.
Der Live-Recorder (jack_capture) laeuft auf dem Streamrechner.

Ablauf:
Werden von diesem Script Sendungen zum Mitschneiden in der db gefunden,
wird die Datei live_recording_conf.sh mit Angaben ueber Dauer, Sendezeit
Autor und Titel gespeichert.
Das per cron stuendlich ausgefuehrte live_recording.sh sucht nach
live_recording_conf.sh um die Aufnahme zu starten.

Hinweis:
Mitschnitte werden mit der Sendungs-Nummer im log registriert.
Sendungen, die einmal fuer den Mitschnitt registriert wurden,
koennen nicht nocheinmal mitgeschnitten werden.
(Wichtig, falls mal eine Sendung in der Zeit verschoben wird
und bereits von der ersten Zeit ein Mitschnitt laeuft)


Dateiname Script: live_record.py
Keine weiteren Einstellungen noetig.
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank

Hinweise:
Der Mitschnitt selber erfolgt durch das Script live_recording.sh
auf dem Stream-Rechner.

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nicht
Error 001 beim Schreiben der Recorder-Config-Datei

Parameterliste:
Param 1: On/Off Switch
Param 2: Max Stunden fuer zusammenhaengende Sendungen (z.B. 04)
Param 3: Sekunden Aufnahmebeginn vor Sendung (30)
Param 4: Sekunden Aufnahmeende nach Sendung (30)
Param 5: Pfad/Dateiname Configdatei fuer jack_capture
        (ohne /home/user des servers)
Param 6: Pfad fuer Recordings (ohne /home/user des stream-servers)

Extern Parameters:
ext_tools
server_settings
server_settings_paths_a
server_settings_paths_b

Dieses Script wird zeitgesteuert jede Stunde zur 58. Minute ausgefuehrt.
Das Script live_recording.sh wird jede Stunde zur 59. Minute
auf dem Streamserver ausgefuehrt.

"""

import sys
import socket
import datetime
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "019"
        self.app_desc = u"Live_Record_Preparation"
        # key for config in db
        self.app_config = u"Live_Record_Preparation"
        self.app_config_develop = u"Live_Recording_e"
        self.app_errorfile = "error_live_recording.log"
        self.app_develop = "no"
        # show messages on console
        self.app_debug_mod = "yes"
        self.app_windows = "no"
        # number of params
        self.app_config_params_range = 7
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "beim Schreiben der Recorder-Config-Datei ")
        # time for schow-search: just now
        #self.time_target = datetime.datetime.now() + datetime.timedelta()
        # ab naechste stunde
        self.time_target = (datetime.datetime.now()
                            + datetime.timedelta(hours=+1))


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
    # extern tools
    ext_params_ok = lib_cm.params_provide_tools(ac, db)
    if ext_params_ok is None:
        return None
    ext_params_ok = lib_cm.params_provide_server_settings(ac, db)
    if ext_params_ok is None:
        return None
    lib_cm.set_server(ac, db)
    ext_params_ok = lib_cm.params_provide_server_paths_a(ac, db,
                                                        ac.server_active)
    if ext_params_ok is None:
        return None
    ext_params_ok = lib_cm.params_server_active(ac, db)
    if ext_params_ok is None:
        return None
    return ext_params_ok


def load_live_sendungen(up_to_target_hour, live):
    """
    search for live-shows for the upcomming hour
    """
    lib_cm.message_write_to_console(ac, "load_live_sendungen")
    # zfill. filling with 0 if hour under 10

    c_date_time_from = (str(ac.time_target.date()) + " "
                        + str(ac.time_target.hour).zfill(2))
    c_date_time_to = (str(ac.time_target.date()) + " "
                        + str(ac.time_target.hour + up_to_target_hour).zfill(2))
    if live is True:
        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) >= '"
            + c_date_time_from + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) <= '"
            + c_date_time_to + "' "
            "AND A.SG_HF_INFOTIME='F' AND A.SG_HF_MAGAZINE='F' "
            "AND A.SG_HF_LIVE='T'"
            )
    else:
        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) >= '"
            + c_date_time_from + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) <= '"
            + c_date_time_to + "' "
            "AND A.SG_HF_INFOTIME='F' AND A.SG_HF_MAGAZINE='F'"
            )

    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_a(ac,
                                        db, db_tbl_condition)
    if sendung_data is None:
        log_message = (u"Keine Live-Sendungen fuer: "
                       + c_date_time_from + u" bis " + c_date_time_to)
    else:
        log_message = (u"Live-Sendungen vorhanden von: "
                       + c_date_time_from + u" bis " + c_date_time_to + " Uhr")

    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
    return sendung_data


def check_live_sendungen(sg_id):
    """
    check if  show is live
    """
    lib_cm.message_write_to_console(ac, "check_live_sendungen")
    # zfill. filling with 0 if hour under 10

    db_tbl_condition = ("A.SG_HF_ID= '" + sg_id + "' AND A.SG_HF_LIVE='T'")

    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_a(ac,
                                        db, db_tbl_condition)
    if sendung_data is None:
        log_message = (u"Keine Live-Sendungen: " + sg_id)
    else:
        log_message = (u"Live-Sendungen: " + sg_id)

    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
    return sendung_data


def check_recording(sg_id):
    """search for currently running recordings"""
    c_date_time_from = (str(ac.time_target.date()) + " "
                        + str(ac.time_target.hour - 4).zfill(2))
    db_tbl_condition = ("USER_LOG_ACTION = 'Live-Sendung: " + str(sg_id)
                + "' AND SUBSTRING(USER_LOG_TIME FROM 1 FOR 13) >= '"
                + c_date_time_from + "' ")
    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, "USER_LOGS", "USER_LOG_ACTION", db_tbl_condition)

    if log_data is None:
        return None
    else:
        for item in log_data:
            db.write_log_to_db(ac, u"Mitschnitt laeuft: " + item[0], "t")
        return log_data


def log_duration(list_live_sendungen):
    """Log recordings and calc length"""
    duration = 0
    title = list_live_sendungen[0][11]
    for item in list_live_sendungen:
        if item[11] == title:
            live = check_live_sendungen(str(list_live_sendungen[0][0]))
            # sum duration
            if live is None:
                # it's not live, so we must go away here
                break
            duration += lib_cm.get_seconds_from_tstr(item[3])
            lib_cm.message_write_to_console(ac, duration)
            # logging
            db.write_log_to_db(ac,
            u"Mitschnitt vorbereiten: "
            + str(item[2]) + u" - "
            + lib_cm.replace_uchar_sonderzeichen_with_latein(item[15])
            + u" - "
            + lib_cm.replace_uchar_sonderzeichen_with_latein(item[11]), "t")
            db.write_log_to_db(ac, u"Live-Sendung: " + str(item[0]), "t")
        else:
            break
    return duration


def write_to_file_record_params(r_duration, list_live_sendungen):
    """write configfile for recorder"""
    log_message = ("Laenge des Mitschnitts: " + str(r_duration) + "Sekunden")
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    c_date_time_from = (str(ac.time_target.date()) + "_"
                        + str(ac.time_target.hour).zfill(2))
    file_recording_config = ac.app_homepath + db.ac_config_1[5]
    r_name = lib_cm.replace_uchar_sonderzeichen_with_latein(
                        list_live_sendungen[0][15]).replace(" ", "")
    r_title = lib_cm.replace_uchar_sonderzeichen_with_latein(
                        list_live_sendungen[0][11][0:8]).replace(" ", "")

    # home path of active stream-server
    if db.ac_config_server_active[3] == "A":
        r_path = ("/home/" + db.ac_config_servset[9]
                        + lib_cm.check_slashes(ac, db.ac_config_1[6]))
    if db.ac_config_server_active[3] == "B":
        r_path = ("/home/" + db.ac_config_servset[10]
                        + lib_cm.check_slashes(ac, db.ac_config_1[6]))
    r_path_file = (r_path + c_date_time_from + "_" + r_name + "_"
                                                    + r_title + ".wav")
    # startdelay when transmitting begins not with 0 minute and 0 sec
    r_wait = list_live_sendungen[0][2].minute * 60
    r_wait += list_live_sendungen[0][2].second
    # begin record a few seconds before transmitt begins
    r_wait -= int(db.ac_config_1[3])
    # crotab starts record script in minute 59, so 60 sec must added
    r_wait += 60

    try:
        f_r_conf = open(file_recording_config, 'w')
    except IOError as (errno, strerror):
        log_message = ("write_to_file_record_params: I/O error({0}): {1}"
                        .format(errno, strerror) + ": " + file_recording_config)
        db.write_log_to_db(ac, log_message, "x")
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
                                             "write_also_to_console")
    else:
        f_r_conf.write("#!/bin/bash\n")
        f_r_conf.write('r_wait=' + str(r_wait) + '\n')
        f_r_conf.write('r_duration=' + str(r_duration) + '\n')
        f_r_conf.write('r_filename="' + r_path_file + '"\n')
        f_r_conf.close

        log_message = ("Datei für Live-Recorder geschrieben")
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        log_message = (u"Mitschnitt vorbereitet: "
        + lib_cm.replace_uchar_sonderzeichen_with_latein(
            list_live_sendungen[0][15])
        + " "
        + lib_cm.replace_uchar_sonderzeichen_with_latein(
            list_live_sendungen[0][11])
        + " Laenge: " + str(r_duration / 60) + " Minuten")
        db.write_log_to_db(ac, log_message, "i")
        db.write_log_to_db(ac, log_message, "n")


def lets_rock():
    """mainfunktion """
    print "lets_rock "
    # extendet params
    load_extended_params_ok = load_extended_params()
    if load_extended_params_ok is None:
        return
    # prepare path
    ac.app_homepath = "/home/" + socket.gethostname()

    # load live-show for the upcomming hour
    list_live_sendung = load_live_sendungen(0, True)
    if list_live_sendung is not None:
        # check if show is always recorded
        recording_now = check_recording(list_live_sendung[0][0])
        if recording_now is None:
            # load all shows for the upcomming hours
            list_live_sendungen = load_live_sendungen(
                                        int(db.ac_config_1[2]) - 1, False)
            if list_live_sendungen is not None:
                duration = log_duration(list_live_sendungen)
                # add duration with pre-rec-seconds
                duration += int(db.ac_config_1[3])
                # add duration with x sec for the end
                duration += int(db.ac_config_1[4])
                write_to_file_record_params(duration, list_live_sendungen)

if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # let's start'
    db.write_log_to_db(ac, ac.app_desc + " gestartet", "r")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # roger, continue
        if param_check is not None:
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, "Live-Recording ausgeschaltet", "e",
                    "write_also_to_console")

    # finish
    db.write_log_to_db(ac, ac.app_desc + " gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
