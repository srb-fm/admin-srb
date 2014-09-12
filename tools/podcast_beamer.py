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
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 Fehler beim Verbinden zum Podcast-ftp-Server
Error 002 Fehler beim Recodieren der Podcast-mp3-Datei
Error 003 Recodierte Podcast-mp3-Datei nicht gefunden
Error 004 Fehler beim Loeschen der Temp-Podcast-Datei

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
        """Einstellungen"""
        # app_config
        self.app_id = "014"
        self.app_desc = u"Podcast_Beamer"
        # schluessel fuer config in db
        self.app_config = u"PC_Beamer_Config_4"
        self.app_config_develop = u"PC_Beamer_Config_1_e"
        # anzahl parameter
        self.app_config_params_range = 12
        self.app_errorfile = "error_podcast_beamer.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim Verbinden zum Podcast-ftp-Server")
        self.app_errorslist.append(u"Error 002 "
            "Fehler beim Recodieren der Podcast-mp3-Datei")
        self.app_errorslist.append(u"Error 003 "
            "Recodierte Podcast-mp3-Datei nicht gefunden")
        self.app_errorslist.append(u"Error 004 "
            "Fehler beim Loeschen der Temp-Podcast-Datei")
        # params-type-list, typ entsprechend der params-liste in der config
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

        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"
        #self.app_encode_out_strings = "utf-8"
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta(days=-1))
        self.time_target = datetime.datetime.now() + datetime.timedelta()


def load_podcast():
    """Pruefen ob Sendungen als Podcast zur Verfuegung stehen"""
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

    # log schreiben
    log_message = (u"Podcast-Sendungen vorhanden für: "
                   + str(ac.time_target.date()))
    db.write_log_to_db(ac, log_message, "t")
    return sendung_data


def encode_file(podcast_sendung):
    """mp3-files mit geringerer Bitrate encoden"""
    lib_cm.message_write_to_console(ac, u"encode_file")
    # damit die uebergabe der befehle richtig klappt
    # muessen alle cmds im richtigen zeichensatz encoded sein
    c_lame_encoder = db.ac_config_1[2].encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type c_lame_encoder")
    lib_cm.message_write_to_console(ac, type(c_lame_encoder))
    lib_cm.message_write_to_console(ac, u"type podcast_sendung[1]")
    lib_cm.message_write_to_console(ac, type(podcast_sendung[1]))

    #c_id3_title = "--tt"
    c_id3_title = u"--tt".encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type( c_id3_title )")
    lib_cm.message_write_to_console(ac, type(c_id3_title))
    #c_id3_title_value = podcast_sendung[1].encode( ac.app_encode_out_strings )
    c_id3_title_value_uni = (lib_cm.replace_sonderzeichen_with_latein(
                                                    podcast_sendung[1]))
    c_id3_title_value = c_id3_title_value_uni.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, u"type( c_id3_title_value )")
    lib_cm.message_write_to_console(ac, type(c_id3_title_value))
    #c_id3_author = "--ta"
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
    #path_sendung_source = db.ac_config_1[4]
    path_sendung_source = lib_cm.check_slashes(ac, db.ac_config_1[5])

    #c_source_file = path_sendung_source + podcast_sendung[0]
    c_source_file = (path_sendung_source.encode(ac.app_encode_out_strings)
                     + podcast_sendung[0].encode(ac.app_encode_out_strings))
    lib_cm.message_write_to_console(ac, c_source_file)

    # source infotime und mag
    #path_it_mg_source = db.ac_config_1[3]
    path_it_mg_source = lib_cm.check_slashes(ac, db.ac_config_1[4])

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

    # dest recoded file
    #path_dest = db.ac_config_1[5]
    path_dest = lib_cm.check_slashes(ac, db.ac_config_1[6])

    #c_dest_file = path_dest + podcast_sendung[0]
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
    #print p
    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    n_encode_percent = string.find(p[1], "(100%)")
    n_encode_percent_1 = string.find(p[1], "(99%)")
    lib_cm.message_write_to_console(ac, n_encode_percent)
    c_complete = "no"

    # bei kurzen files kommt die 100% meldung nicht,
    # deshalb auch 99% durchgehen lassen
    if n_encode_percent == -1:
        # 100% nicht erreicht
        if n_encode_percent_1 != -1:
            # aber 99
            c_complete = "yes"
    else:
        c_complete = "yes"

    if c_complete == "yes":
        log_message = u"recoded_file: " + c_source_file
        db.write_log_to_db(ac, log_message, "k")
        lib_cm.message_write_to_console(ac, "ok")
        return c_dest_file
    else:
        log_message = u"recode_file Error: " + c_source_file
        db.write_log_to_db(ac, log_message, "x")
        return None


def check_files_online(podcast_sendungen):
    """Pruefen welche Podcasts schon online sind"""
    lib_cm.message_write_to_console(ac, u"check_files_online")

    try:
        ftp = ftplib.FTP(db.ac_config_1[8])
    except (socket.error, socket.gaierror):
        #print 'ERROR: cannot reach "%s"' % db.ac_config_1[7]
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

    # liste enthaelt alle online-dateien,
    # nun audiodateien herausfiltern (nummernblock am anfang)
    files_online_1 = []
    for item in files_online:
        if (re.match("[0-9]{7}", item) is not None):
            files_online_1.append(item)

    lib_cm.message_write_to_console(ac, files_online_1)

    # aus podcast_sendungen nur filenames rausholen
    files_podcast = []
    for item in podcast_sendungen:
        files_podcast.append(item[12])

    lib_cm.message_write_to_console(ac, files_podcast)
    files_offline = list(set(files_podcast).difference(set(files_online_1)))
    lib_cm.message_write_to_console(ac, files_offline)
    # nur den ersten titel mitnehmen
    if (len(files_offline) > 0):
        file_offline = files_offline[0]
    else:
        file_offline = u"No files offline"

    lib_cm.message_write_to_console(ac, file_offline)
    return file_offline


def upload_file(podcast_sendung):
    """ Dateien hochladen """
    lib_cm.message_write_to_console(ac, u"upload_file")
    try:
        ftp = ftplib.FTP(db.ac_config_1[8])
    except (socket.error, socket.gaierror):
        #print 'ERROR: cannot reach "%s"' % db.ac_config_1[7]
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
    """ Alte Dateien auf dem Webspace loeschen """
    lib_cm.message_write_to_console(ac, u"delete_files_online")
    try:
        ftp = ftplib.FTP(db.ac_config_1[8])
    except (socket.error, socket.gaierror):
        #print 'ERROR: cannot reach "%s"' % db.ac_config_1[7]
        lib_cm.message_write_to_console(ac, u"ftp: no connect to: "
                                        + db.ac_config_1[8])
        return None

    ftp.login(db.ac_config_1[9], db.ac_config_1[10])
    ftp.cwd(db.ac_config_1[11])

    # online-files einlesen
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

    # liste enthaelt alle online-dateien,
    # nun audiodateien herausfiltern (nummernblock am anfang)
    files_online_1 = []
    for item in files_online:
        if (re.match("[0-9]{7}", item) is not None):
            # Zeitstempel der Datei ermitteln
            ftp_cmd = "MDTM " + item
            ftp_mod_time = ftp.sendcmd(ftp_cmd)
            # reply code wegschneiden und mit Dateiname in Liste speichern
            if ftp_mod_time[:3] == "213":
                ftp_mod_time = ftp_mod_time[3:].strip()
                ftp_time_file = ftp_mod_time + item

            lib_cm.message_write_to_console(ac, ftp_time_file)
            files_online_1.append(ftp_time_file)

    lib_cm.message_write_to_console(ac, files_online_1)
    lib_cm.message_write_to_console(ac, u"sort..........")
    # Dateiliste anhand der Zeitstempel/Dateinamen-Nummern sortieren
    files_online_1.sort()
    lib_cm.message_write_to_console(ac, files_online_1)

    n_anzahl_online = 0
    # pruefen wieviel online sind
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
        #log_message = u"Keine Podcasts auf dem Server zu loeschen.."
        #lib_cm.message_write_to_console( ac, log_message )
        #db.write_log_to_db_log( ac,  log_message, "c" )
        db.write_log_to_db_a(ac, u"Keine Podcasts auf dem Server zu loeschen..",
                              "c", "write_also_to_console")

    ftp.quit()
    return n_anzahl_files_to_delete


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "

    # sendungen holen die fuer podcast vorgesehen
    #podcast_sendungen = load_podcast(ac)
    podcast_sendungen = load_podcast()
    if podcast_sendungen is None:
        db.write_log_to_db(ac, u"Zur Zeit kein neuer Podcast vorgesehen", "t")
        return

    # pruefen was noch nicht hochgeladen ist
    podcast_offline = check_files_online(podcast_sendungen)
    if podcast_offline is None:
        # Error 001 Fehler beim Verbinden zum ftp-Server
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return

    if podcast_offline == "No files offline":
        db.write_log_to_db_a(ac, u"Alle Podcasts bereits online", "k",
            "write_also_to_console")
        return

    # eine sendung aus offline-sendungen rausholen
    podcast_sendung = ()
    for item in podcast_sendungen:
        if item[12] == podcast_offline:
            # filename, titel, vorname, name, infotime, magazin
            podcast_sendung = (item[12], item[11], item[14], item[15],
                               item[4].strip(), item[5].strip())

    lib_cm.message_write_to_console(ac, podcast_sendung)

    # recoden
    podcast_temp = encode_file(podcast_sendung)
    if podcast_temp is None:
        # Error 002 Fehler beim Recodieren der mp3-Datei
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
        #return

        # mit naechstem file versuchen
        # eine sendung aus offline-sendungen rausholen
        podcast_sendung = ()
        for item in podcast_sendungen:
            if item[12] == podcast_offline:
                # nicht das vorige file nochmal
                if item[12] != podcast_sendung[0]:
                    # filename, titel, vorname, name, infotime, magazin
                    podcast_sendung = (item[12], item[11], item[14],
                                item[15], item[4].strip(), item[5].strip())

        lib_cm.message_write_to_console(ac, podcast_sendung)

        # recoden 2. versuch mit naechstem file
        podcast_temp_1 = encode_file(podcast_sendung)
        if podcast_temp_1 is None:
            # Error 002 Fehler beim Recodieren der mp3-Datei
            db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
                "write_also_to_console")
            return

    # uploaden was noch nicht oben ist
    upload_ok = upload_file(podcast_sendung)
    if upload_ok is None:
        # Error 001 Fehler beim Verbinden zum ftp-Server
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return

    if upload_ok == "temporaere Datei nicht vorhanden":
        # Error 003 recodierte mp3-Datei nicht gefunden
        db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
            "write_also_to_console")
        return

    # temp_file fuer encoder wieder loeschen
    delete_temp_ok = lib_cm.erase_file_a(ac, db, podcast_temp,
        u"Temp-Podcast-Datei geloescht ")
    if delete_temp_ok is None:
        # Error 004 Fehler beim Loeschen der Temp-Podcast-Datei
        db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
            "write_also_to_console")

    # alte files auf ftp loeschen
    delete_ok = delete_files_online()
    if delete_ok is None:
        # Error 001 Fehler beim Verbinden zum ftp-Server
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "r")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # alles ok: weiter
        if param_check is not None:
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, "Podcast-Beamer ausgeschaltet", "e",
                    "write_also_to_console")

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
