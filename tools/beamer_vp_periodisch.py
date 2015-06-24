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
(Intra-Menue: Sendungen/Einstellungen/Automatisierte Sendungen)

Dateiname Script: beamer_vp_periodisch.py
Schluesselwort fuer Einstellungen: Beamer_VP_period
Benoetigt: lib_common_1.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank


Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 Fehler beim Kopieren der Vorproduktion in Cloud
Error 002 Fehler beim Kopieren der Meta-Datei in Cloud
Error 003 Fehler beim Generieren des Dateinamens

Error 006 Fehler beim FTP-Ordnerwechsel - viellt. nicht vorhanden

Parameterliste:
Param 1: Pfad vom Server zu Playout-IT/MAG
Param 2: Pfad vom Server zu Playout-Sendung
Param 3: Tage zurueck loeschen alter Dateien in Cloud
Param 4: Kuerzel Sender
Param 5: Pfad vom Server zu Dropbox-Hauptordner


Ausfuehrung: jede Stunde zur Minute 12


"""

import sys
import os
import datetime
import shutil
import socket
import ftplib
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
        # number parameters
        self.app_config_params_range = 9
        self.app_errorfile = "error_beamer_vp_periodisch.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(self.app_desc +
            " Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Kopieren der Vorproduktion in Cloud")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Kopieren der Meta-Datei in Cloud")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Generieren des Dateinamens")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Ermitteln zu loeschender Dateien ")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Loeschen einer veralteten Datei ")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Connect zu FTP-Server")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim LogIn zu FTP-Server")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim FTP-Ordnerwechsel - viellt. nicht vorhanden")
        self.app_errorslist.append(self.app_desc +
            " Fehler beim Zugriff auf FTP-Ordner")

        # params-type-list, typ entsprechend der params-liste in der config
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
        # developmod (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
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
    """search shows"""
    lib_cm.message_write_to_console(ac,
        u"Sendungen suchen, die bearbeitet werden sollen")
    sendungen_data = (db.read_tbl_rows_with_cond(ac, db,
        "SG_HF_ROBOT",
        "SG_HF_ROB_TITEL, SG_HF_ROB_OUT_DROPB, SG_HF_ROB_FILE_OUT_DB, "
        "SG_HF_ROB_OUT_FTP, SG_HF_ROB_FILE_OUT_FTP",
        "SG_HF_ROB_VP_OUT ='T'"))

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


def audio_copy(path_file_source, path_file_cloud):
    """copy audiofile"""
    success_copy = False
    try:
        shutil.copy(path_file_source, path_file_cloud)
        db.write_log_to_db_a(ac, u"Audio Vorproduktion: "
                + path_file_source.encode('ascii', 'ignore'),
                "v", "write_also_to_console")
        db.write_log_to_db_a(ac, u"Audio kopiert nach: "
                + path_file_cloud, "c", "write_also_to_console")
        success_copy = True
    except Exception, e:
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        log_message = u"copy_files_to_dir_retry Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
    return success_copy


def audio_upload(path_f_source, path_ftp, filename_dest):
    """ upload file"""
    success_upload = False
    lib_cm.message_write_to_console(ac, u"upload_file")
    try:
        ftp = ftplib.FTP(db.ac_config_1[7])
    except (socket.error, socket.gaierror):
        lib_cm.message_write_to_console(ac, u"ftp: no connect to: "
                                        + db.ac_config_1[7])
        return success_upload

    ftp.login(db.ac_config_1[8], db.ac_config_1[9])

    if os.path.isfile(path_f_source):
        if ac.app_windows == "yes":
            f = open(path_f_source, "rb")
        else:
            f = open(path_f_source, "r")
        ftp.cwd(path_ftp)
        log_message = u"upload_file: " + path_f_source
        db.write_log_to_db(ac, log_message, "k")
        c_ftp_cmd = "STOR " + filename_dest
        ftp.storbinary(c_ftp_cmd, f)
        f.close()
        db.write_log_to_db_a(ac, u"VP per FTP hochgeladen: "
                        + filename_dest, "i", "write_also_to_console")
        success_upload = True
    ftp.quit()
    return success_upload


def write_to_info_file(path_file_cloud, item, sendung):
    """writing info-file"""
    success_write = True
    manuskript_data = load_manuskript(sendung)
    if manuskript_data is not None:
        manuskript_text = lib_cm.simple_cleanup_html(manuskript_data[0])

    #db.write_log_to_db_a(ac, "Testpoint", "p", "write_also_to_console")
    try:
        #db.write_log_to_db_a(ac, "Testpoint", "p", "write_also_to_console")
        path_text_file_dest = os.path.splitext(path_file_cloud)[0] + ".txt"
        f_info_txt = open(path_text_file_dest, 'w')
        db.write_log_to_db_a(ac, "Info-Text schreiben " + path_file_cloud,
                "v", "write_also_to_console")
    except IOError as (errno, strerror):
        log_message = ("write_to_file_record_params: I/O error({0}): {1}"
                        .format(errno, strerror) + ": " + path_file_cloud)
        db.write_log_to_db(ac, log_message, "x")
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
                                             "write_also_to_console")
        success_write = False
    else:
        # filename rechts von slash extrahieren
        filename = lib_cm.extract_filename(ac, path_file_cloud)

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
    """concatenate path and filename"""
    lib_cm.message_write_to_console(ac, u"filepaths")
    success_file = True
    path_file_cloud = None
    path_ftp = None

    try:
        if sendung[4].strip() == "T" or sendung[5].strip() == "T":
            # IT or MAG
            path_source = lib_cm.check_slashes(ac, db.ac_config_1[1])
        else:
            path_source = lib_cm.check_slashes(ac, db.ac_config_1[2])
        path_file_source = (path_source + sendung[12])

        filename_dest = (sendung[2].strftime('%Y_%m_%d') + "_"
            + db.ac_config_1[4] + str(sendung[12][7:]))

        if item[1].strip() == "T":
            # Cloud
            path_dest_cloud = lib_cm.check_slashes(ac, db.ac_config_1[5])
            path_cloud = lib_cm.check_slashes(ac, item[2])
            path_file_cloud = (path_dest_cloud + path_cloud + filename_dest)
        if item[3].strip() == "T":
            # FTP
            path_ftp_main = lib_cm.check_slashes(ac, db.ac_config_1[6])
            path_ftp_sub = lib_cm.check_slashes(ac, item[4])
            path_ftp = (path_ftp_main + path_ftp_sub)
    except Exception, e:
        log_message = (ac.app_errorslist[3] + " fuer - "
            + sendung[11].encode('ascii', 'ignore')
            + " - vielt. kein Ordner in autm. Sendung angegeben")
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        db.write_log_to_db_a(ac, str(e), "x", "write_also_to_console")
        success_file = False

    lib_cm.message_write_to_console(ac, path_file_source)
    lib_cm.message_write_to_console(ac, path_file_cloud)
    #db.write_log_to_db_a(ac, "Testpoint", "p", "write_also_to_console")
    return (success_file, path_file_source, path_file_cloud, path_ftp,
                                                filename_dest)


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
    lib_cm.message_write_to_console(ac, u"check_files_cloud")
    file_is_online = False
    if os.path.isfile(path_file_cloud):
        filename = lib_cm.extract_filename(ac, path_file_cloud)
        lib_cm.message_write_to_console(ac, u"vorhanden: " + path_file_cloud)
        db.write_log_to_db_a(ac,
            u"Vorproduktion fuer extern in Cloud bereits vorhanden: "
            + filename,
            "k", "write_also_to_console")
        file_is_online = True
    return file_is_online


def check_file_dest_ftp(path_ftp, filename_dest):
    """check if file exist in destination-ftp"""
    lib_cm.message_write_to_console(ac, "check_files_online_ftp")
    file_online = False
    try:
        ftp = ftplib.FTP(db.ac_config_1[7])
    except (socket.error, socket.gaierror):
        lib_cm.message_write_to_console(ac, "ftp: no connect to: "
                                        + db.ac_config_1[7])
        log_message = (ac.app_errorslist[6] + " - " + db.ac_config_1[7])
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
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


def work_on_files(roboting_sgs):
    """
    - search audiofiles from roboting-sgs,
    - if found, work on them
    """
    lib_cm.message_write_to_console(ac, "work_on_files")

    for item in roboting_sgs:
        lib_cm.message_write_to_console(ac, item[0].encode('ascii', 'ignore'))
        titel = item[0]
        # search in db for sheduled shows
        sendungen = load_sg(titel)

        if sendungen is None:
            lib_cm.message_write_to_console(ac, "Keine Sendungen gefunden")
            continue

        for sendung in sendungen:
            db.write_log_to_db_a(ac, "Sendung fuer VP nach extern gefunden: "
                    + sendung[11].encode('ascii', 'ignore'), "t",
                    "write_also_to_console")

            # create path and filename
            (success, path_f_source, path_file_cloud,
                path_ftp, filename_dest) = filepaths(item, sendung)
            if success is False:
                continue

            success_file = check_file_source(path_f_source, sendung)
            if success_file is False:
                continue

            if item[1].strip() == "T":
                # to Cloud
                lib_cm.message_write_to_console(ac, "dropbox")
                file_is_in_cloud = check_file_dest_cloud(path_file_cloud)
                if file_is_in_cloud is True:
                    continue

                # copy to dropbox
                success_copy = audio_copy(path_f_source, path_file_cloud)
                if success_copy is False:
                    continue

                # info-txt-file
                success_write = write_to_info_file(
                                                path_file_cloud, item, sendung)
                if success_write is False:
                    # probs mit datei
                    continue

                db.write_log_to_db_a(ac,
                    "VP in Dropbox kopiert: " + filename_dest, "i",
                                                    "write_also_to_console")
                db.write_log_to_db_a(ac,
                    "VP in Dropbox kopiert: " + filename_dest, "n",
                                                    "write_also_to_console")
            if item[3].strip() == "T":
                # to ftp
                lib_cm.message_write_to_console(ac, "ftp")
                file_is_online = check_file_dest_ftp(path_ftp, filename_dest)
                if file_is_online is True:
                    continue
                if file_is_online is None:
                    # an error occures
                    continue

                # ftp-upload
                success_upload = audio_upload(
                                path_f_source, path_ftp, filename_dest)
                if success_upload is False:
                    continue


def erase_files_prepaere(roboting_sgs):
    """prepaere erasing files"""
    date_back = (datetime.datetime.now()
                 + datetime.timedelta(days=- int(db.ac_config_1[3])))
    c_date_back = date_back.strftime("%Y_%m_%d")
    db.write_log_to_db_a(ac, u"Sendedatum muss aelter sein als: "
                                + c_date_back, "t", "write_also_to_console")
    for item in roboting_sgs:
        if item[1].strip() == "T":
            # in Cloud
            path_dest = lib_cm.check_slashes(ac, db.ac_config_1[5])
            path_cloud = lib_cm.check_slashes(ac, item[2])
            path_dest_cloud = (path_dest + path_cloud)
            try:
                files_sendung_dest = os.listdir(path_dest_cloud)
            except Exception, e:
                log_message = ac.app_errorslist[3] + u": %s" % str(e)
                lib_cm.message_write_to_console(ac, log_message)
                db.write_log_to_db(ac, log_message, "x")
                return
        erase_files_from_cloud(path_dest_cloud, files_sendung_dest, c_date_back)
    return


def erase_files_from_cloud(path_dest_cloud, files_sendung_dest, c_date_back):
    """alte Dateien in cloud-ordnern loeschen"""
    lib_cm.message_write_to_console(ac, u"erase_files_from_cloud")

    x = 0
    z = 0

    for item in files_sendung_dest:
        if item[0:10] < c_date_back:
            try:
                file_to_delete = path_dest_cloud + item
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
    """Mainfunktion """
    # search for roboting-shows
    roboting_sgs = load_roboting_sgs()
    if roboting_sgs is None:
        return

    # beaming files if they not there
    work_on_files(roboting_sgs)

    # delete old files in cloud
    db.write_log_to_db_a(ac, "Veraltete Dateien in Cloud loeschen",
                                            "p", "write_also_to_console")
    erase_files_prepaere(roboting_sgs)
    return


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print "lets_work: " + ac.app_desc
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
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
