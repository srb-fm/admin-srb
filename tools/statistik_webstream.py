#! /usr/bin/env python
# -*- coding: utf-8 -*-

"""
Statistik Webstream-Hoerer

Autor: Joerg Sorge
Org: SRB - Das Buergerradio
Web: www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at gmail om
2011-09-26

Dieses Script extrahiert aus der Status-Webseite
des Icecastservers die Angabe der Peaklisteners und speichert die Anzahl.

Dateiname Script: statistik_webstream.py
Schluesselwort fuer Einstellungen: ST_Webstream_Config
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank, Icecast-Webseite

Ablauf:
1. Parameter aus der Config holen (url, suchstring)
2. Webseite aufrufen
3. Uptime und Peaklisteners extrahieren
4. Peaklisteners registireren
   (wenn sich uptime geaendert, neuer datensatz, sonst alten aktualisieren)

Details:
Die Icecast-Statusseite listet die aktiven Mountpoints
mit den jeweiligen Daten auf. Man muss also innerhalb der Seite zunaechst
den Abschnitt des betreffenden Mountpouints finden
und dann daraus die Werte fuer Mont Uptime und Peak Listeners.
Um die entsprechenden Stellen in der Webseite zu finden,
wird ein Suchstring (hinterlegt in der config) benoetigt.
Dieser Suchstring wird von der Webseite aus den
Oddcast-Einstellungen uebernommen. Werden also die Einstellungen
des upstream in oddcast geaendert,
muss auch unser suchstring in der config angepasst werden.

Liste der moeglichen Haupt-Fehlermeldungen:
000 Parameter-Typ oder Inhalt stimmt nich
001 Webseite Icecast-Streamstatus kann nicht heruntergeladen werden
002 Haupt-Abschnitt kann aus Webseite nicht extrahiert werden.
Schluesselwort(e) nicht gefunden:
003 Abschnitt Peaklisteners kann aus Webseite nicht extrahiert werden.
Schluesselwort(e) nicht gefunden:
004 Abschnitt Uptime kann aus Webseite nicht extrahiert werden.
Schluesselwort(e) nicht gefunden:
005 Zeitformatumwandlung fuer Webstream-Statistik fehlgeschlagen
006 Fehler beim Registireren der Webstream-Hoerer in der Datenbank

Parameterliste:
Param 1: URL maxFm-Statistik
Param 2: Zeichen die gesucht werden: Anfangszeichen
Param 3: Zeichen die gesucht werden: Endzeichen
Param 4: Zeichen die gesucht werden um Anzahl Hoerer zu finden
Param 5: Anzahl Zeichen die vorgezaelt werden muessen.
Wichtig. Sollte auf der Webseite der Eintrag:
[Stream Genre: various] geaendert werden,
so muss die Zahl in Param 5 angepasst werden
Param 6: Zeichen die gesucht werden um Verbindungszeit zu finden
Param 7: Zeichen die vorgezaehlt werden muessen

Erklaerung:
Es werden zwei Werte benoetigt.
Diese sind innerhalb eines Abschnitts zu finden,
die eindeutig dem Sender zugeordnet werden koennen:
 - Stationskennung (z.B. SRB-Radio)
 - Stations-Webseite (z.B. srb.fm)
Zwischen diesen beiden Zeichenketten liegen die beiden Werte, die wir suchen:
 - Peak Listeners
 - Mount Uptime
Von ersterem (z.B. Peak Listeners) muessen wir
eine bestimmte Anzahl Zeichen (Quelltext der html-Seite)
vorzaehlen um an den Wert zu kommen. Genauso von Zweitem.

Das Script wird alle 2 Stunden ausgefuehrt.

Man bedenke:
Die Statistik ist wie eine Laterne im Hafen.
Sie dient dem betrunkenen Seemann mehr zum Halt als zur Erleuchtung.
Hermann Josef Abs (1901 - 1994)

"""

import sys
import string
import time
import re
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "008"
        self.app_desc = u"Statistik Webstream Hoerer"
        # schluessel fuer config in db
        self.app_config = u"ST_Webstream_Config"
        self.app_config_develop = u"ST_Webstream_Config_e"
        # anzahl parameter
        self.app_config_params_range = 8
        self.app_errorfile = "error_statistik_webstream_hoerer.log"
        self.app_errorslist = []
        self.app_params_type_list = []
        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
        self.app_windows = "yes"
        # errorlist
        self.app_errorslist.append(u"000 Parameter-Typ oder "
            "Inhalt stimmt nicht ")
        self.app_errorslist.append(u"001 Webseite Icecast-Streamstatus "
            "kann nicht heruntergeladen werden")
        self.app_errorslist.append(u"002 Haupt-Abschnitt kann aus "
            "Icecast-Webseite nicht extrahiert werden. "
            "Schluesselwort(e) nicht gefunden:")
        self.app_errorslist.append(u"003 Abschnitt Peaklisteners kann aus "
            "Icecast-Webseite nicht extrahiert werden. "
            "Schluesselwort(e) nicht gefunden:")
        self.app_errorslist.append(u"004 Abschnitt Uptime kann aus "
            "Icecast-Webseite nicht extrahiert werden. "
            "Schluesselwort(e) nicht gefunden:")
        self.app_errorslist.append(u"005 Zeitformatumwandlung "
            "fuer Webstream-Statistik fehlgeschlagen")
        self.app_errorslist.append(u"006 Fehler beim Registireren "
            "der Webstream-Hoerer in der Datenbank")
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_url")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.action_summary = ""


def extract_cut_off(website, match_string_1, match_string_2):
    """ Aus Website wichtigsten Abschnitt extrahieren """
    index_begin = string.find(website, match_string_1)
    if index_begin == -1:
        cut_off = None
        #log_message = u"Webstreamhoerer: Ausschnitt Beginn nicht gefunden. "
        #db.write_log_to_db( ac,  log_message , "x"  )
        lib_cm.message_write_to_console(ac, "cut_off_error_1")
        return cut_off

    index_end = string.find(website, match_string_2)
    if index_end == -1:
        cut_off = None
        #log_message = u"Webstreamhoerer: Ausschnitt Ende nicht gefunden. "
        #db.write_log_to_db( ac,  log_message , "x"  )
        lib_cm.message_write_to_console(ac, "cut_off_error_2")
        return  cut_off

    cut_off = website [index_begin:index_end]
    lib_cm.message_write_to_console(ac, cut_off)
    return cut_off


def extract_peaklisteners(website, match_string, charackters_forwards):
    """Aus Abschnitt der Website peaklisteners ermitteln """
    index_b_ident = string.find(website, match_string)
    if index_b_ident == -1:
        peaklisteners = None
        return peaklisteners

    # index_b_begin = index_b_ident - 156
    index_b_begin = (index_b_ident + len(match_string)
                     + int(charackters_forwards))
    index_b_end = index_b_begin + 4
    extract = website[index_b_begin:index_b_end]
    print "extract:" + extract

    # Zahlen extrahieren
    peaklisteners = re.search(r'[0-9\.]+', extract).group(0)
    #print peaklisteners

    if re.match("\d{1,}", peaklisteners) is None:
        # erstes zeichen ist keine zahl
        peaklisteners = None

    return peaklisteners


def extract_time(website, match_string_2, charackters_forwards):
    """ Aus Abschnitt der Website uptime ermitteln """

    index_a_ident = string.find(website, match_string_2)
    if index_a_ident == -1:
        t_uptime_current = None
        return  t_uptime_current

    index_a_begin = index_a_ident + int(charackters_forwards)
    index_a_end   = index_a_begin + 20

    # website_uptime ermitteln fuer dateiname oder db_satz_id
    c_uptime_current = website[index_a_begin:index_a_end]
    lib_cm.message_write_to_console(ac, c_uptime_current)
    t_uptime_current = time.strptime(c_uptime_current, "%d %b %Y %H:%M:%S")
    return t_uptime_current


def write_listeners_to_db(peaklisteners, sql_time):
    """ Peaklisteners in db schreiben """
    # ist schon ein ein satz zur uptime da?
    c_time = db.read_tbl_row_with_cond(
        ac, db, "ST_WEB_STREAM_LISTENERS", "ST_WEB_STREAM_LIS_DAY",
        "ST_WEB_STREAM_LIS_DAY='" + sql_time + "'")

    if c_time is None:
        # nix da, neuen satz einfuegen
        sql_command = ("INSERT INTO ST_WEB_STREAM_LISTENERS"
            "(ST_WEB_STREAM_LIS_DAY, ST_WEB_STREAM_LIS_NUMBER)"
            " values ('" + sql_time + "', '" + peaklisteners + "')")
        lib_cm.message_write_to_console(ac, "Datensatz neu: " + peaklisteners)
        log_message = (u"Statistik Webstream-Hoerer "
            "nach neuer Verbindung aktualisiert. Anzahl: " + peaklisteners)
    else:
        # schon da, updaten
        sql_command = ("UPDATE ST_WEB_STREAM_LISTENERS "
            "SET ST_WEB_STREAM_LIS_NUMBER ='" +
            peaklisteners + "' where ST_WEB_STREAM_LIS_DAY='" + sql_time + "'")
        lib_cm.message_write_to_console(ac, "Datensatz aktualisiert: "
                                        + peaklisteners)
        log_message = (u"Statistik Webstream-Hoerer "
            "bei bestehender Verbindung aktualisiert. Anzahl: " + peaklisteners)

    db_op_success = db.exec_sql(ac, db, sql_command)
    if db_op_success is None:
        # Error 005 Fehler beim Registireren
        # der Webstream-Hoerer in der Datenbank
        err_message = ac.app_errorslist[6] + " " + peaklisteners
        db.write_log_to_db_a(ac, err_message, "x", "write_also_to_console")
    else:
        db.write_log_to_db(ac, log_message, "i")
    return


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "
    # webseite holen
    lib_cm.message_write_to_console(ac, u"webseite holen:")
    lib_cm.message_write_to_console(ac, db.ac_config_1[1])
    website = lib_cm.download_website(ac, db, db.ac_config_1[1])
    if website is None:
        # Error 001 fehler beim download der seite
        db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
        return
    #print website

    # abschnitt aus webseite extrahieren
    cut_off = extract_cut_off(website, db.ac_config_1[2], db.ac_config_1[3])
    if cut_off is None:
        # Error 002 fehler beim extrahieren des hauptabschnitts
        # aus webseite (schluesselwoerter nicht vorhanden)
        err_message = (ac.app_errorslist[2] + " "
                       + db.ac_config_1[2] + " " + db.ac_config_1[3])
        db.write_log_to_db_a(ac, err_message, "x", "write_also_to_console")
        return

    # peaklisteners extrahieren
    peaklisteners = extract_peaklisteners(cut_off,
                                          db.ac_config_1[4], db.ac_config_1[5])
    if peaklisteners is None:
        # Error 003 Abschnitt Peaklisteners kann aus
        # Webseite nicht extrahiert werden. Schluesselwort nicht gefunden:
        err_message = (ac.app_errorslist[3] + " " + db.ac_config_1[4]
                       + " " + db.ac_config_1[5])
        db.write_log_to_db_a(ac, err_message, "x", "write_also_to_console")
        return

    # uptime extrahieren
    t_uptime = extract_time(cut_off, db.ac_config_1[6], db.ac_config_1[7])
    if t_uptime is None:
        # Error 004 Abschnitt Uptime kann aus Webseite
        # nicht extrahiert werden. Schluesselwort nicht gefunden:
        err_message = (ac.app_errorslist[4] + " " + db.ac_config_1[6]
                       + " " + db.ac_config_1[7])
        db.write_log_to_db_a(ac, err_message, "x", "write_also_to_console")
        return

    # Zeit für datensatz
    try:
        sql_time = (str(t_uptime.tm_year) + "-"
            + str(t_uptime.tm_mon).zfill(2) + "-"
            + str(t_uptime.tm_mday).zfill(2) + " "
            + str(t_uptime.tm_hour).zfill(2) + ":"
            + str(t_uptime.tm_min).zfill(2) + ":"
            + str(t_uptime.tm_sec).zfill(2))
        lib_cm.message_write_to_console(ac, sql_time)
    except Exception:
        err_message = ac.app_errorslist[5]
        db.write_log_to_db_a(ac, err_message, "x", "write_also_to_console")
        return

    # registrieren
    write_listeners_to_db(peaklisteners, sql_time)

if __name__ == '__main__':
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

