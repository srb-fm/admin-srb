#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play out Archiv
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at googell
2011-10-10

Dieses Script kopiert die Audios der Sendungen vom Play-Out-Server ins Archiv.
Wurden sie laenger als eine bestimmte Anzahl von Tagen nicht gesendet,
werden sie im Play-Out-Server geloescht.

Dateiname Script: play_out_archiv.py
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank
Schluesselwort fuer Einstellungen: PO_Archiv_Config_1



Hinweise:
Die Archivierung erfolgt in Ordner, die nach dem Kalenderjahr angelegt werden.
Je nachdem, wieviel Dateien zu pruefen bzw. zu archivieren sind,
kann die Archivierung durchaus 15 - 20 Minuten in Anspruch nehmen.
Der Archivierungsintervall und Zeitraum sollte also in eine
Zeit gelegt werden, in der das System damit nicht unnoetig belastet wird.

Besonderheiten:
Die Pruefung ob Dateien zum archivieren vorhanden sind wird uebersprungen,
wenn in den Logs mehr als x-mal registriert wurde,
dass keine Dateien zum archivieren vorhanden sind.
Pruefung erfolgt in write_files_to_archive_prepare.

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nicht
Error 001 Fehler beim Lesen oder Schreiben von Archiv-Ordnern oder Dateien
Error 002 Fehler beim Loeschen von Dateien in Play-Out

Parameterliste:
Param 1: Play_Out_Ordner IT und Maganzine
Param 2: Play_Out_Ordner Sendungen
Param 3: Archiv_Ordner IT und Maganzine
Param 4: Archiv_Ordner Sendungen
Param 5: Tage zurueck fuer kopieren in Archiv
Param 6: Tage zurueck fuer loeschen in PlayOut
Param 7: Anzahl Dateien die kopiert werden
Param 8: Anzahl Dateien die geloescht werden

Das Script laeuft per chron jeden Tag ca. 6:25.

Glaeubiger haben ein besseres Gedaechtnis als Schuldner. Benjamin Franklin
"""

import sys
import re
import datetime
import os
import shutil
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "011"
        self.app_desc = u"Play_Out_Archiv"
        # schluessel fuer config in db
        self.app_config = u"PO_Archiv_Config_3"
        self.app_config_develop = u"PO_Archiv_Config_1_e"
        self.app_errorfile = "error_play_out_archiv.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim Lesen oder Schreiben von Archiv-Ordnern oder Dateien ")
        self.app_errorslist.append(u"Error 002 "
            "Fehler beim Loeschen von Dateien in Play-Out")
        # anzahl parameter
        self.app_config_params_range = 9
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "no"
        self.app_windows = "no"


def write_files_to_archive_prepare(sendung_art):
    """Dateien in Archiv kopieren, Vorbereitung"""
    lib_cm.message_write_to_console(ac, u"write_files_to_archive_prepare")

    # Pfade
    if sendung_art == "Info-Time":
        path_sendung_source = db.ac_config_1[1]
        path_sendung_dest = db.ac_config_1[3]
        log_message = u"Infotime und Magazin archivieren..."

    if sendung_art == "Sendung normal":
        path_sendung_source = db.ac_config_1[2]
        path_sendung_dest = db.ac_config_1[4]
        log_message = u"Sendungen archivieren..."

    db.write_log_to_db(ac, log_message, "p")

    # pfad anpassen
    path_sendung_source = lib_cm.check_slashes(ac, path_sendung_source)
    path_sendung_dest = lib_cm.check_slashes(ac, path_sendung_dest)

    lib_cm.message_write_to_console(ac, path_sendung_source)
    lib_cm.message_write_to_console(ac, path_sendung_dest)

    # Archiv-Ordner einlesen
    try:
        files_sendung_source = os.listdir(path_sendung_source)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message + path_sendung_source)
        db.write_log_to_db(ac, log_message, "x")
        return None

    try:
        list_dirs = os.listdir(path_sendung_dest)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message + path_sendung_dest)
        db.write_log_to_db(ac, log_message, "x")
        return None

    lib_cm.message_write_to_console(ac, list_dirs)

    # pruefen ob neues jahr angelegt werden muss
    current_year = str(datetime.datetime.now().year)

    # pfad anpassen
    current_year_path = path_sendung_dest + lib_cm.check_slashes(ac,
                                                current_year)

    try:
        if not os.path.exists(current_year_path):
            # neuen ordner anlegen
            log_message = u"anlegen Archiv-Verzeichnis: " + current_year_path
            lib_cm.message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "k")
            os.mkdir(current_year_path)

    except Exception, e:
        log_message = u"anlegen Archiv-Verzeichnis Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return None

    # archivierung entsprechend der jahre durchgenen
    for item in list_dirs:
        # pruefen ob logs aus vergangenen archivierungen vorhanden
        db_tbl_condition = ("USER_LOG_ACTION LIKE 'Dateien archiviert von: "
                            + sendung_art + " - "
                            + item + "%' ORDER BY USER_LOG_ID DESC")
        log_data = db.read_tbl_rows_with_cond_log(ac, db,
            "USER_LOGS", "FIRST 7 USER_LOG_ACTION", db_tbl_condition)

        if log_data is not None:
            lib_cm.message_write_to_console(ac, log_data)
            z_non_copys = 0
            for row in log_data:
                #lib_cm.message_write_to_console( ac, row )
                #lib_cm.message_write_to_console( ac, row[0][-1] )
                # archivierungsdurchlaeufe zaehlen
                # bei denen keine dateien kopiert wurden
                if row[0][-1] == "0":
                    z_non_copys += 1

            # sind bei den Durchlaeufen mehr als x mal
            # keine Dateien kopiert worden,
            # erstmal aussetzen indem ein Dummy-Satz eingetragen
            if z_non_copys >= 3:
                log_message = ("Archivierung der Dateien von: "
                               + sendung_art + " - "
                               + item + " - uebersprungen")
                db.write_log_to_db_a(ac, log_message, "e",
                    "write_also_to_console")
                continue
            else:
                write_files_to_archive(files_sendung_source,
                    path_sendung_source, path_sendung_dest, item, sendung_art)
        else:
            write_files_to_archive(files_sendung_source, path_sendung_source,
                    path_sendung_dest, item, sendung_art)

    return "ok"


def write_files_to_archive(files_sendung_source,
        path_sendung_source, path_sendung_dest, dir_year, sendung_art):
    """Dateien in Archiv kopieren, Durchfuerung"""
    lib_cm.message_write_to_console(ac, u"write_files_to_archive " + dir_year)

    # Zeiten
    lib_cm.message_write_to_console(ac, db.ac_config_1[5])
    date_back = (datetime.datetime.now()
                 + datetime.timedelta(days = - int(db.ac_config_1[5])))
    lib_cm.message_write_to_console(ac, db.ac_config_1[7])
    nr_of_files_to_archive = int(db.ac_config_1[7])

    # pfad anpassen
    path_sendung_dest += lib_cm.check_slashes(ac, dir_year)
    log_message = u"Dateien archivieren nach: " + path_sendung_dest
    db.write_log_to_db(ac, log_message, "f")

    try:
        files_sendung_dest = os.listdir(path_sendung_dest)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return

    # items aus sendungen liste rausholen, wenn nix dann eben nix
    x = 0
    y = 0
    z = 0

    # unterschiede, also die dateien in neue liste,
    # die in einer von beiden nicht drinne
    files_sendung = (list(
        set( files_sendung_source).difference( set( files_sendung_dest))))

    # jetzt mit liste weiter,
    # die keine files enthaelt, die schon in destination vorhanden
    for item in files_sendung:
        # srb-dateiname, erkennbar an 7stelliger zahl am anfang
        if re.match("\d{7,}", item) is None:
            continue

        db_tbl_limit = "FIRST 1"
        db_tbl_condition = "B.SG_HF_CONT_ID = '" + item[0:7] + "' "
        db_tbl_order = "A.SG_HF_TIME"
        sendung_data = (
            db.read_tbl_rows_sg_cont_ad_with_limit_cond_and_order(ac, db,
            db_tbl_limit, db_tbl_condition, db_tbl_order))

        if sendung_data is None:
            lib_cm.message_write_to_console(ac, "nix")
            continue

        for row in sendung_data:
            y += 1
            #print row
            sendung_year = row[2].year
            lib_cm.message_write_to_console(ac, sendung_year)

            sendung_date = row[2]
            lib_cm.message_write_to_console(ac, sendung_date)
            lib_cm.message_write_to_console(ac, item)
            # nur

            if (sendung_date < date_back and sendung_year == int(dir_year)):
                lib_cm.message_write_to_console(ac, u"archivieren: " + row[12])
                file_source = path_sendung_source + item
                file_destination = path_sendung_dest + item

                try:
                    shutil.copy(file_source, file_destination)
                    log_message = u"archiviert: " + item
                    db.write_log_to_db(ac, log_message, "c")
                    z += 1
                    # nicht nochmal kopieren
                    # falls noch weitere WH archivert werden muessten
                    #break
                except Exception, e:
                    log_message = u"copy_files_to_dir Error: %s" % str(e)
                    lib_cm.message_write_to_console(ac, log_message)
                    db.write_log_to_db(ac, log_message, "x")

            else:
                lib_cm.message_write_to_console(
                    ac, u"Sendedatum liegt vor Archivdatum, "
                    "noch nicht archivieren ...")

        x += 1
        if z >= nr_of_files_to_archive:
            break

    lib_cm.message_write_to_console(ac, u"dateien bearbeitet: " + str(x))
    lib_cm.message_write_to_console(ac, u"sendungen in db gefunden: " + str(y))
    lib_cm.message_write_to_console(ac, u"dateien archiviert: " + str(z))
    log_message = (u"Archivierung, Dateien bearbeitet: " + sendung_art + " - "
                   + dir_year + " - " + str(x) + u" - in Archiv kopiert: "
                   + str(z))
    db.write_log_to_db(ac, log_message, "k")
    if z != 0:
        db.write_log_to_db(ac, log_message, "i")
    return


def erase_files_from_play_out_prepare(sendung_art):
    """Dateien in Play-Out loeschen, Vorbereitung"""
    lib_cm.message_write_to_console(ac, u"erase_files_from_play_out_prepare")

    # Pfade
    if sendung_art == "Info-Time":
        path_sendung_source = db.ac_config_1[1]
        path_sendung_dest = db.ac_config_1[3]
        log_message = u"Infotime und Magazin in Play_Out loeschen..."

    if sendung_art == "Sendung normal":
        path_sendung_source = db.ac_config_1[2]
        path_sendung_dest = db.ac_config_1[4]
        log_message = u"Sendungen in Play_Out loeschen..."

    db.write_log_to_db(ac, log_message, "p")

    # pfad anpassen
    path_sendung_source = lib_cm.check_slashes(ac, path_sendung_source)
    path_sendung_dest = lib_cm.check_slashes(ac, path_sendung_dest)

    lib_cm.message_write_to_console(ac, path_sendung_source)
    lib_cm.message_write_to_console(ac, path_sendung_dest)

    # source: files in list
    try:
        files_sendung_source = os.listdir(path_sendung_source)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return None

    list_dirs = os.listdir(path_sendung_dest)
    lib_cm.message_write_to_console(ac, list_dirs)

    # loeschen entsprechend der jahre durchgenen
    for item in list_dirs:
        # pruefen ob logs aus vergangenen loeschungen vorhanden
        db_tbl_condition = ("USER_LOG_ACTION LIKE 'Dateien loeschen von: "
            + sendung_art + " - " + item + "%' ORDER BY USER_LOG_ID DESC")
        log_data = db.read_tbl_rows_with_cond_log(ac,
            db, "USER_LOGS", "FIRST 7 USER_LOG_ACTION", db_tbl_condition)

        if log_data is not None:
            lib_cm.message_write_to_console(ac, log_data)
            z_non_copys = 0
            for row in log_data:
                # archivierungsdurchlaeufe zaehlen
                # bei denen keine dateien kopiert wurden
                if row[0][-1] == "0":
                    z_non_copys += 1

            # sind bei den durchlaufen mehr als x mal
            # keine dateien kopiert worden,
            # erstmal aussetzen indem ein dummy-satz eingetragen wird
            if z_non_copys >= 3:
                log_message = (u"Dateien loeschen von: " + sendung_art
                    + " - " + item + " - uebersprungen")
                db.write_log_to_db(ac, log_message, "e")
                lib_cm.message_write_to_console(ac, log_message)
                continue
            else:
                #log_message = "Dateien loeschen von: " + path_sendung_source
                #db.write_log_to_db( ac, log_message, "t" )
                erase_files_from_play_out(files_sendung_source,
                    path_sendung_source, path_sendung_dest, item, sendung_art)
        else:
            #log_message = "Dateien loeschen von: " + path_sendung_source
            #db.write_log_to_db( ac, log_message, "t" )
            erase_files_from_play_out(files_sendung_source,
                path_sendung_source, path_sendung_dest, item, sendung_art)

    return "ok"


def erase_files_from_play_out(files_sendung_source, path_sendung_source,
                              path_sendung_dest, dir_year, sendung_art):
    """Dateien in Play-Out loeschen, Durchfuehrung"""
    lib_cm.message_write_to_console(ac, u"erase_files_from_play_out")
    # Zeiten
    lib_cm.message_write_to_console(ac, db.ac_config_1[6])
    date_back = (datetime.datetime.now()
                 + datetime.timedelta(days = -int(db.ac_config_1[6])))
    #date_back = datetime.datetime.now() + datetime.timedelta( days=-100 )
    lib_cm.message_write_to_console(ac, db.ac_config_1[7])
    nr_of_files_to_archive = int(db.ac_config_1[7])

    # pfad anpassen
    path_sendung_dest += lib_cm.check_slashes(ac, dir_year)
    log_message = (u"Dateien von " + dir_year + u" loeschen aus: "
                   + path_sendung_source)
    db.write_log_to_db(ac, log_message, "v")

    try:
        files_sendung_dest = os.listdir(path_sendung_dest)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return

    # items aus sendungen liste rausholen, wenn nix dann eben nix
    x = 0
    y = 0
    z = 0

    # gemeinsamkeiten, also die dateien in neue liste, die in beiden drinne sind
    files_sendung = (list(
        set(files_sendung_source).intersection(set(files_sendung_dest))))

    # jetzt mit liste weiter, die alle files enthaelt,
    # die in source UND destination
    # damit kann nur geloescht werden, was bereits archiviert ist
    for item in files_sendung:
        # srb-dateiname, erkennbar an 7stelliger zahl am anfang

        if re.match("\d{7,}", item) is None:
            continue

        db_tbl_limit = "FIRST 1"
        db_tbl_condition = "B.SG_HF_CONT_ID = '" + item[0:7] + "' "
        db_tbl_order = "A.SG_HF_TIME DESCENDING"
        sendung_data = db.read_tbl_rows_sg_cont_ad_with_limit_cond_and_order(
                    ac, db, db_tbl_limit, db_tbl_condition, db_tbl_order)
        #print sendung_data
        if sendung_data is None:
            lib_cm.message_write_to_console(ac, "nix")
            continue

        for row in sendung_data:
            y += 1
            #print row
            sendung_year = row[2].year
            sendung_date = row[2]
            lib_cm.message_write_to_console(ac, sendung_date)
            lib_cm.message_write_to_console(ac, item)
            # nur
            if sendung_date < date_back and sendung_year == int(dir_year):
                if row[13].strip() != "05":
                    # Jingle SRB (05) nicht loeschen
                    lib_cm.message_write_to_console(ac, u"loeschen: " + row[12])
                    file_source = path_sendung_source + item

                    try:
                        os.remove(file_source)
                        log_message = u"geloescht: " + item
                        db.write_log_to_db(ac, log_message, "e")
                        z += 1
                        # nicht nochmal bei WH loeschen,
                        # braucme nich, nur ein satz wird geholt (der juengste)
                        # break
                    except Exception, e:
                        log_message = u"erase_files_fom_dir Error: %s" % str(e)
                        lib_cm.message_write_to_console(ac, log_message)
                        db.write_log_to_db(ac, log_message, "x")

                else:
                    lib_cm.message_write_to_console(ac,
                        u"Jingle, nicht loeschen")

            else:
                lib_cm.message_write_to_console(ac,
                u"Sendedatum liegt vor Archivdatum, noch nicht loeschen ...")


        x += 1
        if z >= nr_of_files_to_archive:
            break

    lib_cm.message_write_to_console(ac, u"dateien geloescht: " + str(x))
    lib_cm.message_write_to_console(ac, u"sendungen in db gefunden: " + str(y))
    lib_cm.message_write_to_console(ac, u"dateien geloescht: " + str(z))
    log_message = (u"Archivierung, Dateien bearbeitet: " + sendung_art + " - "
        +  dir_year + " - "  + str(x)
        + u" - Sendungen in Play_Out geloescht: "  +  str(z))
    db.write_log_to_db(ac, log_message, "k")
    if z != 0:
        db.write_log_to_db(ac, log_message, "i")
    return


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "

    sendung_art = "Info-Time"
    archiv_ok = write_files_to_archive_prepare(sendung_art)
    if archiv_ok is None:
        # Error 001 Fehler beim Lesen
        # oder Schreiben von Archiv-Ordnern oder Dateien
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return

    erase_files_ok = erase_files_from_play_out_prepare(sendung_art)
    if erase_files_ok is None:
        # Error 002 Fehler beim Loeschen von Dateien in Play-Out
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
        return

    sendung_art = "Sendung normal"
    archiv_ok_1 = write_files_to_archive_prepare(sendung_art)
    if archiv_ok_1 is None:
        # Error 001 Fehler beim Lesen
        # oder Schreiben von Archiv-Ordnern oder Dateien
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return

    erase_files_ok_1 = erase_files_from_play_out_prepare(sendung_art)
    if erase_files_ok_1 is None:
        # Error 002 Fehler beim Loeschen von Dateien in Play-Out
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
        return

    return


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
