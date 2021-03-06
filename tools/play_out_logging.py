#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out Logging

Autor: Joerg Sorge
Org: SRB - Das Buergerradio
Web: www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at ggooogl
2011-09-26

Dieses Script registriert ausgespielte Beitraege in der Datenbank
und uebertraegt sie auf den Webserver.
Dort koennen die Daten zur Anzeige des aktuellen Beitrags/Titels
und fuer Playlisten genutzt werden.

Dateiname Script: play_out_logging.py
Schluesselworte fuer Einstellungen: PO_Logging_Config/
PO_Time_Config
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank, Logfiles mehrerer mAirlist-Instanzen

Ablauf:
1. Haupt- und erweiterte Parameter aus der Config holen
2. Form aufrufen und im Intervall Metadaten der Beitraege/Titel
ermitteln und in den DBs speichern
3. Pruefen ob Quelle Aussenuebertragung, VP oder Studios
4. Daten aus Logfiles oder db ermitteln
5. Logging in db
6. Upload auf Webserver

Details:
Die Quellen werden aus dem Logeintrag (Sendequellenumschalter)
in der DB ermittelt.
Wenn Aussenuebertragung, dann gibts keine Logfiles,
also Sendedaten aus db hohlen.
Wenn VP oder Studio, dann mAirlist-Logdateien einlesen oder
MPD-Song-Abfrage und mit db vergleichen.
Zu guter letzt, Daten in db schreiben und auf Webserver uebertragen.
Bei Fehlern gibt es ein Fallback.
Es wird der in der Config voreingestellte Autor und Titel
geloggt und uebertragen.

Liste der moeglichen Haupt-Fehlermeldungen:
E 0 Parameter-Typ oder Inhalt stimmt nicht
E 1 Sende-Quelle kann aus Datenbank nicht ermittelt werden
E 2 Play-Out-Log-Datei kann nicht gelesen werden
E 3 Webserver lieferte bei Uebertragung Fehler zurueck
E 4 Webserver fuer nicht erreichbar
E 5 Externes PlayOut-Logging ausgesetzt, Webserver nicht erreichbar
E 6 Fehler bei MPD-Connect
E 7-Fehler bei MPD-Song-Abfrage
E 8-Fehler bei MPD-Status-Abfrage

Parameterliste:
Param 1: none
Param 2: Aktualisierungsintervall in Sekunden
Param 3: Not-Autor
Param 4: Not-Titel
Param 5: URL Webscript
Param 6: Benutzer
Param 7: Passwort
Param 8: mAirList Logdatei der Studio-Rechner, aktueller Titel
(Erweiterung um Nummer und Endung "log"
wird durch das logging-Programm vorgenommen)
Hinweis: Autor und Titel (param 3 und 4)
wird eingesetzt wenn tatsaechlicher nicht ermittelbar
Param 9: mpd oder mairlist

Erweiterte Parameter werden bezogen von:
PO_Time_Config_1


Das Script laeuft mit graphischer Oberflaeche staendig.

Hinweis:
Log und Statusmeldungen (debug_mod= yes), die direkt ausgegeben werden (print),
koennen bei laengerer Laufzeit zu Speicherproblemen fuehren,
besonders unter Windows.

Man bedenke:
Und dein Knecht steht mitten in deinem Volk, das du erwaehlt hast,
einem Volk, so gross,
dass es wegen seiner Menge niemand zaehlen noch berechnen kann.
Die Bibel 1. Koenige 3,8
"""


from Tkinter import Frame, Label, NW, END
from ScrolledText import ScrolledText
import os
import sys
import socket
import string
import re
import datetime
import urllib
import lib_common_1 as lib_cm
import lib_mpd as lib_mp


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "003"
        self.app_desc = u"Play Out Logging"
        self.app_config = u"PO_Logging_Config"
        # display debugmessages on console or no: "no"
        # for normal usage set to no!!!!!!
        self.app_debug_mod = "no"
        self.app_config_develop = u"PO_Logging_Config_1_e"
        self.app_develop = "no"
        self.app_windows = "no"

        self.app_errorfile = "error_play_out_logging.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(self.app_desc + " Parameter-Typ "
            "oder Inhalt stimmt nicht ")
        self.app_errorslist.append(self.app_desc + "Sende-Quelle "
            "kann fuer PlayOut-Logging nicht aus Datenbank ermittelt werden ")
        self.app_errorslist.append(self.app_desc +
            " Play-Out-Log-Datei kann nicht gelesen werden")
        self.app_errorslist.append(self.app_desc +
            " Webserver lieferte bei Uebertragung Fehler zurueck")
        self.app_errorslist.append(self.app_desc +
            " Webserver nicht erreichbar")
        self.app_errorslist.append(self.app_desc +
            " Externes Logging ausgesetzt, Webserver nicht erreichbar")
        self.app_errorslist.append(self.app_desc + "-Fehler bei MPD-Connect")
        self.app_errorslist.append(self.app_desc +
            "-Fehler bei MPD-Song-Abfrage")
        self.app_errorslist.append(self.app_desc +
            "-Fehler bei MPD-Status-Abfrage")
        # anzahl parameter list 0
        self.app_config_params_range = 10
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_url")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_counter = 0
        self.app_counter_error = 0
        self.error_counter_read_log_file = 0
        self.log_start = None
        self.log_author = None
        self.log_title = None
        self.log_songid = None
        #self.log_filename = None


def load_extended_params():
    """load extended params"""
    # Times
    ext_params_ok = True
    db.ac_config_times = db.params_load_1a(ac, db, "PO_Time_Config_1")
    if db.ac_config_times is not None:
        # create extended Paramslist
        app_params_type_list_times = []
        # Types of extended-List
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        # check extended Params
        param_check_time_config = lib_cm.params_check_a(
                        ac, db, 8,
                        app_params_type_list_times,
                        db.ac_config_times)
        if param_check_time_config is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
            ext_params_ok = None
    else:
        ext_params_ok = None
    return ext_params_ok


def check_source(self, c_time, time_now):
    """detect sources and assign transmittimes """
    # source-switch-from user_logs
    #source_log = db.read_tbl_row_with_cond_log(ac, db,
    #        "USER_LOGS", "USER_LOG_ACTION, USER_LOG_TIME",
    #        u"USER_LOG_ACTION LIKE "
    #        u"'Datei für Sendequellenumschalter geschrieben:%' "
    #        + u"AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 13) ='"
    #        + c_time + "' ORDER BY USER_LOG_TIME DESC")
    source_log = db.read_tbl_row_with_cond_log(ac, db,
            "USER_LOGS", "USER_LOG_ACTION, USER_LOG_TIME",
            u"USER_LOG_ACTION LIKE "
            u"'Sendequellen: %' "
            + u"AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 13) ='"
            + c_time + "' ORDER BY USER_LOG_TIME DESC")
    # ATT: log_text, that we here search for is written by play_out_load

    if source_log is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
                "write_also_to_console")
        log_meldung_1 = ac.app_errorslist[1] + "\n"
        self.display_logging(log_meldung_1, None)
        #lib_cm.message_write_to_console(ac, "whoooow")
        return None
    else:
        lib_cm.message_write_to_console(ac, source_log)
        #source_params = source_log[0][46:52]
        source_params = source_log[0][14:20]

    lib_cm.message_write_to_console(ac, source_params)

    # 2. Quelle der aktuellen Sendezeit zuordnen
    #if time_now.minute < 5:
    if time_now.minute < int(db.ac_config_times[4]):
            source_id = source_params[0:2]

    #if time_now.minute >=5 <30:
    if (time_now.minute >= int(db.ac_config_times[4])
            < int(db.ac_config_times[5])):
        source_id = source_params[2:4]

    #if time_now.minute >=30:
    if time_now.minute >= int(db.ac_config_times[5]):
            source_id = source_params[4:6]

    lib_cm.message_write_to_console(ac, source_id)
    return source_id


def check_mairlist_log(self, source_id, time_now, log_data):
    """load data from marilist logfile"""

    # concatenate filename mAirlist-Logfile
    file_mairlist_log = (ac.app_homepath + db.ac_config_1[8]
                                            + "_" + source_id + ".log")
    lib_cm.message_write_to_console(ac, file_mairlist_log)

    # Daten aus mAirlist_Logdatei holen
    mairlist_log_data = lib_cm.read_file_first_line(ac,
                            db, file_mairlist_log)
    lib_cm.message_write_to_console(ac, mairlist_log_data)
    if mairlist_log_data is None:
        # Fehler beim Lesen des Logfiles
        ac.error_counter_read_log_file += 1
        log_meldung_1 = ac.app_errorslist[1] + " \n"
        if ac.error_counter_read_log_file == 1:
            # Error-Meldung nur einmal registrieren
            db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
                    "write_also_to_console")
            # Ausfall-Meldung nur einmal uebertragen
            ac.log_start = (str(time_now.date()) + " "
                                     + str(time_now.time())[0:8])
            ac.log_author = db.ac_config_1[3]
            ac.log_title = db.ac_config_1[4]
            web = upload_data_prepare()
            if web is not None:
                self.display_logging(log_meldung_1, web)
            else:
                self.display_logging(log_meldung_1, None)
        else:
            self.display_logging(log_meldung_1, None)
        return None
    else:
        ac.error_counter_read_log_file = 0

    # bei direktem Vergleich des Inhalts der Logdatei
    # (mairlist_log_data) funktioniert folgender
    # if-Vergleich nicht aussserhalb der ide, deshalb in vari
    mairlist_log_time = mairlist_log_data[6:25]
    if ac.log_start == mairlist_log_time:
        # Keine Aenderung des gespielten Titels, also wieder zurueck
        log_meldung_1 = ("Keine Aenderung in mAirlist-Log... \n" +
                   ac.log_start + " - " + ac.log_author + " - " + ac.log_title)
        self.display_logging(log_meldung_1, None)
        return None
    else:
        # 4. Daten aus Logfiles oder db ermitteln
        ac.log_start = mairlist_log_data[6:25]
        log_data = mairlist_log_data
        # Ermitteln ob gebuchte Sendung, oder Musik
        log_author_title = work_on_data_from_log(time_now, log_data, "mairlist")
        ac.log_author = log_author_title[0]
        ac.log_title = log_author_title[1]
        return True


def logging_source_ext(self, source_id, time_now):
    """logging for extern source e.g ISDN"""
    lib_cm.message_write_to_console(ac, u"ISDN-Uebertragung")
    # Sendestunde ermitteln, anpassen
    if time_now.hour < 10:
        c_hour = "0" + str(time_now.hour)
    else:
        c_hour = str(time_now.hour)
    # Daten aus db holen
    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
                "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
                + str(time_now.date()) + "' "
                "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
                + c_hour + "' AND A.SG_HF_SOURCE_ID ='"
                + source_id + "'")
    sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                            db, db_tbl_condition)
    if sendung_data is not None:
        if ac.log_start == str(sendung_data[2]):
            # Startzeit der Sendung (SG_HF_TIME)
            # ist gleich der im vorigen Durchlauf ermittelten Sendung,
            # also laeuft sie noch, keine Aenderung
            lib_cm.message_write_to_console(ac,
                        u"ISDN-Uebertragung laeuft noch")
            log_meldung_1 = ("Keine Aenderung... \n" +
            ac.log_start + " - " + ac.log_author + " - " + ac.log_title)
            self.display_logging(log_meldung_1, None)
            return None
        else:
            # Daten der Sendung fuer Vergleich
            # bei naechstem Durchlauf einlesen
            ac.log_start = str(sendung_data[2])
            ac.log_author = sendung_data[12] + " " + sendung_data[13]
            ac.log_title = sendung_data[9]
    else:
        lib_cm.message_write_to_console(ac,
            u"ISDN-Uebertragung, Sendung nicht in DB gefunden")
        # Spaeter mit Vorbelegung aus Einstellungen fuellen
        ac.log_author = None
        ac.log_title = None
    return True


def check_mpd_log(self, time_now, log_data):
    """load data from mpd"""
    # 1. playing file ermitteln
    # 2. if id dann aus db holen, sonst tags
    # load current song
    mpd_result = mpd.connect(db, ac)
    if mpd_result is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[6], "x",
                                                    "write_also_to_console")
        self.display_logging("No MPD-Connect", None)
        return None
    current_song = mpd.exec_command(db, ac, "song", None)
    #print current_song
    if current_song is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[7], "x",
                                                    "write_also_to_console")
        mpd.disconnect()
        self.display_logging("Aktueller Song nicht von MPD ermittelbar", None)
        return None

    mpd.disconnect()

    if "id" not in current_song:
        self.display_logging("Aktuelle id von MPD nicht ermittelbar", None)
        return None

    # by stream, it could be, that id is eqal but title not
    # no several ids in stream ar a mess, we will not display this
    #if "file" in current_song:
    #    log_filename = current_song["file"]
    #else:
    #    log_filename = "nicht vorhanden"
    lib_cm.message_write_to_console(ac, current_song["id"])

    #if (current_song["id"] == ac.log_songid
    #                    and log_filename == ac.log_filename):
    if current_song["id"] == ac.log_songid:
        log_meldung_1 = ("Keine Aenderung des MPD-Song-Status... \n" +
                                ac.log_author + " - " + ac.log_title)
        self.display_logging(log_meldung_1, None)
        return None
    else:
        log_author_title = work_on_data_from_log(time_now, current_song, "mpd")
        ac.log_author = log_author_title[0]
        ac.log_title = log_author_title[1]
        ac.log_songid = current_song["id"]
        #ac.log_filename = log_filename
        ac.log_start = (str(time_now.date()) + " " + str(time_now.time())[0:8])
        return True


def extract_from_stuff_after_match(stuff, match_string):
    """extract string after match_string """
    index_trenner = string.find(stuff, match_string)
    index_begin_offset = len(match_string)
    index_end = len(stuff)
    cut_off = stuff[index_trenner + index_begin_offset:index_end]

    if cut_off == "":
        cut_off = "nix"
        #log_message = "extract_from_stuff_after_match: cut_off ist leer. "
        # kein Beinbruch, Fehler nicht loggen
        #db.write_log_to_db(ac, log_message, "x")
        #print "extract_from_stuff_after_match_error_1"
        return  cut_off

    return cut_off


def extract_from_stuff(stuff,
            match_string_1, offset_1, match_string_2, offset_2):
    """extract string """
    index_begin = string.find(stuff, match_string_1)
    if index_begin == -1:
        cut_off = "nix"
        #log_message = "extract_from_stuff: Ausschnitt Beginn nicht gefunden. "
        # kein Beinbruch, Fehler nicht loggen
        #db.write_log_to_db(ac, log_message, "x")
        #print "extract_from_stuff_error_1"
        return  cut_off

    index_end = string.find(stuff, match_string_2)
    if index_end == -1:
        cut_off = "nix"
        #log_message = "extract_from_stuff: Ausschnitt Ende nicht gefunden. "
        # kein Beinbruch, Fehler nicht loggen
        #db.write_log_to_db(ac, log_message, "x")
        #print "extract_from_stuff_error_2"
        return  cut_off

    cut_off = stuff[index_begin + offset_1:index_end + offset_2]
    return cut_off


def upload_data_prepare():
    """prepare pload-data for webserver """
    # Bei I-Netfehler upload aussetzen
    if ac.app_counter_error > 3:
        db.write_log_to_db_a(ac, ac.app_errorslist[5], "x",
            "write_also_to_console")
        ac.app_counter_error = 0
        return

    c_autor = lib_cm.replace_uchar_with_html(ac.log_author)
    c_title = lib_cm.replace_uchar_with_html(ac.log_title)
    data_upload = {'pa': 'hinein',
        'pb': ac.log_start,
        'pc': ac.log_start,
        'pd': c_autor,
        'pe': c_title,
        'pf': db.ac_config_1[6],
        'pg': db.ac_config_1[7]}

    # urlencode kann fehler werfen,
    # wenn sonderzeichen nicht encodet werden können
    try:
        data_upload_encoded = urllib.urlencode(data_upload)
    except Exception, e:
        log_message = "urlencode Error: %s" % str(e)
        db.write_log_to_db(ac, log_message, "x")
        log_message = "urlencode Error Data " + c_autor + " " + c_title
        db.write_log_to_db(ac, log_message, "x")
        data_upload = {'pa': 'hinein',
            'pb': ac.log_start,
            'pc': ac.log_start,
            'pd': db.ac_config_1[3],
            'pe': db.ac_config_1[4],
            'pf': db.ac_config_1[6],
            'pg': db.ac_config_1[7]}

        # use this only for debugging!!
        #lib_cm.message_write_to_console(ac, u"data_upload"
        #                    + c_autor.encode('utf-8', 'ignore') + " "
        #                    + c_title.encode('utf-8', 'ignore'))
        data_upload_encoded = urllib.urlencode(data_upload)

    web = lib_cm.upload_data(ac, db, db.ac_config_1[5], data_upload_encoded)
    if web is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
            "write_also_to_console")
        ac.app_counter_error += 1
        return web

    if web[0:6] == "Fehler":
        db.write_log_to_db(ac, ac.app_errorslist[3], "x")
        db.write_log_to_db(ac, web, "x")

    return web


def work_on_data_from_log(time_now, log_data, load_from):
    """extract data from logs"""
    lib_cm.message_write_to_console(ac, u"work_on_data_from_log")
    if load_from == "mairlist":
        log_author = extract_from_stuff(log_data, "&author=", 8, "&title=", 0)
        log_title = extract_from_stuff(log_data, "&title=", 7, "&file=", 0)
        log_filename = extract_from_stuff_after_match(log_data, "&file=")

    if load_from == "mpd":
        lib_cm.message_write_to_console(ac, u"mpd")
        id3_artist = None
        id3_title = None
    #if test == "mpd":
        if "title" in log_data:
            log_title = lib_cm.convert_to_unicode(log_data["title"])
            id3_title = log_title
        else:
            log_title = db.ac_config_1[4]
            lib_cm.message_write_to_console(ac, u"no title-tag")
        if "file" in log_data:
            if log_data["file"][0:7] == "http://":
                log_filename = log_data["file"]
            else:
                #log_filename = ntpath.basename(log_data["file"])
                #with mpd, we are only on linux, so we can use this
                log_filename = os.path.splitext(
                    os.path.basename(log_data["file"]))[0]
        else:
            log_filename = ""
        if "artist" in log_data:
            log_author = lib_cm.convert_to_unicode(log_data["artist"])
            id3_artist = log_author
        else:
            log_author = db.ac_config_1[3]
            lib_cm.message_write_to_console(ac, u"no artist-tag")

    sendung_data = None
    sendung_data_search_for_id_only = "no"

    via_inet = None
    stream_url = ""
    # Falls Uebernahme per Inetstream, erkennbar an http
    if log_title[0:7] == "http://" and db.ac_config_1[9] == "mairlist":
        lib_cm.message_write_to_console(ac, u"uebernahme_per_inetstream")
        via_inet = True
        stream_url = log_title
    #if log_filename[0:7] == "http://" and test == "mpd":
    if log_filename[0:7] == "http://" and db.ac_config_1[9] == "mpd":
        lib_cm.message_write_to_console(ac, u"uebernahme_per_inetstream")
        via_inet = True
        stream_url = log_filename

    if via_inet is True:
        # Sendestunde ermitteln, anpassen
        if time_now.hour < 10:
            c_hour = "0" + str(time_now.hour)
        else:
            c_hour = str(time_now.hour)

        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
            + str(time_now.date()) + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
            + c_hour + "' AND B.SG_HF_CONT_FILENAME ='"
            + stream_url + "'")
        # daten aus db holen
        sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                db, db_tbl_condition)

    # Falls SRB-Dateiname, erkennbar an 7stelliger Zahl am Anfang
    if re.match("\d{7,}", log_filename) is not None:
        lib_cm.message_write_to_console(ac,
            u"srb_sendung_id_in_title: daten aus db, "
            "erster versuch mit zeit und id")
        # id extrahieren und sendung in db suchen
        sendung_id = log_filename[0:7]
        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
            + str(time_now.date()) + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
            + str(time_now.hour) + "' AND B.SG_HF_CONT_ID ='"
            + sendung_id + "'")
        sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                    db, db_tbl_condition)
        if sendung_data is None:
            sendung_data_search_for_id_only = "yes"

    if sendung_data_search_for_id_only == "yes":
        lib_cm.message_write_to_console(ac,
            u"srb_sendung_id_in_title: daten aus db, "
            "zweiter versuch nur mit id")
        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND B.SG_HF_CONT_ID ='" + sendung_id + "'")
        sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                    db, db_tbl_condition)

    #  Daten aus db
    if sendung_data is not None:
        lib_cm.message_write_to_console(ac, u"daten_aus_db")
        log_author = sendung_data[12] + " " + sendung_data[13]
        log_title = sendung_data[9]
        lib_cm.message_write_to_console(ac,
                            log_author.encode('utf-8', 'ignore') + " - "
                            + log_title.encode('utf-8', 'ignore'))
    else:
        lib_cm.message_write_to_console(ac, u"nothing found in db")
        # check if author and title in logfile
        if load_from == "mairlist":
            author_title_ok = "no"
            if log_author != "":
                author_title_ok = "yes"

            if log_title != "":
                author_title_ok = "yes"

            if author_title_ok == "yes":
                # author und titel in logdatei
                lib_cm.message_write_to_console(ac,
                                    u"daten_aus_mAirList_logfile")
                log_author = lib_cm.convert_to_unicode(log_author)
                log_title = lib_cm.convert_to_unicode(log_title)
            else:
                # keine daten in id3-author, deshalb aus filename nehmen
                lib_cm.message_write_to_console(ac, u"data from filename")
                log_title = log_title[11:len(log_title)]
                # split in author und title,
                # klappt nur wenn ein unterstrich dazwischen
                index_trenner = string.find(log_title, "_")
                log_author = log_title[0:index_trenner]
                log_title = extract_from_stuff_after_match(log_title, "_")
                log_author = lib_cm.convert_to_unicode(log_author)
                log_title = lib_cm.convert_to_unicode(log_title)

        if load_from == "mpd":
            if id3_artist is None and id3_title is None:
                # no data from id3-author and title, take from filename
                lib_cm.message_write_to_console(ac, u"data from filename")
                # split in author and title,
                # success only by pattern:
                index_of = string.find(log_filename, " - ")
                if index_of != -1:
                    log_author = log_filename[0:index_of]
                    log_title = extract_from_stuff_after_match(
                                                log_filename, " - ")
                    log_author = lib_cm.convert_to_unicode(log_author)
                    log_title = lib_cm.convert_to_unicode(log_title)

    log_data_list = []
    #log_data_list.append(log_start)
    log_data_list.append(log_author)
    log_data_list.append(log_title)
    #lib_cm.message_write_to_console(ac, log_data_list)
    return log_data_list


class my_form(Frame):
    """Form"""
    def __init__(self, master=None):
        """Elemente der Form kreieren"""
        Frame.__init__(self, master)
        self.pack()
        #self.createWidgets()
        self.text_label = Label(self,
            height=1, width=80, anchor=NW, text="Play-Out-Logging Nr: ")
        self.text_label.pack()

        self.text_label_1 = Label(self,
            height=1, width=80, text="Titel aktuell")
        self.text_label_1.pack()

        self.textBox = ScrolledText(self, height=5, width=80)
        self.textBox.pack()
        self.textBox.insert(END, "In the Beginning...\n")

        self.text_label_2 = Label(self,
            height=1, width=80, text="Rueckmeldung von Webserver")
        self.text_label_2.pack()

        self.textBox1 = ScrolledText(self, height=10, width=80)
        self.textBox1.pack()
        self.textBox1.insert(END, "...and the End\n")

        # registering callback
        self.listenID = self.after(500, self.lets_rock)

    def display_logging(self, log_meldung_1, log_meldung_2):
        """display messages in form, loading periodically """
        if log_meldung_1 is not None:
            self.text_label.config(text="Play-Out-Logging Nr: "
                + str(ac.app_counter) + "  / Interval von "
                + str(int(db.ac_config_1[2])) + " Sekunden")
            self.textBox.delete('1.0', '3.end')
            self.textBox.insert(END, log_meldung_1 + "\n")

        if log_meldung_2 is not None:
            self.text_label.config(text="Play-Out-Logging Nr: "
                + str(ac.app_counter) + "  / Interval von "
                + str(int(db.ac_config_1[2])) + " Sekunden")
            self.textBox1.delete('1.0', '8.end')
            self.textBox1.insert(END, log_meldung_2 + "\n")

        self.listenID = self.after(
                        int(db.ac_config_1[2]) * 1000, self.lets_rock)
        return

    def lets_rock(self):
        """man funktion"""
        lib_cm.message_write_to_console(ac, u"lets rock")
        ac.app_counter += 1
        log_data = None
        time_now = datetime.datetime.now()
        time_back = datetime.datetime.now() + datetime.timedelta(hours=- 1)
        c_time = time_back.strftime("%Y-%m-%d %H")
        lib_cm.message_write_to_console(ac, c_time)

        # time for reloading mpd
        # skipping mpd-check
        if time_now.hour == 4:
            if time_now.minute == 40:
                if time_now.second >= 5 and time_now.second <= 30:
                    log_meldung_1 = "Logging waehrend MPD-Neustart ausgesetzt"
                    db.write_log_to_db_a(ac, log_meldung_1,
                                                "p", "write_also_to_console")
                    self.display_logging(log_meldung_1, "")
                    return

        # 1. load sources
        # 2. assign sources transmittime
        source_id = check_source(self, c_time, time_now)
        if source_id is None:
            return

        # 3. Pruefen ob Quelle Aussenuebertragung, VP oder Studios
        # Bei Aussenuebertragung stehen keine Logfiles zur Verfuegung,
        # Sendung muesste in db zu finden sein
        if source_id == "05":
            log_changed_ext = logging_source_ext(self, source_id, time_now)
            if log_changed_ext is None:
                return

        if source_id == "01" or source_id == "02":
            # Daten aus Logfiles holen bzw. aus db
            lib_cm.message_write_to_console(ac, u"Sendung aus Studio")
            log_changed = check_mairlist_log(self, source_id,
                                                    time_now, log_data)
            if log_changed is None:
                return

        if source_id == "03":
            # else source_id == "05":
            # Daten aus Logfiles holen bzw. aus db
            lib_cm.message_write_to_console(ac,
                u"Sendung via Playout oder Internetstream")
            # Daten aus mAirlist_Logdatei holen

            # if mairlist
            if db.ac_config_1[9] == "mairlist":
                log_changed = check_mairlist_log(self, source_id,
                                                    time_now, log_data)
                if log_changed is None:
                    return

            if db.ac_config_1[9] == "mpd":
                log_changed = check_mpd_log(self, time_now, log_data)

            if log_changed is None:
                return

        if ac.log_author is None:
            ac.log_start = (str(time_now.date()) + " "
                + str(time_now.time())[0:8])
            ac.log_author = db.ac_config_1[3]

        if ac.log_title is None:
            ac.log_title = db.ac_config_1[4]

        # Bezeichnung der Quelle holen
        log_source_desc = db.read_tbl_row_with_cond(ac,
                db, "SG_HF_SOURCE", "SG_HF_SOURCE_ID, SG_HF_SOURCE_DESC",
                "SG_HF_SOURCE_ID ='" + source_id + "'")
        if log_source_desc is None:
            log_meldung_1 = ("Keine Bezeichnung Sendequelle gefunden,"
                                " nichts uebertragen..")
            db.write_log_to_db_a(ac, log_meldung_1,
                                                "p", "write_also_to_console")
            self.display_logging(log_meldung_1, "")
            return
        # 5. Logging in db
        db.write_log_to_db(ac,
            log_source_desc[1].strip() + ": " + ac.log_author + " - "
            + ac.log_title, "a")
        log_meldung_1 = (log_source_desc[1]
            + ": \n" + ac.log_author + " - " + ac.log_title)

        # 6. Upload auf Webserver
        web = upload_data_prepare()
        if web is not None:
            self.display_logging(log_meldung_1, web)
        else:
            ac.log_start = (str(time_now.date()) + " "
                + str(time_now.time())[0:8])
            ac.log_author = db.ac_config_1[3]
            ac.log_title = db.ac_config_1[4]
            log_meldung_1 = ac.app_errorslist[4] + " \n"
            self.display_logging(log_meldung_1, None)
        return


if __name__ == "__main__":
    print "play_out_logging start"
    db = lib_cm.dbase()
    ac = app_config()
    mpd = lib_mp.myMPD()
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)

    if db.ac_config_1 is not None:
        # Haupt-Params pruefen
        param_check = lib_cm.params_check_1(ac, db)
        if param_check is not None:
            # Haupt-Params ok: weiter
            load_extended_params_ok = load_extended_params()
            if load_extended_params_ok is not None:
                # prepare path
                ac.app_homepath = "/home/" + socket.gethostname()
                mything = my_form()
                mything.master.title("Play-Out-Logging und Play-Out-Load-Web")
                mything.mainloop()

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()

