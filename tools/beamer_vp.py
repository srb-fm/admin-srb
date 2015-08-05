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
import ftplib
import socket
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "021"
        self.app_desc = u"beamer_vp"
        # key of config in db
        self.app_config = u"Beamer_VP"
        self.app_config_develop = u"Beamer_VP_e"
        # number parameters
        self.app_config_params_range = 11
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
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Connect zu FTP-Server")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim LogIn zu FTP-Server")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim FTP-Ordnerwechsel - viellt. nicht vorhanden")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Zugriff auf FTP-Ordner")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Speichern in FTP-Ordner")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Loeschen in FTP-Ordner")

        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
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
        #self.app_encode_out_strings = "utf-8"
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta( days=-1 ))
        #self.time_target = (datetime.datetime.now()
                             #+ datetime.timedelta(days=+1 ))
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


def load_manuskript(sendung):
    """search Manuskript"""
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
    """serch show"""

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


def check_file_source(path_f_source, sendung):
    """check if file exist in source"""
    lib_cm.message_write_to_console(ac, "check if file exist in source")
    success_file = True
    if not os.path.isfile(path_f_source):
        filename = lib_cm.extract_filename(ac, path_f_source)
        lib_cm.message_write_to_console(ac, u"nicht vorhanden: "
                    + filename)
        db.write_log_to_db_a(ac,
            u"Vorproduktion fuer extern noch nicht in Play_Out vorhanden: "
            + sendung[12], "f", "write_also_to_console")
        success_file = False
    return success_file


def check_file_dest_cloud(path_file_cloud):
    """check if file exist in destination"""
    lib_cm.message_write_to_console(ac, "check_files_cloud")
    file_is_online = False
    if os.path.isfile(path_file_cloud):
        filename = lib_cm.extract_filename(ac, path_file_cloud)
        lib_cm.message_write_to_console(ac, "vorhanden: " + path_file_cloud)
        db.write_log_to_db_a(ac,
            "Vorproduktion fuer extern in Cloud bereits vorhanden: "
            + filename,
            "k", "write_also_to_console")
        file_is_online = True
    return file_is_online


def check_file_dest_ftp(path_ftp, filename_dest):
    """check if file exist in destination-ftp"""
    lib_cm.message_write_to_console(ac, "check_files_online_ftp")
    file_online = False
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
        else:
            log_message = (ac.app_errorslist[9])
            db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")

    ftp.quit()
    lib_cm.message_write_to_console(ac, files_online)

    for item in files_online:
        if item == filename_dest:
            file_online = True
            lib_cm.message_write_to_console(ac, u"vorhanden: " + filename_dest)
            db.write_log_to_db_a(ac,
                u"Vorproduktion fuer extern auf FTP bereits vorhanden: "
                + filename_dest,
                "k", "write_also_to_console")
    return file_online


def copy_to_cloud(path_file_source, path_file_dest):
    """copy audiofile and infofile"""
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


def write_to_info_file(filename_dest, sendung):
    """write info-file"""
    success_write = True
    manuskript_data = load_manuskript(sendung)
    if manuskript_data is not None:
        manuskript_text = lib_cm.simple_cleanup_html(manuskript_data[0])

    try:
        path_file_temp = (lib_cm.check_slashes(ac, db.ac_config_1[2])
                                 + filename_dest.replace("mp3", "txt"))
        f_info_txt = open(path_file_temp, 'w')
        db.write_log_to_db_a(ac, "Info-Text schreiben " + path_file_temp,
                "v", "write_also_to_console")
    except IOError as (errno, strerror):
        log_message = ("write_to_file_record_params: I/O error({0}): {1}"
                        .format(errno, strerror) + ": " + path_file_temp)
        db.write_log_to_db(ac, log_message, "x")
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
                                             "write_also_to_console")
        success_write = False
    else:
        f_info_txt.write("Titel: " + sendung[11].encode('utf-8')
                        + "\r\n")
        f_info_txt.write("Autor: " + sendung[15].encode('utf-8')
                            + " " + sendung[16].encode('utf-8')
                            + "\r\n")
        f_info_txt.write("Dateiname: " + filename_dest + "\r\n")
        f_info_txt.write("Interne ID: " + sendung[12][0:7] + "\r\n")

        if manuskript_data is not None:
            f_info_txt.write("Info/ Manuskript: " + "\r\n")
            f_info_txt.write(manuskript_text.encode('utf-8'))
        f_info_txt.close
    return success_write, path_file_temp


def ftp_connect_and_dir(path_ftp):
    """connect to ftp, login and change dir"""
    try:
        ftp = ftplib.FTP(db.ac_config_1[7])
    except (socket.error, socket.gaierror):
        lib_cm.message_write_to_console(ac, u"ftp: no connect to: "
                                        + db.ac_config_1[7])
        db.write_log_to_db_a(ac, ac.app_errorslist[6], "x",
                                        "write_also_to_console")
        return None

    try:
        ftp.login(db.ac_config_1[8], db.ac_config_1[9])
    except ftplib.error_perm, resp:
        lib_cm.message_write_to_console(ac, "ftp: no login to: "
                                        + db.ac_config_1[7])
        log_message = (ac.app_errorslist[7] + " - " + db.ac_config_1[7])
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    try:
        ftp.cwd(path_ftp)
    except ftplib.error_perm, resp:
        lib_cm.message_write_to_console(ac, "ftp: no dirchange possible: "
                                        + db.ac_config_1[7])
        log_message = (ac.app_errorslist[8] + " - " + path_ftp)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None
    return ftp


def ftp_upload(path_f_source, path_ftp, filename_dest):
    """upload file"""
    success_upload = False
    lib_cm.message_write_to_console(ac, u"upload_file")
    ftp = ftp_connect_and_dir(path_ftp)
    if ftp is None:
        return
    if os.path.isfile(path_f_source):
        if ac.app_windows == "yes":
            f = open(path_f_source, "rb")
        else:
            f = open(path_f_source, "r")
    else:
        return success_upload

    try:
        c_ftp_cmd = "STOR " + filename_dest
        ftp.storbinary(c_ftp_cmd, f)
    except ftplib.error_perm, resp:
        log_message = (ac.app_errorslist[9] + " - " + db.ac_config_1[7])
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        f.close()
        return success_upload

    f.close()
    db.write_log_to_db_a(ac, u"VP per FTP hochgeladen: "
                        + filename_dest, "i", "write_also_to_console")
    ftp.quit()
    success_upload = True
    return success_upload


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
    - Zugehoerige Audios suchen
    - wenn vorhanden, bearbeiten
    """
    lib_cm.message_write_to_console(ac, u"check_and_work_on_files")

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

        # info-file
        success_write_temp, path_file_temp = write_to_info_file(
                                filename_dest, sendung)
        if success_write_temp is False:
            # probs with file
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


def erase_files_prepaere():
    """erase files in cloud and ftp"""
    # date for delete files
    date_back = (datetime.datetime.now()
                 + datetime.timedelta(days=- int(db.ac_config_1[3])))
    c_date_back = date_back.strftime("%Y_%m_%d")
    db.write_log_to_db_a(ac, u"Sendedatum muss aelter sein als: "
                                + c_date_back, "t", "write_also_to_console")
    # dropbox
    if db.ac_config_1[10] == "on":
        erase_files_from_cloud(c_date_back)
    # ftp
    if db.ac_config_1[11] == "on":
        erase_files_from_ftp(c_date_back)


def erase_files_from_cloud(c_date_back):
    """erase files from cloud"""
    db.write_log_to_db_a(ac, u"Veraltete Dateien in Cloud loeschen",
                                            "p", "write_also_to_console")
    # Paths
    path_sendung_dest_base = lib_cm.check_slashes(ac,
                                db.ac_config_servpath_b[5])
    path_sendung_dest = (path_sendung_dest_base +
                        lib_cm.check_slashes(ac, db.ac_config_1[5]))
    lib_cm.message_write_to_console(ac, path_sendung_dest)

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


def erase_files_from_ftp(c_date_back):
    """erase files from ftp"""
    lib_cm.message_write_to_console(ac, "erase files from ftp")
    path_ftp = lib_cm.check_slashes(ac, db.ac_config_1[6])
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
        else:
            log_message = (ac.app_errorslist[9])
            db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
            return None

    for item in files_online:
        if item[0:10] < c_date_back:
            try:
                ftp.delete(item)
                log_message = ("Auf ftp-Server geloescht: " + item)
                db.write_log_to_db_a(ac, log_message, "k",
                                    "write_also_to_console")
            except ftplib.error_perm, resp:
                log_message = (ac.app_errorslist[11] + " - " + item)
                db.write_log_to_db_a(ac, log_message, "x",
                                    "write_also_to_console")
                continue

    ftp.quit()
    lib_cm.message_write_to_console(ac, files_online)


def lets_rock():
    """main funktion """
    print "lets_rock "
    # extendet params
    load_extended_params_ok = load_extended_params()
    if load_extended_params_ok is None:
        return

    log_message = u"Infotime bearbeiten.."
    db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
    sendungen = load_sg("IT")
    if sendungen is not None:
        work_on_files(sendungen, db.ac_config_servpath_a[1])

    log_message = u"Magazin bearbeiten.."
    db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
    sendungen = load_sg("MAG")
    if sendungen is not None:
        work_on_files(sendungen, db.ac_config_servpath_a[1])

    log_message = u"Sendung bearbeiten.."
    db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
    sendungen = load_sg("SG")
    if sendungen is not None:
        work_on_files(sendungen, db.ac_config_servpath_a[2])

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
                db.write_log_to_db_a(ac, "VP-Beamer ausgeschaltet", "e",
                    "write_also_to_console")

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
