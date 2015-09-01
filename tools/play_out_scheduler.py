#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out Scheduler

Autor: Joerg Sorge
Org: SRB - Das Buergerradio
Web: www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at ggooogl
2014-11-10

This script provides playlistitems for mpd.
The items are taken from the work of play_out_loader.py in the log-table.

Dieses Script bereitet für den MPD die auszuspielenden Audiodateien
oder zu streamende URLs vor.
Durch den play_out_loader.py wird jede auzuspielende Datei
(Sendebuchungen und weitere aus Pools zufallgenerierte) in der Log-Tabelle
registiert. Diese log-Eintraege werden durch den Scheduler abgefragt um
eine Warteschlange mit den Audiodateien zu erzeugen.
Zusaetzlich werden aus einem Musikpool Musikdateien
fuer die Warteschlange vorbereitet.

Zu den festgelegten Zeiten erhaelt mpd ein play-command
um die vorbereiteten Sendungen auszuspielen. Festegelegte Zeiten sind:
    Volle Stunde (top of the hour) (fest)
    z.B. 5 Minuten nach voller Stunde (variabel ueber Einst. PO_Time_Config)
    z.B. 30 Minuten nach voller Stunde (variabel ueber Einst. PO_Time_Config)

Soll z.B. zur vollen Stunde eine neue Sendung beginnen,
und der gerade abgespielte Titel noch laenger als 3 Sekunden laufen,
wird dieser ausgeblendet, ansonsten wird die neue Sendung danach eingeordnet
und ausgespielt.

Soll zur vollen Stunde eine Stream-URL gepsielt werden,
und laeuft gerade ein Stream mit der gleichen URL, so erfolgt kein reconnect
zum URL.

Beim Start des Scripts werden definierte Standard-Einstellungen an mpd
uebertragen und ein play-command abgesetzt um im Notfall schnell wiedergeben zu
können.

Fuer den Regelbetrieb (Start ueber qjackctrl) muss der Debugmod ausgeschaltet
sein: self.app_debug_mod = "no"

Die Musikrotation wird jeweils 10 Minuten vor der vollen Stunde vorbereitet.
Dazu werden Musikdateien mittels Zufallsgenaerator aus dem vordefinierten
Verzeichnis geladen. Diese Haupt-Playlist kann durch weitere Musik aus anderen
Pools ergaenzt werden. Es koennen Wochentag und Stundenabschnitte
definiert werden an und in denen diese Extra oder Alternativen Playlists
erzeugt werden sollen.


Der Ablauf ist an bestimmte Zeiten im Stundenverlauf gebunden:
Minute 58:
    Update MPD-DB
Minute 59:
    Vorbereitung und play out top of the hour
Minute x (5):
    Vorbereitung und play out erste variable Zeit, z.B. 5 nach Um
Minute x (30):
    Vorbereitung und play out zweite variable Zeit, z.B. 30 nach Um
Minute 14, 31, 44:
    Vorbereitung und play out Magazin-Beitraege
Minute 50:
    Musikrotation vorbereiten

Liste der moeglichen Haupt-Fehlermeldungen:
E 00 Parameter-Typ oder Inhalt stimmt nicht
E 01 Parameter-Typ oder Inhalt stimmt nicht: PO_Time_Config
E 02 Parameter-Typ oder Inhalt stimmt nicht: PO_Rotation
E 03 Musik-Datei in Rotations-Verzeichnis nicht lesbar
E 04 Laenge der Musik-Datei nicht ermittelbar
E 05 MPD-Setup fehlgeschlagen, kein connect zu MPD
E 06 Update MPD-DB fehlgeschlagen, kein connect zu MPD
E 07 Play-Out-Vorbereitung fuer volle Stunde fehlgeschlagen, kein connect zu MPD
E 08 Play-Out-Vorbereitung fuer Minute x fehlgeschlagen, kein connect zu MPD
E 09 MPD-Status kann nicht ermittelt werden
E 10 MPD-Song kann nicht ermittelt werden

Parameterliste:
P 01 mpd Host
P 02 mpd Port
P 03 mpd PW
P 04 none
P 05 Crossfade in Sekunden
P 06 Playlist Consume 1/0
P 07 repeat 1/0
P 08 random 0/1
P 09 single 1/0
P 10 replay_gain_mode: track/album
P 11 path to mpd.conf (without /home/user)

PO_Time_Config_1:
P 01 Beginn Tagesstunde Infotime und Magazin
P 02 Ende Tagesstunde Infotime und Magazin
P 03 Beginn normale Sendung 1 oder Beginn Info-Time
(kann in der Regel nur 00 sein!!!)
P 04 Beginn normale Sendung 2 (Ende Info-Time)
P 05 Beginne normale Sendung 3
P 06 Interval (Abstand) der Magazin-Beitraege in Minuten (zweistellig)
P 07 Beginn Infotime Serie B (stunde zweistellig)
P 08 Interval (Abstand) der Infotime-Beitraege in Sekunden (zweistellig)

PO_Rotation_Server:
P 01 Haupt-Pfad mpd-db
P 02 Pfad Musik
P 03 Pfad Instrumental
P 04 Alternative Musik-Quelle benutzen on/off
P 05 Extra (zusätzliche) Musik-Quelle benutzen on/off
P 06 Laenge Standard-PL in Minuten
P 07 Laenge Extra-Titel in Minuten

Extern Params:
extern_tools

Das Script laeuft mit graphischer Oberflaeche staendig.

Alles ist vorherbestimmt, Anfang wie Ende, durch Kräfte,
über die wir keine Gewalt haben. Es ist vorherbestimmt
für Insekt nicht anders wie für Stern. Die menschlichen Wesen,
Pflanzen oder der Staub, wir alle tanzen nach einer geheimnisvollen Melodie,
die ein unsichtbarer Spieler in den Fernen des Weltalls anstimmt.
Albert Einstein
"""


from Tkinter import Frame, Label, NW, END
from ScrolledText import ScrolledText
from mutagen.mp3 import MP3
from time import sleep
import sys
import socket
import datetime
import string
import random
import lib_common_1 as lib_cm
import lib_mpd as lib_mp


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Settings"""
        # app_config
        self.app_id = "022"
        self.app_desc = u"Play Out Scheduler"
        self.app_config = u"PO_Scheduler_Config"
        self.app_config_develop = u"PO_Scheduler_Config_e"
        # display debugmessages on console or no: "no"
        # for normal usage set to no!!!!!!
        self.app_debug_mod = "no"
        # using develop-params
        self.app_develop = "no"
        self.app_windows = "no"
        self.app_errorfile = "error_play_out_scheduler.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Parameter-Typ "
            "oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Parameter-Typ "
            "oder Inhalt stimmt nicht: PO_Time_Config")
        self.app_errorslist.append(u"Parameter-Typ "
            "oder Inhalt stimmt nicht: PO_Rotation")
        self.app_errorslist.append(u"Rotationsvorbereitung: Musik-Datei "
                                "in Rotations-Verzeichnis nicht lesbar")
        self.app_errorslist.append(u"Rotationsvorbereitung: "
                            "Laenge der Musik-Datei nicht ermittelbar")
        self.app_errorslist.append(u"MPD-Setup fehlgeschlagen, "
                                                "kein connect zu MPD")
        self.app_errorslist.append(u"Update MPD-DB fehlgeschlagen, "
                                                "kein connect zu MPD")
        self.app_errorslist.append(u"Play-Out-Vorbereitung "
                                    "fuer volle Stunde fehlgeschlagen, "
                                                "kein connect zu MPD")
        self.app_errorslist.append(u"Play-Out-Vorbereitung "
                                    "fuer Minute x fehlgeschlagen, "
                                                "kein connect zu MPD")
        self.app_errorslist.append(u"MPD-Status kann nicht ermittelt werden")
        self.app_errorslist.append(u"MPD-Song kann nicht ermittelt werden")
        # number of params 0
        self.app_config_params_range = 11
        # params-type-list
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_int")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_counter = 0
        self.app_counter_error = 0
        self.error_counter_read_log_file = 0
        self.play_out_items = None
        self.play_out_items_mag = None
        self.play_out_infotime = False
        self.play_out_stream = None
        self.play_out_current_continue = False
        self.song_time_elapsed = None
        self.app_msg_1 = None
        self.app_msg_2 = None
        self.music_play_list = []
        self.music_play_list_error = 0
        self.app_encode_out_strings = "cp1252"


def load_extended_params():
    """load extended params"""
    ext_params_ok = True
    # Times
    db.ac_config_times = db.params_load_1a(ac, db, "PO_Time_Config_1")
    if db.ac_config_times is not None:
        # create extended Paramslist
        app_params_type_list_times = []
        # Types of extended-List
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        app_params_type_list_times.append("p_string")
        # check extended Params
        param_check_time_config = lib_cm.params_check_a(
                        ac, db, 8,
                        app_params_type_list_times,
                        db.ac_config_times)
        if param_check_time_config is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[1], "x",
            "write_also_to_console")
            ext_params_ok = None
    else:
        ext_params_ok = None

    if ext_params_ok is None:
        return ext_params_ok

    # Rotation
    db.ac_config_rotation = db.params_load_1a(ac, db, "PO_Rotation_Server")
    if db.ac_config_rotation is not None:
        # create extended Paramslist
        app_params_type_list_rotation = []
        # Types of extended-List
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_int")
        app_params_type_list_rotation.append("p_int")

        # check extended Params
        param_check_rotation_config = lib_cm.params_check_a(
                        ac, db, 8,
                        app_params_type_list_rotation,
                        db.ac_config_rotation)
        if param_check_rotation_config is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
            ext_params_ok = None
    else:
        ext_params_ok = None

    # extern tools
    ext_params_ok = lib_cm.params_provide_tools(ac, db)
    return ext_params_ok


def load_play_out_items(minute_start, broadcast_type):
    """load items for transmitting from db"""
    if int(minute_start) == 0:
        # be save to not loading items from prev hour
        time_back = (datetime.datetime.now()
            + datetime.timedelta(seconds=- 3560))
    else:
        time_back = (datetime.datetime.now()
            + datetime.timedelta(seconds=- 3600))

    c_time_back = time_back.strftime("%Y-%m-%d %H:%M:%S")

    db_tbl = "USER_LOGS A "
    db_tbl_fields = ("A.USER_LOG_ID, A.USER_LOG_TIME, A.USER_LOG_ACTION, "
        "A.USER_LOG_ICON, A.USER_LOG_MODUL_ID ")
    db_tbl_condition = (
            "SUBSTRING( A.USER_LOG_TIME FROM 1 FOR 19) >= '"
            + c_time_back
            + "' AND A.USER_LOG_ACTION LIKE '" + broadcast_type + "%' "
            + " ORDER BY A.USER_LOG_ID")

    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    #lib_cm.message_write_to_console(ac, log_data)
    return log_data


def check_mpd_stat(option):
    """read mpd and song-status"""
    current_status = mpd.exec_command(db, ac, "status", None)
    if current_status is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[9], "x",
                                                    "write_also_to_console")
        ac.app_msg_1 = "mpd-error status"
        return
    if option is not None:
        if option == "time_remain":
            if "time" in current_status:
                lib_cm.message_write_to_console(ac, current_status["time"])
                index = string.find(current_status["time"], ":")
                seconds_remain = (int(current_status["time"][index + 1:]) -
                                int(current_status["time"][0:index]))
                return seconds_remain
        if option == "status":
            status_list = []
            #lib_cm.message_write_to_console(ac, current_status)
            if "state" in current_status:
                # content are play or stop etc.
                lib_cm.message_write_to_console(ac,
                        current_status["state"].encode('utf-8', 'ignore'))
                index = string.find(current_status["state"], ":")
                mpd_state = current_status["state"][index + 1:]
                status_list.append(mpd_state)
            else:
                status_list.append("None State")
            if "volume" in current_status:
                # content: number e.g. 100 for full volume
                lib_cm.message_write_to_console(ac,
                        current_status["volume"].encode('utf-8', 'ignore'))
                index = string.find(current_status["volume"], ":")
                mpd_volume = current_status["volume"][index + 1:]
                status_list.append(mpd_volume)
            else:
                status_list.append("None Volume")
            return status_list

    else:
        return current_status


def check_mpd_song(option):
    """read song-status"""
    current_song = mpd.exec_command(db, ac, "song", None)
    if current_song is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[10], "x",
                                                    "write_also_to_console")
        ac.app_msg_1 = "mpd-error song"
        return "getting song-info failed"
    if option is not None:
        if option == "file":
            if "file" in current_song:
                # can fail if filename can not eincode to ascii
                #lib_cm.message_write_to_console(ac,
                #            current_song["file"].encode('ascii', 'ignore'))
                return current_song["file"]
            else:
                err_message = ("Dateiname nicht ermittelbar."
                        + "Vielleicht wird zur Zeit kein Titel abgespielt...")
                db.write_log_to_db_a(ac, err_message, "x",
                                             "write_also_to_console")
                return "no-file.mp3"
    else:
        return current_song


def play_out():
    """play out"""
    if ac.song_time_elapsed > 4:
        mpd.exec_command(db, ac, "next", None)
        mpd_fade_in()
        ac.song_time_elapsed = None
        ac.app_msg_1 = "Playing next..."
        db.write_log_to_db_a(ac, "Play next", "t",
                                             "write_also_to_console")
    elif ac.song_time_elapsed < -10:
        # it seems like a stream
        mpd.exec_command(db, ac, "next", None)
        mpd_fade_in()
        ac.song_time_elapsed = None
        ac.app_msg_1 = "Playing next..."
        db.write_log_to_db_a(ac, "Play next", "t",
                                             "write_also_to_console")
    else:
        ac.app_msg_1 = "Playing continue..."
        db.write_log_to_db_a(ac, "Play continue", "t",
                                             "write_also_to_console")


def prepare_mpd_0(time_now, minute_start):
    """prepare mpd for top of the hour"""
    msg_1 = None
    msg_2 = None

    if time_now.second == 30:
        # for minute 0
        ac.play_out_items = load_play_out_items(
                                        minute_start, "Playlist Infotime:")

        if ac.play_out_items is None:
            ac.play_out_items = load_play_out_items(
                        minute_start, "Playlist Sendung " + minute_start)
        else:
            ac.play_out_infotime = True

        if ac.play_out_items is not None:
            msg_1 = "Load Items for top of the hour from DB..." + "\n"
            db.write_log_to_db_a(ac, "Load Items for top of the hour from DB",
                                "t", "write_also_to_console")
            for item in ac.play_out_items:
                if ac.play_out_infotime is True:
                    msg_1 = msg_1 + item[2][19:] + "\n"
                else:
                    msg_1 = msg_1 + item[2][18:] + "\n"
        else:
            msg_1 = "No Items for top of the hour from DB.nothing to do" + "\n"

    if time_now.second == 56:
        if ac.play_out_items is not None:
            msg_1 = "Add Items for top of the hour to Playlist..."
            msg_2 = ""
            db.write_log_to_db_a(ac,
                                "Add Items for top of the hour to Playlist",
                                "t", "write_also_to_console")
            # load current song
            mpd_result = mpd.connect(db, ac)
            if mpd_result is None:
                db.write_log_to_db_a(ac, ac.app_errorslist[7], "x",
                                                    "write_also_to_console")
                ac.app_msg_1 = "No mpd-connect, prepaere_mpd"
                return
            current_song_file = check_mpd_song("file")
            # cropping playlist-items
            mpd.exec_command(db, ac, "crop", None)
            # add items to playlist
            for item in ac.play_out_items:
                if ac.play_out_infotime is True:
                    msg_2 = msg_2 + item[2][19:] + "\n"
                    mpd.exec_command(db, ac, "add", item[2][19:])
                else:
                    msg_2 = msg_2 + item[2][21:] + "\n"
                    if item[2][21:25] == "http":
                        # reg stream-url for check
                        # check will fail, if stream is not the first item!
                        # but this is rarely the case
                        ac.play_out_stream = item[2][21:]
                        db.write_log_to_db_a(ac, "Playing Out Stream", "t",
                                             "write_also_to_console")
                    else:
                        ac.play_out_stream = None

                    # trying seamless play
                    if current_song_file.decode('utf_8') == item[2][21:]:
                        # if streaming over one hour,
                        # the filename (url) is the same
                        # so there is no need to repeatly adding url
                        ac.play_out_current_continue = True
                        ac.app_msg_1 = "Playing current continue..."
                        db.write_log_to_db_a(ac, "Play current continue", "t",
                                             "write_also_to_console")
                        log_message = "OnAir seamless: " + item[2][21:]
                        db.write_log_to_db(ac, log_message, "i")
                    else:
                        mpd.exec_command(db, ac, "add", item[2][21:])
                        db.write_log_to_db_a(ac, "OnAir next: "
                                + item[2][21:].encode('utf-8', 'ignore'),
                                "i", "write_also_to_console")

            if ac.play_out_infotime is True:
                db.write_log_to_db_a(ac, "Infotime vorbereitet ", "t",
                                             "write_also_to_console")
                ac.play_out_infotime = False

            # check time of current song
            ac.song_time_elapsed = check_mpd_stat("time_remain")
            log_message = ("Aktueller Titel "
                                #+ current_song_file.encode('utf-8', 'ignore')
                                + " noch "
                                + str(ac.song_time_elapsed) + " Sekunden")
            db.write_log_to_db_a(ac, log_message, "t",
                                             "write_also_to_console")
            # fade and crossfade
            if ac.play_out_current_continue is not True:
                mpd.exec_command(db, ac, "crossfade", "0")
                db.write_log_to_db_a(ac, "Set crossfade to 0",
                                "t", "write_also_to_console")
                if ac.song_time_elapsed > 4:
                    mpd_fade_out()
                if ac.song_time_elapsed < - 10:
                    mpd_fade_out()
        else:
            msg_1 = None

    # now play top of the hour
    if time_now.second == 59:
        if ac.play_out_items is not None:
            # if trying seamless-play, nothing else to do
            if ac.play_out_current_continue is not True:
                play_out()
            else:
                # reset
                ac.play_out_current_continue = False
        push_music_playlist()
        mpd.disconnect()
        # security-check
        mpd_play()

    ac.app_msg_1 = msg_1
    ac.app_msg_2 = msg_2


def prepare_mpd_5x(time_now, minute_start):
    """prepare mpd for another playtimes"""
    msg_1 = None
    msg_2 = None

    if time_now.second == 30:
        ac.play_out_items = load_play_out_items(
                        minute_start, "Playlist Sendung " + minute_start)

        if ac.play_out_items is not None:
            msg_1 = ("Load Items for Minute "
                                + minute_start + " from DB..." + "\n")
            for item in ac.play_out_items:
                msg_1 = msg_1 + item[2][21:] + "\n"
                log_message = ("Sendung vorbereitet: " +
                                item[2][21:].encode('utf-8', 'ignore'))
                db.write_log_to_db_a(ac,
                            log_message, "t", "write_also_to_console")
        else:
            msg_1 = ("No Items for Minute "
                    + minute_start + " from DB...nothing to do" + "\n")

    if time_now.second == 56:
        if ac.play_out_items is not None:
            msg_1 = "Add Items to Playlist..."
            msg_2 = ""
            # load current song
            mpd_result = mpd.connect(db, ac)
            if mpd_result is None:
                db.write_log_to_db_a(ac, ac.app_errorslist[8], "x",
                                                    "write_also_to_console")
                ac.app_msg_1 = "No mpd-connect, prepaere_mpd"
                return
            current_song_file = check_mpd_song("file")
            # cropping playlist-items
            mpd.exec_command(db, ac, "crop", None)
            # add items to playlist
            for item in ac.play_out_items:
                msg_2 = msg_2 + item[2][21:] + "\n"
                if item[2][21:25] == "http":
                    # reg stream-url for check
                    # check will fail, if stream is not the first item!
                    # but this is rarely the case
                    ac.play_out_stream = item[2][21:]
                    db.write_log_to_db_a(ac, "Playing Out Stream", "t",
                                             "write_also_to_console")
                else:
                    ac.play_out_stream = None
                if current_song_file.decode('utf_8') == item[2][21:]:
                    ac.play_out_current_continue = True
                    ac.app_msg_1 = "Playing current continue..."
                    db.write_log_to_db_a(ac, "Play current continue", "t",
                                             "write_also_to_console")
                    log_message = "OnAir semless: " + item[2][21:]
                    db.write_log_to_db(ac, log_message, "i")
                else:
                    mpd.exec_command(db, ac, "add", item[2][21:])
                    db.write_log_to_db_a(ac, "OnAir next: "
                                + item[2][21:].encode('utf-8', 'ignore'),
                                "i", "write_also_to_console")

            # add music
            if minute_start > 5:
                # delete the first items from music-playlist
                del ac.music_play_list[:6]

            # check time of current song
            ac.song_time_elapsed = check_mpd_stat("time_remain")
            log_message = ("Aktueller Titel "
                                #+ current_song_file.encode('utf-8', 'ignore')
                                + " noch "
                                + str(ac.song_time_elapsed) + " Sekunden")
            db.write_log_to_db_a(ac, log_message, "t",
                                             "write_also_to_console")
            # fade
            if ac.play_out_current_continue is not True:
                if ac.song_time_elapsed > 4:
                    mpd_fade_out()
                if ac.song_time_elapsed < - 10:
                    mpd_fade_out()
        else:
            msg_1 = None

    if time_now.second == 59:
        if ac.play_out_items is not None:
            play_out()
            push_music_playlist()
            mpd.disconnect()
        # security-check
        mpd_play()
    ac.app_msg_1 = msg_1
    ac.app_msg_2 = msg_2


def prepare_mpd_magazine(time_now, minute_start, mg_number):
    """prepare mpd for magazine"""
    msg_1 = None
    msg_2 = None

    if time_now.second == 30:
        # loading items from db
        ac.play_out_items_mag = load_play_out_items(
                    minute_start, "Playlist Magazin " + str(mg_number))

        if ac.play_out_items_mag is not None:
            msg_1 = ("Load Magazine-Item "
                            + str(mg_number) + " from DB..." + "\n")
            msg_1 = msg_1 + ac.play_out_items_mag[0][2][20:] + "\n"
            log_message = ("Magazin vorbereitet: " + str(mg_number) + " "
                                        + ac.play_out_items_mag[0][2][20:])
        else:
            msg_1 = ("No Magazine-Item " + str(mg_number)
                                + " from DB...nothing to do" + "\n")
            log_message = ("Play-Out Magazin: "
                                + str(mg_number) + " nicht vorgesehen")

        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    if time_now.second == 59:
        # schedule items for player
        if ac.play_out_items_mag is not None:
            mpd.connect(db, ac)
            msg_1 = "Add Magazine-Item " + str(mg_number) + " to Playlist..."
            msg_2 = ""
            # cropping playlist-items
            mpd.exec_command(db, ac, "crop", None)
            msg_2 = msg_2 + ac.play_out_items_mag[0][2][20:] + "\n"
            # adding playlist-item
            mpd.exec_command(db, ac, "add", ac.play_out_items_mag[0][2][20:])
            # delete the first items from music-playlist
            if mg_number == 1:
                del ac.music_play_list[:5]
            if mg_number == 2:
                del ac.music_play_list[:4]
            if mg_number == 3:
                del ac.music_play_list[:4]
            push_music_playlist()
            mpd.disconnect()
            log_message = ("OnAir Magazine next: "
                                        + ac.play_out_items_mag[0][2][20:])
            db.write_log_to_db_a(ac, log_message, "i", "write_also_to_console")
        else:
            msg_1 = None
        # security-check
        mpd_play()

    ac.app_msg_1 = msg_1
    ac.app_msg_2 = msg_2


def push_music_playlist():
    for item in ac.music_play_list:
        mpd.exec_command(db, ac, "add", item)


def load_music_sources_alternate():
    """load alternate playlists"""
    playlists_alterante = None
    if db.ac_config_rotation[4] == "on":
        # search for alternate Music-Source
        fields_rotation = ("USER_SP_PARAM_1, USER_SP_PARAM_2, "
                + "USER_SP_PARAM_3, USER_SP_PARAM_4, "
                + "USER_SP_PARAM_5, USER_SP_PARAM_6, "
                + "USER_SP_PARAM_7, USER_SP_PARAM_8, "
                + "USER_SP_PARAM_9, USER_SP_PARAM_10, "
                + "USER_SP_PARAM_11, USER_SP_PARAM_12")
        playlists_alterante = db.read_tbl_rows_with_cond(ac, db,
                "USER_SPECIALS", fields_rotation,
                "USER_SP_SPECIAL LIKE 'PO_Rotation_alternate_%'")
        if playlists_alterante is None:
            db.write_log_to_db_a(ac,
                        "Alternative Musik-Playlist nicht gefunden", "e",
                        "write_also_to_console")
    return playlists_alterante


def load_music_sources_extra():
    """load extra music source"""
    playlists_extra = None
    if db.ac_config_rotation[5] == "on":
        # search for extra Music-Source
        fields_rotation = ("USER_SP_PARAM_1, USER_SP_PARAM_2, "
                + "USER_SP_PARAM_3, USER_SP_PARAM_4, "
                + "USER_SP_PARAM_5, USER_SP_PARAM_6, "
                + "USER_SP_PARAM_7, USER_SP_PARAM_8, "
                + "USER_SP_PARAM_9, USER_SP_PARAM_10, "
                + "USER_SP_PARAM_11, USER_SP_PARAM_12")
        playlists_extra = db.read_tbl_rows_with_cond(ac, db,
                "USER_SPECIALS", fields_rotation,
                "USER_SP_SPECIAL LIKE 'PO_Rotation_extra_%'")
        if playlists_extra is None:
            db.write_log_to_db_a(ac,
                        "Extra Musik-Quelle nicht gefunden", "e",
                        "write_also_to_console")
    return playlists_extra


def check_day_options_of_music_sources(music_source_options):
    """check if music-souce is for using now"""
    if len(music_source_options[2]) > 1:
        # single number means single day
        # more means e.g. 23: day 2 and 3 of week
        found_current_day = None
        for day in music_source_options[2]:
            if int(day) == datetime.datetime.today().weekday():
                found_current_day = True

        if found_current_day is None:
            return None
    else:
        if int(music_source_options[2]) != datetime.datetime.today().weekday():
            # if 8, it means every day will using this source
            if int(music_source_options[2]) != 8:
                return None
    return True


def check_music_sources_alt(music_sources):
    """look if alternate playlist is for using now"""
    path_music_sources = None
    using_now = None
    for item in music_sources:
        if item[1] != "on":
            continue
        day_now = check_day_options_of_music_sources(item)
        if day_now is None:
            continue

        hour_begin = item[3][0:2]
        hour_end = item[3][3:5]
        time_target = datetime.datetime.now() + datetime.timedelta(hours=1)
        dt_hour = time_target.hour

        if dt_hour >= int(hour_begin) and dt_hour < int(hour_end):
            using_now = True

        if using_now is True:
            path_music_sources = item[4]
            db.write_log_to_db_a(ac, "Rotation alternativ: " + item[0], "i",
                                             "write_also_to_console")
    return path_music_sources


def work_on_extra_music_sources(music_sources):
    """look if extra playlist is for using now"""
    using_now = None
    for item in music_sources:
        if item[1] != "on":
            continue
        day_now = check_day_options_of_music_sources(item)
        if day_now is None:
            continue

        hour_begin = item[3][0:2]
        hour_end = item[3][3:5]
        time_target = datetime.datetime.now() + datetime.timedelta(hours=1)
        dt_hour = time_target.hour

        if dt_hour >= int(hour_begin) and dt_hour < int(hour_end):
            using_now = True

        if using_now is True:
            path_extra_music = item[4]
            load_extra_music(path_extra_music)
            db.write_log_to_db_a(ac, "Rotation extra: " + item[0], "i",
                                             "write_also_to_console")


def load_extra_music(path_extra_music):
    "add extra music to playlist"
    # Path from play_out_scheduler to Rotation
    main_path_rotation = (ac.app_homepath
                + lib_cm.check_slashes(ac, db.ac_config_rotation[1]))
    path_rotation_music = main_path_rotation + lib_cm.check_slashes(
                                            ac, path_extra_music)
    # Path from mpd to Rotation
    path_rotation_music_mpd = (lib_cm.check_slashes_a(ac,
                             path_extra_music, "no"))

    # Duration of playlist
    duration_minute_music = 0
    duration_minute_target = int(db.ac_config_rotation[7])  # 6

    # Collect Music
    while (duration_minute_music < duration_minute_target):
        if ac.music_play_list_error >= 5:
            db.write_log_to_db(ac,
                    "Musik-Playlist extra Erstellung abgebrochen", "x")
            ac.music_play_list_error = 0
            return
        file_rotation = lib_cm.read_random_file_from_dir(ac,
                                         db, path_rotation_music)
        if file_rotation is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
                                             "write_also_to_console")
            ac.music_play_list_error += 1
            continue
        else:
            ac.music_play_list.append(path_rotation_music_mpd + file_rotation)
            #lib_cm.message_write_to_console(ac, ac.music_play_list)
        try:
            audio_rotation_music = MP3(path_rotation_music + file_rotation)
            duration_minute_music += audio_rotation_music.info.length / 60
        except Exception, e:
            err_message = "Error by reading duration: %s" % str(e)
            lib_cm.message_write_to_console(ac, err_message)
            db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
                                             "write_also_to_console")
        #lib_cm.message_write_to_console(ac, "Duration Music")
        #lib_cm.message_write_to_console(ac,
        #                    str(audio_rotation_music.info.length))
    db.write_log_to_db(ac,
                "Musik-Playlist extra fuer Rotation vorbereitet", "k")
    ac.app_msg_1 = "Music-extra-Playlist createt..."


def create_music_playlist():
    "create music playlist"
    # Standard or alternate
    music_sources_alt = load_music_sources_alternate()
    if music_sources_alt is not None:
        path_playlist_alternate = check_music_sources_alt(music_sources_alt)
    else:
        path_playlist_alternate = None

    if path_playlist_alternate is None:
        # Standard
        # Path from play_out_scheduler to Rotation
        main_path_rotation = (ac.app_homepath
                        + lib_cm.check_slashes(ac, db.ac_config_rotation[1]))
        path_rotation_music = main_path_rotation + lib_cm.check_slashes(
                                            ac, db.ac_config_rotation[2])
        # Path from mpd to Rotation
        path_rotation_music_mpd = (lib_cm.check_slashes_a(ac,
                             db.ac_config_rotation[2], "no"))
        #db.write_log_to_db_a(ac, "Rotation Standard ", "i",
        #                                     "write_also_to_console")
    else:
        # Alternate
        # Path from play_out_scheduler to Rotation
        main_path_rotation = (ac.app_homepath
                    + lib_cm.check_slashes(ac, db.ac_config_rotation[1]))
        path_rotation_music = main_path_rotation + lib_cm.check_slashes(
                                            ac, path_playlist_alternate)
        # Path from mpd to Rotation
        path_rotation_music_mpd = (lib_cm.check_slashes_a(ac,
                             path_playlist_alternate, "no"))

    # Duration of playlist
    duration_minute_music = 0
    duration_minute_target = int(db.ac_config_rotation[6])  # 90

    # delete all items in playlist
    del ac.music_play_list[:]

    # Collect Music
    while (duration_minute_music < duration_minute_target):
        if ac.music_play_list_error >= 5:
            db.write_log_to_db(ac, "Musik-Playlist Erstellung abgebrochen", "x")
            ac.music_play_list_error = 0
            return
        file_rotation = lib_cm.read_random_file_from_dir(ac,
                                         db, path_rotation_music)
        #db.write_log_to_db(ac, str(duration_minute_music), "t")

        if (file_rotation[len(file_rotation) - 3:len(file_rotation)]
                                                        != "mp3".lower()):
            # no mp3 file
            continue

        if file_rotation is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
                                             "write_also_to_console")
            ac.music_play_list_error += 1
            continue
        else:
            ac.music_play_list.append(path_rotation_music_mpd + file_rotation)
            #lib_cm.message_write_to_console(ac, ac.music_play_list)
        try:
            audio_rotation_music = MP3(path_rotation_music + file_rotation)
            duration_minute_music += audio_rotation_music.info.length / 60
        except Exception, e:
            db.write_log_to_db_a(ac, ac.app_errorslist[4], "x",
                                             "write_also_to_console")
            db.write_log_to_db_a(ac, e, "x",
                                             "write_also_to_console")
        #lib_cm.message_write_to_console(ac, "Duration Music")
        #lib_cm.message_write_to_console(ac,
        #                    str(audio_rotation_music.info.length))
    db.write_log_to_db(ac, "Musik-Playlist Rotation vorbereitet", "t")
    if ac.app_msg_1 is not None:
        # append msg by first run
        ac.app_msg_1 = ac.app_msg_1 + "Music-Playlist createt..."
    else:
        ac.app_msg_1 = "Music-Playlist createt..."


def mpd_fade_in():
    """fade player-volume to 100%"""
    mpd.exec_command(db, ac, "vol", "15")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "20")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "35")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "45")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "55")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "60")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "75")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "85")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "100")
    db.write_log_to_db_a(ac, "Fade in", "t", "write_also_to_console")


def mpd_fade_out():
    """fade player-volume to 0%"""
    mpd.exec_command(db, ac, "vol", "-5")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "-5")
    sleep(0.150)
    mpd.exec_command(db, ac, "vol", "-10")
    sleep(0.150)
    mpd.exec_command(db, ac, "vol", "-15")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "-15")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "-15")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "-10")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "-10")
    sleep(0.100)
    mpd.exec_command(db, ac, "vol", "-15")
    db.write_log_to_db_a(ac, "Fade out", "t", "write_also_to_console")


def mpd_setup():
    """basic config of mpd"""
    mpd_result = mpd.connect(db, ac)
    if mpd_result is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[5], "x",
                                                    "write_also_to_console")
        return
    mpd.exec_command(db, ac, "crossfade", db.ac_config_1[5])
    mpd.exec_command(db, ac, "consume", db.ac_config_1[6])
    mpd.exec_command(db, ac, "repeat", db.ac_config_1[7])
    mpd.exec_command(db, ac, "random", db.ac_config_1[8])
    mpd.exec_command(db, ac, "single", db.ac_config_1[9])
    mpd.exec_command(db, ac, "replay_gain_mode", db.ac_config_1[10])
    current_status = check_mpd_stat("status")
    if current_status[0] != "play":
        mpd.exec_command(db, ac, "play", None)
    if current_status[1] != "100":
        mpd_fade_in()
        db.write_log_to_db(ac, "MPD - Fade In", "x")
    mpd.disconnect()
    ac.app_msg_1 = "MPD Setup..." + "\n"
    db.write_log_to_db(ac, "MPD Setup durchgefuehrt", "k")


def mpd_play():
    """mpd must continue playing"""
    mpd_result = mpd.connect(db, ac)
    if mpd_result is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[5], "x",
                                                    "write_also_to_console")
        return
    current_status = check_mpd_stat("status")
    if current_status[0] != "play":
        mpd.exec_command(db, ac, "play", None)
        db.write_log_to_db(ac, "MPD - Force Play while not playing", "x")
    if current_status[1] != "100":
        mpd_fade_in()
        db.write_log_to_db(ac, "MPD - Force Fade In, while volume not 100", "x")
    mpd.disconnect()

    ac.app_msg_1 = "Check if MPD is playing and volume at 100 %..." + "\n"
    db.write_log_to_db(ac, "Check if MPD is playing and volume at 100 %", "k")


def mpd_reset_crossfade():
    """resetting crossfade"""
    mpd_result = mpd.connect(db, ac)
    if mpd_result is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[5], "x",
                                                    "write_also_to_console")
        return
    mpd.exec_command(db, ac, "crossfade", db.ac_config_1[5])
    mpd.disconnect()
    ac.app_msg_1 = "Resetting Crossfade..." + "\n"
    db.write_log_to_db(ac, "Resetting Crossfade", "k")


class my_form(Frame):
    """Form"""
    def __init__(self, master=None):
        """Elements of Form"""
        Frame.__init__(self, master)
        self.pack()
        self.text_label = Label(self,
            height=1, width=80, anchor=NW, text="Play-Out-Schedule Nr: ")
        self.text_label.pack()

        self.text_label_1 = Label(self,
            height=1, width=80, text="Actions")
        self.text_label_1.pack()

        self.textBox = ScrolledText(self, height=10, width=80)
        self.textBox.pack()
        self.textBox.insert(END, "Waiting for first Action\n")

        self.text_label_2 = Label(self,
            height=1, width=80, text="Playlist")
        self.text_label_2.pack()

        self.textBox1 = ScrolledText(self, height=5, width=80)
        self.textBox1.pack()
        self.textBox1.insert(END, "...and the End\n")

        # registering callback
        self.listenID = self.after(500, self.lets_rock)

    def run_scheduling(self):
        """Display Schedule in Form"""
        self.text_label.config(text="Play-Out-Schedule Nr: "
                + str(ac.app_counter))
        if ac.app_msg_1 is not None:
            self.textBox.delete('1.0', '2.end')
            self.textBox.insert(END, ac.app_msg_1 + "\n")
            self.textBox.see(END)
            lines = int(self.textBox.index('end-1c').split('.')[0])
            #lines = self.textBox.index('end-1c')
            #self.textBox.insert(END, str(lines) + "\n")
            if lines > 50:
                self.textBox.delete('1.0', '20.end')
            ac.app_msg_1 = None

        if ac.app_msg_2 is not None:
            self.textBox1.delete('1.0', '8.end')
            self.textBox1.insert(END, ac.app_msg_2 + "\n")
            ac.app_msg_2 = None

        self.listenID = self.after(1000, self.lets_rock)
        return

    def lets_rock(self):
        """mainfunction"""
        # prepare path
        ac.app_homepath = "/home/" + socket.gethostname() + "/"

        # playlist at startup
        if ac.app_counter == 2:
            ac.app_msg_1 = "Creating music playlist"
            mpd_setup()
            create_music_playlist()

        time_now = datetime.datetime.now()
        ac.app_counter += 1
        minute_start = db.ac_config_times[3]

        if time_now.minute == 58:
            if time_now.second == 2:
                # update mpd-db
                mpd_result = mpd.connect(db, ac)
                if mpd_result is not None:
                    ac.app_msg_1 = "Update MPD-DB..."
                    mpd.exec_command(db, ac, "update", None)
                    mpd.disconnect()
                else:
                    db.write_log_to_db_a(ac, ac.app_errorslist[6], "x",
                                                    "write_also_to_console")

        # prepare and play_out top of the hour
        if time_now.minute == 59:
            prepare_mpd_0(time_now, minute_start)

        # cleaning up top of the hour
        if time_now.minute == 1:
            if time_now.second == 2:
                # free for next run
                ac.play_out_items = None

        # prepare play_out 5x
        #if time_now.minute == 4:
        if time_now.minute == int(db.ac_config_times[4]) - 1:
            minute_start = db.ac_config_times[4]
            prepare_mpd_5x(time_now, minute_start)

        # cleaning up 5x 1.
        if time_now.minute == int(db.ac_config_times[4]) + 1:
            if time_now.second == 2:
                mpd_reset_crossfade()
                if ac.play_out_items is not None:
                    # free for next run
                    ac.play_out_items = None

        # prepare play_out 5x 2.
        # if time_now.minute == 28:
        if time_now.minute == int(db.ac_config_times[5]) - 1:
            minute_start = db.ac_config_times[5]
            prepare_mpd_5x(time_now, minute_start)

        # cleaning up 5x 2.
        if time_now.minute == int(db.ac_config_times[5]) + 1:
            if time_now.second == 2:
                if ac.play_out_items is not None:
                    # free for next run
                    ac.play_out_items = None

        # schedule magazines
        if time_now.minute == 14:
            prepare_mpd_magazine(time_now, minute_start, 1)
        if time_now.minute == 31:
            prepare_mpd_magazine(time_now, minute_start, 2)
        if time_now.minute == 44:
            prepare_mpd_magazine(time_now, minute_start, 3)

        # load music
        if time_now.minute == 50:
            if time_now.second == 30:
                create_music_playlist()
                music_sources_extra = load_music_sources_extra()
                if music_sources_extra is not None:
                    work_on_extra_music_sources(music_sources_extra)
                    # shuffling music-playlist
                    random.shuffle(ac.music_play_list)
                db.write_log_to_db_a(ac,
                            "Rotation vorbereitet", "i",
                                             "write_also_to_console")

        # check if mpd is playing
        if time_now.minute == 10:
            if time_now.second == 30:
                mpd_play()
        if time_now.minute == 35:
            if time_now.second == 30:
                mpd_play()
        if time_now.minute == 54:
            if time_now.second == 30:
                mpd_play()

        # reload mpd
        # this is for freeing mpd.log to rotate them
        if time_now.hour == 4:
            if time_now.minute == 40:
                if time_now.second == 10:
                    mpd.exec_command(db, ac, "reload-1", "mpd")
                if time_now.second == 15:
                    path_file_mpd_log = ac.app_homepath + db.ac_config_1[11]
                    mpd.exec_command(db, ac, "reload-2", path_file_mpd_log)
                    db.write_log_to_db(ac, "MPD - neu gestartet", "k")
                if time_now.second == 25:
                    mpd_setup()
                    create_music_playlist()

        self.run_scheduling()


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    mpd = lib_mp.myMPD()

    print  "lets_work: " + ac.app_desc
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)

    if db.ac_config_1 is not None:
        # check main-params
        param_check = lib_cm.params_check_1(ac, db)
        if param_check is not None:
            load_extended_params_ok = load_extended_params()
            if load_extended_params_ok is not None:
                #mpd.exec_command(db, ac, "access", None)
                #mpd._host = db.ac_config_1[1]
                #mpd._port = db.ac_config_1[2]
                #mpd._password = db.ac_config_1[3]
                mything = my_form()
                mything.master.title("Play-Out-Scheduler")
                mything.mainloop()

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
