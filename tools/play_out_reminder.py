#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Play Out Reminder 
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at guugell
2011-10-06

Dieses Script ermittelt Sendungen die eine Pruefung/ Beachtung durch 
einen Bearbeiter anraten und registriert sie im Log, 
sodass der Log_Tweeter erinnern kann. 
- Sendungen die nicht freigeschalten sind
- Sendungen die live aus den Sudios erfolgen


Dateiname Script: play_out_reminder.py
Keine weiteren Einstellungen noetig.
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank 

Fehlerliste:
keine

Parameterliste:
keine

Dieses Script wird zeitgesteuert jede Stunde zur 46. Minute ausgefuehrt.

Was nuetzt die Freiheit des Denkens, 
wenn sie nicht zur Freiheit des Handelns fuehrt.
Jonathan Swift
"""

import sys
import datetime
import lib_common as lib_cm


class app_config( object ):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "015"
        self.app_desc = u"Play_Out_Reminder"
        self.app_errorfile = "error_play_out_reminder.log"
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
        # zeit fuer sendungensuche: ab jetzt
        #self.time_target = datetime.datetime.now() + datetime.timedelta( )
        # ab naechste stunde
        self.time_target = datetime.datetime.now() + datetime.timedelta( hours=+1 )


def load_off_air_sendungen(ac ):
    """In DB nachsehen, ob nicht freigeschaltene Sendungen fuer die kommende Stunde vorgesehen sind """
    lib_cm.message_write_to_console( ac, "load_prev_sendungen" )
    # zfill fuellt nullen auf bei einstelliger stundenzahl
    
    c_date_time_from = str( ac.time_target.date() ) + " " + str( ac.time_target.hour ).zfill(2) 
    c_date_time_to = str( ac.time_target.date() ) + " " + str( ac.time_target.hour +1 ).zfill(2) 
    db_tbl_condition = ("A.SG_HF_ON_AIR = 'F' "
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) >= '" + c_date_time_from + "' " 
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) <= '" + c_date_time_to + "' " 
        "AND A.SG_HF_INFOTIME='F' AND A.SG_HF_MAGAZINE='F'")
    
    sendung_data =  db.read_tbl_rows_sg_cont_ad_with_cond_1( ac,  db,  db_tbl_condition )
    if sendung_data is None:
        log_message = u"Keine Off-Air-Sendungen fuer: " + c_date_time_from + u" bis " + c_date_time_to
    else:   
        log_message = u"Off-Air-Sendungen vorhanden von: " + c_date_time_from + u" bis " + c_date_time_to + " Uhr"
    
    db.write_log_to_db_1(ac,  log_message, "t", "write_also_to_console" )
    return sendung_data
    
def load_studio_sendungen(ac ):
    """In DB nachsehen, ob Studio-Sendungen fuer die kommende Stunde vorgesehen sind """
    lib_cm.message_write_to_console( ac, "load_studio_live_sendungen" )
    # zfill fuellt nullen auf bei einstelliger stundenzahl
    
    c_date_time_from = str( ac.time_target.date() ) + " " + str( ac.time_target.hour ).zfill(2) 
    c_date_time_to = str( ac.time_target.date() ) + " " + str( ac.time_target.hour +1 ).zfill(2) 
    db_tbl_condition = ("A.SG_HF_SOURCE_ID <> '03' "
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) >= '" + c_date_time_from + "' " 
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) <= '" + c_date_time_to + "' " 
        "AND A.SG_HF_INFOTIME='F' AND A.SG_HF_MAGAZINE='F'")
    
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_1( ac,  db,  db_tbl_condition )
    if sendung_data is None:
        log_message = u"Keine Studio-Sendungen fuer: " + c_date_time_from + u" bis " + c_date_time_to
    else:
        log_message = u"Studio-Sendungen vorhanden von: " + c_date_time_from + u" bis " + c_date_time_to + " Uhr"
    
    db.write_log_to_db_1(ac,  log_message, "t", "write_also_to_console" )
    return sendung_data


def log_off_air_sendungen(list_off_air_sendungen ):
    """ Off-Air Sendungen in Log registrieren """
    for item in list_off_air_sendungen:            
        db.write_log_to_db( ac, u"Achtung, Sendung nicht freigeschalten: " + str(item[2]) + u" - " + item[15] + u" - " + item[11] , "n")

def log_studio_sendungen(list_studio_sendungen ):
    """ Studio Sendungen in Log registrieren """
    for item in list_studio_sendungen:            
        db.write_log_to_db( ac, u"Achtung, Sendung aus Studio vorgesehen: " + str(item[2]) + u" - " + item[15] + u" - " + item[11] , "n" )


def lets_rock():
    """Hauptfunktion """
    print "lets_rock " 
    # off-air-sendungen holen 
    list_off_air_sendungen = load_off_air_sendungen(ac)
    if list_off_air_sendungen is not None: 
        # sendungen in log-tabelle schreiben
        log_off_air_sendungen(list_off_air_sendungen)

    # studio-sendungen holen 
    list_studio_sendungen = load_studio_sendungen(ac)
    if list_studio_sendungen is not None: 
        # sendungen in log-tabelle schreiben
        log_studio_sendungen(list_studio_sendungen)


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc 
    # losgehts
    db.write_log_to_db( ac,  ac.app_desc + " gestartet", "r"  )
    lets_rock()
    # fertsch
    db.write_log_to_db( ac, ac.app_desc + " gestoppt", "s"  )
    print "lets_lay_down" 
    sys.exit()
