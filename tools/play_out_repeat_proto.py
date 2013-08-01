#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out Repeat Proto
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at gooocom
2011-09-30

Dieses Script stellt das Audio-Protokoll
der Erstausstrahlung fuer Wiederholungssendungen (mp3-Dateien)
die in der Datenbank als "WH Proto" markiert wurden
im Play-Out-Server zur Verfuegung.
Dabei werden sie durch diverse Tools bearbeitet (z.B. mp3Gain)

Dateiname Script: play_out_repeat_proto.py
Schluesselwort fuer Einstellungen: PO_Repeat_Proto_Config_3
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank

Param 1: Pfad vom Server zu Protokolldateien
Param 2: Pfad vom Server zu Playout-IT
Param 3: Pfad vom Server zu Playout-Sendung
Param 4: Pfad vom Server zu Protokolldateien
    (gleich wie in PO_Protokoll_Config_3)
Param 5: Dateiprefix Protokoll (gleich wie in PO_Protokoll_Config_1)
Param 6: Pfad/Programm mp3-validator
Param 7: Pfad/Programm mp3-gain
Param 8: Pfad/Programm id3tag

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 Fehler beim Kopieren der Protokolldatei in Play-Out
Error 002 Fehler beim mp3-Validator
Error 003 Fehler bei mp3gain
Error 004 Fehler beim Loeschen der mp3validator-bak-Datei
Error 005 Fehler bei id3tag

Besonderheiten:
Es werden nur Sendungen beruecksichtigt, die zur vollen Stunde beginnen.
Es werden nur WH-Sendung ab dem naechsten Tag beruecksichtigt.

Das Script arbeitet stuendlich (zu Minute 30)

Das blosse Verlangen nach der Wiederholung des Vergnuegens ruft Schmerz hervor,
denn es ist nicht mehr das gleiche wie gestern.
Krishnamurti
"""

import sys
import os
import string
import re
import datetime
import shutil
import subprocess
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "004"
        self.app_desc = u"play_out_repeat_protokoll"
        # schluessel fuer config in db
        self.app_config = u"PO_Repeat_Proto_Config_3"
        self.app_config_develop = u"PO_Repeat_Proto_Config_3_e"
        # anzahl parameter
        self.app_config_params_range = 8
        self.app_errorfile = "error_play_out_repeat_protokoll.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim Kopieren der Protokolldatei in Play-Out")
        self.app_errorslist.append(u"Error 002 Fehler beim mp3-Validator: ")
        self.app_errorslist.append(u"Error 003 Fehler bei mp3gain: ")
        self.app_errorslist.append(u"Error 004 "
            "Fehler beim Loeschen der mp3validator-bak-Datei")
        self.app_errorslist.append(u"Error 005 Fehler bei id3tag: ")
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

        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"
        #self.app_encode_out_strings = "utf-8"
        #self.time_target = (datetime.datetime.now()
                            # + datetime.timedelta( days=-1 ))
        self.time_target = (datetime.datetime.now()
                            + datetime.timedelta(days=+1 ))
        #self.time_target = datetime.datetime.now()


def load_sg_repeat():
    """Pruefen ob Sendungen zur WH vorgesehen"""
    lib_cm.message_write_to_console(ac, u"load_podcast")
    # wenn in Stunde 0, dann mit aktuellem Tag suchen,
    # damit Sendungen von 23 bis 0 Uhr beruecksichtigt werden
    if datetime.datetime.now().hour == 0:
        ac.time_target = datetime.datetime.now()

    db_tbl_condition = ("SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
                        + str(ac.time_target.date()) + "' "
                        + "AND A.SG_HF_REPEAT_PROTO='T' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_b(ac,
                        db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Wiederholungssendungen aus Protokoll für: "
                        + str(ac.time_target.date()))
        db.write_log_to_db(ac, log_message, "t")
        return sendung_data

    # log schreiben
    log_message = (u"Wiederholungssendungen aus Protokoll noetig für: "
                   + str(ac.time_target.date()))
    db.write_log_to_db(ac, log_message, "e")
    return sendung_data


def load_sg_first(sg_cont_nr):
    """Erstsednung zur WH suchen"""
    lib_cm.message_write_to_console(ac, u"Erstsednung zur WH suchen")

    db_tbl_condition = ("A.SG_HF_FIRST_SG = 'T' AND A.SG_HF_CONTENT_ID='"
                        + str(sg_cont_nr) + "' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_a(ac,
                                            db, db_tbl_condition)

    if sendung_data is None:
        log_message = u"Keine Erstsendung zu Wiederholungssendung gefunden "
        db.write_log_to_db(ac, log_message, "t")
        return sendung_data

    return sendung_data


def audio_validate(file_dest):
    """mp3-File validieren"""
    lib_cm.message_write_to_console(ac, u"mp3-File validieren")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    c_validator = db.ac_config_1[6].encode(ac.app_encode_out_strings)
    c_source_file = file_dest.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, c_source_file)
    # subprozess starten
    try:
        p = subprocess.Popen([c_validator, u"-f", c_source_file],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[2] + u" %s" % str(e)
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
        #bak-Datei löschen
        c_source_file = c_source_file + ".bak"
        delete_bak_ok = lib_cm.erase_file_a(ac, db,
                            c_source_file, u"mp3validator-bak-Datei geloescht ")
        if delete_bak_ok is None:
            # Error 004 Fehler beim Loeschen der mp3validator-bak-Datei
            db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
                "write_also_to_console")
    else:
        db.write_log_to_db_a(ac, u"mp3-Validator fix offenbar nicht noetig: "
                             + c_source_file, "p", "write_also_to_console")


def audio_mp3gain(file_dest):
    """mp3-File Gainanpassung"""
    lib_cm.message_write_to_console(ac, u"mp3-File Gainanpassung")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    c_mp3gain = db.ac_config_1[7].encode(ac.app_encode_out_strings)
    c_source_file = file_dest.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, c_source_file)
    # subprozess starten
    try:
        p = subprocess.Popen([c_mp3gain, u"-r", c_source_file],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[3] + u" %s" % str(e)
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


def audio_id3tag(file_dest, id3_author, id3_title):
    """mp3-id3Tag schreiben"""
    lib_cm.message_write_to_console(ac, u"mp3-id3Tag schreiben")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    c_id3tag = db.ac_config_1[8].encode(ac.app_encode_out_strings)
    c_source_file = file_dest.encode(ac.app_encode_out_strings)
    # Unterstriche entfernen
    c_id3_author = re.sub("_", " ", id3_author)
    c_id3_title = re.sub("_", " ", id3_title)
    lib_cm.message_write_to_console(ac, c_source_file)
    # subprozess starten
    try:
        p = subprocess.Popen([c_id3tag, u"-2", u"-a", c_id3_author, u"-s",
                c_id3_title, c_source_file],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[5] + str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return
    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0] )
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    id3tag_output = string.find(p[0], "tagged")
    #id3tag_output_1 = string.find( p[1],  "Artist" )
    lib_cm.message_write_to_console(ac, id3tag_output)
    #lib_cm.message_write_to_console( ac, id3tag_output_1 )
    # wenn gefunden, position, sonst -1
    if id3tag_output != -1:
        log_message = u"id3tag angepasst: " + c_source_file
        db.write_log_to_db(ac, log_message, "k")
        lib_cm.message_write_to_console(ac, "ok")
    else:
        db.write_log_to_db_a(ac,
            u"id3tag offenbar nicht noetig oder fehlgeschlagen: "
            + c_source_file, "p", "write_also_to_console")


def check_and_work_on_files(repeat_sendung):
    """ Dateien bearbeiten """
    lib_cm.message_write_to_console(ac, u"check_and_work_on_files")

    # Filename splitten um zu pruefen ob Filename nach SRB-Muster vorhanden
    lib_cm.message_write_to_console(ac, type(repeat_sendung[12].split("_")[0]))
    lib_cm.message_write_to_console(ac, repeat_sendung[8])
    lib_cm.message_write_to_console(ac, type(repeat_sendung[8]))
    # wenn Filename nicht gsplittet werden kann, wird nur ein splitt erzeugt
    try:
        # wenn keine nr im filename fehler abfangen
        # Ergebnis des split repeat_sendung[12].split("_")[0]) ist unicode,
        # repeat_sendung[8] ist jedoch int,
        # deshalb in int wandeln damit vergleich geht
        filename_nr = int(repeat_sendung[12].split("_")[0])
    except Exception, e:
        filename_nr = "no_nr"

    try:
        # der versuch den 2. splitt zu bekommen gibt fehler
        # wenn split-muster nicht gefunden
        filename_name = repeat_sendung[12].split("_")[1]
    except Exception, e:
        filename_name = "no_name"

    if filename_nr == repeat_sendung[8] and filename_name == repeat_sendung[16]:
        # Play-Out-Dateiname beginnt mit SG-Cont_Nr und Nachname
        lib_cm.message_write_to_console(ac, u"Filename aus db: "
                                        + repeat_sendung[12])
        if repeat_sendung[4].strip() == "T" or repeat_sendung[5].strip() == "T":
            # InfoTime
            path_dest = lib_cm.check_slashes(ac, db.ac_config_1[2])
        else:
            # Sendung
            path_dest = lib_cm.check_slashes(ac, db.ac_config_1[3])

        file_dest = path_dest + repeat_sendung[12]
    else:
        # kein Filename nach SRB-Muster vorhanden, Muster zusammenbauen:
        lib_cm.message_write_to_console(ac,
            u"SRB-Muster-Filename nicht in db, zusammenbauen")
        if repeat_sendung[4].strip() == "T" or repeat_sendung[5].strip() == "T":
            # InfoTime
            path_dest = lib_cm.check_slashes(ac, db.ac_config_1[2])
        else:
            # Sendung
            path_dest = lib_cm.check_slashes(ac, db.ac_config_1[3])

    # filename: SG-Content-Nr_Nachname_Stichworte
    file_dest = (path_dest + str(repeat_sendung[8]) + "_"
        + lib_cm.replace_uchar_sonderzeichen_with_latein(repeat_sendung[16])
        + "_"
        + lib_cm.replace_uchar_sonderzeichen_with_latein(repeat_sendung[13])
        + ".mp3")

    # Suchen ob vorhanden
    if os.path.isfile(file_dest):
        lib_cm.message_write_to_console(ac, u"vorhanden: " + file_dest)
        db.write_log_to_db_a(ac,
            u"Audiodatei fuer Wiederholung bereits vorhanden: "
            + file_dest, "k", "write_also_to_console")
        return

    lib_cm.message_write_to_console(ac, u"nicht vorhanden: " + file_dest)
    # Erstsendung suchen
    #first_sendung = load_sg_first(ac, repeat_sendung[8])
    first_sendung = load_sg_first(repeat_sendung[8])
    lib_cm.message_write_to_console(ac, first_sendung)
    if first_sendung is None:
        # keine Erstsendung gefunden
        return

    # Erstsendung gefunden
    # Sendedatum und Zeit der ES ermitteln
    #for repeat_sendung in first_sendung:
    #first_sg_date_time = repeat_sendung[2]
    # kann eigentlich nur ein eintrag in der liste sein: [0]
    first_sg_date_time = first_sendung[0][2]
    db.write_log_to_db(ac, u"Erstsendung zu Wiederholungssendung gefunden: "
        + first_sg_date_time.strftime('%Y_%m_%d_%H') + " "
        + repeat_sendung[11], "t")
    lib_cm.message_write_to_console(ac, first_sg_date_time)
    lib_cm.message_write_to_console(ac, first_sg_date_time.minute)
    lib_cm.message_write_to_console(ac, datetime.datetime.now())
    if first_sg_date_time > datetime.datetime.now():
        db.write_log_to_db_a(ac,
            u"Erstsendung noch nicht gelaufen, Verarbeitung abgebrochen: "
            + first_sg_date_time.strftime('%Y_%m_%d_%H'), "t",
            "write_also_to_console")
        return

    if first_sg_date_time.minute != 0:
        db.write_log_to_db_a(ac,
        u"Nur WH-Sendungen, die zur vollen Stunde beginnen, "
        + "koennen verarbeitet werden: "
        + first_sg_date_time.strftime('%Y_%m_%d_%H_%M'), "t",
        "write_also_to_console")
        return
    # Nur Sendungen die zur vollen Stunde beginnen bearbeiten
    lib_cm.message_write_to_console(ac, first_sg_date_time.minute)
    # Pfad-Datei der Protodatei
    path_source = lib_cm.check_slashes(ac, db.ac_config_1[4])
    if ac.app_windows == "yes":
        file_source = (path_source + first_sg_date_time.strftime('%Y_%m_%d')
            + "\\" + db.ac_config_1[5] + "_"
            + first_sg_date_time.strftime('%Y_%m_%d_%H') + ".mp3")
    else:
        file_source = (path_source + first_sg_date_time.strftime('%Y_%m_%d')
            + "/" + db.ac_config_1[5] + "_"
            + first_sg_date_time.strftime('%Y_%m_%d_%H') + ".mp3")
    lib_cm.message_write_to_console(ac, file_source)

    # In Play-Out kopieren
    try:
        shutil.copy(file_source, file_dest)
        db.write_log_to_db_a(ac, u"Protokoll fuer Wiederholung: "
            + file_source, "k", "write_also_to_console")
        db.write_log_to_db_a(ac, u"Protokoll kopiert nach: " + file_dest,
            "c", "write_also_to_console")
    except Exception, e:
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        log_message = u"copy_files_to_dir_retry Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return

    # audio-fx
    audio_validate(file_dest)
    audio_mp3gain(file_dest)
    audio_id3tag(file_dest,
        lib_cm.replace_uchar_sonderzeichen_with_latein(repeat_sendung[16]),
        lib_cm.replace_uchar_sonderzeichen_with_latein(repeat_sendung[13]))

    # filename rechts von slash extrahieren
    if ac.app_windows == "no":
        filename = file_dest[string.rfind(file_dest, "/") + 1:]
    else:
        filename = file_dest[string.rfind(file_dest, "\\") + 1:]

    # in sendeanmeldung filename ersetzen
    sql_command = ("UPDATE SG_HF_CONTENT SET SG_HF_CONT_FILENAME ='"
        + filename + "' where SG_HF_CONT_ID='" + str(repeat_sendung[8]) + "'")

    db_op_success = db.exec_sql(ac, db, sql_command)
    if db_op_success is not None:
        lib_cm.message_write_to_console(ac, "Datensatz aktualisiert: "
                            + filename)
        db.write_log_to_db(ac, u"Dateiname in Sendeanmeldung aktualisiert: "
                            + filename, "k")

    db.write_log_to_db(ac, u"WH von Proto bearbeitet: " + filename, "i")
    db.write_log_to_db_a(ac, u"WH von Proto bearbeitet: " + filename, "n",
        "write_also_to_console")


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "

    # Sendungen holen die fuer Wiederholung aus Proto vorgesehen
    repeat_sendungen = load_sg_repeat()
    if repeat_sendungen is None:
        db.write_log_to_db(ac,
            u"Zur Zeit keine Wiederholungssendungen aus Protokoll vorgesehen",
            "t")
        return

    # Erstsendung suchen und pruefen ob sie schon gelaufen sind
    for item in repeat_sendungen:
        # item:  ASG_HF_ID, A.SG_HF_CONTENT_ID,
        # A.SG_HF_TIME, A.SG_HF_DURATION, "
        # "A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE,
        # A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
        # "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID,
        # B.SG_HF_CONT_AD_ID, "
        # "B.SG_HF_CONT_TITEL, B.SG_HF_CONT_FILENAME,
        # B.SG_HF_CONT_STICHWORTE, "
        # "C.AD_ID, C.AD_VORNAME, C.AD_NAME
        #first_sendung = load_sg_first(ac, item[8])
        first_sendung = load_sg_first(item[8])
        lib_cm.message_write_to_console(ac, first_sendung)

        if first_sendung is not None:
            # first_sendung: A.SG_HF_ID, A.SG_HF_CONTENT_ID,
            # A.SG_HF_TIME, A.SG_HF_DURATION, "
            # "A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE,
            # A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
            # "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID,
            # B.SG_HF_CONT_AD_ID, "
            # "B.SG_HF_CONT_TITEL, B.SG_HF_CONT_FILENAME, "
            # "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
            # Erstsendung gefunden
            lib_cm.message_write_to_console(ac, first_sendung[0])
            if (first_sendung[0][2] + datetime.timedelta(hours=+ 1)
                 <= datetime.datetime.now()):
                lib_cm.message_write_to_console(ac,
                "Erstsendung vor mindestens einer"
                + " Stunde gelaufen oder beendet, "
                + "Proto muss vorhanden sein")
                lib_cm.message_write_to_console(ac, first_sendung[0][2])
                lib_cm.message_write_to_console(ac, datetime.datetime.now())
                # Bearbeiten
                #repeat_offline = check_and_work_on_files(item)
                check_and_work_on_files(item)
            else:
                lib_cm.message_write_to_console(ac,
                    "Erstsendung noch nicht gelaufen oder beendet, "
                    + "Proto kann nicht vorhanden sein")
                lib_cm.message_write_to_console(ac, first_sendung[0][2])
                lib_cm.message_write_to_console(ac, datetime.datetime.now())
                db.write_log_to_db(ac, first_sendung[0][11] +
                    u": Erstsendung zu WH noch nicht gelaufen, "
                    + "Weiterverarbeitung abgebrochen...", "t")


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
            lets_rock()

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
