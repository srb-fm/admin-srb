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

Parameterliste:


"""


from Tkinter import Frame, Label, NW, END
from ScrolledText import ScrolledText
from mutagen.mp3 import MP3
from time import sleep
import sys
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
        self.app_develop = "no"
        self.app_windows = "no"
        self.app_errorfile = "error_play_out_sceduler.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Parameter-Typ "
            "oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Parameter-Typ "
            "oder Inhalt stimmt nicht: PO_Time_Config")
        self.app_errorslist.append(u"Parameter-Typ "
            "oder Inhalt stimmt nicht: PO_Rotation")
        self.app_errorslist.append(u"Musik-Datei "
            "in Rotations-Verzeichnis nicht lesbar")
        self.app_errorslist.append(u"Laenge der Musik-Datei "
            "nicht ermittelbar")
        # display debugmessages on console or no: "no"
        self.app_debug_mod = "yes"
        # number of params 0
        self.app_config_params_range = 10
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
        self.app_counter = 0
        self.app_counter_error = 0
        self.error_counter_read_log_file = 0
        self.play_out_items = None
        self.play_out_items_mag = None
        self.play_out_infotime = False
        self.play_out_current_continue = False
        self.song_time_elapsed = None
        self.app_msg_1 = None
        self.app_msg_2 = None
        self.music_play_list = []
        self.music_play_list_error = 0


def load_extended_params():
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
            return None

    # Rotation
    db.ac_config_rotation = db.params_load_1a(ac, db, "PO_Rotation")
    if db.ac_config_rotation is not None:
        # create extended Paramslist
        app_params_type_list_rotation = []
        # Types of extended-List
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")
        app_params_type_list_rotation.append("p_string")

        # check extended Params
        param_check_rotation_config = lib_cm.params_check_a(
                        ac, db, 4,
                        app_params_type_list_rotation,
                        db.ac_config_rotation)
        if param_check_rotation_config is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[2], "x",
            "write_also_to_console")
            return None
    return "ok"


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
            #+ "' AND A.USER_LOG_ACTION LIKE 'In Playlist aufgenommen:%' "
            + "' AND A.USER_LOG_ACTION LIKE '" + broadcast_type + "%' "
            + " ORDER BY A.USER_LOG_ID")

    log_data = db.read_tbl_rows_with_cond_log(ac,
                db, db_tbl, db_tbl_fields, db_tbl_condition)

    lib_cm.message_write_to_console(ac, log_data)
    return log_data


def check_mpd_stat(option):
    """read mpd and song-status"""
    current_status = mpd.exec_command("status", None)
    if option is not None:
        if option == "time_remain":
            if "time" in current_status:
                lib_cm.message_write_to_console(ac, current_status["time"])
                index = string.find(current_status["time"], ":")
                seconds_remain = (int(current_status["time"][index + 1:]) -
                                int(current_status["time"][0:index]))
                return seconds_remain
        if option == "status":
            if "state" in current_status:
                print "sss"
                lib_cm.message_write_to_console(ac, current_status["state"])
                index = string.find(current_status["state"], ":")
                mpd_state = current_status["state"][index + 1:]
                return mpd_state

    else:
        return current_status


def check_mpd_song(option):
    """read song-status"""
    current_song = mpd.exec_command("song", None)
    if option is not None:
        if option == "file":
            if "file" in current_song:
                lib_cm.message_write_to_console(ac, current_song["file"])
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
    print "play_out"
    if ac.song_time_elapsed > 4:
        mpd.exec_command("next", None)
        mpd_fade_in()
        ac.song_time_elapsed = None
        ac.app_msg_1 = "Playing next..."
        db.write_log_to_db_a(ac, "Play next", "t",
                                             "write_also_to_console")
    elif ac.song_time_elapsed < -10:
        # it seems like a stream
        mpd.exec_command("next", None)
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
            # load current song
            mpd.connect()
            current_song_file = check_mpd_song("file")
            # cropping playlist-items
            mpd.exec_command("crop", None)
            # add items to playlist
            for item in ac.play_out_items:
                if ac.play_out_infotime is True:
                    msg_2 = msg_2 + item[2][19:] + "\n"
                    mpd.exec_command("add", item[2][19:])
                else:
                    msg_2 = msg_2 + item[2][21:] + "\n"
                    # trying seamless play
                    if current_song_file == item[2][21:]:
                        # if streaming over one hour,
                        # the filename (url) is the same
                        # so there is no need to repeatly adding url
                        ac.play_out_current_continue = True
                        ac.app_msg_1 = "Playing current continue..."
                        db.write_log_to_db_a(ac, "Play current continue", "t",
                                             "write_also_to_console")
                        log_message = "Nahtloses Play-Out: " + item[2][21:]
                        db.write_log_to_db(ac, log_message, "i")
                    else:
                        mpd.exec_command("add", item[2][21:])
                        db.write_log_to_db_a(ac, "Gleich: "
                                + item[2][21:], "i", "write_also_to_console")

            if ac.play_out_infotime is True:
                db.write_log_to_db_a(ac, "Infotime vorbereitet ", "t",
                                             "write_also_to_console")
                ac.play_out_infotime = False

            # check time of current song
            ac.song_time_elapsed = check_mpd_stat("time_remain")
            log_message = ("Aktueller Titel "
                                + current_song_file + " noch "
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
                log_message = ("Sendung vorbereitet: " + item[2][21:])
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
            mpd.connect()
            current_song_file = check_mpd_song("file")
            # cropping playlist-items
            mpd.exec_command("crop", None)
            # add items to playlist
            for item in ac.play_out_items:
                msg_2 = msg_2 + item[2][21:] + "\n"
                if current_song_file == item[2][21:]:
                    ac.play_out_current_continue = True
                    ac.app_msg_1 = "Playing current continue..."
                    db.write_log_to_db_a(ac, "Play current continue", "t",
                                             "write_also_to_console")
                    log_message = "Nahtloses Play-Out: " + item[2][21:]
                    db.write_log_to_db(ac, log_message, "i")
                else:
                    mpd.exec_command("add", item[2][21:])
                    db.write_log_to_db_a(ac, "Gleich: "
                                + item[2][21:], "i", "write_also_to_console")

            # add music
            if minute_start > 5:
                # delete the first items from music-playlist
                del ac.music_play_list[:6]

            # check time of current song
            ac.song_time_elapsed = check_mpd_stat("time_remain")
            log_message = ("Aktueller Titel "
                                + current_song_file + " noch "
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
            mpd.connect()
            msg_1 = "Add Magazine-Item " + str(mg_number) + " to Playlist..."
            msg_2 = ""
            # cropping playlist-items
            mpd.exec_command("crop", None)
            msg_2 = msg_2 + ac.play_out_items_mag[0][2][20:] + "\n"
            # adding playlist-item
            mpd.exec_command("add", ac.play_out_items_mag[0][2][20:])
            # delete the first items from music-playlist
            if mg_number == 1:
                del ac.music_play_list[:4]
            if mg_number == 2:
                del ac.music_play_list[:3]
            if mg_number == 3:
                del ac.music_play_list[:3]
            push_music_playlist()
            mpd.disconnect()
            log_message = ("Play-Out Magazin: "
                                        + ac.play_out_items_mag[0][2][20:])
            db.write_log_to_db_a(ac, log_message, "i", "write_also_to_console")
        else:
            msg_1 = None

    ac.app_msg_1 = msg_1
    ac.app_msg_2 = msg_2


def push_music_playlist():
    for item in ac.music_play_list:
        mpd.exec_command("add", item)


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


def check_music_sources_alt(music_sources):
    """look if alternate playlist is for using now"""
    path_music_sources = None
    using_now = None
    for item in music_sources:
        if item[1] != "on":
            continue
        if len(item[2]) > 1:
            print "more options come here"
        else:
            if int(item[2]) != datetime.datetime.today().weekday():
                # if 8, it means every day will using this source
                if int(item[2]) != 8:
                    continue
            hour_begin = item[3][0:2]
            hour_end = item[3][3:5]
            #print hour_begin
            #print hour_end
            time_target = datetime.datetime.now() + datetime.timedelta(hours=1)
            dt_hour = time_target.hour
            #print dt_hour
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
        if len(item[2]) > 1:
            print "more options come here"
        else:
            if int(item[2]) != datetime.datetime.today().weekday():
                # if 8, it means every day will using this source
                if int(item[2]) != 8:
                    continue
            hour_begin = item[3][0:2]
            hour_end = item[3][3:5]
            #print hour_begin
            #print hour_end
            time_target = datetime.datetime.now() + datetime.timedelta(hours=1)
            dt_hour = time_target.hour
            #print dt_hour
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
    main_path_rotation = lib_cm.check_slashes(ac, db.ac_config_rotation[1])
    path_rotation_music = main_path_rotation + lib_cm.check_slashes(
                                            ac, path_extra_music)
    # Path from mpd to Rotation
    path_rotation_music_mpd = (lib_cm.check_slashes_a(ac,
                             path_extra_music, "no"))

    # Duration of playlist
    duration_minute_music = 0
    duration_minute_target = 6

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
            lib_cm.message_write_to_console(ac, ac.music_play_list)
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
        main_path_rotation = lib_cm.check_slashes(ac, db.ac_config_rotation[1])
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
        main_path_rotation = lib_cm.check_slashes(ac, db.ac_config_rotation[1])
        path_rotation_music = main_path_rotation + lib_cm.check_slashes(
                                            ac, path_playlist_alternate)
        # Path from mpd to Rotation
        path_rotation_music_mpd = (lib_cm.check_slashes_a(ac,
                             path_playlist_alternate, "no"))

    # Duration of playlist
    duration_minute_music = 0
    duration_minute_target = 90

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
        if file_rotation is None:
            db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
                                             "write_also_to_console")
            ac.music_play_list_error += 1
            continue
        else:
            ac.music_play_list.append(path_rotation_music_mpd + file_rotation)
            lib_cm.message_write_to_console(ac, ac.music_play_list)
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
    db.write_log_to_db(ac, "Musik-Playlist fuer Rotation vorbereitet", "i")
    ac.app_msg_1 = "Music-Playlist createt..."


def mpd_fade_in():
    """fade player-volume to 100%"""
    mpd.exec_command("vol", "15")
    sleep(0.100)
    mpd.exec_command("vol", "20")
    sleep(0.100)
    mpd.exec_command("vol", "35")
    sleep(0.100)
    mpd.exec_command("vol", "45")
    sleep(0.100)
    mpd.exec_command("vol", "55")
    sleep(0.100)
    mpd.exec_command("vol", "60")
    sleep(0.100)
    mpd.exec_command("vol", "75")
    sleep(0.100)
    mpd.exec_command("vol", "85")
    sleep(0.100)
    mpd.exec_command("vol", "100")
    db.write_log_to_db_a(ac, "Fade in", "t", "write_also_to_console")


def mpd_fade_out():
    """fade player-volume to 0%"""
    mpd.exec_command("vol", "-5")
    sleep(0.100)
    mpd.exec_command("vol", "-10")
    sleep(0.100)
    mpd.exec_command("vol", "-15")
    sleep(0.100)
    mpd.exec_command("vol", "-10")
    sleep(0.100)
    mpd.exec_command("vol", "-10")
    sleep(0.100)
    mpd.exec_command("vol", "-15")
    sleep(0.100)
    mpd.exec_command("vol", "-15")
    sleep(0.100)
    mpd.exec_command("vol", "-10")
    sleep(0.100)
    mpd.exec_command("vol", "-10")
    db.write_log_to_db_a(ac, "Fade out", "t", "write_also_to_console")


def mpd_setup():
    """basic config of mpd"""
    mpd.connect()
    mpd.exec_command("crossfade", db.ac_config_1[5])
    mpd.exec_command("consume", db.ac_config_1[6])
    mpd.exec_command("repeat", db.ac_config_1[7])
    mpd.exec_command("random", db.ac_config_1[8])
    mpd.exec_command("single", db.ac_config_1[9])
    mpd.exec_command("replay_gain_mode", db.ac_config_1[10])
    current_status = check_mpd_stat("status")
    if current_status != "play":
        mpd.exec_command("play", None)
        mpd_fade_in()
    mpd.disconnect()


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

        self.textBox = ScrolledText(self, height=5, width=80)
        self.textBox.pack()
        self.textBox.insert(END, "Waiting for first Action\n")

        self.text_label_2 = Label(self,
            height=1, width=80, text="Playlist")
        self.text_label_2.pack()

        self.textBox1 = ScrolledText(self, height=10, width=80)
        self.textBox1.pack()
        self.textBox1.insert(END, "...and the End\n")

        # registering callback
        self.listenID = self.after(500, self.lets_rock)

    def display_scheduling(self):
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
            if lines > 40:
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
        #mpd.connect()
        #current_status = check_mpd_stat("status")
        #mpd.disconnect()
        #print current_status
        #return
        if ac.app_counter == 2:
            mpd_setup()
            create_music_playlist()

        #lib_cm.message_write_to_console(ac, u"lets rock")
        time_now = datetime.datetime.now()
        ac.app_counter += 1
        minute_start = db.ac_config_times[3]

        if time_now.minute == 58:
            if time_now.second == 2:
                # update mpd-db
                mpd.connect()
                ac.app_msg_1 = "Update MPD-DB..."
                mpd.exec_command("update", None)
                mpd.disconnect()

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

        self.display_scheduling()


if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    mpd = lib_mp.myMPD()
    print  "lets_work: " + ac.app_desc
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac, db)
    #param_check_counter = 0

    if db.ac_config_1 is not None:
        # Haupt-Params pruefen
        param_check = lib_cm.params_check_1(ac, db)
        if param_check is not None:
            load_extended_params_ok = load_extended_params()
            if load_extended_params_ok is not None:
                mything = my_form()
                mything.master.title("Play-Out-Scheduler")
                mything.mainloop()

    # fertsch
    db.write_log_to_db(ac, ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
