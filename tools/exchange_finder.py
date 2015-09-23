#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Vorproduktionen extern suchen
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at goooglee
2014-09-24

Dieses Script sucht vorproduzierte Audio-Dateien
auf der Austauschplattform.

This script is for finding mp3-files on a ftp-server.

Dateiname Script: exchange_finder.py
Schluesselwort fuer Einstellungen: Exchange_Finder
Benoetigt: lib_common_1.py im gleichen Verzeichnis
Bezieht Daten aus und schreibt in: Firebird-Datenbank


Fehlerliste:
E 00 Parameter-Typ oder Inhalt stimmt nicht
E 01 Fehler beim Connect zu FTP-Server
E 02 Fehler beim LogIn zu FTP-Server
E 03 Fehler beim FTP-Ordnerwechsel
E 04 Fehler beim Zugriff auf FTP-Ordner
E 05 Fehler beim Loeschen alter Logs


Parameterliste:
Param 01: On/Off Switch
Param 02: temp-Ordner fuer Info-File
Param 03: Eigenes Senderkuerzel fuer Dateiname
Param 04: Unterpfad ftp
Param 05: ftp-Domain
Param 06: ftp-User
Param 07: ftp-PW

Extern Parameters:
server_settings
server_settings_paths_a
server_settings_paths_b

Ausfuehrung: jede Stunde zur Minute 38


"""

import sys
import datetime
import ftplib
import socket
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "024"
        self.app_desc = u"Exchange_Finder"
        # key of config in db
        self.app_config = u"Exchange_Finder"
        self.app_config_develop = u"Exchange_Finder_e"
        # number parameters
        self.app_config_params_range = 8
        self.app_errorfile = "error_exchange_finder.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(self.app_desc +
            " Parameter-Typ oder Inhalt stimmt nicht")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Connect zu FTP-Server")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim LogIn zu FTP-Server")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim FTP-Ordnerwechsel - viellt. nicht vorhanden")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Zugriff auf FTP-Ordner")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Loeschen alter Logs")

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

        # develop-mod
        self.app_develop = "no"
        # debug-mod
        self.app_debug_mod = "no"
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"
        self.time_target = datetime.datetime.now()


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
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


def load_files_ftp(path_ftp):
    """load filenames from ftp"""
    lib_cm.message_write_to_console(ac, "load_filenames_online_ftp")
    ftp = ftp_connect_and_dir(path_ftp)
    if ftp is None:
        return
    files_online = []
    try:
        files_online = ftp.nlst()
    except ftplib.error_perm, resp:
        if str(resp) == "550 No files found":
            lib_cm.message_write_to_console(ac,
            u"ftp: no files in this directory")
            files_online = None
        else:
            log_message = (ac.app_errorslist[9])
            db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")

    ftp.quit()
    #lib_cm.message_write_to_console(ac, files_online)
    db.write_log_to_db(ac, ac.app_desc + " Filelist von ftp geladen", "t")
    return files_online


def ftp_connect_and_dir(path_ftp):
    """connect to ftp, login and change dir"""
    try:
        ftp = ftplib.FTP(db.ac_config_1[6])
    except (socket.error, socket.gaierror):
        lib_cm.message_write_to_console(ac, u"ftp: no connect to: "
                                        + db.ac_config_1[7])
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
                                        "write_also_to_console")
        return None

    try:
        ftp.login(db.ac_config_1[7], db.ac_config_1[8])
    except ftplib.error_perm, resp:
        lib_cm.message_write_to_console(ac, "ftp: no login to: "
                                        + db.ac_config_1[7])
        log_message = (ac.app_errorslist[2] + " - " + db.ac_config_1[7])
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    try:
        ftp.cwd(path_ftp)
    except ftplib.error_perm, resp:
        lib_cm.message_write_to_console(ac, "ftp: no dirchange possible: "
                                        + db.ac_config_1[5])
        log_message = (ac.app_errorslist[3] + " - " + path_ftp)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None
    return ftp


def write_filelist_to_db(files_online):
    """write filelist to db"""
    db.write_exchange_log_to_db(ac, files_online, "write_also_to_console")
    db.write_log_to_db(ac, ac.app_desc + " Filelist in db geschrieben", "t")


def load_filelist_from_log(c_time_back, c_time_now):
    """load filelist from Log"""
    db_tbl = "EXCHANGE_LOGS A "
    db_tbl_fields = ("A.EX_LOG_ID, A.EX_LOG_TIME, A.EX_LOG_FILE ")
    db_tbl_condition = ("SUBSTRING( A.EX_LOG_TIME FROM 1 FOR 19) >= '"
        + c_time_back + "' AND SUBSTRING( A.EX_LOG_TIME FROM 1 FOR 19) < '"
        + c_time_now + "' ORDER BY A.EX_LOG_ID")

    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    if log_data is None:
        return None
    return log_data


def check_filelist(filelist_db, files_online):
    """compare file lists"""
    #print len(filelist_db)
    #print len(files_online)
    #print filelist_db
    #print "files-online"
    #print files_online

    new_files = []
    files_from_db_list = []
    for item in filelist_db:
        files_from_db_list.append(item[2])
    #print "files-db"
    #print files_from_db_list
    new_files = (list(
        set(files_online).difference(set(files_from_db_list))))
    if len(new_files) > 0:
        for item_new_file in new_files:
            log_message = ("Neue Uebernahme gefunden: " + item_new_file)
            db.write_log_to_db_a(ac, log_message, "n", "write_also_to_console")
    else:
        log_message = ("Keine neue Uebernahme gefunden")
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")


def delete_exchange_log_in_db_log():
    """delete old items in db"""
    log_message = (u"Loeschen der Exchangelogs von vorgestern")
    db.write_log_to_db(ac, log_message, "e")
    date_log_back = (datetime.datetime.now()
                     + datetime.timedelta(days=- 3))
    c_date_log_back = date_log_back.strftime("%Y-%m-%d %H:%M")

    sql_command = ("DELETE FROM EXCHANGE_LOGS WHERE EX_LOG_TIME < '"
              + c_date_log_back + "'")

    delete_ok = db.delete_logs_in_db_log(ac, sql_command, log_message)
    if delete_ok is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[5],
                                    "x", "write_also_to_console")
    return


def lets_rock():
    """main funktion """
    print "lets_rock "
    # extendet params
    load_extended_params_ok = load_extended_params()
    if load_extended_params_ok is None:
        return

    # load file list from ftp
    path_ftp = lib_cm.check_slashes(ac, db.ac_config_1[5])
    files_online = load_files_ftp(path_ftp)
    if files_online is None:
        return

    # load old file list from db
    time_back = (datetime.datetime.now()
                 + datetime.timedelta(seconds=- 3660))
    c_time_back = time_back.strftime("%Y-%m-%d %H:%M:%S")
    c_time_now = ac.time_target.strftime("%Y-%m-%d %H:%M:%S")
    filelist_db = load_filelist_from_log(c_time_back, c_time_now)
    if filelist_db is None:
        # write current list in db
        write_filelist_to_db(files_online)
        return
    #print "db-list-old"
    #print filelist_db

    db.write_log_to_db(ac, ac.app_desc
                + " Vergleich mit db-list der verg. Stunde..", "t")
    check_filelist(filelist_db, files_online)

    # write current list in db
    write_filelist_to_db(files_online)

    # delete old logs
    #if datetime.datetime.now().hour == 0:
    delete_exchange_log_in_db_log()


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
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, "Exchange_Finder ausgeschaltet", "e",
                    "write_also_to_console")

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
