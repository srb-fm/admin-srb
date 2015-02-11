#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Vorproduktion extern bereitstellen
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at goooglee
2014-09-24

Dieses Script stellt vorproduzierte Audio-Dateien
fuer Sendungen (mp3-Dateien)
extern (Cloud) zur Verfuegung.
Zusaetzlich wird eine Text-Datei mit Meta-Daten gespeichert.
Festgelegt sind die Sendungen durch SG_HF_VP_OUT
in der Tabelle SG_HF_MAIN. (VP-out in der Seneanmeldung)

Dateiname Script: beamer_vp.py
Schluesselwort fuer Einstellungen: Beamer_VP
Benoetigt: lib_common_1.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank


Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 beim Kopieren der Vorproduktion in Cloud
Error 002 beim Kopieren der Meta-Datei in Cloud
Error 003 beim Generieren des Dateinamens
Error 004 beim Ermitteln zu loeschender Dateien
Error 005 beim Loeschen einer veralteten Datei

Parameterliste:
Param 1: Pfad vom Server zu Playout-Infotime
Param 2: Pfad vom Server zu Playout-Sendung
Param 3: Tage zurueck loeschen alter Dateien in Cloud
Param 4: Kuerzel Sender
Param 5: Pfad vom Server zu Dropbox-Hauptordner


Ausfuehrung: jede Stunde zur Minute 18


"""

import sys
import os
import datetime
import shutil
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "021"
        self.app_desc = u"beamer_vp"
        # schluessel fuer config in db
        self.app_config = u"Beamer_VP"
        self.app_config_develop = u"Beamer_VP_e"
        # anzahl parameter
        self.app_config_params_range = 6
        self.app_errorfile = "error_beamer_vp.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(self.app_desc +
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(self.app_desc +
            "Fehler beim Kopieren der Vorproduktion in Cloud")
        self.app_errorslist.append(self.app_desc +
            "Fehler beim Kopieren der Meta-Datei in Cloud")
        self.app_errorslist.append(self.app_desc +
            "Fehler beim Generieren des Dateinamens")
        self.app_errorslist.append(self.app_desc +
            "Fehler beim Ermitteln zu loeschender Dateien ")
        self.app_errorslist.append(self.app_desc +
            "Fehler beim Loeschen einer veralteten Datei ")

        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")

        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "no"
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"
        #self.app_encode_out_strings = "utf-8"
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta( days=-1 ))
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta(days=+1 ))
        self.time_target = datetime.datetime.now()


def load_manuskript(sendung):
    """Manuskript suchen"""
    lib_cm.message_write_to_console(ac, u"Manuskript suchen")
    manuskript_data = db.read_tbl_row_with_cond(ac, db,
        "SG_MANUSKRIPT",
        "SG_MK_TEXT",
        "SG_MK_SG_CONT_ID =" + str(sendung[1]))

    if manuskript_data is None:
        log_message = u"Kein Manuskript fuer externe VP gefunden.. "
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return manuskript_data

    return manuskript_data


def load_sg(sg_option):
    """Sendung suchen"""

    if sg_option == "IT":
        db_tbl_condition = ("SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) >= '"
        + str(ac.time_target.date()) + "' " + "AND A.SG_HF_FIRST_SG='T' "
        + "AND A.SG_HF_INFOTIME='T'" + "AND A.SG_HF_VP_OUT='T'")

    if sg_option == "MAG":
        db_tbl_condition = ("SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) >= '"
        + str(ac.time_target.date()) + "' " + "AND A.SG_HF_FIRST_SG='T' "
        + "AND A.SG_HF_MAGAZINE='T'" + "AND A.SG_HF_VP_OUT='T'")

    if sg_option == "SG":
        db_tbl_condition = ("SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) >= '"
        + str(ac.time_target.date()) + "' " + "AND A.SG_HF_FIRST_SG='T' "
        + "AND A.SG_HF_INFOTIME='F'" + "AND A.SG_HF_MAGAZINE='F'"
        + "AND A.SG_HF_VP_OUT='T'")

    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_b(ac,
                    db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Sendung fuer extern gefunden")
        #db.write_log_to_db( ac,  log_message, "t" )
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return sendung_data

    return sendung_data


def audio_copy(path_file_source, path_file_dest):
    """audiofile kopieren"""
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


def write_to_info_file(path_file_dest, sendung):
    """info-file schreiben"""
    success_write = True
    manuskript_data = load_manuskript(sendung)
    if manuskript_data is not None:
        manuskript_text = lib_cm.simple_cleanup_html(manuskript_data[0])
    #db.write_log_to_db_a(ac, "Testpoint", "p", "write_also_to_console")
    try:
        #db.write_log_to_db_a(ac, "Testpoint", "p", "write_also_to_console")
        path_text_file_dest = os.path.splitext(path_file_dest)[0] + ".txt"
        f_info_txt = open(path_text_file_dest, 'w')
        db.write_log_to_db_a(ac, u"Info-Text schreiben " + path_file_dest,
                "v", "write_also_to_console")
    except IOError as (errno, strerror):
        log_message = ("write_to_file_record_params: I/O error({0}): {1}"
                        .format(errno, strerror) + ": " + path_file_dest)
        db.write_log_to_db(ac, log_message, "x")
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
                                             "write_also_to_console")
        success_write = None
    else:
        # filename rechts von slash extrahieren
        filename = lib_cm.extract_filename(ac, path_file_dest)

        f_info_txt.write("Titel: " + sendung[11].encode('utf-8')
                        + "\r\n")
        f_info_txt.write("Autor: " + sendung[15].encode('utf-8')
                            + " " + sendung[16].encode('utf-8')
                            + "\r\n")
        f_info_txt.write("Dateiname: " + filename + "\r\n")
        f_info_txt.write("Interne ID: " + sendung[12][0:7] + "\r\n")

        if manuskript_data is not None:
            f_info_txt.write("Info/ Manuskript: " + "\r\n")
            f_info_txt.write(manuskript_text.encode('utf-8'))
        f_info_txt.close
    return success_write


def filepaths(sendung, path_audio):
    """Pfade und Dateinamen zusammenbauen"""
    success_file = True
    try:
        path_source = lib_cm.check_slashes(ac, path_audio)
        path_file_source = (path_source + sendung[12])

        path_dest = lib_cm.check_slashes(ac, db.ac_config_1[5])
        filename_dest = (sendung[2].strftime('%Y_%m_%d') + "_"
            + db.ac_config_1[4] + str(sendung[12][7:]))
        path_file_dest = (path_dest + filename_dest)
    except Exception, e:
        log_message = (ac.app_errorslist[3] + "fuer: "
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
            u"Vorproduktion fuer extern noch nicht in Play_Out vorhanden: "
            + sendung[12], "f", "write_also_to_console")
        success_file = None

    if os.path.isfile(path_file_dest):
        lib_cm.message_write_to_console(ac, u"vorhanden: " + path_file_dest)
        db.write_log_to_db_a(ac,
            u"Vorproduktion fuer extern in Cloud bereits vorhanden: "
            + filename_dest,
            "k", "write_also_to_console")
        success_file = None
    return success_file, path_file_source, path_file_dest


def check_and_work_on_files(sendungen, path_audio):
    """
    - Zugehoerige Audios suchen
    - wenn vorhanden, bearbeiten
    """
    lib_cm.message_write_to_console(ac, u"check_and_work_on_files")

    for sendung in sendungen:
        db.write_log_to_db_a(ac, u"Sendung fuer VP nach extern gefunden: "
                    + sendung[11].encode('ascii', 'ignore'), "t",
                    "write_also_to_console")

        # Pfade und Dateinamen zusammenbauen
        success_file, path_file_source, path_file_dest = filepaths(
                                     sendung, path_audio)
        if success_file is None:
            continue

        # In Cloud kopieren
        success_copy = audio_copy(path_file_source, path_file_dest)
        if success_copy is None:
            continue

        # info-txt-Datei
        success_write = write_to_info_file(path_file_dest, sendung)
        if success_write is None:
            # probs mit datei
            continue

        # filename rechts von slash extrahieren
        filename = lib_cm.extract_filename(ac, path_file_dest)

        db.write_log_to_db_a(ac,
                "VP nach extern bearbeitet: " + filename, "i",
                                                    "write_also_to_console")
        db.write_log_to_db_a(ac,
                "VP nach extern bearbeitet: " + filename, "n",
                                                    "write_also_to_console")


def erase_files_from_cloud():
    """alte Dateien in cloud-ordnern loeschen"""
    lib_cm.message_write_to_console(ac, u"erase_files_from_cloud")
    # Paths
    path_sendung_dest = lib_cm.check_slashes(ac, db.ac_config_1[5])
    lib_cm.message_write_to_console(ac, path_sendung_dest)
    date_back = (datetime.datetime.now()
                 + datetime.timedelta(days=- int(db.ac_config_1[3])))
    c_date_back = date_back.strftime("%Y_%m_%d")
    db.write_log_to_db_a(ac, u"Sendedatum muss aelter sein als: "
                                + c_date_back, "t", "write_also_to_console")

    try:
        files_sendung_dest = os.listdir(path_sendung_dest)
    except Exception, e:
        log_message = ac.app_errorslist[3] + u": %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return

    x = 0
    z = 0

    for item in files_sendung_dest:
        if item[0:10] < c_date_back:
            try:
                file_to_delete = path_sendung_dest + item
                os.remove(file_to_delete)
                log_message = u"geloescht: " + item
                db.write_log_to_db(ac, log_message, "e")
                z += 1
            except Exception, e:
                log_message = ac.app_errorslist[4] + u": %s" % str(e)
                lib_cm.message_write_to_console(ac, log_message)
                db.write_log_to_db(ac, log_message, "x")
        x += 1
    log_message = (u"Dateien in Cloud bearbeitet: " + str(x)
                                + u" - Sendungen geloescht: " + str(z))
    db.write_log_to_db(ac, log_message, "k")
    if z != 0:
        db.write_log_to_db(ac, log_message, "i")
    return


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "

    log_message = u"Infotime bearbeiten.."
    db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
    sendungen = load_sg("IT")
    if sendungen is not None:
        check_and_work_on_files(sendungen, db.ac_config_1[1])

    log_message = u"Magazin bearbeiten.."
    db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
    sendungen = load_sg("MAG")
    if sendungen is not None:
        check_and_work_on_files(sendungen, db.ac_config_1[1])

    log_message = u"Sendung bearbeiten.."
    db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
    sendungen = load_sg("SG")
    if sendungen is not None:
        check_and_work_on_files(sendungen, db.ac_config_1[2])

    db.write_log_to_db_a(ac, u"Veraltete Dateien in Cloud loeschen",
                                            "p", "write_also_to_console")
    erase_files_from_cloud()
    return


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print "lets_work: " + ac.app_desc
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
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
