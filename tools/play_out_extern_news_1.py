#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out extern News
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at googell
2013-06-30

Dieses Script laedt eine Audio-Datei von einem ftp- oder Clound-Server,
bearbeitet diese, und stellt sie der Radioautomation
zum PlayOut zur Verfuegung.

This script is for providing and editing files of radio news in the automation
from external sources, like ftp or cloud servers

Filename script: play_out_extern_news.py
Keyword for Settings: PO_News_extern_Config_1
Uses: lib_common.py im gleichen Verzeichnis
Loading data from: Firebird-Database

Fehlerliste:
E 00 Parameter-Typ oder Inhalt stimmt nich
E 01 beim Herunterladen externer News
E 02 beim Stille enfernen externer News
E 03 beim Komprimieren der Sprache externer News
E 04 beim ermitteln der Laenge externer News
E 05 beim Trimmen des Soundbeds fuer externe News
E 06 beim Mixen der externen News
E 07 beim Verketten von Layout und externer News
E 08 beim Schreiben von id3Tags in externe News
E 09 beim Aktualisieren der Sendebuchung der externen News
E 10 weder ftp noch Cloud- Diesnt definiert, Verarbeitung abgebrochen
E 11 Datum in Dateiname nicht zu finden, Zuordnung der Datei nicht moeglich


Parameterliste:
Param 1: On/Off Switch
Param 2: Title of show
Param 3: Starting minute
Param 4: Source is Dropbox
Param 5: Source is ftp
Param 6: Dropbox path/file
Param 7: ftp-URL
Param 8: ftp-username
Param 9: ftp-Password
Param 10: Path to layout files Server A
Param 11: Path to layout files Server B
Param 12: Params for audio compression

Extern tools:
This additional libs are used:
wget
sox
libsox-fmt-mp3
soxi
id3v2

Dieses Script wird zeitgesteuert
in der Stunde vor der Ausstrahlung
und nach Bereitstellung der News ausgefuehrt.
In der Regel also ca. 11:51 Uhr

This script is running via cron, several minutes before transmitting
"""

import sys
import os
import socket
import string
import subprocess
import shutil
import datetime
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "018"
        self.app_desc = u"play_out_news_extern"
        # key of config in db
        self.app_config = u"PO_News_extern_Config_Elias"
        self.app_config_develop = u"PO_News_extern_Config_1_e"
        # display debugmessages on console or no: "no"
        # for normal usage set to no!!!!!!
        self.app_debug_mod = "no"
        # using develop-params
        self.app_develop = "no"
        # number of main-parameters
        self.app_config_params_range = 12
        self.app_errorfile = "error_play_out_news_extern.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "beim Herunterladen externer News: ")
        self.app_errorslist.append(u"Error 002 "
            "beim Stille enfernen externer News: ")
        self.app_errorslist.append(u"Error 003 "
            "beim Komprimieren der Sprache externer News: ")
        self.app_errorslist.append(u"Error 004 "
            "beim ermitteln der Laenge externer News")
        self.app_errorslist.append(u"Error 005 "
            "beim Trimmen des Soundbeds fuer externe News ")
        self.app_errorslist.append(u"Error 006 "
            "beim Mixen der externen News")
        self.app_errorslist.append(u"Error 007 "
            "beim Verketten von Layout und externer News")
        self.app_errorslist.append(u"Error 008 "
            "beim Schreiben von id3Tags in externe News")
        self.app_errorslist.append(u"Error 009 "
            "beim Aktualisieren der Sendebuchung der externen News")
        self.app_errorslist.append(u"Error 010 "
            "Weder ftp noch Cloud- Dienst f. ext News definiert, "
            "Verarbeitung abgebrochen")
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_url")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"

        # runtime approximately 10 minutes before transmitting
        self.time_target_start = (datetime.datetime.now()
                            + datetime.timedelta(hours=+1))
        self.app_file_orig_temp = "News_ext_orig"
        self.app_file_bed = "News_ext_Automation_Bed.wav"
        self.app_file_bed_trim = "News_ext_Automation_Bed_trimmed.wav"
        self.app_file_intro = "News_ext_Automation_Intro.wav"
        self.app_file_closer = "News_ext_Automation_Closer.wav"


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
    # extern tools
    ext_params_ok = lib_cm.params_provide_tools(ac, db)
    ext_params_ok = lib_cm.params_provide_server_settings(ac, db)
    lib_cm.set_server(ac, db)
    ext_params_ok = lib_cm.params_provide_server_paths_a(ac, db,
                                                        ac.server_active)
    ext_params_ok = lib_cm.params_provide_server_paths_b(ac, db,
                                                        ac.server_active)
    return ext_params_ok


def load_sg():
    """search news in db"""
    lib_cm.message_write_to_console(ac, "search news in db")

    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 16) = '"
        + ac.time_target_start.strftime("%Y-%m-%d %H")
        + ":" + db.ac_config_1[3].strip() + "' "
        "AND A.SG_HF_INFOTIME = 'T' "
        "AND B.SG_HF_CONT_TITEL = '" + db.ac_config_1[2].strip() + "'")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_b(ac,
        db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine externen News "
            "fuer diese Zeit vorgesehen: "
            + ac.time_target_start.strftime("%Y-%m-%d %H")
            + ":" + db.ac_config_1[3].strip() + " Uhr")
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return sendung_data
    return sendung_data


def filepaths():
    """concatenate path and filename"""
    #d_h_pattern, l_path_title = date_hour_pattern(db.ac_config_1[6])

    path_source = lib_cm.check_slashes(ac, db.ac_config_servpath_b[5])
    path_file_source_conf = path_source + db.ac_config_1[6]
    d_h_pattern, l_path_title = date_hour_pattern(path_file_source_conf)
    if d_h_pattern is None:
        return None, None
    #curr_date_hour = ac.time_target_start.strftime("%Y_%m_%d_%H")
    date_hour = ac.time_target_start.strftime("%Y_%m_%d_%H")
    #lib_cm.message_write_to_console(ac, curr_date_hour)
    #path_file_source = path_source + curr_date_hour + "_Elias_News_ohne.mp3"
    path_file_source = (l_path_title[0]
            + date_hour + l_path_title[1].rstrip())
    lib_cm.message_write_to_console(ac, path_file_source)
    temp_orig_file = ac.app_file_orig_temp + ".mp3"
    return path_file_source, temp_orig_file


def date_hour_pattern(path_filename):
    """find datepattern, return two parts"""
    d_h_pattern = None
    l_path_title = None
    if path_filename.find("yyyy_mm_dd_hh") != -1:
        l_path_title = path_filename.split("yyyy_mm_dd_hh")
        d_h_pattern = "%Y_%m_%d_%H"
    if path_filename.find("yyyymmddhh") != -1:
        l_path_title = path_filename.split("yyyymmddhh")
        d_h_pattern = "%Y%m%d%H"
    if path_filename.find("yyyy-mm-dd-hh") != -1:
        l_path_title = path_filename.split("yyyy-mm-dd-hh")
        d_h_pattern = "%Y-%m-%d-%H"
    if path_filename.find("ddmmyyhh") != -1:
        l_path_title = path_filename.split("ddmmyyhh")
        d_h_pattern = "%d%m%y%H"

    if d_h_pattern is None:
        log_message = (ac.app_errorslist[11] + path_filename)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
    return d_h_pattern, l_path_title


def fetch_media_ftp(dest_file):
    """mp3-File von Server holen"""
    lib_cm.message_write_to_console(ac, u"mp3-File von Server holen")
    # all cmds must be in the right charset
    cmd = db.ac_config_etools[1].encode(ac.app_encode_out_strings)
    #cmd = "wget"
    url_source_file = db.ac_config_1[7].encode(ac.app_encode_out_strings)
    url_user = "--user=" + db.ac_config_1[8].encode(ac.app_encode_out_strings)
    url_pw = "--password=" + db.ac_config_1[9].encode(ac.app_encode_out_strings)
    # starting subprozess
    try:
        p = subprocess.Popen([cmd, url_user, url_pw, url_source_file],
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
        log_message = "Externe News heruntergeladen... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        file_orig = lib_cm.extract_filename(ac, db.ac_config_1[7])
        lib_cm.message_write_to_console(ac, file_orig)
        lib_cm.message_write_to_console(ac, dest_file)
        os.rename(file_orig, dest_file)
        return True
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[1]
            + u"100% beim Download nicht erreicht...",
            "x", "write_also_to_console")
        return None


def copy_media_db(path_file_source, dest_file):
    """copy audiofile"""
    success_copy = None
    try:
        shutil.copy(path_file_source, dest_file)
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


def trim_silence(temp_orig_file):
    """Stille am Anfang und Ende entfernen"""
    lib_cm.message_write_to_console(ac, u"Stille am Anfang und Ende entfernen")
    # all cmds must be in the right charset
    cmd = db.ac_config_etools[2].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, cmd)
    dest_file = ac.app_file_orig_temp + ".wav"
    lib_cm.message_write_to_console(ac, temp_orig_file)
    # start subprozess
    #silence 1 0.1 1% reverse silence 1 0.1 1% reverse
    try:
        p = subprocess.Popen([cmd, u"-S", temp_orig_file, dest_file,
            u"silence", u"1", u"0.1", u"1%", u"reverse",
            u"silence", u"1", u"0.1", u"1%", u"reverse"],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[2] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    #lib_cm.message_write_to_console(ac, u"returncode 0" )
    #lib_cm.message_write_to_console(ac, p[0] )
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for success-msg, if not found: -1
    cmd_output_1 = string.find(p[1], "100%")
    lib_cm.message_write_to_console(ac, cmd_output_1)

    if cmd_output_1 != -1:
        log_message = u"Audio getrimmt... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[2]
            + u"100% beim Trimmen nicht erreicht...",
            "x", "write_also_to_console")
        return None


def trim_bed(c_lenght):
    """trim soundbed to length of news"""
    lib_cm.message_write_to_console(ac, u"Soundbed auf News trimmen")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[2].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, c_lenght)
    source_path = ac.app_homepath + lib_cm.check_slashes(ac, db.ac_config_1[10])
    source_file = source_path + ac.app_file_bed
    dest_file = ac.app_file_bed_trim
    # start subprocess
    #silence 1 0.1 1% reverse silence 1 0.1 1% reverse
    l = c_lenght[0:7]
    try:
        p = subprocess.Popen([cmd, u"-S", source_file, dest_file,
            u"trim", u"0", l],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[5] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for success-msg, if not found: -1
    #cmd_output_1 = string.find(p[1], c_lenght[0:8])
    # Suche nach exacter Time ist nicht immer moeglich
    # Es gibt Abweichungen um Sekunden und Hundertstel
    cmd_output_1 = string.find(p[1], "Done.")

    if cmd_output_1 != -1:
        log_message = u"Bed getrimmt... " + c_lenght[0:8]
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[5]
            + u"Erforderliche Laenge beim Trimmen nicht erreicht...",
            "x", "write_also_to_console")
        return None


def compand_voice():
    """compand voice"""
    lib_cm.message_write_to_console(ac, u"Sprache komprimieren")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[2].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, cmd)
    source_file = ac.app_file_orig_temp + ".wav"
    #dest_file = lib_cm.extract_filename(ac,
    #            db.ac_config_1[6]).replace(".mp3", "_comp.wav")
    dest_file = ac.app_file_orig_temp + "_comp.wav"
    lib_cm.message_write_to_console(ac, source_file)
    compand_prams = db.ac_config_1[12].split()
    lib_cm.message_write_to_console(ac, compand_prams)
    # start subprozess
    #compand 0.3,1 6:-70,-60,-20 -5 -90
    try:
        p = subprocess.Popen([cmd, u"-S", source_file, dest_file,
            #u"compand", u"0.3,1","6:-70,-60,-20", u"-12", u"-90", u"0.2"],
            #u"compand", u"0.3,1","-80,-60,-75,-16", u"-18", u"-80", u"0.2"],
            u"compand", compand_prams[0], compand_prams[1],
            compand_prams[2], compand_prams[3]],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[3] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for success-msg, if not found: -1
    cmd_output_1 = string.find(p[1], "100%")
    lib_cm.message_write_to_console(ac, cmd_output_1)

    if cmd_output_1 != -1:
        log_message = u"Sprache komprimiert... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[3]
            + u"100% beim Komprimieren nicht erreicht...",
            "x", "write_also_to_console")
        return None


def check_lenght(source_file):
    """calc length of news file"""
    lib_cm.message_write_to_console(ac, u"Laenge der News ermitteln")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[3].encode(ac.app_encode_out_strings)
    #cmd = "soxi"
    lib_cm.message_write_to_console(ac, cmd)
    # start subprozess
    try:
        p = subprocess.Popen([cmd, u"-d", source_file],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[4] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])

    log_message = u"Laenge: " + p[0]
    db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
    return p[0]


def mix_bed():
    """adding soundbed"""
    lib_cm.message_write_to_console(ac, u"Soundbed drunter legen")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[2].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, cmd)
    #news_file = lib_cm.extract_filename(ac,
    #            db.ac_config_1[6]).replace(".mp3", "_comp.wav")
    news_file = ac.app_file_orig_temp + "_comp.wav"
    news_file_temp = news_file.replace("_comp.wav", "_temp.wav")
    # start subprocess
    #silence 1 0.1 1% reverse silence 1 0.1 1% reverse
    try:
        p = subprocess.Popen([cmd, u"-S", u"-m",
            ac.app_file_bed_trim, news_file,
            news_file_temp],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[6] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for success-msg, if not found: -1
    cmd_output_1 = string.find(p[1], "100%")
    #cmd_output_1 = string.find( p[1], "written" )
    #lib_cm.message_write_to_console(ac, cmd_output )
    #lib_cm.message_write_to_console(ac, cmd_output_1 )
    if cmd_output_1 != -1:
        log_message = u"Bed mixed... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[6]
            + u"100% beim Mixen nicht erreicht...",
            "x", "write_also_to_console")
        return None


def concatenate_media(filename):
    """concatenate audio files"""
    lib_cm.message_write_to_console(ac, u"mp3-Files kombinieren")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[2].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    #news_file = lib_cm.extract_filename(ac,
    #            db.ac_config_1[6]).replace("mp3", "wav")
    news_file = ac.app_file_orig_temp + ".wav"
    news_file_temp = news_file.replace(".wav", "_temp.wav")
    #source_path = lib_cm.check_slashes(ac, db.ac_config_1[10])
    source_path = ac.app_homepath + lib_cm.check_slashes(ac, db.ac_config_1[10])
    #source_file_intro = source_path + "News_ext_Automation_Intro.wav"
    source_file_intro = source_path + ac.app_file_intro
    #source_file_closer = source_path + "News_ext_Automation_Closer.wav"
    source_file_closer = source_path + ac.app_file_closer
    #dest_path = lib_cm.check_slashes(ac, db.ac_config_1[11])
    dest_path = lib_cm.check_slashes(ac, db.ac_config_servpath_a[1])
    dest_path_file = dest_path + filename
    lib_cm.message_write_to_console(ac, cmd)
    # start subprocess
    try:
        p = subprocess.Popen([cmd, u"-S",
            source_file_intro, news_file_temp, source_file_closer,
            u"-C" u"192.2", dest_path_file],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[7] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for success-msg, if not found: -1
    cmd_output_1 = string.find(p[1], "100%")
    if cmd_output_1 != -1:
        log_message = (u"Externe News bearbeitet"
            + " und in Play-Out bereitgestellt... ")
        db.write_log_to_db_a(ac, log_message, "i", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[7]
            + u"100% beim Kombinieren nicht erreicht...",
            "x", "write_also_to_console")
        return None


def add_id3(sendung_data):
    """write id3-tag in mp3-file"""
    lib_cm.message_write_to_console(ac, u"id3-Tag in mp3-File schreiben")
    # use the right char-encoding for supprocesses
    cmd = db.ac_config_etools[4].encode(ac.app_encode_out_strings)
    #cmd = "id3v2"
    #dest_path = lib_cm.check_slashes(ac, db.ac_config_1[11])
    dest_path = lib_cm.check_slashes(ac, db.ac_config_servpath_a[1])
    dest_path_file = dest_path + sendung_data[12]
    c_author = (sendung_data[15].encode(
            ac.app_encode_out_strings) + " "
            + sendung_data[16].encode(ac.app_encode_out_strings))
    c_title = sendung_data[11].encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, cmd)
    # start subprocess
    try:
        p = subprocess.Popen([cmd, u"-a",
            c_author, u"-t", c_title,
            dest_path_file],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[8] + u": %s" % str(e)
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
            ac.app_errorslist[8], "x", "write_also_to_console")
        return None
    else:
        log_message = u"ID3-Tags in Externe News geschrieben... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"


def collect_garbage(garbage_counter):
    """ clean up temp files"""
    if garbage_counter >= 2:
        #temp_file = lib_cm.extract_filename(ac, db.ac_config_1[6])
        temp_file = ac.app_file_orig_temp + ".mp3"
        lib_cm.erase_file_a(ac, db, temp_file,
            u"Externe News-mp3-Datei geloescht ")

    if garbage_counter >= 2:
        temp_file_1 = temp_file.replace("mp3", "wav")
        lib_cm.erase_file_a(ac, db, temp_file_1,
            u"Externe News-wav-Datei geloescht ")

    if garbage_counter >= 3:
        temp_file_2 = temp_file_1.replace(".wav", "_comp.wav")
        lib_cm.erase_file_a(ac, db, temp_file_2,
            u"Externe News-comp-Datei geloescht ")

    if garbage_counter >= 4:
        lib_cm.erase_file_a(ac, db, ac.app_file_bed_trim,
            u"Externe News-Bed-Datei geloescht ")

    if garbage_counter == 5:
        temp_file_2 = temp_file_1.replace(".wav", "_temp.wav")
        lib_cm.erase_file_a(ac, db, temp_file_2,
            "Externe News-temp-Datei geloescht ")


def lets_rock():
    """mainfunction """
    print "lets_rock "
    # prepare path
    ac.app_homepath = "/home/" + socket.gethostname()

    sendung_data = load_sg()
    if sendung_data is None:
        return

    if db.ac_config_1[4] == "yes":
        # dropbox
        path_file_source, temp_orig_file = filepaths()
        copy_ok = copy_media_db(path_file_source, temp_orig_file)
        if copy_ok is None:
            return

    if db.ac_config_1[5] == "yes":
        # ftp
        temp_orig_file = ac.app_file_orig_temp + ".mp3"
        download_ok = fetch_media_ftp(temp_orig_file)
        if download_ok is None:
            return

    if db.ac_config_1[4] != "yes" and db.ac_config_1[5] != "yes":
        db.write_log_to_db_a(ac, ac.app_errorslist[10], "x",
            "write_also_to_console")
        return

    trim_ok = trim_silence(temp_orig_file)
    if trim_ok is None:
        collect_garbage(2)
        return

    compand_ok = compand_voice()
    if compand_ok is None:
        collect_garbage(2)
        return

    #source_file = lib_cm.extract_filename(ac,
    #            db.ac_config_1[6]).replace("mp3", "wav")
    source_file = ac.app_file_orig_temp + ".wav"
    lenght_news = check_lenght(source_file)
    if lenght_news is None:
        return

    trim_bed_ok = trim_bed(lenght_news)
    if trim_bed_ok is None:
        collect_garbage(4)
        return

    mix_bed_ok = mix_bed()
    if mix_bed_ok is None:
        collect_garbage(4)
        return

    concatenate_ok = concatenate_media(sendung_data[0][12])
    if concatenate_ok is None:
        collect_garbage(4)
        return

    id3_ok = add_id3(sendung_data[0])
    if id3_ok is None:
        return

    #dest_path = lib_cm.check_slashes(ac, db.ac_config_1[11])
    dest_path = lib_cm.check_slashes(ac, db.ac_config_servpath_a[1])
    source_file = dest_path + sendung_data[0][12]
    lenght_news = check_lenght(source_file)
    if lenght_news is None:
        return

    # Laenge eintragen
    sql_command = ("UPDATE SG_HF_MAIN "
        + "SET SG_HF_DURATION='" + lenght_news[0:8] + "' "
        + "WHERE SG_HF_ID='" + str(sendung_data[0][0]) + "'")
    db_ok = db.exec_sql(ac, db, sql_command)
    if db_ok is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[9], "x",
            "write_also_to_console")
    else:
        log_message = u"Laenge der externen News in Buchung aktualisiert... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")

    collect_garbage(5)

if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # let's start'
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        # check main-params
        param_check = lib_cm.params_check_1(ac, db)
        if param_check is not None:
            # extended params
            load_extended_params_ok = load_extended_params()
            if load_extended_params_ok is not None:
                if db.ac_config_1[1] == "on":
                    lets_rock()
                else:
                    db.write_log_to_db_a(ac, ac.app_desc
                                    + " ausgeschaltet", "e",
                                    "write_also_to_console")
    # finish
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
