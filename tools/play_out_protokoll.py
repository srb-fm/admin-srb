#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out Protokoll
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at goggell
2011-10-06

Dieses Script kopiert mitgeschnittene Sendungen (mp3-Dateien)
in das Protokoll-Verzeichnis.
Alte Dateien werden nach der eingestellten Anzahl von Tagen geloescht.

Dateiname Script: play_out_protokoll.py
Schluesselwort fuer Einstellungen: PO_Protokoll_Config
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank
Arbeitet zusammen mit: wave-recorder o.ae. (nicht direkt)

Parameterliste:
P 1: on/off switch
P 2: Recorder WaveRecorder oder rotter
P 3: Tage zurueck, loeschen Audio-Prot-Dateien muss dreistellig sein!!!
(z.B. 030)
P 4: Tage zurueck, loeschen Protokoll-Dateien in media_hf
und logs in DB user_logs
P 5: Pefix fuer Protokolldateien in media_hf
P 6: Endung fuer Protokolldateien in media_hf
P 7: Tage zurueck, kontrollieren ob Protokoll vollstaendig

Liste der moeglichen Haupt-Fehlermeldungen:
Error 000 Parameter-Typ oder Inhalt stimmt nicht
Error 001 Fehler beim Kopieren in Protokoll-Archiv
Error 002 Fehler beim Loeschen veralteter archivierter Protokoll-Dateien
Error 003 Fehler beim Loeschen veralteter temporaerer Protokoll-Dateien
Error 004 Fehler beim Ueberpruefen von Protokoll-Dateien
Error 005 Fehler beim Loeschen veralteter Logeintraege

Das Script wird stuendlich eine Minute nach der vollen Stunde ausgefuehrt.

Als letzte Institution auf der Welt wird vermutlich das
diplomatische Protokoll verschwinden.
Seine Vertreter werden sich vermutlich bemuehen,
auch noch den Weltuntergang in wuerdiger Form zu regeln.
Roger Peyrefitte (*1907), frz. Schriftsteller u. Politiker

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
        """Settings"""
        # app_config
        self.app_id = "012"
        self.app_desc = u"Play_Out_Protokoll"
        # key for config in db
        self.app_config = u"PO_Protokoll_Config"
        self.app_config_develop = u"PO_Protokoll_Config_1_e"
        # number of parameters
        self.app_config_params_range = 7
        self.app_errorfile = "error_play_out_protokoll.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim Kopieren in Protokoll-Archiv")
        self.app_errorslist.append(u"Error 002 "
            "Fehler beim Loeschen veralteter archivierter Protokoll-Dateien")
        self.app_errorslist.append(u"Error 003 "
            "Fehler beim Loeschen veralteter temporaerer Protokoll-Dateien")
        self.app_errorslist.append(u"Error 004 "
            "Fehler beim Ueberpruefen von Protokoll-Dateien")
        self.app_errorslist.append(u"Error 005 "
            "Fehler beim Loeschen veralteter Logeintraege")
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")

        # develop-mod
        self.app_develop = "no"
        # display debugmessages on console or no: "no"
        self.app_debug_mod = "no"
        self.app_windows = "no"
        self.action_summary = None


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
    # extern tools
    ext_params_ok = lib_cm.params_provide_server_settings(ac, db)
    lib_cm.set_server(ac, db)
    ext_params_ok = lib_cm.params_provide_server_paths_b(ac, db,
                                                        ac.server_active)
    return ext_params_ok


def delete_log_in_db():
    """delete old logs in DB"""
    lib_cm.message_write_to_console(ac, u"delete_log_in_db")

    #n_days_back = int( db.ac_config_1[4] )
    date_log_back = (datetime.datetime.now()
                     + datetime.timedelta(days=- int(db.ac_config_1[4])))
    c_date_log_back = date_log_back.strftime("%Y-%m-%d %H:%M")

    ACTION = ("DELETE FROM USER_LOGS WHERE USER_LOG_TIME <= '"
              + c_date_log_back + "' ROWS 4000")

    db.dbase_connect(ac)
    if db.db_con is None:
        err_message = u"No connect to db for delete_log_in_db:"
        lib_cm.error_write_to_file(ac, err_message)
        return None

    try:
        db_cur = db.db_con.cursor()
        db_cur.execute(ACTION)
        db.db_con.commit()
        db.db_con.close()
        log_message = (u"Loeschen der Action- "
            "und Errorlogs in DB-Tabelle die aelter sind als: "
            + c_date_log_back)
        db.write_log_to_db(ac, log_message, "e")
    except Exception, e:
        lib_cm.message_write_to_console(ac, log_message +
            u"Error 2 delete_log_in_db: %s</p>" % str(e))
        err_message = log_message + u"Error 2 delete_log_in_db: %s" % str(e)
        lib_cm.error_write_to_file(ac, err_message)
        db.db_con.rollback()
        db.db_con.close()
        return None
    return "ok"


def delete_log_in_db_log():
    """delete old logs in DB-log"""
    lib_cm.message_write_to_console(ac, u"delete_log_in_db_log")

    #n_days_back = int( db.ac_config_1[4] )
    date_log_back = (datetime.datetime.now()
                     + datetime.timedelta(days=- int(db.ac_config_1[4])))
    c_date_log_back = date_log_back.strftime("%Y-%m-%d %H:%M")

    ACTION = ("DELETE FROM USER_LOGS WHERE USER_LOG_TIME <= '"
              + c_date_log_back + "' ROWS 2000")

    db.dbase_log_connect(ac)
    if db.db_log_con is None:
        err_message = u"No connect to db for delete_log_in_db_log:"
        lib_cm.error_write_to_file(ac, err_message)
        return None

    try:
        db_log_cur = db.db_log_con.cursor()
        db_log_cur.execute(ACTION)
        db.db_log_con.commit()
        db.db_log_con.close()
        log_message = (u"Loeschen der Action- und "
            + "Errorlogs in DB-Log-Tabelle die aelter sind als: "
            + c_date_log_back)
        db.write_log_to_db(ac, log_message, "e")
    except Exception, e:
        lib_cm.message_write_to_console(ac,
                log_message + u"Error 2 delete_log_in_db_log: %s</p>" % str(e))
        err_message = log_message + u"Error 2 delete_log_in_db: %s" % str(e)
        lib_cm.error_write_to_file(ac, err_message)
        db.db_log_con.rollback()
        db.db_log_con.close()
        return None
    return "ok"


def write_files_to_protokoll():
    """copy files in Protokoll-Archive"""
    lib_cm.message_write_to_console(ac, u"write_files_to_protokoll")

    # one hour back
    date_proto = datetime.datetime.now() + datetime.timedelta(hours=- 1)
    # Path slashes
    path_sendung_source = lib_cm.check_slashes(ac, db.ac_config_servpath_b[1])
    path_sendung_dest = lib_cm.check_slashes(ac, db.ac_config_servpath_b[2])
    path_sendung_dest += date_proto.strftime("%Y_%m_%d")
    path_sendung_dest = lib_cm.check_slashes(ac, path_sendung_dest)

    lib_cm.message_write_to_console(ac, path_sendung_source)
    lib_cm.message_write_to_console(ac, path_sendung_dest)

    if db.ac_config_1[2] == "WaveRecorder":
        file_source = (db.ac_config_1[5] + "_"
            + date_proto.strftime("%Y-%m-%d") + "_"
            + date_proto.strftime("%H.00.01") + "." + db.ac_config_1[6])
        file_dest = (db.ac_config_1[5] + "_"
            + date_proto.strftime("%Y_%m_%d") + "_"
            + date_proto.strftime("%H.00.01") + "." + db.ac_config_1[6])

    if db.ac_config_1[2] == "rotter":
        file_source = (db.ac_config_1[5] + "-"
            + date_proto.strftime("%Y-%m-%d") + "-"
            + date_proto.strftime("%H") + "." + db.ac_config_1[6])
        file_dest = (db.ac_config_1[5] + "_"
            + date_proto.strftime("%Y_%m_%d") + "_"
            + date_proto.strftime("%H") + "." + db.ac_config_1[6])

    lib_cm.message_write_to_console(ac, file_source)
    lib_cm.message_write_to_console(ac, file_dest)

    path_file_source = path_sendung_source + file_source
    path_file_destination = path_sendung_dest + file_dest

    lib_cm.message_write_to_console(ac, path_file_source)
    lib_cm.message_write_to_console(ac, path_file_destination)
    lib_cm.message_write_to_console(ac, u"kopieren: " + file_dest)

    try:
        if not os.path.exists(path_sendung_dest):
            log_message = u"anlegen Verzeichnis: " + path_sendung_dest
            lib_cm.message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "k")
            os.mkdir(path_sendung_dest)

        shutil.copy(path_file_source, path_file_destination)
        log_message = u"kopiert Protokoll: " + file_source
        db.write_log_to_db(ac, log_message, "k")
    except Exception, e:
        log_message = u"copy_files_to_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return None
    return path_file_destination


def erase_file(path_filename):
    """erase file"""
    lib_cm.message_write_to_console(ac, u"erase_file: " + path_filename)

    try:
        if os.path.isfile(path_filename):
            os.remove(path_filename)
            lib_cm.message_write_to_console(ac,
                u"Datei geloescht: " + path_filename)
            log_message = u"Datei gelöscht: " + path_filename
            db.write_log_to_db(ac, log_message, "e")

    except OSError, msg:
        lib_cm.message_write_to_console(ac,
            u"erase_file: " + "%r: %s" % (msg, path_filename))
        log_message = u"erase_file: " + "%r: %s" % (msg, path_filename)
        db.write_log_to_db(ac, log_message, "x")
    return


def erase_files_from_protokoll():
    """erase old archived file"""
    lib_cm.message_write_to_console(ac, u"erase_files_from_protokoll")

    path_sendung_dest = lib_cm.check_slashes(ac, db.ac_config_servpath_b[2])
    lib_cm.message_write_to_console(ac, path_sendung_dest)

    # Days back
    days_back = int(db.ac_config_1[4])
    date_proto_erase = (datetime.datetime.now()
                        + datetime.timedelta(days=- days_back))
    date_to_erase = date_proto_erase.strftime("%Y_%m_%d")
    lib_cm.message_write_to_console(ac, date_proto_erase)

    try:
        files_sendung_dest = os.listdir(path_sendung_dest)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return None

    z = 0
    for item in files_sendung_dest:
        lib_cm.message_write_to_console(ac, item)
        if item <= date_to_erase:
            if item[0:3] != "001":
                try:
                    dir_to_erase = path_sendung_dest + item
                    shutil.rmtree(dir_to_erase)
                    log_message = u"Verzeichnis geloescht: " + dir_to_erase
                    lib_cm.message_write_to_console(ac, log_message)
                    db.write_log_to_db(ac, log_message, "e")
                    z += 1
                except Exception, e:
                    log_message = u"erase_dir Error: %s" % str(e)
                    lib_cm.message_write_to_console(ac, log_message)
                    db.write_log_to_db(ac, log_message, "x")
                    return None

    if z == 0:
        log_message = (u"Keine alten Protokolldateien zum Loeschen vorhanden: "
                       + date_to_erase)
    return z


def erase_files_from_protokoll_temp():
    """erase temp file"""
    lib_cm.message_write_to_console(ac, u"erase_files_from_protokoll_temp")

    file_ext = db.ac_config_1[6]
    #path_sendung_source = lib_cm.check_slashes(ac, db.ac_config_1[1])
    path_sendung_source = lib_cm.check_slashes(ac, db.ac_config_servpath_b[1])
    lib_cm.message_write_to_console(ac, path_sendung_source)

    # Days back
    days_back = int(db.ac_config_1[3][0:3])
    date_proto_erase = (datetime.datetime.now()
                        + datetime.timedelta(days=- days_back))
    date_to_erase = date_proto_erase.strftime("%Y-%m-%d")
    lib_cm.message_write_to_console(ac, date_to_erase)

    try:
        files_sendung_temp = os.listdir(path_sendung_source)
    except Exception, e:
        log_message = u"read_proto_temp_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return None

    #lib_cm.message_write_to_console( ac, files_sendung_temp )

    # Dateinamensyntax WaveRecorder: SRB_2012-06-11_10.00.01.mp3
    # Dateinamensyntax Rotter: SRB_Prot-2012-05-04-17.mp3
    # item[4:14]

    z = 0
    for item in files_sendung_temp:
        # extract date
        date_filename = (item[len(db.ac_config_1[5])
                            + 1:len(db.ac_config_1[5]) + 11])
        if date_filename <= date_to_erase:
            # ignore files like "001" at the beginning
            if item[0:3] != "001":
                # delete only files with rigth extention
                if item[len(item) - 3:len(item)] == file_ext:
                    try:
                        path_file_to_erase = path_sendung_source + item
                        erase_file(path_file_to_erase)
                        z += 1
                    except Exception, e:
                        log_message = (u"erase_proto_temp_files Error: %s"
                                       % str(e))
                        lib_cm.message_write_to_console(ac, log_message)
                        db.write_log_to_db(ac, log_message, "x")
                        return None

    if z != 0:
        log_message = (u"Alte temporaere Protokolldateien bis "
                       + date_to_erase + u" geloescht: " + str(z))
        db.write_log_to_db(ac, log_message, "e")
    else:
        log_message = (u"Keine alten temporaeren "
                + "Protokolldateien zum Loeschen vorhanden: " + date_to_erase)
        db.write_log_to_db(ac, log_message, "t")

    return "ok"


def check_files_in_protokoll_completely(n_days_back):
    """
    copy files, they in prev loops not had been copied
    """
    lib_cm.message_write_to_console(ac, u"check_files_in_protokoll_completely")

    # Days back
    date_proto = (datetime.datetime.now()
                  + datetime.timedelta(days=- n_days_back))
    log_message = (u"Vollstaendigkeit der Protokolle pruefen fuer: "
                   + str(date_proto))
    lib_cm.message_write_to_console(ac, log_message)
    db.write_log_to_db(ac, log_message, "t")

    path_sendung_source = lib_cm.check_slashes(ac, db.ac_config_servpath_b[1])
    path_sendung_dest = lib_cm.check_slashes(ac, db.ac_config_servpath_b[2])
    path_sendung_dest += date_proto.strftime("%Y_%m_%d")
    path_sendung_dest = lib_cm.check_slashes(ac, path_sendung_dest)

    lib_cm.message_write_to_console(ac, path_sendung_source)
    lib_cm.message_write_to_console(ac, path_sendung_dest)

    # files from temp in list
    try:
        files_sendung_source = os.listdir(path_sendung_source)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return None

    # Dateien in Archiv in Liste
    try:
        files_sendung_dest = os.listdir(path_sendung_dest)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
        return None

    lib_cm.message_write_to_console(ac, files_sendung_source)
    lib_cm.message_write_to_console(ac, files_sendung_dest)
    # source hat bindestriche im namen,
    # dest unterstriche,
    # hier source in unterstriche wandeln, damit vergleich moeglich ist

    # Bindestriche der Dateinamen der temp-Liste in Unterstriche wandeln
    files_sendung_source_1 = []
    for item in files_sendung_source:
        #print item[4:14]
        # Datum kommt nach Prefix, Laenge des Prefix aus Einstellungen ermitteln
        date_filename = (item[len(db.ac_config_1[5])
                             + 1:len(db.ac_config_1[5]) + 11])
        #if item[4:14] == date_proto.strftime("%Y-%m-%d") :
        if date_filename == date_proto.strftime("%Y-%m-%d"):
            files_sendung_source_1.append(re.sub("-", "_", item))

    lib_cm.message_write_to_console(ac, files_sendung_source_1)

    # Differenz der beiden Dateilisten ermitteln
    # ist nicht egal welche Liste vor dem difference steht:
    files_sendung = (list(
            set(files_sendung_source_1).difference(set(files_sendung_dest))))
    lib_cm.message_write_to_console(ac, files_sendung)

    # Eintraege in der Liste kopieren
    z = 0
    for item in files_sendung:
        # Bindestriche wieder in Datum rein und dann in filename
        #file_date = re.sub ("_", "-", item[4:14])
        #file_date = (re.sub(
        #   "_", "-", item[len(db.ac_config_1[5])+1:len(db.ac_config_1[5])+11]))

        #file_source = item[0:04] +  file_date +  item[14:27]
        if db.ac_config_1[2] == "WaveRecorder":
            file_source = (db.ac_config_1[5] + "_"
                           + date_proto.strftime("%Y-%m-%d") + "_"
                           + date_proto.strftime("%H.00.01") + "."
                           + db.ac_config_1[6])

        if db.ac_config_1[2] == "rotter":
            file_source = (db.ac_config_1[5] + "-"
                           + date_proto.strftime("%Y-%m-%d") + "-"
                           + date_proto.strftime("%H") + "."
                           + db.ac_config_1[6])

        path_file_source = path_sendung_source + file_source
        path_file_destination = path_sendung_dest + item
        lib_cm.message_write_to_console(ac, path_file_source)
        lib_cm.message_write_to_console(ac, path_file_destination)

        try:
            if not os.path.exists(path_sendung_dest):
                log_message = u"anlegen Verzeichnis: " + path_sendung_dest
                lib_cm.message_write_to_console(ac, log_message)
                db.write_log_to_db(ac, log_message, "k")
                os.mkdir(path_sendung_dest)

            lib_cm.message_write_to_console(ac, u"kopieren: " + item)
            shutil.copy(path_file_source, path_file_destination)
            log_message = u"kopieren nachgeholt Protokoll: " + item
            db.write_log_to_db(ac, log_message, "c")
            z += 1
        except Exception, e:
            log_message = u"copy_files_to_dir_retry Error: %s" % str(e)
            lib_cm.message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

    if z != 0:
        log_message = u"Protokolldateien nachtraeglich kopiert: " + str(z)
    else:
        log_message = u"Protokolldateien vollstaendig"
    db.write_log_to_db(ac, log_message, "k")
    return z


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "
    # extendet params
    load_extended_params_ok = load_extended_params()
    if load_extended_params_ok is None:
        return

    # copy files in Protokoll-Archive
    write_ok = write_files_to_protokoll()
    if write_ok is None:
        # Error 001 Fehler beim Kopieren in Protokoll-Archiv
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
    else:
        ac.action_summary = u"Audio-Protokoll archiviert"

    # delete archived Protokoll-files
    erase_proto_ok = erase_files_from_protokoll()
    if erase_proto_ok is None:
        # Error 002 Fehler beim Loeschen
        # veralteter archivierter Protokoll-Dateien
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")

    # Temp-Protokoll-Dateien loeschen
    erase_temp_ok = erase_files_from_protokoll_temp()
    if erase_temp_ok is None:
        # Error 003 Fehler beim Loeschen
        # veralteter temporaerer Protokoll-Dateien
        db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
            "write_also_to_console")

    # Kopieren nachholen wenn bei vorigen Tagen Fehler beim Kopieren aufgetreten
    n_days_back = int(db.ac_config_1[7]) + 1
    for i in range(n_days_back):
        if i > 0:
            # heute nicht!
            check_ok = check_files_in_protokoll_completely(i)
            if check_ok is None:
                # Error 004 Fehler beim Ueberpruefen von Protokoll-Dateien
                db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
                    "write_also_to_console")

    # Veraltete Log-Eintraege in DB-log loeschen
    delete_ok = delete_log_in_db_log()
    if delete_ok is None:
        # Error 005 Fehler beim Loeschen veralteter Logeintraege
        db.write_log_to_db_a(ac, ac.app_errorslist[5], "x",
            "write_also_to_console")

    return

if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # lets rock
    db.write_log_to_db(ac, ac.app_desc + " gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # ok: continue
        if param_check is not None:
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac,
                                "Protokoll-Archivierung ausgeschaltet", "e",
                                "write_also_to_console")

    # finish
    if ac.action_summary is not None:
        db.write_log_to_db(ac, ac.action_summary, "i")
    db.write_log_to_db(ac, ac.app_desc + " gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
