#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Watch Dog mp3gain
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at googell
2011-09-30

Dieses Script ueberwacht ein Verzeichnis
und bearbeitet enthaltene mp3-Dateien
mit dem Tool mp3Gain.
Danach wird die Audio-Datei aus dem Verzeichnis
in ein anderes verschoben.

Dateiname Script: watch_dog_mp3gain.py
Schluesselwort fuer Einstellungen: WD_mp3gain_Config_1
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 bei mp3gain
Error 002 Fehler beim Kopieren der bearbeiteten Datei:

Parameterliste:
Param 1: Pfad/Programm mp3gain
Param 2: Pfad in dem die mp3's zum Bearbeiten sind
Param 3: Pfad in den die bearbeiteten mp3's verschoben werden sollen

Dieses Script wird zeitgesteuert alle 2 Minuten ausgefuehrt.

Ich bin der Ueberzeugung, dass es kaum jemanden gibt,
dessen Intimleben die Welt nicht in Staunen und Horror versetzte,
wenn es uebers Radio gesendet werden wuerde.
William Somerset Maugham
"""

import sys
import os
import string
import shutil
import subprocess
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "017"
        self.app_desc = u"watch_dog_mp3gain"
        # key for config in db
        self.app_config = u"WD_mp3gain_Config_3"
        self.app_config_develop = u"WD_mp3gain_Config_3_e"
        # number of parameters
        self.app_config_params_range = 1
        self.app_errorfile = "error_watch_dog_mp3gain.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "bei mp3gain:")
        self.app_errorslist.append(u"Error 002 "
            "Fehler beim Kopieren nach mp3Gaining: ")
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        # develop-mode
        self.app_develop = "no"
        # messages to console
        self.app_debug_mod = "no"
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
    # extern tools ...
    ext_params_ok = lib_cm.params_provide_tools(ac, db)
    if ext_params_ok is None:
        return None
    ext_params_ok = lib_cm.params_provide_server_settings(ac, db)
    if ext_params_ok is None:
        return None
    lib_cm.set_server(ac, db)
    ext_params_ok = lib_cm.params_provide_server_paths_b(ac, db,
                                                        ac.server_active)
    return ext_params_ok


def audio_mp3gain(path_file):
    """make mp3-gain"""
    lib_cm.message_write_to_console(ac, u"mp3-File Gainanpassung")
    c_mp3gain = db.ac_config_etools[5].encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, c_mp3gain)
    c_source_file = path_file.encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, c_source_file)
    # start subprozess
    try:
        p = subprocess.Popen([c_mp3gain, u"-r", c_source_file],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[1] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message,
                                "x", "write_also_to_console")
        return

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])

    # search for succes message, if not found: -1
    mp3gain_output = string.find(p[1], "99%")
    mp3gain_output_1 = string.find(p[1], "written")
    lib_cm.message_write_to_console(ac, mp3gain_output)
    lib_cm.message_write_to_console(ac, mp3gain_output_1)
    # wenn gefunden, position, sonst -1
    if mp3gain_output != -1 and mp3gain_output_1 != -1:
        log_message = u"mp3gain angepasst: " + c_source_file.decode('cp1252')
        db.write_log_to_db(ac, log_message, "k")
        lib_cm.message_write_to_console(ac, "ok")
    else:
        db.write_log_to_db_a(ac, u"mp3gain offenbar nicht noetig: "
                             + c_source_file.decode('cp1252'),
                             "p", "write_also_to_console")


def lets_rock():
    """main function """
    print "lets_rock "
    lib_cm.message_write_to_console(ac, u"lets_rock check_and_work_on_files")
    path_source = lib_cm.check_slashes(ac, db.ac_config_servpath_b[3])
    path_dest = lib_cm.check_slashes(ac, db.ac_config_servpath_b[4])

    lib_cm.message_write_to_console(ac, path_source)
    lib_cm.message_write_to_console(ac, path_dest)

    # read mp3gain-folder
    try:
        files_source = os.listdir(path_source)
    except Exception, e:
        log_message = u"read_files_from_dir Error: %s" % str(e)
        lib_cm.message_write_to_console(ac, log_message + path_source)
        db.write_log_to_db(ac, log_message, "x")
        return None

    # loop through files
    z = 0
    for item in files_source:
        if string.rfind(item, ".mp3") == -1:
            # no mp3:
            continue
        z += 1
        path_file_source = path_source + item
        # audio_mp3gain(ac, path_file_source )
        audio_mp3gain(path_file_source)

        # move
        path_file_dest = path_dest + item

        # extract filename right from slash
        if ac.app_windows == "no":
            filename = path_file_dest[string.rfind(path_file_dest, "/") + 1:]
        else:
            filename = path_file_dest[string.rfind(path_file_dest, "\\") + 1:]

        try:
            shutil.move(path_file_source, path_file_dest)
            # if the file very short (<30 sec),
            # it could be, that it will not be copy and no error will occure
            # why??
        except Exception, e:
            db.write_log_to_db_a(ac, ac.app_errorslist[2] + filename,
                "x", "write_also_to_console")
            log_message = u"copy_files_to_dir_retry Error: %s" % str(e)
            lib_cm.message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            continue

        db.write_log_to_db_a(ac, u"Audio mit mp3gain bearbeitet und kopiert: "
            + filename, "i", "write_also_to_console")


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # losgehts
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # ok: continue
        if param_check is not None:
            # extended params
            load_extended_params_ok = load_extended_params()
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, ac.app_desc
                                    + " ausgeschaltet", "e",
                                    "write_also_to_console")

    # finish
    print "lets_lay_down"
    sys.exit()
