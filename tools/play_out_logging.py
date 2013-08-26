#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out Logging

Autor: Joerg Sorge
Org: SRB - Das Buergerradio
Web: www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at ggooogl
2011-09-26

Dieses Script registriert ausgespielte Beitraege in der Datenbank
und uebertraegt sie auf den Webserver.
Dort koennen die  zur Anzeige des aktuellen Beitrags/Titels
und fuer Playlisten genutzt werden.

Dateiname Script: play_out_logging.py
Schluesselworte fuer Einstellungen: PO_Logging_Config_1/
PO_Switch_Broadcast_Config_1
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank, Logfiles mehrerer mAirlist-Instanzen

Ablauf:
1. Haupt- und erweiterte Parameter aus der Config holen
2. Form aufrufen und im Intervall Metadaten der Beitraege/Titel
ermitteln und in den DBs speichern
3. Pruefen ob Quelle Aussenuebertragung, VP oder Studios
4. Daten aus Logfiles oder db ermitteln
5. Logging in db
6. Upload auf Webserver

Details:
Die Quellen werden aus dem Logeintrag (Sendequellenumschalter)
in der DB ermittelt.
Wenn Aussenuebertragung, dann gibts keine Logfiles,
also Sendedaten aus db hohlen.
Wenn VP oder Studio, dann die mAirlist-Logdateien einlesen
und mit db vergleichen.
Zu guter letzt, Daten in db schreiben und auf Webserver uebertragen.
Bei Fehlern gibt es ein Fallback.
Es wird der in der Config voreingestellte Autor und Titel
geloggt und uebertragen.

Liste der moeglichen Haupt-Fehlermeldungen:
Error 000 Parameter-Typ oder Inhalt stimmt nicht
Error 001 Sende-Quelle kann aus Datenbank nicht ermittelt werden
Error 002 Play-Out-Log-Datei kann nicht gelesen werden
Error 003 Webserver fuer PlayOut-Logging lieferte bei
Uebertragung Fehler zurueck
Error 004 Webserver fuer PlayOut-Logging nicht erreichbar

Parameterliste:
Param 1: mAirList Logdatei aktueller Titel
(Erweiterung um Nummer und Endung "log" wird durch das
logging-Programm vorgenommen)
Param 2: Aktualisierungsintervall in Sekunden
Param 3: Not-Autor
Param 4: Not-Titel
Param 5: URL Webseite
Param 6: Benutzer
Param 7: Passwort
Param 8: mAirList Logdatei der Studio-Rechner, aktueller Titel
(Erweiterung um Nummer und Endung "log"
wird durch das logging-Programm vorgenommen)
Hinweis: Autor und Titel (param 3 und 4)
wird eingesetzt wenn tatsaechlicher nicht ermittelbar

Erweiterte Parameter werden bezogen von:
PO_Time_Config_1


Das Script laeuft mit graphischer Oberflaeche staendig.

Hinweis:
Log und Statusmeldungen, die direkt ausgegeben werden (print),
koennen bei laengerer Laufzeit zu Speicherproblemen fuehren,
besonders unter Windows.

Man bedenke:
Und dein Knecht steht mitten in deinem Volk, das du erwaehlt hast,
einem Volk, so gross,
dass es wegen seiner Menge niemand zaehlen noch berechnen kann.
Die Bibel 1. Koenige 3,8
"""


from Tkinter import Frame, Label, NW, END
from ScrolledText import ScrolledText
import sys
import string
import re
import datetime
import urllib
import lib_common_1 as lib_cm


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "003"
        self.app_desc = u"Play Out Logging"
        self.app_config = u"PO_Logging_Config_3"
        self.app_config_develop = u"PO_Logging_Config_1_e"
        self.app_develop = "no"
        self.app_windows = "no"
        self.app_errorfile = "error_play_out_logging.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "Sende-Quelle kann aus Datenbank nicht ermittelt werden ")
        self.app_errorslist.append(u"Error 002 "
            "Play-Out-Log-Datei kann nicht gelesen werden")
        self.app_errorslist.append(u"Error 003 Webserver fuer PlayOut-Logging"
            " lieferte bei Uebertragung Fehler zurueck")
        self.app_errorslist.append(u"Error 004 "
            "Webserver fuer PlayOut-Logging nicht erreichbar")
        self.app_errorslist.append(u"Error 005 "
            "Externes PlayOut-Logging ausgesetzt, Webserver nicht erreichbar")
        # meldungen auf konsole ausgeben oder nicht: "no"
        self.app_debug_mod = "no"
        # anzahl parameter list 0
        self.app_config_params_range = 9
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_url")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_counter = 0
        self.app_counter_error = 0
        self.error_counter_read_log_file = 0
        self.log_start = None
        self.log_author = None
        self.log_title = None


def extract_from_stuff_after_match(stuff, match_string):
    """Aus String Abschnitt extrahieren was nach match_string kommt """
    index_trenner = string.find(stuff, match_string)
    index_begin_offset = len(match_string)
    index_end = len(stuff)
    cut_off = stuff[index_trenner + index_begin_offset:index_end]

    if cut_off == "":
        cut_off = "nix"
        #log_message = "extract_from_stuff_after_match: cut_off ist leer. "
        # kein Beinbruch, Fehler nicht loggen
        #db.write_log_to_db(ac, log_message, "x")
        #print "extract_from_stuff_after_match_error_1"
        return  cut_off

    return cut_off


def extract_from_stuff(stuff,
            match_string_1, offset_1, match_string_2, offset_2):
    """ String aus Abschnitt rausholen """
    index_begin = string.find(stuff, match_string_1)
    if index_begin == -1:
        cut_off = "nix"
        #log_message = "extract_from_stuff: Ausschnitt Beginn nicht gefunden. "
        # kein Beinbruch, Fehler nicht loggen
        #db.write_log_to_db(ac, log_message, "x")
        #print "extract_from_stuff_error_1"
        return  cut_off

    index_end = string.find(stuff, match_string_2)
    if index_end == -1:
        cut_off = "nix"
        #log_message = "extract_from_stuff: Ausschnitt Ende nicht gefunden. "
        # kein Beinbruch, Fehler nicht loggen
        #db.write_log_to_db(ac, log_message, "x")
        #print "extract_from_stuff_error_2"
        return  cut_off

    cut_off = stuff[index_begin + offset_1:index_end + offset_2]
    return cut_off


def upload_data_prepare():
    """ Upload auf Webserver vorbereiten """
    # Bei I-Netfehler upload aussetzen
    if ac.app_counter_error > 3:
        db.write_log_to_db_a(ac, ac.app_errorslist[5], "x",
            "write_also_to_console")
        ac.app_counter_error = 0
        return

    c_autor = lib_cm.replace_uchar_with_html(ac.log_author)
    c_title = lib_cm.replace_uchar_with_html(ac.log_title)
    data_upload = {'pa': 'hinein',
        'pb': ac.log_start,
        'pc': ac.log_start,
        'pd': c_autor,
        'pe': c_title,
        'pf': db.config_extended[6],
        'pg': db.config_extended[7]}

    # urlencode kann fehler werfen,
    # wenn sonderzeichen nicht encodet werden können
    try:
        data_upload_encoded = urllib.urlencode(data_upload)
    except Exception, e:
        log_message = "urlencode Error: %s" % str(e)
        db.write_log_to_db(ac, log_message, "x")
        log_message = "urlencode Error Data " + c_autor + " " + c_title
        db.write_log_to_db(ac, log_message, "x")
        data_upload = {'pa': 'hinein',
            'pb': ac.log_start,
            'pc': ac.log_start,
            'pd': db.config_extended[3],
            'pe': db.config_extended[4],
            'pf': db.config_extended[6],
            'pg': db.config_extended[7]}

        lib_cm.message_write_to_console(ac, u"data_upload"
                + c_autor + " " + c_title)
        data_upload_encoded = urllib.urlencode(data_upload)

    web = lib_cm.upload_data(ac, db, db.config_extended[5], data_upload_encoded)
    if web is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
            "write_also_to_console")
        ac.app_counter_error += 1
        return web

    if web[0:6] == "Fehler":
        #print web
        db.write_log_to_db(ac, ac.app_errorslist[3], "x")
        db.write_log_to_db(ac, web, "x")

    return web


def work_on_data_from_logfile(time_now, log_data):
    """Daten aus mAirlist-Logfile extrahieren"""
    lib_cm.message_write_to_console(ac, u"work_on_data_from_logfile")
    #log_start = extract_from_stuff(log_data, "start=", 6, "&author=", 0)
    log_author = extract_from_stuff(log_data, "&author=", 8, "&title=", 0)
    log_title = extract_from_stuff(log_data, "&title=", 7, "&file=", 0)
    log_filename = extract_from_stuff_after_match(log_data, "&file=")

    sendung_data = None
    sendung_data_search_for_id_only = "no"

    # Falls Uebernahme per Inetstream, erkennbar an http
    if log_title[0:7] == "http://":
        lib_cm.message_write_to_console(ac, u"uebernahme_per_inetstream")
        # Sendestunde ermitteln, anpassen
        if time_now.hour < 10:
            c_hour = "0" + str(time_now.hour)
        else:
            c_hour = str(time_now.hour)

        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
            + str(time_now.date()) + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
            + c_hour + "' AND B.SG_HF_CONT_FILENAME ='"
            + log_title + "'")
        # daten aus db holen
        sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                db, db_tbl_condition)

    # Falls SRB-Dateiname, erkennbar an 7stelliger Zahl am Anfang
    if re.match("\d{7,}", log_filename) is not None:
        lib_cm.message_write_to_console(ac,
            u"srb_sendung_id_in_title: daten aus db, "
            "erster versuch mit zeit und id")
        # id extrahieren und sendung in db suchen
        sendung_id = log_filename[0:7]
        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
            + str(time_now.date()) + "' "
            "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
            + str(time_now.hour) + "' AND B.SG_HF_CONT_ID ='"
            + sendung_id + "'")
        sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                    db, db_tbl_condition)
        if sendung_data is None:
            sendung_data_search_for_id_only = "yes"

    if sendung_data_search_for_id_only == "yes":
        lib_cm.message_write_to_console(ac,
            u"srb_sendung_id_in_title: daten aus db, "
            "zweiter versuch nur mit id")
        db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
            "AND B.SG_HF_CONT_ID ='" + sendung_id + "'")
        sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                    db, db_tbl_condition)

    #  Daten aus db
    if sendung_data is not None:
        lib_cm.message_write_to_console(ac, u"daten_aus_db")
        log_author = sendung_data[12] + " " + sendung_data[13]
        log_title = sendung_data[9]
        lib_cm.message_write_to_console(ac, log_author + " - " + log_title)
    else:
        lib_cm.message_write_to_console(ac, u"nix in db gefunden")
        # prüfen ob autor und titel in logdatei vorhanden
        author_title_ok = "no"
        if  log_author != "":
            author_title_ok = "yes"

        if  log_title != "":
            author_title_ok = "yes"

        if author_title_ok == "yes":
            # author und titel in logdatei
            lib_cm.message_write_to_console(ac, u"daten_aus_mAirList_logfile")
            log_author = lib_cm.convert_to_unicode(log_author)
            log_title = lib_cm.convert_to_unicode(log_title)
        else:
            # keine daten in id3-author, deshalb aus filename nehmen
            lib_cm.message_write_to_console(ac, u"daten_aus_filname")
            log_title = log_title[11:len(log_title)]
            #print log_title
            # split in autor und title,
            # klappt nur wenn ein unterstrich dazwischen
            index_trenner = string.find(log_title, "_")
            log_author = log_title[0:index_trenner]
            log_title = extract_from_stuff_after_match(log_title, "_")
            log_author = lib_cm.convert_to_unicode(log_author)
            log_title = lib_cm.convert_to_unicode(log_title)

    log_data_list = []
    #log_data_list.append(log_start)
    log_data_list.append(log_author)
    log_data_list.append(log_title)
    lib_cm.message_write_to_console(ac, log_data_list)
    return log_data_list


class my_form(Frame):
    """Form"""
    def __init__(self, master=None):
        """Elemente der Form kreieren"""
        Frame.__init__(self, master)
        self.pack()
        #self.createWidgets()
        self.text_label = Label(self,
            height=1, width=80, anchor=NW, text="Play-Out-Logging Nr: ")
        self.text_label.pack()

        self.text_label_1 = Label(self,
            height=1, width=80, text="Titel aktuell")
        self.text_label_1.pack()

        self.textBox = ScrolledText(self, height=5, width=80)
        self.textBox.pack()
        self.textBox.insert(END, "In the Beginning...\n")

        self.text_label_2 = Label(self,
            height=1, width=80, text="Rueckmeldung von Webserver")
        self.text_label_2.pack()

        self.textBox1 = ScrolledText(self, height=10, width=80)
        self.textBox1.pack()
        self.textBox1.insert(END, "...and the End\n")

        # registering callback
        self.listenID = self.after(500, self.lets_rock)

    def display_logging(self, log_meldung_1, log_meldung_2):
        """Logging in Form zur Anzeige bringen """
        if log_meldung_1 is not None:
            self.text_label.config(text="Play-Out-Logging Nr: "
                + str(ac.app_counter) + "  / Interval von "
                + str(int(db.config_extended[2])) + " Sekunden")
            self.textBox.delete('1.0', '3.end')
            self.textBox.insert(END, log_meldung_1 + "\n")

        if log_meldung_2 is not None:
            self.text_label.config(text="Play-Out-Logging Nr: "
                + str(ac.app_counter) + "  / Interval von "
                + str(int(db.config_extended[2])) + " Sekunden")
            self.textBox1.delete('1.0', '8.end')
            self.textBox1.insert(END, log_meldung_2 + "\n")

        self.listenID = self.after(
                        int(db.config_extended[2]) * 1000, self.lets_rock)
        return

    def lets_rock(self):
        """Hauptfunktion"""
        lib_cm.message_write_to_console(ac, u"lets rock")
        ac.app_counter += 1
        log_data = None
        time_now = datetime.datetime.now()
        time_back = datetime.datetime.now() + datetime.timedelta(hours=- 1)
        c_time = time_back.strftime("%Y-%m-%d %H")
        lib_cm.message_write_to_console(ac, c_time)

        # 1. Quellen ermitteln
        # Quellen-Switch-Stellungen aus user_logs lesen
        source_log = db.read_tbl_row_with_cond_log(ac, db,
            "USER_LOGS", "USER_LOG_ACTION, USER_LOG_TIME",
            u"USER_LOG_ACTION LIKE "
            u"'Datei für Sendequellenumschalter geschrieben:%' "
            + u"AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 13) ='"
            + c_time + "' ORDER BY USER_LOG_TIME DESC")
        # WICHTIG:  Der log_text nach dem hier gesucht wird,
        # wird von play_out_load geschrieben

        if source_log is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
                "write_also_to_console")
            log_meldung_1 = ac.app_errorslist[1] + "\n"
            self.display_logging(log_meldung_1, None)
            return
        else:
            lib_cm.message_write_to_console(ac, source_log)
            source_params = source_log[0][46:52]

        lib_cm.message_write_to_console(ac, source_params)

        # 2. Quelle der aktuellen Sendezeit zuordnen
        #if time_now.minute < 5:
        #if time_now.minute < int(db.config_extended[15]):
        if time_now.minute < int(db.config_extended[13]):
            source_id = source_params[0:2]

        #if time_now.minute >=5 <30:
        if (time_now.minute >= int(db.config_extended[13])
            < int(db.config_extended[14])):
            source_id = source_params[2:4]

        #if time_now.minute >=30:
        if time_now.minute >= int(db.config_extended[14]):
            source_id = source_params[4:6]

        lib_cm.message_write_to_console(ac, source_id)

        # Dateinamen der mAirlist-Logdatei zusammenbauen
        if source_id == "03":
            file_mairlist_log = db.config_extended[1] + "_" + source_id + ".log"
        else:
            file_mairlist_log = db.config_extended[8] + "_" + source_id + ".log"
        lib_cm.message_write_to_console(ac, file_mairlist_log)

        # 3. Pruefen ob Quelle Aussenuebertragung, VP oder Studios
        # Bei Aussenuebertragung stehen keine Logfiles zur Verfuegung,
        # Sendung muesste in db zu finden sein
        if source_id == "05":
            lib_cm.message_write_to_console(ac, u"ISDN-Uebertragung")
            # Sendestunde ermitteln, anpassen
            if time_now.hour < 10:
                c_hour = "0" + str(time_now.hour)
            else:
                c_hour = str(time_now.hour)
            # Daten aus db holen
            db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
                "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 10) = '"
                + str(time_now.date()) + "' "
                "AND SUBSTRING(A.SG_HF_TIME FROM 12 FOR 2) = '"
                + c_hour + "' AND A.SG_HF_SOURCE_ID ='"
                + source_id + "'")
            sendung_data = db.read_tbl_row_sg_cont_ad_with_cond(ac,
                            db, db_tbl_condition)
            if sendung_data is not None:
                if ac.log_start == str(sendung_data[2]):
                    # Startzeit der Sendung (SG_HF_TIME)
                    # ist gleich der im vorigen Durchlauf ermittelten Sendung,
                    # also laeuft sie noch, keine Aenderung
                    lib_cm.message_write_to_console(ac,
                        u"ISDN-Uebertragung laeuft noch")
                    log_meldung_1 = ("Keine Aenderung... \n" +
                    ac.log_start + " - " + ac.log_author + " - " + ac.log_title)
                    self.display_logging(log_meldung_1, None)
                    return
                else:
                    # Daten der Sendung fuer Vergleich
                    # bei naechstem Durchlauf einlesen
                    ac.log_start = str(sendung_data[2])
                    ac.log_author = sendung_data[12] + " " + sendung_data[13]
                    ac.log_title = sendung_data[9]
            else:
                lib_cm.message_write_to_console(ac,
                    u"ISDN-Uebertragung, Sendung nicht in DB gefunden")
                # Spaeter mit Vorbelegung aus Einstellungen fuellen
                ac.log_author = None
                ac.log_title = None

        else:
            # else source_id == "05":
            # Daten aus Logfiles holen bzw. aus db
            lib_cm.message_write_to_console(ac,
                u"Sendung aus Studios, Playout oder Internetstream")
            # Daten aus mAirlist_Logdatei holen
            mairlist_log_data = lib_cm.read_file_first_line(ac,
                            db, file_mairlist_log)
            lib_cm.message_write_to_console(ac, mairlist_log_data)
            if mairlist_log_data is None:
                # Fehler beim Lesen des Logfiles
                ac.error_counter_read_log_file += 1
                log_meldung_1 = ac.app_errorslist[1] + " \n"
                if ac.error_counter_read_log_file == 1:
                    # Error-Meldung nur einmal registrieren
                    db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
                    "write_also_to_console")
                    # Ausfall-Meldung nur einmal uebertragen
                    ac.log_start = (str(time_now.date()) + " "
                        + str(time_now.time())[0:8])
                    ac.log_author = db.config_extended[3]
                    ac.log_title = db.config_extended[4]
                    web = upload_data_prepare()
                    if web is not None:
                        self.display_logging(log_meldung_1, web)
                    else:
                        self.display_logging(log_meldung_1, None)
                else:
                    self.display_logging(log_meldung_1, None)
                return
            else:
                ac.error_counter_read_log_file = 0

            # bei direktem Vergleich des Inhalts der Logdatei
            # (mairlist_log_data) funktioniert folgender
            # if-Vergleich nicht aussserhalb der ide, deshalb in vari
            mairlist_log_time = mairlist_log_data[6:25]
            if ac.log_start == mairlist_log_time:
                # Keine Aenderung des gespielten Titels, also wieder zurueck
                log_meldung_1 = ("Keine Aenderung... \n" +
                   ac.log_start + " - " + ac.log_author + " - " + ac.log_title)
                self.display_logging(log_meldung_1, None)
                return
            else:
                # 4. Daten aus Logfiles oder db ermitteln
                ac.log_start = mairlist_log_data[6:25]
                log_data = mairlist_log_data
                # Ermitteln ob gebuchte Sendung, oder Musik
                log_author_title = work_on_data_from_logfile(time_now, log_data)
                ac.log_author = log_author_title[0]
                ac.log_title = log_author_title[1]

        if ac.log_author is None:
            ac.log_start = (str(time_now.date()) + " "
                + str(time_now.time())[0:8])
            ac.log_author = db.config_extended[3]

        if ac.log_title is None:
            ac.log_title = db.config_extended[4]

        # Bezeichnung der Quelle holen
        log_source_desc = db.read_tbl_row_with_cond(ac,
                db, "SG_HF_SOURCE", "SG_HF_SOURCE_ID, SG_HF_SOURCE_DESC",
                "SG_HF_SOURCE_ID ='" + source_id + "'")
        # 5. Logging in db
        db.write_log_to_db(ac,
            log_source_desc[1].strip() + ": " + ac.log_author + " - "
            + ac.log_title, "a")
        log_meldung_1 = (log_source_desc[1]
            + ": \n" + ac.log_author + " - " + ac.log_title)

        # 6. Upload auf Webserver
        web = upload_data_prepare()
        if web is not None:
            self.display_logging(log_meldung_1, web)
        else:
            ac.log_start = (str(time_now.date()) + " "
                + str(time_now.time())[0:8])
            ac.log_author = db.config_extended[3]
            ac.log_title = db.config_extended[4]
            log_meldung_1 = ac.app_errorslist[4] + " \n"
            self.display_logging(log_meldung_1, None)
        return


if __name__ == "__main__":
    print "play_out_logging start"
    db = lib_cm.dbase()
    ac = app_config()
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    param_check_counter = 0

    if db.ac_config_1 is not None:
        # Haupt-Params pruefen
        param_check = lib_cm.params_check_1(ac, db)
        if param_check is not None:
            # Haupt-Params ok: weiter
            param_check_counter += 1
            #print "ok"

    # Erweiterte Params laden
    db.ac_config_2 = db.params_load_1a(ac, db, "PO_Time_Config_1")
    if db.ac_config_2 is not None:
        # Erweiterte Paramsliste anlegen
        app_params_type_list_2 = []
        # Erweiterte Params-Type-List,
        # Typ entsprechend der Params-Liste in der Config
        app_params_type_list_2.append("p_string")
        app_params_type_list_2.append("p_int")
        app_params_type_list_2.append("p_int")
        app_params_type_list_2.append("p_int")
        app_params_type_list_2.append("p_int")
        app_params_type_list_2.append("p_int")
        app_params_type_list_2.append("p_int")
        # Erweiterte Params pruefen
        param_check_3 = lib_cm.params_check_a(ac,
                    db, 7, app_params_type_list_2, db.ac_config_2)
        if param_check_3 is not None:
           # Erweiterte Params ok: weiter
            param_check_counter += 1

    if param_check_counter == 2:
        # Params aus Param-Tuples (Haupt und erweitert)
        # zu einer neuen Parameterliste zusammenbauen
        db.config_extended = (list(db.ac_config_1[:ac.app_config_params_range])
                            + list(db.ac_config_2[:7]))
        #print db.config_extended
        #print db.config_extended[2]
        mything = my_form()
        mything.master.title("Play-Out-Logging und Play-Out-Load-Web")
        mything.mainloop()

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()

