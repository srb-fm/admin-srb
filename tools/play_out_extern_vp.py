#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out load Vorproduktion von extern
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at goooglee
2011-09-30

Dieses Script stellt vorproduzierte Audio-Dateien
fuer Sendungen (mp3-Dateien)
im Play-Out-Server zur Verfuegung.
Dabei werden sie durch diverse Tools bearbeitet (z.B. mp3Gain)
Festgelegt sind die Sendungen in der Tabelle SG_HF_ROBOT.

Dateiname Script: play_out_extern_vp.py
Schluesselwort fuer Einstellungen: PO_VP_Config_1
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank


Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 Fehler beim Kopieren der Vorproduktion in Play-Out
Error 002 beim mp3-Validator:
Error 003 bei mp3gain:
Error 004 Fehler beim Loeschen der mp3validator-bak-Datei
Error 005 Fehler beim Generieren des Dateinamens:
Error 006 Fehler beim Datums-Muster

Parameterliste:
Param 1: Pfad vom Server zu Dropbox-Hauptordner
Param 2: Pfad vom Server zu Playout-Sendung
Param 3: Pfad/Programm mp3-validator
Param 4: Pfad/Programm mp3-gain

Ausfuehrung: jede Stunde zur Minute 45

In unserer Gesellschaft geht ein Gespenst um,
das nur wenige deutlich sehen.
Es ist nicht der alte Geist des Kommunismus oder des Faschismus.
Es ist ein neues Gespenst:
eine voellig mechanisierte Gesellschaft,
die sich der maximalen Produktion
und dem maximalen Konsum verschrieben hat
und von Computern gesteuert wird.
Erich Fromm, Die Revolution der Hoffnung
"""

import sys
import os
import string
import datetime
import shutil
import subprocess
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "005"
        self.app_desc = u"play_out_load_vp_extern"
        # key of config in db
        self.app_config = u"PO_VP_extern_Config"
        self.app_config_develop = u"PO_VP_extern_Config_3_e"
        # nunber of parameters
        self.app_config_params_range = 5
        self.app_errorfile = "error_play_out_load_vp_extern.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim Kopieren der Vorproduktion in Play-Out")
        self.app_errorslist.append(u"Error 002 "
            "beim mp3-Validator: ")
        self.app_errorslist.append(u"Error 003 bei mp3gain: ")
        self.app_errorslist.append(u"Error 004 "
            "Fehler beim Loeschen der mp3validator-bak-Datei")
        self.app_errorslist.append(u"Error 005 "
            "Fehler beim Generieren des Dateinamens ")
        self.app_errorslist.append(u"Error 006 "
            "Fehler beim Datums-Muster: ")
        self.app_errorslist.append(u"Error 007 "
            "beim Schreiben von id3Tags in VP von extern")
        self.app_errorslist.append(u"Error 008 "
            "beim ermitteln der Laenge VP von extern")
        self.app_errorslist.append(u"Error 009 "
            "beim Aktualisieren der Sendebuchung der VP von extern")
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")

        # develop-mod
        self.app_develop = "no"
        # debug-mod
        self.app_debug_mod = "no"
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"
        #self.app_encode_out_strings = "utf-8"
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta( days=-1 ))
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta(days=+1 ))
        self.time_target = datetime.datetime.now()


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
    ext_params_ok = lib_cm.params_provide_server_paths_b(ac, db,
                                                        ac.server_active)
    return ext_params_ok


def load_roboting_sgs():
    """search shows"""
    lib_cm.message_write_to_console(ac,
        "search for radio-shows")
    sendungen_data = db.read_tbl_rows_with_cond(ac, db,
        "SG_HF_ROBOT",
        "SG_HF_ROB_TITEL, SG_HF_ROB_FILE_IN_DB, SG_HF_ROB_SHIFT",
        "SG_HF_ROB_VP_IN ='T'")

    if sendungen_data is None:
        log_message = u"Keine Sendungen fuer Uebernahme als VPs vorgesehen.. "
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return sendungen_data

    return sendungen_data


def load_sg(sg_titel):
    """search show"""
    lib_cm.message_write_to_console(ac, "search show")
    db_tbl_condition = ("SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) >= '"
        + str(ac.time_target.date()) + "' " + "AND B.SG_HF_CONT_TITEL='"
        + sg_titel + "' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_b(ac,
                    db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Sendung mit diesem Titel gefunden: "
                            + sg_titel.encode('ascii', 'ignore'))
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return sendung_data

    return sendung_data


def audio_copy(path_file_source, path_file_dest):
    """copy audiofile"""
    success_copy = None
    try:
        shutil.copy(path_file_source, path_file_dest)
        db.write_log_to_db_a(ac, u"Audio Vorproduktion: "
                + path_file_source.encode('ascii', 'ignore'),
                "v", "write_also_to_console")
        db.write_log_to_db_a(ac, u"Audio kopiert nach: "
                + path_file_dest, "c", "write_also_to_console")
        success_copy = True
    except Exception, e:
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        log_message = u"copy_files_to_dir_retry Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
    return success_copy


def audio_validate(file_dest):
    """validate mp3-File"""
    lib_cm.message_write_to_console(ac, u"mp3-File validieren")
    # all cmds must be in the right charset
    c_validator = db.ac_config_etools[7].encode(ac.app_encode_out_strings)
    #c_validator = "/usr/bin/mp3val"
    c_source_file = file_dest.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, c_source_file)
    # start subprocess
    try:
        p = subprocess.Popen([c_validator, u"-f", c_source_file],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[4] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return
    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    validate_output = string.find(p[0], "FIXED")

    # wenn gefunden, position, sonst -1
    if validate_output != -1:
        log_message = u"mp3-Validator fixed: " + c_source_file
        db.write_log_to_db(ac, log_message, "k")
        lib_cm.message_write_to_console(ac, "ok")
        # bak-Datei loeschen
        c_source_file = c_source_file + ".bak"
        delete_bak_ok = lib_cm.erase_file_a(ac, db, c_source_file,
            u"mp3validator-bak-Datei geloescht ")
        if delete_bak_ok is None:
            # Error 004 Fehler beim Loeschen der mp3validator-bak-Datei
            db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
                "write_also_to_console")
    else:
        db.write_log_to_db_a(ac, u"mp3-Validator fix offenbar nicht noetig: "
                             + c_source_file, "p", "write_also_to_console")


def audio_mp3gain(path_file_dest):
    """mp3-Gain"""
    lib_cm.message_write_to_console(ac, u"mp3-File Gainanpassung")
    # damit die uebergabe der befehle richtig klappt
    # muessen alle cmds im richtigen zeichensatz encoded sein
    c_mp3gain = db.ac_config_etools[5].encode(ac.app_encode_out_strings)
    c_source_file = path_file_dest.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, c_source_file)
    # start subprocess
    try:
        p = subprocess.Popen([c_mp3gain, u"-r", c_source_file],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[3] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    mp3gain_output = string.find(p[1], "99%")
    mp3gain_output_1 = string.find(p[1], "written")
    lib_cm.message_write_to_console(ac, mp3gain_output)
    lib_cm.message_write_to_console(ac, mp3gain_output_1)
    # wenn gefunden, position, sonst -1
    if mp3gain_output != -1 and mp3gain_output_1 != -1:
        log_message = u"mp3gain angepasst: " + c_source_file
        db.write_log_to_db(ac, log_message, "k")
        lib_cm.message_write_to_console(ac, "ok")
    else:
        db.write_log_to_db_a(ac, u"mp3gain offenbar nicht noetig: "
                             + c_source_file, "p", "write_also_to_console")


def add_id3(sendung_data, path_file_dest):
    """write id3-tag in mp3-file"""
    lib_cm.message_write_to_console(ac, u"id3-Tag in mp3-File schreiben")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[4].encode(ac.app_encode_out_strings)
    #cmd = "id3v2"
    c_author = (sendung_data[15].encode(
            ac.app_encode_out_strings) + " "
            + sendung_data[16].encode(ac.app_encode_out_strings))
    c_title = sendung_data[11].encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, cmd)
    # start subprocess
    try:
        p = subprocess.Popen([cmd, u"-a",
            c_author, u"-t", c_title,
            path_file_dest],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[7] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # error?
    cmd_output_1 = p[1]
    if cmd_output_1 != "":
        lib_cm.message_write_to_console(ac, cmd_output_1)
        db.write_log_to_db_a(ac,
            ac.app_errorslist[7], "x", "write_also_to_console")
        return None
    else:
        log_message = u"ID3-Tags in VP von extern geschrieben... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return True


def reg_lenght(sendung_data, path_file_dest):
    """calc length of news file and register in db"""
    lib_cm.message_write_to_console(ac, u"Laenge der VP from extern ermitteln")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[3].encode(ac.app_encode_out_strings)
    #cmd = "soxi"
    lib_cm.message_write_to_console(ac, cmd)
    # start subprozess
    try:
        p = subprocess.Popen([cmd, u"-d", path_file_dest],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[8] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])

    log_message = u"Laenge: " + p[0]
    db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")

    # reg lenght
    sql_command = ("UPDATE SG_HF_MAIN "
        + "SET SG_HF_DURATION='" + p[0][0:8] + "' "
        + "WHERE SG_HF_ID='" + str(sendung_data[0]) + "'")
    db_ok = db.exec_sql(ac, db, sql_command)
    if db_ok is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[9], "x",
            "write_also_to_console")
    else:
        log_message = u"Laenge der VP von extern in Buchung aktualisiert... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")


def date_pattern(audio_filename):
    """search date pattern and transform"""
    d_pattern = None
    l_path_title = None
    if audio_filename.find("yyyy_mm_dd") != -1:
        l_path_title = audio_filename.split("yyyy_mm_dd")
        d_pattern = "%Y_%m_%d"
    if audio_filename.find("yyyymmdd") != -1:
        l_path_title = audio_filename.split("yyyymmdd")
        d_pattern = "%Y%m%d"
    if audio_filename.find("yyyy-mm-dd") != -1:
        l_path_title = audio_filename.split("yyyy-mm-dd")
        d_pattern = "%Y-%m-%d"
    if audio_filename.find("ddmmyy") != -1:
        l_path_title = audio_filename.split("ddmmyy")
        d_pattern = "%d%m%y"

    if d_pattern is None:
        log_message = (ac.app_errorslist[6] + audio_filename)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
    return d_pattern, l_path_title


def filepaths(d_pattern, l_path_title, item, sendung):
    """concatenate paths and filenames"""
    success_file = True
    try:
        path_source = lib_cm.check_slashes(ac, db.ac_config_servpath_b[5])
        # Verschiebung von Datum Erstsendung
        new_date = sendung[2] + datetime.timedelta(days=-item[2])
        lib_cm.message_write_to_console(ac, new_date.strftime(d_pattern))

        path_file_source = (path_source + l_path_title[0]
        #+ sendung[0][2].strftime('%Y_%m_%d') + l_path_title[1].rstrip())
            + new_date.strftime(d_pattern) + l_path_title[1].rstrip())
        path_dest = lib_cm.check_slashes(ac, db.ac_config_servpath_a[2])

        # replace sonderzeichen
        # replace_uchar_sonderzeichen_with_latein
        path_file_dest = (path_dest + str(sendung[8]) + "_"
            + lib_cm.replace_sonderzeichen_with_latein(sendung[16]) + "_"
             + lib_cm.replace_sonderzeichen_with_latein(sendung[13])
        #+ lib_cm.replace_uchar_sonderzeichen_with_latein(sendung[0][13])
            + ".mp3")
    except Exception, e:
        log_message = (ac.app_errorslist[5] + "fuer: "
            + sendung[11].encode('ascii', 'ignore') + " " + str(e))
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        success_file = None

    lib_cm.message_write_to_console(ac, path_file_source)
    lib_cm.message_write_to_console(ac, path_file_dest)
    #db.write_log_to_db_a(ac, "Testpoint", "p", "write_also_to_console")

    if not os.path.isfile(path_file_source):
        lib_cm.message_write_to_console(ac, u"nicht vorhanden: "
                    + path_file_dest)
        db.write_log_to_db_a(ac,
            u"Audio Vorproduktion noch nicht vorhanden: "
            + path_file_source, "f", "write_also_to_console")
        success_file = None

    if os.path.isfile(path_file_dest):
        lib_cm.message_write_to_console(ac, u"vorhanden: " + path_file_dest)
        db.write_log_to_db_a(ac,
            u"Audiodatei fuer Sendung bereits vorhanden: " + path_file_dest,
            "k", "write_also_to_console")
        success_file = None
    return success_file, path_file_source, path_file_dest


def check_and_work_on_files(roboting_sgs):
    """
    - search audio files
    - if found, work on it
    """
    lib_cm.message_write_to_console(ac, u"check_and_work_on_files")

    for item in roboting_sgs:
        lib_cm.message_write_to_console(ac, item[0].encode('ascii', 'ignore'))
        titel = item[0]
        # Sendung suchen
        sendungen = load_sg(titel)

        if sendungen is None:
            lib_cm.message_write_to_console(ac, u"Keine Sendungen gefunden")
            continue

        for sendung in sendungen:
            db.write_log_to_db_a(ac, u"Sendung fuer VP-Uebernahme gefunden: "
                    + sendung[11].encode('ascii', 'ignore'), "t",
                    "write_also_to_console")

            # Pfad-Datei und Titel nach Datums-Muster teilen
            d_pattern, l_path_title = date_pattern(item[1])
            if d_pattern is None:
                continue

            # Pfade und Dateinamen zusammenbauen
            success_file, path_file_source, path_file_dest = filepaths(
                                    d_pattern, l_path_title, item, sendung)
            if success_file is None:
                continue

            # In Play-Out kopieren
            success_copy = audio_copy(path_file_source, path_file_dest)
            if success_copy is None:
                continue

            audio_validate(path_file_dest)
            audio_mp3gain(path_file_dest)
            add_id3(sendung, path_file_dest)
            reg_lenght(sendung, path_file_dest)

            # filename rechts von slash extrahieren
            if ac.app_windows == "no":
                filename = path_file_dest[string.rfind(path_file_dest,
                                                                    "/") + 1:]
            else:
                filename = path_file_dest[string.rfind(path_file_dest,
                                                                "\\") + 1:]

            db.write_log_to_db_a(ac, "VP bearbeitet: " + filename, "i",
                                                    "write_also_to_console")
            db.write_log_to_db_a(ac, "VP bearbeitet: " + filename, "n",
                                                    "write_also_to_console")


def lets_rock():
    """main funktion """
    print "lets_rock "
    # extendet params
    load_extended_params_ok = load_extended_params()
    if load_extended_params_ok is None:
        return
    # serach shows for processing
    roboting_sgs = load_roboting_sgs()
    if roboting_sgs is None:
        return

    # pruefen was noch nicht im play_out ist und kopieren und bearbeiten
    check_and_work_on_files(roboting_sgs)
    return


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # alles ok: weiter
        if param_check is not None:
            lets_rock()

    # finish
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
