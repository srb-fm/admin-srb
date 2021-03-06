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
E 00 Parameter-Typ oder Inhalt stimmt nicht
E 01 Fehler beim Kopieren der Vorproduktion in Play-Out
E 02 beim mp3-Validator:
E 03 bei mp3gain:
E 04 Fehler beim Loeschen der mp3validator-bak-Datei
E 05 Fehler beim Generieren des Dateinamens:
E 06 Fehler beim Datums-Muster
E 07 Fehler beim Schreiben von id3Tags in VP von extern
E 08 Fehler beim Ermitteln der Laenge VP von extern
E 09 Fehler beim Aktualisieren der Sendebuchung der VP von extern


Parameterliste:
P 1: On/Off Switch
P 2: none
P 3: ftp-Verzeichnis
P 4: ftp-Host
P 5: ftp-Benutzer
P 6: ftp-PW

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
import lib_audio as lib_au
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "005"
        self.app_desc = u"play_out_load_vp_extern"
        # debug-mod
        self.app_debug_mod = "no"
        # key of config in db
        self.app_config = u"PO_VP_extern_Config"
        self.app_config_develop = u"PO_VP_extern_Config_3_e"
        # nunber of parameters
        self.app_config_params_range = 6
        self.app_errorfile = "error_play_out_load_vp_extern.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(self.app_desc +
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Kopieren der Vorproduktion in Play-Out")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim mp3-Validator: ")
        self.app_errorslist.append(self.app_desc + "Fehler bei mp3gain: ")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Loeschen der mp3validator-bak-Datei")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Generieren des Dateinamens ")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Datums-Muster: ")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Schreiben von id3Tags in VP von extern")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Ermitteln der Laenge VP von extern")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Aktualisieren der Sendebuchung der VP von extern")

        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")

        # develop-mod
        self.app_develop = "no"
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
    lib_cm.message_write_to_console(ac, "search for radio-shows")
    sendungen_data = db.read_tbl_rows_with_cond(ac, db,
        "SG_HF_ROBOT",
        "SG_HF_ROB_TITEL, "
        "SG_HF_ROB_IN_DROPB, SG_HF_ROB_FILE_IN_DB, "
        "SG_HF_ROB_IN_FTP, SG_HF_ROB_FILE_IN_FTP, "
        " SG_HF_ROB_SHIFT",
        "SG_HF_ROB_VP_IN ='T'")

    if sendungen_data is None:
        log_message = "Keine VP-Uebernahme vorgesehen.. "
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
        log_message = ("Keine Sendung mit diesem Titel gefunden: "
                            + sg_titel.encode('ascii', 'ignore'))
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return sendung_data

    return sendung_data


def audio_copy(path_file_source, path_file_dest):
    """copy audiofile"""
    success_copy = None
    try:
        shutil.copy(path_file_source, path_file_dest)
        db.write_log_to_db_a(ac, "Audio Vorproduktion: "
                + path_file_source.encode('ascii', 'ignore'),
                "v", "write_also_to_console")
        db.write_log_to_db_a(ac, "Audio kopiert nach: "
                + path_file_dest, "c", "write_also_to_console")
        success_copy = True
    except Exception, e:
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        log_message = u"copy_files_to_dir_retry Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
    return success_copy


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
        # Verschiebung von Datum Erstsendung
        new_date = sendung[2] + datetime.timedelta(days=-item[5])
        lib_cm.message_write_to_console(ac, new_date.strftime(d_pattern))

        if item[1].strip() == "T":
            # from dropbox
            path_source = lib_cm.check_slashes(ac, db.ac_config_servpath_b[5])
            path_file_source = (path_source + l_path_title[0]
            #+ sendung[0][2].strftime('%Y_%m_%d') + l_path_title[1].rstrip())
            + new_date.strftime(d_pattern) + l_path_title[1].rstrip())

        if item[3].strip() == "T":
            # from ftp
            #url_base = db.ac_config_1[3].encode(ac.app_encode_out_strings)
            #url_source_file = db.ac_config_1[7].encode(ac.app_encode_out_strings)
            path_source = lib_cm.check_slashes(ac, db.ac_config_1[3])
            path_file_source = (path_source + l_path_title[0]
            #+ sendung[0][2].strftime('%Y_%m_%d') + l_path_title[1].rstrip())
            + new_date.strftime(d_pattern) + l_path_title[1].rstrip())

        # it or mag else sendung
        if sendung[4].strip() == "T" or sendung[5].strip() == "T":
            path_dest = lib_cm.check_slashes(ac, db.ac_config_servpath_a[1])
        else:
            path_dest = lib_cm.check_slashes(ac, db.ac_config_servpath_a[2])

        # replace special char
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

    return success_file, path_file_source, path_file_dest


def check_file_dest_play_out(path_file_dest, sendung):
    """check if file exist in play-out"""
    lib_cm.message_write_to_console(ac, "check if file exist in play-out")
    success_file = None
    if os.path.isfile(path_file_dest):
        filename = lib_cm.extract_filename(ac, path_file_dest)
        lib_cm.message_write_to_console(ac, "vorhanden: "
                    + filename)
        db.write_log_to_db_a(ac,
            "Vorproduktion von extern bereits in Play_Out vorhanden: "
            + sendung[12], "f", "write_also_to_console")
        success_file = True
    return success_file


def check_file_source_cloud(path_file_source):
    """check if file exist in dropbox"""
    lib_cm.message_write_to_console(ac, "check_files_cloud")
    file_is_online = False
    if os.path.isfile(path_file_source):
        filename = lib_cm.extract_filename(ac, path_file_source)
        lib_cm.message_write_to_console(ac, "vorhanden: " + path_file_source)
        db.write_log_to_db_a(ac,
            "Vorproduktion von extern in Cloud vorhanden: "
            + filename,
            "k", "write_also_to_console")
        file_is_online = True
    return file_is_online


def fetch_media_ftp(dest_file, url_source_file):
    """mp3-File von Server holen"""
    lib_cm.message_write_to_console(ac, u"mp3-File von Server holen")
    # all cmds must be in the right charset
    cmd = db.ac_config_etools[1].encode(ac.app_encode_out_strings)
    #cmd = "wget"
    #url_base = db.ac_config_1[3].encode(ac.app_encode_out_strings)

    if url_source_file[0:7] == "http://":
        # downlaod via http must become another wget-syntax
        url_user = ("--user="
                        + db.ac_config_1[5].encode(ac.app_encode_out_strings))
        url_pw = ("--password="
                        + db.ac_config_1[6].encode(ac.app_encode_out_strings))
        # starting subprozess
        try:
            p = subprocess.Popen([cmd, url_user, url_pw, url_source_file],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
        except Exception, e:
            log_message = ac.app_errorslist[1] + u": %s" % str(e)
            db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
            return
    else:
        # download via ftp
        ftp_url_source_file = (
                        db.ac_config_1[4].encode(ac.app_encode_out_strings)
                        + url_source_file)
        # starting subprozess
        try:
            p = subprocess.Popen([cmd, ftp_url_source_file],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
        except Exception, e:
            log_message = ac.app_errorslist[1] + u": %s" % str(e)
            db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
            return

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for success-msg, if not found: -1
    cmd_output_1 = string.find(p[1], "100%")
    lib_cm.message_write_to_console(ac, cmd_output_1)
    # if found, position, otherwise -1
    if cmd_output_1 != -1:
        log_message = "Externe VP heruntergeladen... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        file_orig = lib_cm.extract_filename(ac, db.ac_config_1[7])
        lib_cm.message_write_to_console(ac, file_orig)
        lib_cm.message_write_to_console(ac, dest_file)
        #os.rename(file_orig, dest_file)
        return True
    else:
        # no 100% message, trying to find another error, here in german!
        cmd_output_1 = string.find(p[1], "gibt es nicht")
        if cmd_output_1 != -1:
            db.write_log_to_db_a(ac, "Datei auf ftp-Server nicht vorhanden: "
                + url_source_file, "t", "write_also_to_console")
        else:
            db.write_log_to_db_a(ac, ac.app_errorslist[1]
            + u"100% beim Download nicht erreicht...",
            "x", "write_also_to_console")
            filename_ftp_temp = lib_cm.extract_filename(ac,
                                                        url_source_file)
            lib_cm.erase_file_a(ac, db, filename_ftp_temp,
                                    "temp-Datei geloescht ")
        return None


def copy_media_to_play_out(path_file_source, dest_file):
    """copy audiofile"""
    success_copy = None
    try:
        shutil.move(path_file_source, dest_file)
        db.write_log_to_db_a(ac, u"Audio Vorproduktion: "
                + path_file_source.encode('ascii', 'ignore'),
                "v", "write_also_to_console")
        db.write_log_to_db_a(ac, u"Audio kopiert: "
                + dest_file, "c", "write_also_to_console")
        success_copy = True
    except Exception, e:
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        log_message = u"copy_files_to_dir_retry Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
    return success_copy


def check_and_work_on_files(roboting_sgs):
    """
    - search audio files
    - if found, work on it
    """
    lib_cm.message_write_to_console(ac, u"check_and_work_on_files")

    for item in roboting_sgs:
        lib_cm.message_write_to_console(ac, item[0].encode('ascii', 'ignore'))
        titel = item[0]
        # search shows
        sendungen = load_sg(titel)

        if sendungen is None:
            lib_cm.message_write_to_console(ac, "Keine Sendungen gefunden")
            continue

        for sendung in sendungen:
            db.write_log_to_db_a(ac, "Sendung fuer VP-Uebernahme gefunden: "
                    + sendung[11].encode('ascii', 'ignore'), "t",
                    "write_also_to_console")

            # path-file split with date-pattern
            if item[1].strip() == "T":
                d_pattern, l_path_title = date_pattern(item[2])
            if item[3].strip() == "T":
                d_pattern, l_path_title = date_pattern(item[4])
            if d_pattern is None:
                continue

            # concatenate path and filename
            success_file, path_file_source, path_file_dest = filepaths(
                                    d_pattern, l_path_title, item, sendung)
            if success_file is None:
                continue

            # check if file always in play-out
            file_in_play_out = check_file_dest_play_out(path_file_dest, sendung)
            if file_in_play_out is True:
                continue

            if item[1].strip() == "T":
                # check if file in dropbox
                file_in_cloud = check_file_source_cloud(path_file_source)
                if file_in_cloud is False:
                    continue

                # In Play-Out kopieren
                success_copy = audio_copy(path_file_source, path_file_dest)
                if success_copy is None:
                    continue

            if item[3].strip() == "T":
                # fetch file from ftp
                filename_ftp_temp = lib_cm.extract_filename(ac,
                                                        path_file_source)
                file_from_ftp = (fetch_media_ftp(
                                    path_file_dest, path_file_source))
                if file_from_ftp is None:
                    continue

                copy_success = copy_media_to_play_out(
                                    filename_ftp_temp, path_file_dest)
                if copy_success is None:
                    continue

            #audio_validate(path_file_dest)
            success_mp3validate = lib_au.validate_mp3(
                                        ac, db, lib_cm, path_file_dest)

            if success_mp3validate is None:
                db.write_log_to_db_a(ac, ac.app_errorslist[4],
                                        "x", "write_also_to_console")

            success_add_id3 = lib_au.add_id3(
                                ac, db, lib_cm, sendung, path_file_dest)

            if success_add_id3 is None:
                db.write_log_to_db_a(ac, ac.app_errorslist[7],
                                        "x", "write_also_to_console")
                continue

            # mp3gain must proceed after id3-tag is written
            # python-rgain has an error if no id3-tag is present
            #audio_mp3gain(path_file_dest)
            #success_add_rgain = lib_au.add_replaygain(
            #                    ac, db, lib_cm, path_file_dest)

            #if success_add_rgain is None:
            #    db.write_log_to_db_a(ac, ac.app_errorslist[3],
            #                            "x", "write_also_to_console")

            success_add_mp3gain = lib_au.add_mp3gain(
                                        ac, db, lib_cm, path_file_dest)

            if success_add_mp3gain is None:
                db.write_log_to_db_a(ac, ac.app_errorslist[3],
                                        "x", "write_also_to_console")

            reg_lenght(sendung, path_file_dest)

            # filename rechts von slash extrahieren
            if ac.app_windows == "no":
                filename = path_file_dest[string.rfind(path_file_dest,
                                                                    "/") + 1:]
            else:
                filename = path_file_dest[string.rfind(path_file_dest,
                                                                "\\") + 1:]

            #db.write_log_to_db_a(ac, "VP bearbeitet: " + filename, "i",
            #                                        "write_also_to_console")
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

    # check, if not in play_out, copy and editing
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
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac,
                                    "Play-Out-Extern-VP ausgeschaltet",
                                    "e", "write_also_to_console")

    # finish
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
