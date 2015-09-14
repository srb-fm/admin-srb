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
E 00 Parameter-Typ oder Inhalt stimmt nich
E 01 Fehler beim Connect zu FTP-Server
E 02 Fehler beim LogIn zu FTP-Server
E 03 Fehler beim FTP-Ordnerwechsel
E 04 Fehler beim Zugriff auf FTP-Ordner


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

Ausfuehrung: jede Stunde zur Minute 18


"""

import sys
import os
import datetime
import shutil
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
    lib_cm.message_write_to_console(ac, files_online)
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


def filepaths(sendung, base_path_source):
    """concatenate path and filenames"""
    success_file = True
    try:
        path_source = lib_cm.check_slashes(ac, base_path_source)
        path_file_source = (path_source + sendung[12])
        # filename
        filename_dest = (sendung[2].strftime('%Y_%m_%d') + "_"
            + db.ac_config_1[4] + str(sendung[12][7:]))

        # Cloud
        path_dest_base = lib_cm.check_slashes(ac,
                                db.ac_config_servpath_b[5])
        path_cloud = (path_dest_base +
                        lib_cm.check_slashes(ac, db.ac_config_1[5]))
        path_file_cloud = (path_cloud + filename_dest)

        # ftp
        path_ftp = lib_cm.check_slashes(ac, db.ac_config_1[6])

    except Exception, e:
        log_message = (ac.app_errorslist[3] + "fuer: "
            + sendung[11].encode('ascii', 'ignore') + " " + str(e))
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        success_file = None

    lib_cm.message_write_to_console(ac, path_file_source)
    lib_cm.message_write_to_console(ac, path_file_cloud)
    #db.write_log_to_db_a(ac, "Testpoint", "p", "write_also_to_console")

    return (success_file, path_file_source, path_file_cloud, path_ftp,
                                                filename_dest)


def work_on_files(sendungen, base_path_source):
    """
    - search for audio files of shows
    - if found, work on it
    """
    lib_cm.message_write_to_console(ac, u"check_and_work_on_files")

    # dropbox
    for sendung in sendungen:
        db.write_log_to_db_a(ac, u"Sendung fuer VP nach extern gefunden: "
                    + sendung[11].encode('ascii', 'ignore'), "t",
                    "write_also_to_console")

        (success, path_f_source, path_file_cloud,
                path_ftp, filename_dest) = filepaths(sendung, base_path_source)
        if success is False:
            continue

        success_file = check_file_source(path_f_source, sendung)
        if success_file is False:
            continue

        # dropbox
        if db.ac_config_1[10] == "on":
            db.write_log_to_db_a(ac, "VP nach Dropbox bearbeiten: ", "p",
                                                    "write_also_to_console")

            file_is_in_cloud = check_file_dest_cloud(path_file_cloud)
            if file_is_in_cloud is True:
                continue

            # copy to cloud
            success_copy = copy_to_cloud(path_f_source, path_file_cloud)
            if success_copy is None:
                continue

            # info-file
            success_write_temp, path_file_temp = write_to_info_file(
                                filename_dest, sendung)
            if success_write_temp is False:
                # probs with file
                continue

            # copy info-file to dropbox
            filename_info = path_file_cloud.replace("mp3", "txt")
            success_copy = copy_to_cloud(path_file_temp, filename_info)
            if success_copy is False:
                continue

            db.write_log_to_db_a(ac,
                "VP in Dropbox kopiert: " + filename_dest, "n",
                                                    "write_also_to_console")

            # delete files in cloud
            erase_files_prepaere()

            # delete tmp-info-file
            if success_write_temp is not False:
                lib_cm.erase_file(ac, db, path_file_temp)

    # ftp
    for sendung in sendungen:
        db.write_log_to_db_a(ac, u"Sendung fuer VP nach extern gefunden: "
                    + sendung[11].encode('ascii', 'ignore'), "t",
                    "write_also_to_console")

        (success, path_f_source, path_file_cloud,
                path_ftp, filename_dest) = filepaths(sendung, base_path_source)
        if success is False:
            continue

        success_file = check_file_source(path_f_source, sendung)
        if success_file is False:
            continue

        # ftp
        if db.ac_config_1[11] == "on":
            db.write_log_to_db_a(ac, "VP nach ftp bearbeiten: ", "p",
                                                    "write_also_to_console")
            file_is_online = check_file_dest_ftp(path_ftp, filename_dest)
            if file_is_online is True:
                continue
            if file_is_online is None:
                # an error occures
                continue

            # ftp-upload
            success_upload = ftp_upload(
                                path_f_source, path_ftp, filename_dest)
            if success_upload is False:
                continue

            # info-file
            success_write_temp, path_file_temp = write_to_info_file(
                                filename_dest, sendung)
            if success_write_temp is False:
                # probs with file
                continue

            # ftp-upload info-file
            filename_info = filename_dest.replace("mp3", "txt")
            success_upload = ftp_upload(
                                path_file_temp, path_ftp, filename_info)
            if success_upload is False:
                continue

            db.write_log_to_db_a(ac,
                    "VP auf ftp uebertragen: " + filename_dest, "n",
                                                    "write_also_to_console")
            # delete fiels on ftp
            erase_files_prepaere()

            # delete tmp-info-file
            if success_write_temp is not False:
                lib_cm.erase_file(ac, db, path_file_temp)


def lets_rock():
    """main funktion """
    print "lets_rock "
    # extendet params
    load_extended_params_ok = load_extended_params()
    if load_extended_params_ok is None:
        return

    path_ftp = lib_cm.check_slashes(ac, db.ac_config_1[5])
    files_online = load_files_ftp(path_ftp)
    if files_online is not None:
        print files_online
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
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, "Exchange_Finder ausgeschaltet", "e",
                    "write_also_to_console")

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
