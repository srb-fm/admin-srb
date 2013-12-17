#! /usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Log-Eintraege twittern

Autor: Joerg Sorge
Org: SRB - Das Buergerradio
Web: www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at googell
2012-08-06

Dieses Script durchsucht die Logs nach Actions und Errors um sie zu twittern.

Dateiname Script: log_tweeter.py
Schluesselwort fuer Einstellungen: Log_Tweeter_Config
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank

Ablauf:
1. Parameter aus der Config holen (url, suchstring)
2. Errors suchen und twittern
3. Ations suchen und twittern
4. Notifications suchen und twittern

Details:
Die Logs sind mit verschiedenen Merkmalen gekennzeichnet.
x steht fuer Fehler, i fuer Informationen ueber Aktionen.
n fuer Notifications. Nach diesen Kriterien sucht das Script.
Notifications werden per Personal-Message getwittert.

Liste der moeglichen Haupt-Fehlermeldungen:
000 Parameter-Typ oder Inhalt stimmt nicht
001 Fehler beim Twittern

Parameterliste:
Param 1: On/Off Switch
Param 2: Twitter consumer_key
Param 3: Twitter consumer_secret
Param 4: Twitter token_key
Param 5: Twitter token_secret
Param 6: Sekunden zurueck, die im Log beruecksichtigt werden sollen
Param 7: Twitter-User, die Personal-Messages mit Notifications bekommen
(durch Komma getrennt)


Dieses Script wird zeitgesteuert alle 2 Minuten ausgefuehrt.

Man bedenke:
Wer so tut, als bringe er die Menschen zum Nachdenken,
den lieben sie. Wer sie wirklich zum Nachdenken bringt,
den hassen sie.
Aldous Huxley

"""

import sys
import datetime
import tweepy
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config """
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "006"
        self.app_desc = u"Log-Tweeter"
        # schluessel fuer config in db
        self.app_config = u"Log_Tweeter_Config"
        self.app_config_develop = u"Log_Tweeter_Config_e"
        # anzahl parameter
        self.app_config_params_range = 7
        self.app_errorfile = "error_log_tweeter.log"
        self.app_errorslist = []
        self.app_params_type_list = []
        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "no"
        #self.app_windows = "no"
        # errorlist
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht")
        self.app_errorslist.append(u"Error 001 Fehler beim Twittern ")
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")

        self.log_id = 0


def load_actions(c_time_back):
    """ Actions aus Log holen """
    db_tbl = "USER_LOGS A "
    db_tbl_fields = ("A.USER_LOG_ID, A.USER_LOG_TIME, A.USER_LOG_ACTION, "
        "A.USER_LOG_ICON, A.USER_LOG_MODUL_ID ")
    db_tbl_condition = ("SUBSTRING( A.USER_LOG_ICON FROM 1 FOR 1 ) = 'i' "
        "AND SUBSTRING( A.USER_LOG_TIME FROM 1 FOR 19) >= '"
        + c_time_back + "' AND A.USER_LOG_ID > " + str(ac.log_id)
        + " ORDER BY A.USER_LOG_ID")

    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    if log_data is None:
        return

    #  daten aus db durchgehen
    item_log_last = ""
    for row in log_data:
        tweetet_log = load_tweet_logs(row[0])
        if tweetet_log is not None:
            lib_cm.message_write_to_console(ac, "schon getwittert: " + row[2])
            continue
        item_log_current = row[2] + " - " + row[1].strftime("%Y-%m-%d %H:%M:%S")
        if row[2] == "Log-Tweeter gestartet":
            continue
        if item_log_last == item_log_current:
            #TODO: funktioniert das wirklich?
            db.write_log_to_db(ac,
                ac.app_desc + " doppelte Meldung" + item_log_last, "x")
            continue
        lib_cm.message_write_to_console(ac, item_log_current)
        tweet_log(item_log_current)
        item_log_last = item_log_current


def load_errors(c_time_back):
    """ Errors aus Log holen """
    twitter_errors = None
    db_tbl = "USER_LOGS A "
    db_tbl_fields = ("A.USER_LOG_ID, A.USER_LOG_TIME, A.USER_LOG_ACTION, "
        "A.USER_LOG_ICON, A.USER_LOG_MODUL_ID ")
    db_tbl_condition = ("SUBSTRING( A.USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND "
        "SUBSTRING( A.USER_LOG_TIME FROM 1 FOR 19) >= '"
        + c_time_back + "' AND A.USER_LOG_ID > " + str(ac.log_id)
        + " ORDER BY A.USER_LOG_ID")

    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    if log_data is None:
        return

    #  daten aus db durchgehen
    item_log_last = ""
    for row in log_data:
        #lib_cm.message_write_to_console(ac, row[2][33:94])
        if (row[2][33:94] == "-message-: -Not authorized to use this endpoint."
            "-, -code-: 37"):
            # ignore
            lib_cm.message_write_to_console(ac, "Twitter-Error-Code 37: ")
            continue
        tweetet_log = load_tweet_logs(row[0])

        if tweetet_log is not None:
            lib_cm.message_write_to_console(ac, "schon getwittert: " + row[2])
            continue

        item_log_current = row[2] + " - " + row[1].strftime("%Y-%m-%d %H:%M:%S")
        # twitter nicht bombardieren
        item_log_current_a = row[2]
        if item_log_last == item_log_current_a:
            # doppelte Meldung
            continue

        if (row[2] == "001 Fehler beim Twittern "
            "User is over daily status update limit."):
            twitter_errors = "yes"
            return twitter_errors
        if (row[2] == "001 Fehler beim Twittern "
            "Failed to send request: [Errno -2] Name or service not known"):
            twitter_errors = "yes"
            return twitter_errors
        if (row[2] == "Vorhergehende Twitter-Fehler,"):
            twitter_errors = "yes"
            return twitter_errors

        lib_cm.message_write_to_console(ac, item_log_current)
        twitter_errors = tweet_message(item_log_current)
        item_log_last = row[2]

    return twitter_errors


def load_notis(c_time_back):
    """ Notifications aus Log holen """
    db_tbl = "USER_LOGS A "
    db_tbl_fields = ("A.USER_LOG_ID, A.USER_LOG_TIME, A.USER_LOG_ACTION, "
        "A.USER_LOG_ICON, A.USER_LOG_MODUL_ID ")
    db_tbl_condition = ("SUBSTRING( A.USER_LOG_ICON FROM 1 FOR 1 ) = 'n' AND "
        "SUBSTRING( A.USER_LOG_TIME FROM 1 FOR 19) >= '"
        + c_time_back + "' AND A.USER_LOG_ID > " + str(ac.log_id)
        + " ORDER BY A.USER_LOG_ID")

    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    if log_data is None:
        return

    #  daten aus db durchgehen
    for row in log_data:
        tweetet_log = load_tweet_logs(row[0])
        if tweetet_log is not None:
            lib_cm.message_write_to_console(ac, "schon getwittert: " + row[2])
            continue

        item_log_current = row[2] + " - " + row[1].strftime("%Y-%m-%d %H:%M:%S")
        lib_cm.message_write_to_console(ac, item_log_current)
        tweet_message(item_log_current)


def load_tweet_logs(user_log_id):
    """ Checken ob log bereits getweetet wurde """
    db_tbl_condition = "TW_USER_LOG_ID = " + str(user_log_id)
    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, "TWITTER_LOGS", "TW_USER_LOG_ID", db_tbl_condition)

    if log_data is None:
        db.write_twitter_log_to_db_1(ac, user_log_id, "write_also_to_console")
        return None
    else:
        return "bereits_getweetet"


def tweet_log(log_message):
    """ Log an Twitter uebertragen """
    #auth = tweepy.OAuthHandler(ac.consumer_key, ac.consumer_secret)
    auth = tweepy.OAuthHandler(db.ac_config_1[2], db.ac_config_1[3])
    #auth.set_access_token(ac.access_token, ac.access_token_secret)
    auth.set_access_token(db.ac_config_1[4], db.ac_config_1[5])
    api = tweepy.API(auth)
    try:
        api.update_status(log_message[0:140])
        #lib_cm.message_write_to_console( ac, tweet_log )
    except Exception, e:
        log_message = ac.app_errorslist[1] + str(e) + " " + log_message[0:90]
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")


def tweet_message(log_message):
    """ Log als Message an Twitter uebertragen """
    tweet_error = None
    auth = tweepy.OAuthHandler(db.ac_config_1[2], db.ac_config_1[3])
    auth.set_access_token(db.ac_config_1[4], db.ac_config_1[5])
    api = tweepy.API(auth)
    try:
        #api.send_direct_message("Name", text=log_message[0:140])
        message_to = db.ac_config_1[7].split(",")
        for item in message_to:
            # mit strip ev. leerzeichen
            # vor und hinter dem empfaengernamen entfernen
            api.send_direct_message(item.strip(), text=log_message[0:140])
            db.write_log_to_db_a(ac, log_message[0:140], "p",
                "write_also_to_console")
    except Exception, e:
        err_message = ac.app_errorslist[1] + str(e) + " " + log_message[0:90]
        if err_message[0:90] != log_message[0:90]:
            # nur einmal registrieren
            db.write_log_to_db_a(ac, err_message, "x", "write_also_to_console")
        # damit die naechsten tweets nicht erst abgesendet werden,
        # wenn hier keine verbindung erfolgt
        tweet_error = "yes"

    return tweet_error


def delete_tweet_log_in_db_log():
    """Veraltete Log-Eintraege in DB-log loeschen"""
    lib_cm.message_write_to_console(ac, u"delete_tweet_log_in_db_log")
    date_log_back = (datetime.datetime.now()
                     + datetime.timedelta(days=- 1))
    c_date_log_back = date_log_back.strftime("%Y-%m-%d %H:%M")

    ACTION = ("DELETE FROM TWITTER_LOGS WHERE TW_LOG_TIME < '"
              + c_date_log_back + "'")

    #ACTION = "DELETE FROM TWITTER_LOGS"

    db.dbase_log_connect(ac)
    if db.db_log_con is None:
        err_message = u"No connect to db for delete_tweet_log_in_db_log"
        lib_cm.error_write_to_file(ac, err_message)
        return None

    try:
        db_log_cur = db.db_log_con.cursor()
        db_log_cur.execute(ACTION)
        db.db_log_con.commit()
        db.db_log_con.close()
        log_message = (u"Loeschen der Tweetlogs "
            "in DB-Log-Tabelle die von gestern sind")
        db.write_log_to_db(ac, log_message, "e")
    except Exception, e:
        lib_cm.message_write_to_console(ac,
            log_message
            + u"Error 2 delete_tweet_log_in_db_log: %s</p>" % str(e))
        err_message = (log_message
                         + u"Error 2 delete_tweet_log_in_db: %s" % str(e))
        lib_cm.error_write_to_file(ac, err_message)
        db.db_log_con.rollback()
        db.db_log_con.close()
        return None
    return "ok"


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "

    #time_back = datetime.datetime.now() + datetime.timedelta( seconds= - 120 )
    time_back = (datetime.datetime.now()
                 + datetime.timedelta(seconds=- int(db.ac_config_1[6])))

    c_time_back = time_back.strftime("%Y-%m-%d %H:%M:%S")
    # zuerst errors suchen
    # wenn twitter errors dabei sind, nicht weiter machen
    twitter_errors = load_errors(c_time_back)
    if twitter_errors is None:
        load_actions(c_time_back)
        load_notis(c_time_back)
    #else:
        #log_message = ac.app_errorslist[1] + " Twittern ausgesetzt"
        #db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        #log_message = "Vorhergehende Twitter-Fehler, versuche sie zu uebergehen"
        #db.write_log_to_db_a(ac, log_message, "n", "write_also_to_console")

    # alte logs des vortages loeschen
    if datetime.datetime.now().hour == 0:
        delete_tweet_log_in_db_log()

if __name__ == '__main__':
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
    # losgehts
    #db.write_log_to_db(ac,  ac.app_desc + " gestartet", "r")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db)
        # alles ok: weiter
        if param_check is not None:
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, "Log_Tweeter ausgeschaltet", "e",
                    "write_also_to_console")
    # fertsch
    #db.write_log_to_db(ac,  ac.app_desc + " gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
