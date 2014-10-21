#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Vorproduktion periodisch extern bereitstellen
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at goooglee
2014-09-24

Dieses Script stellt vorproduzierte Audio-Dateien
fuer regelmaessige Sendungen (mp3-Dateien)
extern (Cloud) zur Verfuegung.
Zusaetzlich wird eine Text-Datei mit Meta-Daten gespeichert.
Festgelegt sind die Sendungen in der Tabelle SG_HF_ROBOT.

Dateiname Script: beamer_vp_periodisch.py
Schluesselwort fuer Einstellungen: Beamer_VP_period
Benoetigt: lib_common_1.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank


Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 Fehler beim Kopieren der Vorproduktion in Cloud
Error 002 Fehler beim Kopieren der Meta-Datei in Cloud
Error 005 Fehler beim Generieren des Dateinamens

Parameterliste:
Param 1: Pfad vom Server zu Playout-Sendung
Param 2: Tage zurueck loeschen alter Dateien in Cloud
Param 3: Kuerzel Sender
Param 4: Pfad vom Server zu Dropbox-Hauptordner


Ausfuehrung: jede Stunde zur Minute 12


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
        self.app_id = "020"
        self.app_desc = u"beamer_vp_periodisch"
        # schluessel fuer config in db
        self.app_config = u"Beamer_VP_period"
        self.app_config_develop = u"Beamer_VP_period_e"
        # anzahl parameter
        self.app_config_params_range = 5
        self.app_errorfile = "error_beamer_vp_periodisch.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim Kopieren der Vorproduktion in Cloud")
        self.app_errorslist.append(u"Error 002 "
            "Fehler beim Kopieren der Meta-Datei in Cloud")
        self.app_errorslist.append(u"Error 003 "
            "Fehler beim Generieren des Dateinamens")

        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
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


def load_roboting_sgs():
    """Sendungen suchen, die bearbeitet werden sollen"""
    lib_cm.message_write_to_console(ac,
        u"Sendungen suchen, die bearbeitet werden sollen")
    sendungen_data = db.read_tbl_rows_with_cond(ac, db,
        "SG_HF_ROBOT",
        "SG_HF_ROB_TITEL, SG_HF_ROB_FILENAME_OUT",
        "SG_HF_ROB_VP_OUT ='T'")

    if sendungen_data is None:
        log_message = u"Keine Sendungen fuer externe VP vorgesehen.. "
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return sendungen_data

    return sendungen_data


def load_sg(sg_titel):
    """Sendung suchen"""
    lib_cm.message_write_to_console(ac, u"Sendung suchen")
    db_tbl_condition = ("SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) >= '"
        + str(ac.time_target.date()) + "' " + "AND A.SG_HF_FIRST_SG='T' "
        + "AND B.SG_HF_CONT_TITEL='" + sg_titel + "' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_b(ac,
                    db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Sendung mit diesem Titel gefunden: "
                            + sg_titel.encode('ascii', 'ignore'))
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


def write_to_info_file(path_file_dest, item, sendung):
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


def filepaths(item, sendung):
    """Pfade und Dateinamen zusammenbauen"""
    success_file = True
    try:
        path_source = lib_cm.check_slashes(ac, db.ac_config_1[1])
        path_file_source = (path_source + sendung[12])

        path_dest = lib_cm.check_slashes(ac, db.ac_config_1[4])
        path_cloud = lib_cm.check_slashes(ac, item[1])
        filename_dest = (sendung[2].strftime('%Y_%m_%d') + "_"
            + db.ac_config_1[3] + str(sendung[12][7:]))
        path_file_dest = (path_dest + path_cloud + filename_dest)
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


def check_and_work_on_files(roboting_sgs):
    """
    - Zugehoerige Audios suchen
    - wenn vorhanden, bearbeiten
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
            db.write_log_to_db_a(ac, u"Sendung fuer VP nach extern gefunden: "
                    + sendung[11].encode('ascii', 'ignore'), "t",
                    "write_also_to_console")

            # Pfade und Dateinamen zusammenbauen
            success_file, path_file_source, path_file_dest = filepaths(
                                     item, sendung)
            if success_file is None:
                continue

            # In Cloud kopieren
            success_copy = audio_copy(path_file_source, path_file_dest)
            if success_copy is None:
                continue

            # info-txt-Datei
            success_write = write_to_info_file(path_file_dest, item, sendung)
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


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "
    # Sendungen suchen, die bearbeitet werden sollen
    roboting_sgs = load_roboting_sgs()
    if roboting_sgs is None:
        return

    # pruefen was noch nicht in cloud ist, kopieren und meta schreiben
    check_and_work_on_files(roboting_sgs)
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
