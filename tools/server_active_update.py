#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Server Active Update
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at guugel
2017-05-30

Dieses Script aktualisiert die Einstellung
der aktiven Serverlinie. Es laeuft auf dem redundanten Server.
Wird der red. Server zum Hauptserver, muss dieses Script deaktiviert werden!!!!

Dateiname Script: server_active_update.py
Schluesselwort fuer Einstellungen:
Server aktive redundant updater/ server_redundant_active
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nicht
Error 001 Fehler beim update der Einstellung active-server

Parameterliste:
Param 1: on/off
Param 2: server_main
Param 3: server_play_out
Param 4: server_stream

Dieses Script wird zeitgesteuert
nach dem Sync der DB!!! vom Hauptserver ausgefuehrt.

"""

import sys
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "026"
        self.app_desc = u"Server aktive redundant updater"
        # key for config in db
        self.app_config = u"server_redundant_active"
        self.app_config_develop = u"server_redundant_active_dev"
        self.app_errorfile = "error_server_redundant_active.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim update der Einstellung active-server ")
        # amount parameters
        self.app_config_params_range = 3
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")

        # develop-mod
        self.app_develop = "no"
        # debug-mod
        self.app_debug_mod = "no"


def lets_rock():
    """mainfunktion """
    print "lets_rock "

    db_tbl = "USER_SPECIALS A "
    db_tbl_fields = ("A.USER_SP_PARAM_1, A.USER_SP_PARAM_2, A.USER_SP_PARAM_3")
    db_tbl_condition = ("USER_SP_SPECIAL='server_active'")

    server_active = db.read_tbl_row_with_cond(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    # write param server_redundant in param server_active
    # server_active
    db.write_log_to_db_a(ac, u"Server active old: "
                        + server_active[0]
                        + server_active[1]
                        + server_active[2],
                         "t", "write_also_to_console")

    # server_redundant_active
    db.write_log_to_db_a(ac, u"Server active new: "
                        + db.ac_config_1[2]
                        + db.ac_config_1[3]
                        + db.ac_config_1[4],
                         "t", "write_also_to_console")

    sql_command = ("UPDATE USER_SPECIALS "
        "SET USER_SP_PARAM_1='" + db.ac_config_1[2].strip() + "', "
        "USER_SP_PARAM_2='" + db.ac_config_1[3].strip() + "', "
        "USER_SP_PARAM_3='" + db.ac_config_1[4].strip() + "' "
        "where USER_SP_SPECIAL='server_active'")

    db_ok = db.exec_sql(ac, db, sql_command)
    if db_ok is None:
        # Error 003 update
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return
    return

if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + " gestartet", "r")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # alles ok: weiter
        if param_check is not None:
            if db.ac_config_1[1].strip() == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, ac.app_desc
                                    + " ausgeschaltet", "e",
                                    "write_also_to_console")

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + " gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
