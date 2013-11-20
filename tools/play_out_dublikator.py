#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out Dublikator
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at gooogell
2011-09-30

Dieses Script dupliziert perjodische Erst-Sendungen
(Buchungen in Sendabwicklung)
die woechentlich gesendet werden.


Dateiname Script: play_out_dublikator.py
Schluesselwort fuer Einstellungen: PO_DB_Config
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank

Parameterliste:
Param 1: On/Off Switch

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich


Besonderheiten:
Es werden Sendungen beruecksichtigt, die in der config gefunden werden.
Nur Sendungen, die in einem woechentlichen Rythmus ausgestrahlt werden,
koennen von dem Script bearbeitet werden.

Das Script arbeitet einmal woechentlich (Mittwoch Nacht).

In unserer Gesellschaft geht ein Gespenst um,
das nur wenige deutlich sehen.
Es ist nicht der alte Geist des Kommunismus oder des Faschismus.
Es ist ein neues Gespenst:
eine voellig mechanisierte Gesellschaft,
die sich der maximalen Produktion
und dem maximalen Konsum verschrieben hat
und von Computern gesteuert wird.
Erich Fromm, Die Revolution der Hoffnung
"""

import time
import sys
import datetime
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "016"
        self.app_desc = u"play_out_dublikator"
        # schluessel fuer config in db
        self.app_config = u"PO_DB_Config"
        self.app_config_develop = u"PO_DB_Config"
        # anzahl parameter
        self.app_config_params_range = 1
        self.app_errorfile = "error_play_out_dublikator.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "beim Generieren des Stichwortes fuer duplizierte Sendung")
        self.app_errorslist.append(u"Error 002 "
            "Duplizierte Sendung konnte nicht gebucht werden ")
        self.app_errorslist.append(u"Error 003 "
            "beim Loeschen der duplizierten Sendung "
            "(Eintrag der Meta-Daten ist fehlgeschlagen) ")
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")

        # entwicklungsmodus, andere parameter, z.b. bei verzeichnissen
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "no"
        # das script laeuft mitwochs 9:30 uhr, hier wochenzeitraum einstellen
        self.time_target_start = (datetime.datetime.now()
                            + datetime.timedelta(days=-2)
                            + datetime.timedelta(hours=-9)
                            + datetime.timedelta(minutes=-30))
        self.time_target_end = (datetime.datetime.now()
                            + datetime.timedelta(days=+4)
                            + datetime.timedelta(hours=+14)
                            + datetime.timedelta(minutes=+30))
        # develop at Tuesday
        #self.time_target_start = (datetime.datetime.now()
        #                          + datetime.timedelta(days=-1)
        #                          + datetime.timedelta(hours=-10))
        #self.time_target_end = (datetime.datetime.now()
        #                        + datetime.timedelta(days=+5)
        #                        + datetime.timedelta(hours=+4))
        #self.time_target = datetime.datetime.now()


def search_sg(sg_titel, t_sg_time):
    """Pruefen ob geplante Sendebuchung schon vorhanden"""
    lib_cm.message_write_to_console(ac,
        u"Pruefen ob geplante Sendebuchung schon vorhanden")
    db_tbl_condition = ("A.SG_HF_FIRST_SG ='T' AND "
        "SUBSTRING(A.SG_HF_TIME FROM 1 FOR 19) = '"
        + str(t_sg_time) + "' " + "AND B.SG_HF_CONT_TITEL='" + sg_titel + "' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_a(ac,
                            db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Noch keine Sendung mit diesem Titel "
            "zu dieser Zeit gefunden: " + sg_titel.encode('ascii', 'ignore')
            + " " + str(t_sg_time))
    else:
        log_message = (u"Sendung bereits gebucht mit diesem Titel "
            "zu dieser Zeit: " + sg_titel.encode('ascii', 'ignore')
            + " " + str(t_sg_time))
    db.write_log_to_db_a(ac, log_message, "p", "write_also_to_console")
    return sendung_data


def load_roboting_sgs(dub_way):
    """Sendungen suchen, die bearbeitet werden sollen"""
    lib_cm.message_write_to_console(ac,
        u"Sendungen suchen, die bearbeitet werden sollen")
    sendungen_data = db.read_tbl_rows_with_cond(ac, db,
        "SG_HF_ROBOT", "SG_HF_ROB_TITEL, SG_HF_ROB_STICHWORTE",
        "SG_HF_ROB_DUB_ID ='" + dub_way + "'")

    if sendungen_data is None:
        log_message = u"Keine Sendungen zur Duplizierung vorgesehen.. "
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    return sendungen_data


def load_sg(sg_titel, dub_way):
    """Erstsendung als Vorlage suchen"""
    lib_cm.message_write_to_console(ac, u"Sendung als Vorlage suchen")

    db_tbl_condition = ("A.SG_HF_FIRST_SG ='T' "
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 19) >= '"
        + ac.time_target_start.strftime("%Y-%m-%d %T") + "' "
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 19) <= '"
        + ac.time_target_end.strftime("%Y-%m-%d %T") + "' "
        "AND B.SG_HF_CONT_TITEL='" + sg_titel + "' ")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_c(ac,
                                        db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine Erst-Sendung mit diesem Titel "
            "in diesem Zeitraum gefunden: " + sg_titel + " - "
            + ac.time_target_start.strftime("%Y-%m-%d %T")
            + " bis " + ac.time_target_end.strftime("%Y-%m-%d %T"))
        db.write_log_to_db_a(ac, log_message, "e", "write_also_to_console")
        return sendung_data
    return sendung_data


def write_sg_main(l_data_sg_main):
    """Sendung Main eintragen"""
    lib_cm.message_write_to_console(ac, u"Sendung eintragen")
    sql_command_sg_main = ("INSERT INTO SG_HF_MAIN( SG_HF_ID, "
            "SG_HF_CONTENT_ID, SG_HF_TIME, "
            "SG_HF_DURATION, SG_HF_INFOTIME, SG_HF_MAGAZINE, "
            "SG_HF_PODCAST, SG_HF_ON_AIR, "
            "SG_HF_SOURCE_ID, SG_HF_REPEAT_PROTO, SG_HF_FIRST_SG)"
            " values ('" + str(l_data_sg_main[0]) + "', '"
            + str(l_data_sg_main[1]) + "', '" + str(l_data_sg_main[2]) + "', '"
            + l_data_sg_main[3] + "', '" + l_data_sg_main[4] + "', '"
            + l_data_sg_main[5] + "', '" + l_data_sg_main[6] + "', '"
            + l_data_sg_main[7] + "', '" + l_data_sg_main[8] + "', '"
            + l_data_sg_main[9] + "', '" + l_data_sg_main[10] + "')")
    lib_cm.message_write_to_console(ac, sql_command_sg_main)
    db_op_success_main = db.exec_sql(ac, db, sql_command_sg_main)
    return db_op_success_main


def write_sg_cont(l_data_sg_content):
    """Sendung Content eintragen"""
    sql_command_sg_cont = ("INSERT INTO SG_HF_CONTENT( SG_HF_CONT_ID, "
        "SG_HF_CONT_SG_ID, "
        "SG_HF_CONT_AD_ID, SG_HF_CONT_TITEL, "
        "SG_HF_CONT_FILENAME, SG_HF_CONT_STICHWORTE, "
        "SG_HF_CONT_GENRE_ID, SG_HF_CONT_SPEECH_ID, SG_HF_CONT_TEAMPRODUCTION, "
        "SG_HF_CONT_UNTERTITEL, SG_HF_CONT_REGIEANWEISUNG, SG_HF_CONT_WEB )"
        " values ('" + str(l_data_sg_content[0]) + "', '"
        + str(l_data_sg_content[1]) + "', '" + str(l_data_sg_content[2])
        + "', '" + l_data_sg_content[3] + "', '" + l_data_sg_content[4]
        + "', '" + l_data_sg_content[5] + "', '" + l_data_sg_content[6]
        + "', '" + l_data_sg_content[7] + "', '" + l_data_sg_content[8]
        + "', '" + l_data_sg_content[9] + "', '" + l_data_sg_content[10]
        + "', '" + l_data_sg_content[11] + "')")
    lib_cm.message_write_to_console(ac, sql_command_sg_cont)
    db_op_success_cont = db.exec_sql(ac, db, sql_command_sg_cont)
    return db_op_success_cont


def delete_failed_sg_in_db(main_id_sg):
    """In SG_MAIN wieder loeschen wenn SG_CONT fehlgeschlagen"""
    lib_cm.message_write_to_console(ac,
        u"delete_failed_sg_in_db with nr: " + str(main_id_sg))
    ACTION = ("DELETE FROM SG_HF_MAIN WHERE SG_HF_ID = "
              + str(main_id_sg) + " ROWS 1")
    db.dbase_connect(ac)
    if db.db_con is None:
        err_message = (u"Error General: "
            "No connection to db for delete_failed_sg_in_db")
        lib_cm.error_write_to_file(ac, err_message)
        return None

    try:
        db_cur = db.db_con.cursor()
        db_cur.execute(ACTION)
        db.db_con.commit()
        db.db_con.close()
        log_message = u"Loeschen der Sendung bei Fehlschlag SG_CONT.. "
        db.write_log_to_db_a(ac, log_message, "e", "write_also_to_console")
    except Exception, e:
        err_message = u"Error 2 delete_failed_sg_in_db: %s" % str(e)
        #db.write_log_to_db_a(ac,  err_message , "x", "write_also_to_console" )
        lib_cm.error_write_to_file(ac, err_message)
        db.write_log_to_db(ac, ac.app_errorslist[3], "x")
        db.db_con.rollback()
        db.db_con.close()
        return None
    return "ok"


def lets_rock():
    """Hauptfunktion """
    print "lets_rock "
    lib_cm.message_write_to_console(ac, u"lets_rock")

    # Sendungen suchen, die bearbeitet werden sollen
    # weekly
    roboting_sgs = load_roboting_sgs("01")
    if roboting_sgs is None:
        return
    rock_weekly(roboting_sgs)

    # Sendungen suchen, die bearbeitet werden sollen
    # daily
    roboting_sgs = load_roboting_sgs("02")
    if roboting_sgs is None:
        return
    rock_daily(roboting_sgs)


def rock_weekly(roboting_sgs):
    """weekly dublikating"""
    log_message = u"Duplizierung woechentlich bearbeiten.. "
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
    for item in roboting_sgs:
        lib_cm.message_write_to_console(ac, item)
        # Sendung suchen
        sendung = load_sg(item[0], "01")
        if sendung is None:
            lib_cm.message_write_to_console(ac, u"Keine Sendungen gefunden")
            continue

        db.write_log_to_db_a(ac, u"Sendung zum Duplizieren gefunden: "
                            + sendung[0][14].encode('ascii', 'ignore'),
                            "t", "write_also_to_console")
        # ids fuer neue datensaetze holen
        main_id_sg = db.load_gen_id(ac, db)
        main_id_sg_cont = db.load_gen_id(ac, db)
        #lib_cm.message_write_to_console(ac, main_id_sg )
        #lib_cm.message_write_to_console(ac, main_id_sg_cont )

        # Datum um 7 Tage vorzaehlen
        lib_cm.message_write_to_console(ac, sendung[0][2].day)
        lib_cm.message_write_to_console(ac,
                        sendung[0][2] + datetime.timedelta(days=+7))
        lib_cm.message_write_to_console(ac, ac.time_target_start.date())
        lib_cm.message_write_to_console(ac,
                        ac.time_target_start.strftime("%Y-%m-%d %T"))
        dt_sg_new_date = sendung[0][2] + datetime.timedelta(days=+7)

        # pruefen ob sendung schon gebucht
        #sendung_dub = search_sg(ac, item[0], dt_sg_new_date )
        sendung_dub = search_sg(item[0], dt_sg_new_date)
        if sendung_dub is not None:
            continue

        # values l_data_sg_main aus gefundener sendung zusammenbauen
        l_data_sg_main = [main_id_sg, main_id_sg_cont,
            dt_sg_new_date, sendung[0][3].strip(),
            sendung[0][4].strip(), sendung[0][5].strip(),
            sendung[0][6].strip(), u"F", sendung[0][8].strip(),
            sendung[0][9].strip(), sendung[0][10].strip()]

        # Stichwort zusammenbauen
        try:
            lib_cm.message_write_to_console(ac, sendung[0][16])
            lib_cm.message_write_to_console(ac, item[1])
            # counter suchen und erhoehen
            counter_pos = item[1].find("nnn")
            if counter_pos != -1:
                counter_old = sendung[0][16][counter_pos:counter_pos + 3]
                lib_cm.message_write_to_console(ac, counter_old)
                # bei zweistelligem ergebnis null auffuellen
                counter_new = str(int(counter_old) + 1).zfill(3)
                lib_cm.message_write_to_console(ac, counter_new)
                # Stichwort bis Counter
                # stichwort_anfang = sendung[0][16][0:counter_pos]

            # datum in stichwort suchen
            date_pos = item[1].find("yyyy_mm_dd")

            # counter und date vorhanden
            if counter_pos != -1 and date_pos != -1:
                # durch sendung[0][16][date_pos+10:] wird alles
                # was in stichwort sonst noch eingetragen
                # hinten wieder dran gesetzt
                sg_stichwort = (item[1][0:counter_pos] + counter_new
                                + "_" + dt_sg_new_date.strftime("%Y_%m_%d")
                                + sendung[0][16][date_pos + 10:])
            # counter aber kein date vorhanden
            if counter_pos != -1 and date_pos == -1:
                sg_stichwort = item[1][0:counter_pos] + counter_new
            # counter nicht, aber date vorhanden
            if counter_pos == -1 and date_pos != -1:
                sg_stichwort = (item[1][0:date_pos]
                                + dt_sg_new_date.strftime("%Y_%m_%d")
                                + sendung[0][16][date_pos + 10:])
            # weder counter noch date in stichwort vorhanden
            if counter_pos == -1 and date_pos == -1:
                sg_stichwort = sendung[0][16]

            # max. laenge stichwort pruefen, wenn noetig kuerzen
            if len(sg_stichwort) > 40:
                sg_stichwort = sg_stichwort[0:40]

            lib_cm.message_write_to_console(ac, sg_stichwort)
        except Exception, e:
            lib_cm.message_write_to_console(ac,
                "Fehler Stichwort generieren :" + str(e))
            db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
                "write_also_to_console")
            continue

        # filename zusammenbauen
        if sendung[0][8].strip() == "03":
            # Vorproduziert oder stream
            if sendung[0][15][0:7] == "http://":
                # wenn stream dann nix bauen sondern uebernehmen
                sg_filename = sendung[0][15]
            else:
                sg_filename = (str(main_id_sg_cont) + "_"
                + lib_cm.replace_uchar_sonderzeichen_with_latein(sendung[0][25])
                + "_"
                + lib_cm.replace_uchar_sonderzeichen_with_latein(sg_stichwort)
                + ".mp3")
        else:
            # Live
            sg_filename = "Keine_Audiodatei"

        # values l_data_sg_content aus gefundener sendung zusammenbauen
        l_data_sg_content = [main_id_sg_cont, main_id_sg, sendung[0][13],
            sendung[0][14], sg_filename, sg_stichwort, sendung[0][17].strip(),
            sendung[0][18].strip(), sendung[0][19].strip(), sendung[0][20],
            sendung[0][21], sendung[0][22]]
        # Daten eintragen
        db_op_success_main = write_sg_main(l_data_sg_main)
        if db_op_success_main is not None:
            db_op_success_cont = write_sg_cont(l_data_sg_content)
            if db_op_success_cont is None:
                # Fehler beim insert in sg_cont ac.app_errorslist[1]
                lib_cm.message_write_to_console(ac,
                    "Fehler bei insert into sg_cont, main_sg wieder loeschen! "
                    + sendung[0][14].encode('ascii', 'ignore'))
                db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
                    "write_also_to_console")
                delete_failed_sg_in_db(main_id_sg)

        if db_op_success_cont is not None:
            db.write_log_to_db_a(ac, "automatisch gebucht, bitte pruefen: "
                + sendung[0][14].encode('ascii', 'ignore') + " "
                + str(dt_sg_new_date), "i", "write_also_to_console")
            time.sleep(1)
            db.write_log_to_db_a(ac, "automatisch gebucht, bitte pruefen: "
                + sendung[0][14].encode('ascii', 'ignore') + " "
                + str(dt_sg_new_date), "n", "write_also_to_console")
            time.sleep(2)


def rock_daily(roboting_sgs):
    """daily dublikating"""
    log_message = u"Duplizierung taeglich bearbeiten.. "
    db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
    for item in roboting_sgs:
        lib_cm.message_write_to_console(ac, item)
        # Sendung suchen
        sendung = load_sg(item[0], "02")
        if sendung is None:
            lib_cm.message_write_to_console(ac, u"Keine Sendungen gefunden")
            continue

        db.write_log_to_db_a(ac, u"Sendung zum Duplizieren gefunden: "
                            + sendung[0][14].encode('ascii', 'ignore'),
                            "t", "write_also_to_console")

if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc
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
                db.write_log_to_db_a(ac, "Sendungs-Dublikator ausgeschaltet",
                "e", "write_also_to_console")

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
