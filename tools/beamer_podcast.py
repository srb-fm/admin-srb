#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Podcast Beamer
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at g mail
2011-09-30

Dieses Script uebertraegt Sendungen (mp3-Dateien),
die in der Datenbank als Podcast markiert wurden,
auf den Web-Server.

Dateiname Script: podcast_beamer.py
Schluesselwort fuer Einstellungen: PC_Beamer_Config_1
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank
Script auf Webspace (RSS-Fedd-Generierung): dircaster.php

Dieses Script uebertraegt Sendungen (mp3-Dateien),
die in der Datenbank als Podcast markiert wurden,
in der Reihenfolge der vorgesehenen Sendezeit, auf den Web-Server.
Vorher werden sie in geringerer Aufloesung recodiert (mp3).
Bei jedem Aufruf wird zudem ueberprueft, ob die maximale Anzahl
Podcasts (siehe Einstellungen) auf dem Webspace ueberschritten wurde.
Ueberzaehlige alte Podcasts werden auf vom Webspace geloescht.
Auf dem Webserver sorgt ein weiteres Script fuer die Generierung des RSS-Feeds.

Fehlerliste:
E 0 Parameter-Typ oder Inhalt stimmt nicht
E 1 Fehler beim Verbinden zum Podcast-ftp-Server
E 2 Fehler beim Recodieren der Podcast-mp3-Datei
E 3 Recodierte Podcast-mp3-Datei nicht gefunden
E 4 Fehler beim Loeschen der Temp-Podcast-Datei
E 5 Podcast-mp3-Datei in Play-Out nicht gefunden:

Parameterliste:
Param 1: On/Off Switch
Param 2: Pfad/Programm mp3-encoder
Param 3: none
Param 4: Pfad Sendungen Infotime/Magazin
Param 5: Pfad Sendungen normal
Param 6: Pfad temporaere Dateien fuer Encoder
Param 7: Bitrate fuer Encoder (momentan nicht genutzt)
Param 8: ftp-Host
Param 9: ftp-Benutzer
Param 10: ftp-PW
Param 11: ftp-Verzeichnis
Param 12: Anzahl Dateien, die max auf dem Podcast-Server bleiben

Das Script wird zeitgesteuert zwischen 6 und 20 Uhr
jeweils zu Minute 15 ausgefuehrt.

Die beste und sicherste Tarnung ist immer noch die blanke und nackte Wahrheit.
Die glaubt niemand! Max Frisch
"""

import sys
import os
import string
import re
import datetime
import subprocess
import ftplib
import socket
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "014"
        self.app_desc = u"Podcast_Beamer"
        # key for config in db
        self.app_config = u"PC_Beamer_Config"
        self.app_config_develop = u"PC_Beamer_Config_1_e"
        # amount parameter
        self.app_config_params_range = 12
        self.app_errorfile = "error_podcast_beamer.log"
        # dev-mod (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # show messages on console
        self.app_debug_mod = "no"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append("Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(
                        "Fehler beim Verbinden zum Podcast-ftp-Server")
        self.app_errorslist.append(
                        "Fehler beim Recodieren der Podcast-mp3-Datei")
        self.app_errorslist.append(
                        "Recodierte Podcast-mp3-Datei nicht gefunden")
        self.app_errorslist.append(
                        "Fehler beim Loeschen der Temp-Podcast-Datei")
        self.app_errorslist.append(
                        "Podcast-mp3-Datei in Play-Out nicht gefunden:")
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")

        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"
        #self.app_encode_out_strings = "utf-8"
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta(days=-1))
        self.time_target = datetime.datetime.now() + datetime.timedelta()


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
    # extern tools
    ext_params_ok = lib_cm.params_provide_tools(ac, db)
    ext_params_ok = lib_cm.params_provide_server_settings(ac, db)
    lib_cm.set_server(ac, db)
    ext_params_ok = lib_cm.params_provide_server_paths_a(ac, db,
                                                        ac.server_active)
    return ext_params_ok


def load_podcast():
    """check for Podcasts in db"""
    lib_cm.message_write_to_console(ac, u"load_podcast")

    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' AND "
                        + "SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
                        + str(ac.time_target.date()) + "' "
                        + "AND A.SG_HF_PODCAST='T' ")
    # ORDER BY A.SG_HF_TIME kommt dazu in read_tbl_rows_sg_cont_ad_with_cond_a
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_a(ac,
                                                        db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Podcast-Sendungen für: "
                       + str(ac.time_target.date()))
        db.write_log_to_db(ac, log_message, "t")
        return sendung_data

    log_message = (u"Podcast-Sendungen vorhanden für: "
                   + str(ac.time_target.date()))
    db.write_log_to_db(ac, log_message, "t")
    return sendung_data


def encode_file(podcast_sendung):
    """recode mp3-files with lower rate"""
    lib_cm.message_write_to_console(ac, u"encode_file")
    # all cmds must be in the right charset
    c_lame_encoder = db.ac_config_etools[6].encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type c_lame_encoder")
    lib_cm.message_write_to_console(ac, type(c_lame_encoder))
    lib_cm.message_write_to_console(ac, u"type podcast_sendung[1]")
    lib_cm.message_write_to_console(ac, type(podcast_sendung[1]))

    c_id3_title = u"--tt".encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type( c_id3_title )")
    lib_cm.message_write_to_console(ac, type(c_id3_title))
    #c_id3_title_value = podcast_sendung[1].encode( ac.app_encode_out_strings )
    c_id3_title_value_uni = (lib_cm.replace_sonderzeichen_with_latein(
                                                    podcast_sendung[1]))
    c_id3_title_value = c_id3_title_value_uni.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type( c_id3_title_value )")
    lib_cm.message_write_to_console(ac, type(c_id3_title_value))

    c_id3_author = u"--ta".encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type( c_id3_author )")
    lib_cm.message_write_to_console(ac, type(c_id3_author))
    id3_author_value_uni = (lib_cm.replace_sonderzeichen_with_latein(
            podcast_sendung[2]) + " "
            + lib_cm.replace_sonderzeichen_with_latein(podcast_sendung[3]))
    c_id3_author_value = id3_author_value_uni.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type(c_id3_author_value )")
    lib_cm.message_write_to_console(ac, type(c_id3_author_value))

    # source sendung
    path_sendung_source = lib_cm.check_slashes(ac, db.ac_config_servpath_a[6])
    c_source_file = (path_sendung_source.encode(ac.app_encode_out_strings)
                     + podcast_sendung[0].encode(ac.app_encode_out_strings))
    lib_cm.message_write_to_console(ac, c_source_file)

    # source infotime und mag
    path_it_mg_source = lib_cm.check_slashes(ac, db.ac_config_servpath_a[5])

    # infotime
    if podcast_sendung[4] == "T":
        #c_source_file = path_it_mg_source + podcast_sendung[0]
        c_source_file = (path_it_mg_source.encode(ac.app_encode_out_strings)
                    + podcast_sendung[0].encode(ac.app_encode_out_strings))

    # magazin
    if podcast_sendung[5] == "T":
        #c_source_file = path_it_mg_source + podcast_sendung[0]
        c_source_file = (path_it_mg_source.encode(ac.app_encode_out_strings)
                    + podcast_sendung[0].encode(ac.app_encode_out_strings))

    lib_cm.message_write_to_console(ac, c_source_file)
    lib_cm.message_write_to_console(ac, u"type(c_source_file)")
    lib_cm.message_write_to_console(ac, type(c_source_file))

    if not os.path.isfile(c_source_file):
        db.write_log_to_db_a(ac, ac.app_errorslist[5] + " "
                + podcast_sendung[0].encode(ac.app_encode_out_strings), "x",
            "write_also_to_console")
        return None

    # dest recoded file
    path_dest = lib_cm.check_slashes(ac, db.ac_config_1[6])

    c_dest_file = (path_dest.encode(ac.app_encode_out_strings)
                   + podcast_sendung[0].encode(ac.app_encode_out_strings))
    lib_cm.message_write_to_console(ac, c_dest_file)
    lib_cm.message_write_to_console(ac, u"type(c_dest_file)")
    lib_cm.message_write_to_console(ac, type(c_dest_file))

    # das geht auch
    #p = subprocess.Popen([c_lame_encoder, "--add-id3v2",  c_id3_title,
                #c_id3_title_value,
                #c_id3_author,  c_id3_author_value,
                #c_source_file, c_dest_file ]).communicate()
    # ausgaben abfangen
    #p = subprocess.Popen([c_lame_encoder, "--add-id3v2",  c_id3_title,
                #c_id3_title_value, c_id3_author,  c_id3_author_value,
                #c_source_file, c_dest_file ],
                #stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    p = subprocess.Popen([c_lame_encoder, u"--add-id3v2".encode(
                        ac.app_encode_out_strings), c_id3_title,
                        c_id3_title_value, c_id3_author, c_id3_author_value,
                        c_source_file, c_dest_file],
                        stdout=subprocess.PIPE,
                        stderr=subprocess.PIPE).communicate()

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for success-mesg, if not found: -1
    n_encode_percent = string.find(p[1], "(100%)")
    n_encode_percent_1 = string.find(p[1], "(99%)")
    lib_cm.message_write_to_console(ac, n_encode_percent)
    c_complete = "no"

    # by short files 100% will not be reached,
    # therfor also 99%
    if n_encode_percent == -1:
        # 100% not reached
        if n_encode_percent_1 != -1:
            # but 99
            c_complete = "yes"
    else:
        c_complete = "yes"

    if c_complete == "yes":
        log_message = u"recoded_file: " + c_source_file
        db.write_log_to_db(ac, log_message, "k")
        lib_cm.message_write_to_console(ac, "ok")
        return c_dest_file
    else:
        #log_message = u"recode_file Error: " + c_source_file
        #db.write_log_to_db(ac, log_message, "x")
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
        return None


def check_files_online(podcast_sendungen):
    """check what's online"""
    lib_cm.message_write_to_console(ac, u"check_files_online")

    try:
        ftp = ftplib.FTP(db.ac_config_1[8])
    except (socket.error, socket.gaierror):
        lib_cm.message_write_to_console(ac, u"ftp: no connect to: "
                                        + db.ac_config_1[8])
        return None

    ftp.login(db.ac_config_1[9], db.ac_config_1[10])
    ftp.cwd(db.ac_config_1[11])

    files_online = []
    try:
        files_online = ftp.nlst()
    except ftplib.error_perm, resp:
        if str(resp) == "550 No files found":
            lib_cm.message_write_to_console(ac,
            u"ftp: no files in this directory")
        else:
            raise

    ftp.quit()
    lib_cm.message_write_to_console(ac, files_online)

    # list of all online-files,
    # filter out admin-srb audio-files (numbers in the beginning)
    files_online_1 = []
    for item in files_online:
        if (re.match("[0-9]{7}", item) is not None):
            files_online_1.append(item)

    lib_cm.message_write_to_console(ac, files_online_1)

    # extract filenames from podcast_sendungen
    files_podcast = []
    for item in podcast_sendungen:
        files_podcast.append(item[12])

    lib_cm.message_write_to_console(ac, files_podcast)
    files_offline = list(set(files_podcast).difference(set(files_online_1)))
    lib_cm.message_write_to_console(ac, files_offline)
    # take only thirst title
    if (len(files_offline) > 0):
        file_offline = files_offline[0]
    else:
        file_offline = u"No files offline"

    lib_cm.message_write_to_console(ac, file_offline)
    return file_offline


def upload_file(podcast_sendung):
    """ upload files"""
    lib_cm.message_write_to_console(ac, u"upload_file")
    try:
        ftp = ftplib.FTP(db.ac_config_1[8])
    except (socket.error, socket.gaierror):
        lib_cm.message_write_to_console(ac, u"ftp: no connect to: "
                                        + db.ac_config_1[8])
        return None

    ftp.login(db.ac_config_1[9], db.ac_config_1[10])

    # dest recoded file
    path_source = lib_cm.check_slashes(ac, db.ac_config_1[6])
    c_source_file = path_source + podcast_sendung[0]
    lib_cm.message_write_to_console(ac, c_source_file)

    if os.path.isfile(c_source_file):
        if ac.app_windows == "yes":
            f = open(c_source_file, "rb")
        else:
            f = open(c_source_file, "r")

        ftp.cwd(db.ac_config_1[11])
        log_message = u"upload_file: " + c_source_file
        db.write_log_to_db(ac, log_message, "k")
        c_ftp_cmd = "STOR " + podcast_sendung[0]
        ftp.storbinary(c_ftp_cmd, f)
        f.close()
        db.write_log_to_db_a(ac, u"Podcast hochgeladen: "
                        + podcast_sendung[0], "i", "write_also_to_console")

    else:
        msg = u"temporaere Datei nicht vorhanden"
        lib_cm.message_write_to_console(ac, u"upload_files: "
                                        + "%r: %s" % (msg, c_source_file))
        log_message = u"upload_files: " + "%r: %s" % (msg, c_source_file)
        db.write_log_to_db(ac, log_message, "x")
        return msg

    ftp.quit()
    return log_message


def delete_files_online():
    """delete old files at Webspace"""
    lib_cm.message_write_to_console(ac, u"delete_files_online")
    try:
        ftp = ftplib.FTP(db.ac_config_1[8])
    except (socket.error, socket.gaierror):
        lib_cm.message_write_to_console(ac, u"ftp: no connect to: "
                                        + db.ac_config_1[8])
        return None

    ftp.login(db.ac_config_1[9], db.ac_config_1[10])
    ftp.cwd(db.ac_config_1[11])

    # read online-files
    files_online = []
    try:
        files_online = ftp.nlst()
    except ftplib.error_perm, resp:
        if str(resp) == "550 No files found":
            lib_cm.message_write_to_console(ac,
            u"ftp: no files in this directory")
        else:
            raise

    #ftp.quit()
    lib_cm.message_write_to_console(ac, files_online)

    # list of all online-files,
    # filter out admin-srb audio-files (numbers in the beginning)
    files_online_1 = []
    for item in files_online:
        if (re.match("[0-9]{7}", item) is not None):
            # timestamp of file
            ftp_cmd = "MDTM " + item
            ftp_mod_time = ftp.sendcmd(ftp_cmd)
            # cut reply code and save with filename in list
            if ftp_mod_time[:3] == "213":
                ftp_mod_time = ftp_mod_time[3:].strip()
                ftp_time_file = ftp_mod_time + item

            lib_cm.message_write_to_console(ac, ftp_time_file)
            files_online_1.append(ftp_time_file)

    lib_cm.message_write_to_console(ac, files_online_1)
    lib_cm.message_write_to_console(ac, u"sort..........")
    # sort filelist on timestamp/filenamenumbers
    files_online_1.sort()
    lib_cm.message_write_to_console(ac, files_online_1)

    n_anzahl_online = 0
    # check how much is online
    for item in files_online_1:
        n_anzahl_online += 1

    zz = 0
    n_anzahl_files_to_delete = n_anzahl_online - int(db.ac_config_1[12])
    if n_anzahl_online > int(db.ac_config_1[12]):
        log_message = u"Alte Podcasts auf Server loeschen.."
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "c")
        for item in files_online_1:
            ftp_file = item[14:].strip()
            lib_cm.message_write_to_console(ac, ftp_file)
            c_return = ftp.delete(ftp_file)
            lib_cm.message_write_to_console(ac, c_return)
            log_message = u"Podcast geloescht: " + ftp_file
            db.write_log_to_db(ac, log_message, "k")
            zz += 1
            if zz >= n_anzahl_files_to_delete:
                break
    else:
        db.write_log_to_db_a(ac, u"Keine Podcasts auf dem Server zu loeschen..",
                              "c", "write_also_to_console")

    ftp.quit()
    return n_anzahl_files_to_delete


def lets_rock():
    """Mainfunction """
    print "lets_rock "

    # load from db
    podcast_sendungen = load_podcast()
    if podcast_sendungen is None:
        db.write_log_to_db(ac, u"Zur Zeit kein neuer Podcast vorgesehen", "t")
        return

    # check whats not online
    podcast_offline = check_files_online(podcast_sendungen)
    if podcast_offline is None:
        # Error 1
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return

    if podcast_offline == "No files offline":
        db.write_log_to_db_a(ac, u"Alle Podcasts bereits online", "k",
            "write_also_to_console")
        return

    # take one item from offlines
    podcast_sendung = ()
    for item in podcast_sendungen:
        if item[12] == podcast_offline:
            # filename, titel, vorname, name, infotime, magazin
            podcast_sendung = (item[12], item[11], item[14], item[15],
                               item[4].strip(), item[5].strip())

    lib_cm.message_write_to_console(ac, podcast_sendung)
    podcast_sendung_temp = podcast_sendung
    # recode
    podcast_temp = encode_file(podcast_sendung)
    if podcast_temp is None:
        # try with next file
        # take one item from offlines
        podcast_sendung = ()
        for item in podcast_sendungen:
            if item[12] == podcast_offline:
                # nicht das vorige file nochmal
                print "podcast_sendung"
                print podcast_sendung
                if item[12] != podcast_sendung_temp[0]:
                    # filename, titel, vorname, name, infotime, magazin
                    podcast_sendung = (item[12], item[11], item[14],
                                item[15], item[4].strip(), item[5].strip())

        lib_cm.message_write_to_console(ac, podcast_sendung)

        if len(podcast_sendung) == 0:
            # nothing else to do
            return

        # recode nr 2. with next file
        podcast_temp_1 = encode_file(podcast_sendung)
        if podcast_temp_1 is None:
            # Error 2
            db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
                "write_also_to_console")
            return

    # upload waths not online
    upload_ok = upload_file(podcast_sendung)
    if upload_ok is None:
        # Error 1
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return

    if upload_ok == "temporaere Datei nicht vorhanden":
        # Error 3
        db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
            "write_also_to_console")
        return

    # delete temp_file from encode
    delete_temp_ok = lib_cm.erase_file_a(ac, db, podcast_temp,
        u"Temp-Podcast-Datei geloescht ")
    if delete_temp_ok is None:
        # Error 4
        db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
            "write_also_to_console")

    # delete old online-files
    delete_ok = delete_files_online()
    if delete_ok is None:
        # Error 1
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # lets start
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "r")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # all ok: continue
        if param_check is not None:
            # extended params
            load_extended_params_ok = load_extended_params()
            if load_extended_params_ok is not None:
                if db.ac_config_1[1] == "on":
                    lets_rock()
                else:
                    db.write_log_to_db_a(ac,
                                "Podcast-Beamer ausgeschaltet", "e",
                                "write_also_to_console")

    # finish
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
