#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out Preview
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at guugel
2011-10-06

Dieses Script ermittelt vorgesehene Sendungen
und uebertraegt sie in die Web-Datenbank.
Dort werden diese Datensaetze zur Programmvorschau angezeigt

Dateiname Script: play_out_preview.py
Schluesselwort fuer Einstellungen: PO_Preview_Config_1
Benoetigt: ib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank
Arbeitet zusammen mit: srb_tools_load_preview.php auf dem Webserver

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 Fehler beim Uebertragen zum Web-Server

Parameterliste:
Param 1: Anzahl Sendungen, die fuer Vorschau geladen werden
(nicht zu hoch einstellen!)
Param 2: URL Web-Script
Param 3: Benutzer
Param 4: Passwort

Dieses Script wird zeitgesteuert jede Stunde zu Minute 50 ausgefuehrt.

Eine Luege ist bereits dreimal um die Erde gelaufen,
ehe sich die Wahrheit die Schuhe anzieht. Mark Twain
"""

import sys
import datetime
import urllib
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "007"
        self.app_desc = u"Play_Out_Preview"
        # schluessel fuer config in db
        self.app_config = u"PO_Preview_Config_3"
        self.app_config_develop = u"PO_Preview_Config_3_e"
        self.app_errorfile = "error_play_out_preview.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Fehler beim Uebertragen zum Web-Server ")
        self.app_errorslist.append(u"Error 002 "
            "Web-Server fuer Vorschau nicht erreichbar")
        # anzahl parameter
        self.app_config_params_range = 5
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_url")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "no"
        #self.app_windows = "yes"
        # zeit fuer sendungensuche: ab jetzt
        #self.time_target = datetime.datetime.now() + datetime.timedelta()
        self.time_target = datetime.datetime.now()


def load_prev_sendungen():
    """In DB nachsehen, ob Sendungen fuer die kommende Stunde vorgesehen sind"""
    lib_cm.message_write_to_console(ac, "load_prev_sendungen")
    # zfill fuellt nullen auf bei einstelliger stundenzahl

    c_date_time = (str(ac.time_target.date())
                    + " " + str(ac.time_target.hour).zfill(2))
    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' AND "
        "SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) >= '" + c_date_time + "' "
        "AND A.SG_HF_INFOTIME='F' AND A.SG_HF_MAGAZINE='F'")

    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_a(ac,
                                db, db_tbl_condition)
    if sendung_data is None:
        log_message = u"Keine Sendungen für: " + str(ac.time_target.date())
        db.write_log_to_db(ac, log_message, "t")
        return sendung_data

    #log_message = (u"Vorschau-Sendungen vorhanden ab: "
            #+ str(ac.time_target.date() ) + ", "
            #+ str(ac.time_target.hour ).zfill(2) + " Uhr")
    #db.write_log_to_db(ac, log_message, "t" )
    return sendung_data


def beam_prev_sendungen(list_preview_sendungen):
    """ Sendungen an php-Script auf Webserver uebergeben """
    # urllib noetig
    # config
    anzahl_sendungen = int(db.ac_config_1[1])
    #anzahl_sendungen = 5
    url = db.ac_config_1[2]

    # Assoziatives Array / Dictionary / Hashmap
    # fuer datenuebertragung append: data_upload['px']='peix''
    data_upload = {'pa': 'hinein',
        'pc': db.ac_config_1[3],
        'pd': db.ac_config_1[4]}

    z = 0
    for item in list_preview_sendungen:
        c_time = str(item[2])
        c_autor = (lib_cm.replace_uchar_with_html(item[14]) + " "
                + lib_cm.replace_uchar_with_html(item[15]))
        c_title = lib_cm.replace_uchar_with_html(item[11])

        data_upload['px' + str(z + 1)] = c_time
        data_upload['py' + str(z + 1)] = c_autor
        data_upload['pz' + str(z + 1)] = c_title
        z += 1
        if z == anzahl_sendungen:
            break

    # anzahel der uebergebenen sendungen
    data_upload['pb'] = str(anzahl_sendungen)
    lib_cm.message_write_to_console(ac, data_upload)

    # urlencode kann fehler werfen,
    # wenn sonderzeichen nicht encodet werden können
    try:
        data_upload_encoded = urllib.urlencode(data_upload)
    except Exception, e:
        log_message = u"urlencode Error: %s" % str(e)
        db.write_log_to_db(ac, log_message, "x")
        log_message = u"urlencode Error Data " + c_autor + " " + c_title
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        log_message = u"Uebertragung auf Web-Server abgebrochen "
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    # uebertragen
    web = lib_cm.upload_data(ac, db, url, data_upload_encoded)
    if web is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, web)

    if web[0:6] == "Fehler":
        db.write_log_to_db(ac, u"web-script:" + web, "x")
        return None
    else:
        db.write_log_to_db(ac, u"Sendungen fuer Programmvorschau ab "
            + str(ac.time_target.hour).zfill(2) + " Uhr uebertragen: "
            + str(z) + u" Stueck", "i")

    return str(z)


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "
    # sendungen holen
    list_preview_sendungen = load_prev_sendungen()
    if list_preview_sendungen is None:
        log_message = u"Keine Sendungen fuer Programm-Vorschau gefunden"
        db.write_log_to_db(ac, log_message, "t")
        return

    # sendungen in web-db schreiben
    beam_ok = beam_prev_sendungen(list_preview_sendungen)
    if beam_ok is None:
        # Error 001 Fehler beim Uebertragen zum Web-Server
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")

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
            lets_rock()

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + " gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
