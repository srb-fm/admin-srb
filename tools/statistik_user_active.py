#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Statistik aktiver User (Macher)
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge googge mail
2011-09-25

Dieses Script ermittelt die aktiven Nutzer (Macher) und speichert die Anzahl.

Dateiname Script: statistik_user_active.py
Schluesselwort fuer Einstellungen: nicht benoetigt
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank, Icecast-Webseite 

Details:
Aktive Nutzer (macher) sind solche, 
auf die eine Sendeanmeldung oder Ausleihe gebucht wurde.
Es wird jeweils zu Beginn eines neuen Quartals ausgefuehrt (chronjob).

Ablauf:
1. Anzahl ermitteln
2. Anzahl speichern
3. Alle aktiven Macher wieder auf false setzen

Liste der moeglichen Fehlermeldungen:
000 Fehler beim Ermitteln der aktiven Macher 
001 Fehler beim Registireren der Aktiven Macher in der Datenbank: 
002 Fehler beim Zuruecksetzen der Aktiven Macher in der Datenbank

Parameterliste:
keine

Ausfuehrung per chron an jedem 1. Tag des Quartals

Man bedenke:
Die Statistik ist eine sehr gefaellige Dame. 
Naehert man sich ihr mit entsprechender Hoeflichkeit, 
dann verweigert sie einem fast nie etwas.
Edouard Herriot (1872-1957)
"""

import sys
import lib_common as lib_cm


class app_config( object ):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "010"
        self.app_desc = u"Statistik aktive Macher"
        #schluessel fuer config in db
        #self.app_config = nicht nenoetigt
        #self.app_config_develop = nicht nenoetigt
        # anzahl parameter
        #self.app_config_params_range = 0
        self.app_errorfile = "error_statistik_user_active.log"
        self.app_errorslist = []
        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
        self.app_windows = "yes"
        # errorlist
        self.app_errorslist.append(u"000 Fehler beim Ermitteln der aktiven Macher ")
        self.app_errorslist.append(u"001 Fehler beim Registrieren der Aktiven Macher in der Datenbank: ")
        self.app_errorslist.append(u"002 Fehler beim Zuruecksetzen der Aktiven Macher in der Datenbank")


def lets_rock():
    """Hauptfunktion """
    print "lets_rock " 
    # Aktive User in Adress-Tabelle lesen
    user_active_number = db.count_rows(ac, db,"AD_MAIN", "AD_USER_OK_AKTIV='T'")
    if user_active_number is None:
        # Error 000 Fehler beim Ermitteln der aktiven Macher
        db.write_log_to_db_1(ac, ac.app_errorslist[0] , "x", "write_also_to_console"  )
        return
    
    log_message = "Aktive Macher: " + str( user_active_number )
    db.write_log_to_db( ac,  log_message, "t"  )
    lib_cm.message_write_to_console( ac, log_message )

    # Anzahl speichern
    sql_command = ("INSERT INTO ST_USER_OK_ACTIVE ( ST_USER_OK_ACTIVE_NUMBER ) "
        "VALUES ( '"  + str( user_active_number ) + "')")
    db_ok = db.exec_sql( ac, db, sql_command )
    if db_ok is None:
        # Error 001 Fehler beim Registireren der Aktiven Macher in der Datenbank
        err_message = ac.app_desc + " " + ac.app_errorslist[1] + " " + user_active_number
        db.write_log_to_db_1(ac, err_message, "x", "write_also_to_console" )
        return
    
    # user_active zuruecksetzen
    sql_command = "UPDATE AD_MAIN SET AD_USER_OK_AKTIV='F' "
    db_ok_1 = db.exec_sql( ac, db, sql_command )
    if db_ok_1 is None:
        # Error 002 Fehler beim Zuruecksetzen der Aktiven Macher in der Datenbank
        db.write_log_to_db_1(ac,  ac.app_errorslist[2], "x", "write_also_to_console"  )
        return
    
    return


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc 
    # losgehts
    db.write_log_to_db( ac,  ac.app_desc + " gestartet", "a"  )
    lets_rock()
    # fertsch
    db.write_log_to_db( ac,  ac.app_desc + " gestoppt", "s"  )
    print "lets_lay_down" 
    sys.exit()
 
