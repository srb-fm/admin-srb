#!/usr/bin/env python
# -*- coding: utf-8 -*-


import sys
import codecs
import string
#import re
import datetime
import os
import random
from mutagen.mp3 import MP3
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "009"
        self.app_desc = "play_out_loader"
        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
        self.app_windows = "no"
        # schluessel fuer config in db
        self.app_config = "PO_Loader"
        self.app_config_develop = "PO_Loader"
        self.app_errorfile = "error_play_out_loader.log"
        self.app_config_params_range = 6
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "beim Lesen Parameter mAirlist")
        self.app_errorslist.append(u"Error 002 "
            "beim Lesen Parameter InfoTime Pfade")
        self.app_errorslist.append(u"Error 003 "
            "beim Lesen Parameter Times")
        self.app_errorslist.append(u"Error 004 "
            "beim Lesen Parameter Zeitansage")
        self.app_errorslist.append(u"Error 005 "
            "beim Lesen Parameter Audioswitch")
        self.app_errorslist.append(u"Error 006 "
            "beim Loeschen der Playlist")
        self.app_errorslist.append(u"Error 007 "
            "beim Schreiben der Playlist")
        self.app_errorslist.append(u"Error 008 "
            "beim Schreiben der Audio-Switch Steuerdatei")
        self.app_errorslist.append(u"Error 009 "
            "beim Lesen der Zeitansage")
        self.app_errorslist.append(u"Error 010 "
            "beim Lesen des Jingles")
        self.app_errorslist.append(u"Error 011 "
            "beim Lesen des Instrumentals")
        self.app_errorslist.append(u"Error 012 "
            "beim Lesen der Laenge von Instrumentals")
        self.app_errorslist.append(u"Error 013 "
            "beim Loeschen der Magazin-Playlist")
        # IT senden
        self.po_it = None
        # Mags senden
        self.po_mg = None
        self.po_mg_list = []
        self.po_mg_list.append(None)
        self.po_mg_list.append(None)
        self.po_mg_list.append(None)
        # Instrumental senden
        self.po_instrumental = None
        # Switch Schaltpunkte
        self.po_switch = []
        self.po_switch.append("03")
        self.po_switch.append("03")
        self.po_switch.append("03")
        # Liste Infotime-Items
        self.po_it_pl = []
        # Laenge InfoTime
        self.po_it_duration = 0
        # aktuelle Stunde
        #self.time_target = datetime.datetime.now()
        # kommende stunde
        self.time_target = datetime.datetime.now() + datetime.timedelta(hours=1)


def load_extended_params():
    """Erweiterte Params laden"""
    # mairlist
    db.ac_config_mairlist = db.params_load_1a(ac, db, "PO_mAirList_PL_3")
    if db.ac_config_mairlist is not None:
        # Erweiterte Paramsliste anlegen
        app_params_type_list_mairlist = []
        # Erweiterte Params-Type-List,
        # Typ entsprechend der Params-Liste in der Config
        app_params_type_list_mairlist.append("p_string")
        app_params_type_list_mairlist.append("p_string")
        app_params_type_list_mairlist.append("p_string")
        app_params_type_list_mairlist.append("p_string")
        app_params_type_list_mairlist.append("p_string")
        app_params_type_list_mairlist.append("p_string")
        # Erweiterte Params pruefen
        param_check_mairlist = lib_cm.params_check_a(
                        ac, db, 6,
                        app_params_type_list_mairlist,
                        db.ac_config_mairlist)
        if param_check_mairlist is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
            return None

    # InfoTime-Pfade
    db.ac_config_it_paths = db.params_load_1a(
                            ac, db, "PO_Info_Time_Config_Paths")
    if db.ac_config_it_paths is not None:
        # Erweiterte Paramsliste anlegen
        app_params_type_list_it_paths = []
        # Erweiterte Params-Type-List,
        # Typ entsprechend der Params-Liste in der Config
        app_params_type_list_it_paths.append("p_string")
        app_params_type_list_it_paths.append("p_string")
        app_params_type_list_it_paths.append("p_string")
        app_params_type_list_it_paths.append("p_string")
        app_params_type_list_it_paths.append("p_string")
        app_params_type_list_it_paths.append("p_string")
        # Erweiterte Params pruefen
        param_check_it_paths = lib_cm.params_check_a(
                        ac, db, 6,
                        app_params_type_list_it_paths,
                        db.ac_config_it_paths)
        if param_check_it_paths is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
            return None

    # Times
    db.ac_config_times = db.params_load_1a(
                            ac, db, "PO_Time_Config_1")
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
        # Erweiterte Params pruefen
        param_check_it_paths = lib_cm.params_check_a(
                        ac, db, 8,
                        app_params_type_list_times,
                        db.ac_config_times)
        if param_check_it_paths is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
            "write_also_to_console")
            return None

    # Zeitansage
    db.ac_config_zeitansage = db.params_load_1a(
                            ac, db, "PO_Zeitansage_Config_3")
    if db.ac_config_zeitansage is not None:
        # Erweiterte Paramsliste anlegen
        app_params_type_list_zeitansage = []
        # Erweiterte Params-Type-List,
        # Typ entsprechend der Params-Liste in der Config
        app_params_type_list_zeitansage.append("p_string")
        app_params_type_list_zeitansage.append("p_string")
        app_params_type_list_zeitansage.append("p_string")
        app_params_type_list_zeitansage.append("p_string")
        # Erweiterte Params pruefen
        param_check_it_paths = lib_cm.params_check_a(
                        ac, db, 4,
                        app_params_type_list_zeitansage,
                        db.ac_config_zeitansage)
        if param_check_it_paths is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
            "write_also_to_console")
            return None

    # Audioswitch
    db.ac_config_audioswitch = db.params_load_1a(
                            ac, db, "PO_Switch_Broadcast_Config_3")
    if db.ac_config_audioswitch is not None:
        # Erweiterte Paramsliste anlegen
        app_params_type_list_audioswitch = []
        # Erweiterte Params-Type-List,
        # Typ entsprechend der Params-Liste in der Config
        app_params_type_list_audioswitch.append("p_string")
        # Erweiterte Params pruefen
        param_check_it_paths = lib_cm.params_check_a(
                        ac, db, 1,
                        app_params_type_list_audioswitch,
                        db.ac_config_audioswitch)
        if param_check_it_paths is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[5], "x",
            "write_also_to_console")
            return None
    return "ok"


def load_broadcast(minute_start, minute_end):
    """Sendungen aus db holen"""

    list_sendung_filename = []
    list_sendung_duration = []
    list_sendung_source = []
    int_sum_duration = 0
    list_sendung_title = []

    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' AND "
            "SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
            + str(ac.time_target.date()) + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
            + str(ac.time_target.hour).zfill(2) + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 15 FOR 2) >= '"
            + minute_start + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 15 FOR 2) <= '"
            + minute_end + "' "
            "AND A.SG_HF_INFOTIME='F' AND A.SG_HF_MAGAZINE='F' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond(ac,
                                            db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Sendungen für: " + str(ac.time_target.date())
                     + " " + str(ac.time_target.hour) + ":" + minute_start)
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        list_sendung_filename.append("nix")
        list_sendung_source.append("nix")
        list_result = [list_sendung_filename, int_sum_duration,
                            list_sendung_source]
        lib_cm.message_write_to_console(ac, "load_broadcast list_result: ")
        lib_cm.message_write_to_console(ac, list_result)
        return list_result

    # in List schreiben
    log_message = (u"Sendungen vorhanden für: " + str(ac.time_target.date())
                     + " " + str(ac.time_target.hour) + ":" + minute_start)
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    for row in sendung_data:
        hh = row[3][0:2]
        mm = row[3][3:5]
        ss = row[3][6:8]

        int_sum_duration = (int(hh) * 60 * 60) + (int(mm) * 60) + int(ss)
        # list mit filename und sekunden
        list_sendung_filename.append(row[12])
        list_sendung_source.append(row[7])
        list_sendung_duration.append(int_sum_duration)
        list_sendung_title.append(row[11])

    int_sum_duration = int_sum_duration / 60
    list_result = [list_sendung_filename, list_sendung_duration,
         int_sum_duration, list_sendung_source, list_sendung_title]
    lib_cm.message_write_to_console(ac, "load_broadcast list_result: ")
    lib_cm.message_write_to_console(ac, list_result)
    return list_result


def load_infotime(sende_stunde_start):
    """InfoTime-Sendungen aus db holen"""
    list_sendung_filename = []
    list_sendung_duration = []
    int_sum_duration = 0

    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' AND "
            "SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
            + str(ac.time_target.date()) + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
            + sende_stunde_start + "' "
            "AND A.SG_HF_INFOTIME='T' AND A.SG_HF_MAGAZINE='F' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond(ac,
                                                 db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine IT-Sendungen für: "
                 + str(ac.time_target.date()) + " " + str(ac.time_target.hour))
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        list_sendung_filename.append("nix")
        #list_result mit Laenge IT 0 Minuten:
        list_result = [list_sendung_filename, 0, int_sum_duration]
        lib_cm.message_write_to_console(ac, "load_infotime list_result: ")
        lib_cm.message_write_to_console(ac, list_result)
        return list_result

    # in List schreiben
    log_message = (u"IT-Sendungen vorhanden für: " + str(ac.time_target.date())
                     + " " + str(ac.time_target.hour))
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    for row in sendung_data:
        #print row
        hh = row[3][0:2]
        mm = row[3][3:5]
        ss = row[3][6:8]

        int_sum_duration = (int(hh) * 60 * 60) + (int(mm) * 60) + int(ss)
        # list mit filename und sekunden
        list_sendung_filename.append(row[12])
        list_sendung_duration.append(int_sum_duration)

    int_sum_duration = int_sum_duration / 60
    list_result = [list_sendung_filename, list_sendung_duration,
         int_sum_duration]
    lib_cm.message_write_to_console(ac, "load_infotime list_result: ")
    lib_cm.message_write_to_console(ac, list_result)
    return list_result


def rock_sendung(minute_start, minute_end):
    """SENDUNGEN abarbeiten"""
    # Sendungen aus db holen
    lib_cm.message_write_to_console(ac, minute_start + minute_end)
    list_result = load_broadcast(minute_start, minute_end)

    # Volle Stunde entscheidet ueber IT
    if minute_start == "00":
        if list_result[0][0] == "nix":
            # Wenn keine Sendungen vorhanden, dann IT!
            ac.po_it = True
            log_message = "Infotime vorsehen!"
            db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
            # Wenn keine Sendungen vorhanden, dann MAG!
            ac.po_mg = True
            ac.po_mg_list[0] = True
            ac.po_mg_list[1] = True
            ac.po_mg_list[2] = True
            log_message = "Magazin komplett vorsehen!"
            db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        else:
            # Quelle fuer Switch einstellen
            # Quelle befindet sich an Postition 0
            # der vierten liste [3] innerhalb der liste 'list_result'
            ac.po_switch[0] = list_result[3][0][:2]
            ac.po_switch[1] = list_result[3][0][:2]
            ac.po_switch[2] = list_result[3][0][:2]
            # Mag nur in Abhaegigkeit der Laenge der Sendungen
            if list_result[2] < 5:
                ac.po_mg = True
                ac.po_mg_list[0] = True
                log_message = "Magazin 1 vorsehen!"
                db.write_log_to_db_a(ac, log_message,
                                         "t", "write_also_to_console")
            if list_result[2] < 25:
                ac.po_mg = True
                ac.po_mg_list[1] = True
                log_message = "Magazin 2 vorsehen!"
                db.write_log_to_db_a(ac, log_message,
                                         "t", "write_also_to_console")
            if list_result[2] < 35:
                ac.po_mg = True
                ac.po_mg_list[2] = True
                log_message = "Magazin 3 vorsehen!"
                db.write_log_to_db_a(ac, log_message,
                                         "t", "write_also_to_console")

    if minute_start > "00" and minute_start < "30":
        # Quelle fuer Switch 2 und 3 einstellen
        # Quelle befindet sich an Postition 0
        # der vierten liste [3] innerhalb der liste 'list_result'
        if list_result[0][0] != "nix":
            ac.po_switch[1] = list_result[3][0][:2]
            ac.po_switch[2] = list_result[3][0][:2]
            # Instrumental nach IT senden
            ac.po_instrumental = True
            # Mag nur in Abhaegigkeit der Laenge der Sendungen
            if list_result[2] < (20 - int(minute_start)):
                ac.po_mg = True
                ac.po_mg_list[1] = True
            else:
                ac.po_mg_list[1] = None
        else:
            # Wenn keine Sendungen vorhanden, dann MAG!
            ac.po_mg = True
            ac.po_mg_list[1] = True

    if minute_start == "30":
        # Quelle fuer Switch 3 einstellen
        # Quelle befindet sich an Postition 0
        # der vierten liste [3] innerhalb der liste 'list_result'
        if list_result[0][0] != "nix":
            ac.po_switch[2] = list_result[3][0][:2]
            # Mag nur in Abhaegigkeit der Laenge der Sendungen
            if list_result[2] < (20 - int(minute_start)):
                ac.po_mg = True
                ac.po_mg_list[2] = True
            else:
                ac.po_mg_list[2] = None
        else:
            # Wenn keine Sendungen vorhanden, dann MAG!
            ac.po_mg = True
            ac.po_mg_list[2] = True

    # PL schreiben
    prepare_pl_broadcast(minute_start, list_result)


def prepare_pl_broadcast(minute_start, list_result):
    """Playlist fuer Sendungen schreiben"""

    if minute_start == "00":
        nZ_po_switch = 0
        path_filename = db.ac_config_mairlist[1] + "_00.m3u"
    if minute_start > "00" and minute_start < "30":
        nZ_po_switch = 1
        path_filename = db.ac_config_mairlist[1] + "_" + minute_start + ".m3u"
    if minute_start == "30":
        nZ_po_switch = 2
        path_filename = db.ac_config_mairlist[1] + "_30.m3u"

    # Playlist loeschen
    lib_cm.message_write_to_console(ac, path_filename)
    delete_pl_ok = lib_cm.erase_file_a(ac, db, path_filename,
        u"Playlist geloescht ")
    if delete_pl_ok is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[6], "x",
                                             "write_also_to_console")
    if list_result[0][0] == "nix":
        log_message = "Keine normale Sendung! keine PL schreiben!"
        lib_cm.message_write_to_console(ac, log_message)
        return

    # PL nur schreiben wenn auch pl benoetigt wird
    if len(list_result[0]) == 1 and ac.po_switch[nZ_po_switch] != "03":
        log_message = (u"Sendung nicht via Play-Out,"
                " keine PL geschrieben: " + "".join(list_result[4]))
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        db.write_log_to_db(ac, "".join(list_result[4]), "i")
    else:
        write_to_file_playlist(path_filename, list_result[0], list_result[1])


def write_to_file_playlist(path_filename,
                         list_sendung_filename, list_sendung_duration):
    """Playlist Sendung schreiben"""
    #print "write_to_file_playlist_broadcast: "
    # + filename.encode('ascii', 'ignore')
    #print list_sendung_filename
    #db.write_log_to_db(ac, "Play_Out_Load write", "a")

    try:
        if (ac.app_windows == "yes"):
            f_playlist = codecs.open(path_filename, 'w', "iso-8859-1")
        else:
            f_playlist = codecs.open(path_filename, 'w', "utf-8")

    except IOError as (errno, strerror):
        log_message = ("write_to_file_playlist_broadcast: I/O error({0}): {1}"
        .format(errno, strerror) + ": "
         + path_filename.encode('ascii', 'ignore'))
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        db.write_log_to_db_a(ac, ac.app_errorslist[7], "x",
                                             "write_also_to_console")
        return

    path_mediafile = lib_cm.check_slashes(ac, db.ac_config_mairlist[3])

    z = 0
    sendung = ""
    action_msg = ""
    for item in list_sendung_filename:
        sendung = item
        if item[0:7] == "http://":
            f_playlist.write("#mAirList STREAM "
                 + str(list_sendung_duration[z]) + " [] " + item + "\r\n")
            action_msg = item[8:-1]
        elif item[0:11] == "XXno_pathXX":
            # pfad uebernehmen
            # prefix und windows-zeilenumbruch (aus win-playlisten) wegnehmen
            sendung = item[11:-1]
            action_msg = item[11:-1]
            f_playlist.write(sendung + "\r\n")

        elif item[0:11] == "YYno_pathYY":
            # pfad uebernehmen
            # prefix wegnehmen
            sendung = item[11:len(item)]
            action_msg = item[11:len(item)]
            f_playlist.write(sendung + "\r\n")

        else:
            # pfad voranstellen
            f_playlist.write(path_mediafile + sendung + "\r\n")
            action_msg = item

        log_message = "In Playlist aufgenommen: " + sendung
        db.write_log_to_db(ac, log_message, "k")

        # Einige Eintraege fuer Info-Meldung uebergehen
        #waste = None
        #if string.find(action_msg, "Zeitansage") != -1:
        #    waste = True
        #if string.find(action_msg, "SRB_Jingles") != -1:
        #    waste = True
        #if string.find(action_msg, "Instrumental") != -1:
        #    waste = True
        #if waste is None:
        db.write_log_to_db(ac, action_msg, "i")
        z += 1
    f_playlist.close


def write_to_file_playlist_it(path_filename, list_sendung_filename):
    """Playlist InfoTime schreiben"""
    try:
        if (ac.app_windows == "yes"):
            f_playlist = codecs.open(path_filename, 'w', "iso-8859-1")
        else:
            f_playlist = codecs.open(path_filename, 'w', "utf-8")

    except IOError as (errno, strerror):
        log_message = ("write_to_file_playlist_infotime: I/O error({0}): {1}"
        .format(errno, strerror) + ": "
         + path_filename.encode('ascii', 'ignore'))
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        db.write_log_to_db_a(ac, ac.app_errorslist[7], "x",
                                             "write_also_to_console")
        return

    path_mediafile = lib_cm.check_slashes(ac, db.ac_config_mairlist[3])

    z = 0
    sendung = ""
    action_msg = ""
    for item in list_sendung_filename:
        sendung = item
        if item[0:11] == "XXno_pathXX":
            # pfad uebernehmen
            # prefix und windows-zeilenumbruch (aus win-playlisten) wegnehmen
            sendung = item[11:-1]
            action_msg = item[11:-1]
            f_playlist.write(sendung + "\r\n")

        elif item[0:11] == "YYno_pathYY":
            # pfad uebernehmen
            # prefix wegnehmen
            sendung = item[11:len(item)]
            action_msg = item[11:len(item)]
            f_playlist.write(sendung + "\r\n")

        else:
            # pfad voranstellen
            f_playlist.write(path_mediafile + sendung + "\r\n")
            action_msg = item

        log_message = "In Playlist aufgenommen: " + sendung
        db.write_log_to_db(ac, log_message, "k")

        # Einige Eintraege fuer Info-Meldung uebergehen
        waste = None
        if string.find(action_msg, "Zeitansage") != -1:
            waste = True
        if string.find(action_msg, "SRB_Jingles") != -1:
            waste = True
        if string.find(action_msg, "Instrumental") != -1:
            waste = True
        if waste is None:
            db.write_log_to_db(ac, action_msg, "i")
        z += 1
    f_playlist.close


def write_to_file_playlist_mg(path_file_pl, path_file_mg):
    """Playlist Magazin schreiben"""
    try:
        if (ac.app_windows == "yes"):
            f_playlist = codecs.open(path_file_pl, 'w', "iso-8859-1")
        else:
            f_playlist = codecs.open(path_file_pl, 'w', "utf-8")

    except IOError as (errno, strerror):
        log_message = ("write_to_file_playlist_magazin: I/O error({0}): {1}"
        .format(errno, strerror) + ": "
         + path_file_pl.encode('ascii', 'ignore'))
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        db.write_log_to_db_a(ac, ac.app_errorslist[7], "x",
                                             "write_also_to_console")
        return
    f_playlist.write(path_file_mg + "\r\n")
    f_playlist.close


def write_to_file_switch_params():
    """Switch Steuerdatei schreiben"""
    log_message = ("Switch 1-3: " + ac.po_switch[0]
                         + ac.po_switch[1] + ac.po_switch[2])
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    file_audio_switch = db.ac_config_audioswitch[1]
    data_audio_switch = read_from_file_lines_in_list(file_audio_switch)
    if data_audio_switch is None:
        log_message = (u"Datei für Sendequellenumschalter "
                    "kann nicht gelesen werden: " + file_audio_switch)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")

    try:
        f_switch = open(file_audio_switch, 'w')
    except IOError as (errno, strerror):
        log_message = ("write_to_file_switch_params: I/O error({0}): {1}"
                        .format(errno, strerror) + ": " + file_audio_switch)
        db.write_log_to_db(ac, log_message, "x")
        db.write_log_to_db_a(ac, ac.app_errorslist[8], "x",
                                             "write_also_to_console")
    else:
        f_switch.write(str(ac.time_target.hour).zfill(2)
                                 + ":00 #" + ac.po_switch[0] + "\r\n")
        minute_zweiter_schaltpunkt = db.ac_config_times[4]
        f_switch.write(str(ac.time_target.hour).zfill(2)
        + ":" + minute_zweiter_schaltpunkt + " #" + ac.po_switch[1] + "\r\n")
        f_switch.write(str(ac.time_target.hour).zfill(2)
                                 + ":30 #" + ac.po_switch[2] + "\r\n")
        f_switch.write(data_audio_switch[0])
        f_switch.write(data_audio_switch[1])
        f_switch.write(data_audio_switch[2])
        f_switch.write("Parameter fuer Sendequellenumschalter:"
                                        " hh:mm #Quell-Nr. am Switch\r\n")
        # WICHTIG: der log_text wird von play_out_logging gesucht
        # um die audio_switch_prameter zu finden
        log_message = (u"Datei für Sendequellenumschalter geschrieben: "
                 + ac.po_switch[0] + ac.po_switch[1] + ac.po_switch[2])
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        ac.action_summary = (u"Sendequellen: "
                 + ac.po_switch[0] + ac.po_switch[1] + ac.po_switch[2])
        db.write_log_to_db(ac, ac.action_summary, "i")
        f_switch.close


def read_zeitansage():
    """Zeitansage abarbeiten"""
    # Pfad von play_out_loader zu Zeitansage
    path_zeitansage = (lib_cm.check_slashes(ac, db.ac_config_zeitansage[4])
                    + str(ac.time_target.hour).zfill(2))
    # Pfad von mAirlist zu Zeitansage
    path_zeitansage_po = (lib_cm.check_slashes(ac, db.ac_config_zeitansage[3])
                    + str(ac.time_target.hour).zfill(2))
    path_zeitansage_po = (lib_cm.check_slashes(ac, path_zeitansage_po))
    # Zeitansage zu passender Zeit per Zufall aus Pool holen
    file_zeitansage = lib_cm.read_random_file_from_dir(ac, db, path_zeitansage)
    if file_zeitansage is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[9], "x",
                                             "write_also_to_console")
    else:
        ac.po_it_pl.append("YYno_pathYY" + path_zeitansage_po + file_zeitansage)
        lib_cm.message_write_to_console(ac, ac.po_it_pl)


def read_jingle():
    """Jingle abarbeiten"""
    # Pfad von play_out_loader zu Jingle
    path_jingle = (lib_cm.check_slashes(ac, db.ac_config_it_paths[2]))
    # Pfad von mAirlist zu Jingle
    path_jingle_po = (lib_cm.check_slashes(ac, db.ac_config_it_paths[4]))
    path_jingle_po = (lib_cm.check_slashes(ac, path_jingle_po))
    # Jingle per Zufall aus Pool holen
    file_jingle = lib_cm.read_random_file_from_dir(ac, db, path_jingle)
    if file_jingle is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[10], "x",
                                             "write_also_to_console")
    else:
        if db.ac_config_1[1] == "on":
            # wenn Zeitansage, dann danach einsortieren
            ac.po_it_pl.insert(1, "YYno_pathYY" + path_jingle_po + file_jingle)
        else:
            ac.po_it_pl.insert(0, "YYno_pathYY" + path_jingle_po + file_jingle)
        lib_cm.message_write_to_console(ac, ac.po_it_pl)


def read_infotime():
    """InfoTime-Beitraege"""
    # Zeitfenster fuer InfoTime
    if (str(ac.time_target.hour).zfill(2) >= db.ac_config_times[1]
        and str(ac.time_target.hour).zfill(2) < db.ac_config_times[2]):
        # erst auf feste (kommende) Stunde gebuchte IT suchen
        time_target_start = (datetime.datetime.now()
                                 + datetime.timedelta(hours=+1))
        list_result = load_infotime(time_target_start.strftime("%H"))
        # keine feste IT Buchung, normale nehmen
        if list_result[0][0] == "nix":
            db.write_log_to_db_a(ac,
                "Keine fest gebuchte Info-Time-Sendung vorhanden fuer: "
                                     + str(ac.time_target.hour),
                                     "e", "write_also_to_console")
            if ac.time_target.hour > 0 and ac.time_target.hour % 2 != 0:
                # ungerade stunde
                db.write_log_to_db_a(ac, "IT: ungerade Stunde pruefen",
                                     "e", "write_also_to_console")
                list_result = load_infotime(db.ac_config_times[7])
            else:
                db.write_log_to_db_a(ac, "IT: gerade Stunde pruefen",
                                     "e", "write_also_to_console")
                list_result = load_infotime(db.ac_config_times[1])
    else:
        db.write_log_to_db_a(ac, "Ausserhalb des InfoTime Zeitfensters!",
                                     "e", "write_also_to_console")
        return None

    if list_result[0][0] == "nix":
        db.write_log_to_db_a(ac, "Keine InfoTime Sendungen vorhanden",
                                     "e", "write_also_to_console")
        return None

    # Laenge fuer Instrumentals
    ac.po_it_duration = list_result[2]

    # Pfad von mAirlist zu InfoTime
    path_it_po = lib_cm.check_slashes(ac, db.ac_config_it_paths[3])

    # Filenames mit Path in List
    for item in list_result[0]:
        ac.po_it_pl.append("YYno_pathYY" + path_it_po + item)
    return True


def read_instrumental():
    """Instrumentals abarbeiten"""
    # Pfad von play_out_loader zu Instrumentals
    path_instrumental = lib_cm.check_slashes(ac, db.ac_config_it_paths[5])
    # Pfad von mAirlist zu Instrumentals
    path_instrumental_po = (lib_cm.check_slashes(ac, db.ac_config_it_paths[6]))

    duration_minute_instr = 0
    duration_minute_target = int(db.ac_config_times[4]) - ac.po_it_duration
    lib_cm.message_write_to_console(ac, "Duration in Minuten")
    lib_cm.message_write_to_console(ac, str(ac.po_it_duration))

    while (duration_minute_instr < duration_minute_target):
        file_instrumental = lib_cm.read_random_file_from_dir(ac,
                                         db, path_instrumental)
        if file_instrumental is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[11], "x",
                                             "write_also_to_console")
        else:
            ac.po_it_pl.append("YYno_pathYY" + path_instrumental_po
                                             + file_instrumental)
            lib_cm.message_write_to_console(ac, ac.po_it_pl)

        try:
            audio_instrumental = MP3(path_instrumental + file_instrumental)
            duration_minute_instr += audio_instrumental.info.length / 60
        except Exception, e:
            err_message = "Error by reading duration: %s" % str(e)
            lib_cm.message_write_to_console(ac, err_message)
            db.write_log_to_db_a(ac, ac.app_errorslist[12], "x",
                                             "write_also_to_console")

        lib_cm.message_write_to_console(ac, "Duration Instrumental")
        lib_cm.message_write_to_console(ac, str(audio_instrumental.info.length))
        lib_cm.message_write_to_console(ac, str(duration_minute_instr))


def load_magazin():
    """Magazin-Beitraege aus db holen"""
    list_sendung_filename = []
    list_sendung_duration = []
    int_sum_duration = 0

    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' AND "
            "SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
            + str(ac.time_target.date()) + "' "
            "AND A.SG_HF_INFOTIME='F' AND A.SG_HF_MAGAZINE='T' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond(ac,
                                                 db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Magazin-Sendungen für: "
                 + str(ac.time_target.date()) + " " + str(ac.time_target.hour))
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        list_sendung_filename.append("nix")
        #list_result mit Laenge IT 0 Minuten:
        list_result = [list_sendung_filename, 0, int_sum_duration]
        lib_cm.message_write_to_console(ac, "load_infotime list_result: ")
        lib_cm.message_write_to_console(ac, list_result)
        return list_result

    # in List schreiben
    log_message = (u"Magazin-Sendungen vorhanden für: "
                     + str(ac.time_target.date())
                     + " " + str(ac.time_target.hour))
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    for row in sendung_data:
        #print row
        hh = row[3][0:2]
        mm = row[3][3:5]
        ss = row[3][6:8]

        int_sum_duration = (int(hh) * 60 * 60) + (int(mm) * 60) + int(ss)
        # list mit filename und sekunden
        list_sendung_filename.append(row[12])
        list_sendung_duration.append(int_sum_duration)

    int_sum_duration = int_sum_duration / 60
    list_result = [list_sendung_filename, list_sendung_duration,
         int_sum_duration]
    lib_cm.message_write_to_console(ac, "load_magazin list_result: ")
    lib_cm.message_write_to_console(ac, list_result)
    return list_result


def prepare_pl_infotime():
    """Playlist InfoTime vorbereiten"""
    # Fader an den Anfang
    # Dies hier und nicht in read_zeitansage
    # weil der Fader auch bei deaktivierter Zeitansage rein soll
    path_fader = lib_cm.check_slashes(ac, db.ac_config_zeitansage[3])
    path_file_fader = "YYno_pathYY" + path_fader + db.ac_config_zeitansage[2]
    ac.po_it_pl.insert(0, path_file_fader)
    lib_cm.message_write_to_console(ac, ac.po_it_pl)
    path_filename = db.ac_config_mairlist[1] + "_00.m3u"
    write_to_file_playlist_it(path_filename, ac.po_it_pl)


def rock_infotime():
    """Infotime abarbeiten"""
    if db.ac_config_1[1] == "on":
        read_zeitansage()
    else:
        db.write_log_to_db_a(ac, "Zeitansage ist deaktiviert",
                                     "e", "write_also_to_console")
    if db.ac_config_1[4] == "on":
        transmit_it = read_infotime()
    else:
        db.write_log_to_db_a(ac, "InfoTime ist deaktiviert",
                                     "e", "write_also_to_console")
    if db.ac_config_1[2] == "on":
        # Jingle aktiviert
        if db.ac_config_1[3] == "on":
            # Jingle auch bei IT-Beitraegen aktiviert
            read_jingle()
        else:
            if transmit_it is None:
                # Jingle doch wenn keine IT-Betraege vorhanden
                # oder ausserhalb IT-Zeit
                read_jingle()
            else:
                db.write_log_to_db_a(ac, "Jingle bei IT-Sendungen deaktiviert",
                                     "e", "write_also_to_console")

    else:
        db.write_log_to_db_a(ac, "Jingle ist deaktiviert",
                                     "e", "write_also_to_console")

    if db.ac_config_1[5] == "on" and ac.po_instrumental is True:
        read_instrumental()
    else:
        db.write_log_to_db_a(ac, "Instrumental ist deaktiviert "
                    "oder nicht noetig", "e", "write_also_to_console")

    prepare_pl_infotime()


def rock_magazin():
    """Magazin abarbeiten"""
    # Alle PL loeschen
    mag_z = [1, 2, 3]
    for i in mag_z:
        path_pl_file = (db.ac_config_mairlist[1]
                                + "_magazine_0" + str(i) + ".m3u")
        lib_cm.message_write_to_console(ac, path_pl_file)
        delete_pl_ok = lib_cm.erase_file_a(ac, db, path_pl_file,
                                        u"Playlist geloescht ")
        if delete_pl_ok is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[13], "x",
                                             "write_also_to_console")
    # Einstellungen
    if db.ac_config_1[6] == "off" and ac.po_mg is None:
        db.write_log_to_db_a(ac, "Magazin ist deaktiviert "
                    "oder nicht noetig", "e", "write_also_to_console")
        return
    # Zeitfenster fuer InfoTime
    if (str(ac.time_target.hour).zfill(2) >= db.ac_config_times[1]
        and str(ac.time_target.hour).zfill(2) < db.ac_config_times[2]):
        list_result = load_magazin()
    else:
        db.write_log_to_db_a(ac,
             "Ausserhalb des InfoTime/ Magazin Zeitfensters!",
                                    "e", "write_also_to_console")
        return

    if list_result[0][0] == "nix":
        db.write_log_to_db_a(ac, "Keine Magazin Sendungen vorhanden",
                                     "e", "write_also_to_console")
        return

    # Anzahl
    nZ_Magazins = len(list_result[0])
    log_message = u"Anzahl Magazinbeitraege: " + str(nZ_Magazins)
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    # Bis 3 Stueck
    if nZ_Magazins <= 3:
        zz = 1
        for item in list_result[0]:
            #sendung = item
            if ac.po_mg_list[zz - 1] is True:
                path_file_pl = (db.ac_config_mairlist[1]
                                    + "_magazine_0" + str(zz) + ".m3u")
                path_mg = lib_cm.check_slashes(ac, db.ac_config_mairlist[2])
                path_file_mg = path_mg + item
                write_to_file_playlist_mg(path_file_pl, path_file_mg)
            else:
                log_message = (u"Magazinbeitrag "
                     + str(zz) + u" fällt wegen Normalsendung aus")
                db.write_log_to_db_a(ac, log_message, "t",
                                             "write_also_to_console")
            zz += 1

    # Bis 6 Stueck
    if nZ_Magazins > 3 and nZ_Magazins <= 6:
        zz = 1
        mag_hour = ac.time_target.hour
        list_sendung = list_result[0]
        if mag_hour > 0 and mag_hour % 2 == 0:
            # gerade Stunde
            log_message = u"Magazin Serie A geht auf Sendung"
            db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")

            for index, item in enumerate(list_sendung):
                if index < 3:
                    if ac.po_mg_list[zz - 1] is True:
                        path_file_pl = (db.ac_config_mairlist[1]
                                    + "_magazine_0" + str(zz) + ".m3u")
                        path_mg = lib_cm.check_slashes(ac,
                                             db.ac_config_mairlist[2])
                        path_file_mg = path_mg + item
                        write_to_file_playlist_mg(path_file_pl, path_file_mg)
                    else:
                        log_message = (u"Magazinbeitrag "
                                 + str(zz) + u" fällt wegen Normalsendung aus")
                        db.write_log_to_db_a(ac, log_message, "t",
                                             "write_also_to_console")
                    zz += 1
        else:
            # ungerade Stunde
            log_message = u"Magazin Serie B geht auf Sendung"
            db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
            for index, item in enumerate(list_sendung):
                if index > 2:
                    if ac.po_mg_list[zz - 1] is True:
                        path_file_pl = (db.ac_config_mairlist[1]
                                    + "_magazine_0" + str(zz) + ".m3u")
                        path_mg = lib_cm.check_slashes(ac,
                                             db.ac_config_mairlist[2])
                        path_file_mg = path_mg + item
                        write_to_file_playlist_mg(path_file_pl, path_file_mg)
                    else:
                        log_message = (u"Magazinbeitrag "
                                 + str(zz) + u" fällt wegen Normalsendung aus")
                        db.write_log_to_db_a(ac, log_message, "t",
                                             "write_also_to_console")
                    zz += 1

    # Bis 9 Stueck
    if nZ_Magazins > 9:
        zz = 1
        list_sendung = list_result[0]
        # diese liste enthaelt 24 items,
        # jedes item enthaelt die startnr der 3 mags,
        # die gesendet werden sollen (1.=0/4.=3/6.=5)
        list_index_sendung_hour = [0, 0, 0, 0, 0, 0, 0, 3, 6, 0, 3, 6, 0,
                                             3, 6, 0, 3, 6, 0, 3, 6, 0, 3, 6]
        # aus list_sendung die 3 mag_sendungen rausholen
        # die in der stunde gesendet werden sollen
        list_sendung_mag = (list_sendung[
                   list_index_sendung_hour[ac.time_target.hour]:
                   list_index_sendung_hour[ac.time_target.hour] + 3])
         # Serie ermitteln und loggen
        n_mag_serie = list_index_sendung_hour[mag_hour]
        if n_mag_serie == 0:
            log_message = u"Magazin Serie A geht auf Sendung"
        if n_mag_serie == 3:
            log_message = u"Magazin Serie B geht auf Sendung"
        if n_mag_serie == 6:
            log_message = u"Magazin Serie C geht auf Sendung"
        db.write_log_to_db_a(ac, log_message, "t",
                                             "write_also_to_console")
        for index, item in enumerate(list_sendung_mag):
            if ac.po_mg_list[zz - 1] is True:
                path_file_pl = (db.ac_config_mairlist[1]
                                    + "_magazine_0" + str(zz) + ".m3u")
                path_mg = lib_cm.check_slashes(ac,
                                             db.ac_config_mairlist[2])
                path_file_mg = path_mg + item
                write_to_file_playlist_mg(path_file_pl, path_file_mg)
            else:
                log_message = (u"Magazinbeitrag "
                                 + str(zz) + u" fällt wegen Normalsendung aus")
                db.write_log_to_db_a(ac, log_message, "t",
                                             "write_also_to_console")
            zz += 1


def read_from_file_lines_in_list(filename):
    """Zeilen aus Datei in Liste schreiben"""
    try:
        f = open(filename, 'r')
        lines = f.readlines()
        f.close()
    except IOError as (errno, strerror):
        lines = None
        log_message = ("read_from_file_lines_in_list - I/O error({0}): {1}"
                        .format(errno, strerror) + ": " + filename)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
    return lines


def lets_rock():
    print "lets_rock "
    # Weitere Params
    load_extended_params_ok = load_extended_params()
    if load_extended_params_ok is None:
        return
    # Sendungen volle Stunde
    # Minute Start, Minute Ende
    # Minute Ende ist Minute Anfang des naechsten Punktes -1, zw. 59
    minute_start = db.ac_config_times[3]
    minute_end = str(int(db.ac_config_times[4]) - 1).zfill(2)
    rock_sendung(minute_start, minute_end)

    minute_start = db.ac_config_times[4]
    minute_end = str(int(db.ac_config_times[5]) - 1).zfill(2)
    rock_sendung(minute_start, minute_end)

    minute_start = db.ac_config_times[5]
    minute_end = "59"
    rock_sendung(minute_start, minute_end)
    # Audioswitch
    write_to_file_switch_params()
    # InfoTime
    if ac.po_it is not None:
        rock_infotime()
    # Magazin
    rock_magazin()


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + " gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # alles ok: weiter
        if param_check is not None:
            lets_rock()

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + " gestoppt", "s")
    print "lets_lay_down"
    sys.exit()