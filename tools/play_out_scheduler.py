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



"""


from Tkinter import Frame, Label, NW, END
from ScrolledText import ScrolledText
import sys
import string
import re
import datetime
import lib_common_1 as lib_cm
import lib_mpd as lib_mp


class app_config(object):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
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
        self.app_errorslist.append(u"Sende-Quelle "
            "kann fuer PlayOut-Logging nicht aus Datenbank ermittelt werden ")
        self.app_errorslist.append(u""
            "Play-Out-Log-Datei kann nicht gelesen werden")
        self.app_errorslist.append(u"Webserver fuer PlayOut-Logging"
            " lieferte bei Uebertragung Fehler zurueck")
        self.app_errorslist.append(u"Webserver "
            "fuer PlayOut-Logging nicht erreichbar")
        self.app_errorslist.append(u"Externes PlayOut-Logging ausgesetzt, "
            "Webserver nicht erreichbar")
        # meldungen auf konsole ausgeben oder nicht: "no"
        self.app_debug_mod = "yes"
        # anzahl parameter list 0
        self.app_config_params_range = 9
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_counter = 0
        self.app_counter_error = 0
        self.error_counter_read_log_file = 0
        self.play_out_items = None
        self.play_out_infotime = False
        self.app_msg_1 = None
        self.app_msg_2 = None


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
            db.write_log_to_db_a(ac, ac.app_errorslist[3], "x",
            "write_also_to_console")
            return None
    return "ok"


def load_play_out_items(minute_start, broadcast_type):
    if minute_start == 0:
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


def prepare_mpd_0(time_now, minute_start):
    """prepare mpd for top of the hour"""
    msg_1 = None
    msg_2 = None
    if time_now.second == 1:
        # connect for loop at minute 0
        mpd.connect()
        # update mpd-db
        msg_1 = "Update MPD-DB..."
        mpd.exec_command("update", None)

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

    if time_now.second == 59:
        if ac.play_out_items is not None:
            msg_1 = "Add Items for top of the hour to Playlist..."
            msg_2 = ""
            # cropping playlist-items
            mpd.exec_command("crop", None)
            for item in ac.play_out_items:
                if ac.play_out_infotime is True:
                    msg_2 = msg_2 + item[2][19:] + "\n"
                    mpd.exec_command("add", item[2][19:])
                else:
                    msg_2 = msg_2 + item[2][21:] + "\n"
                    mpd.exec_command("add", item[2][21:])

            if ac.play_out_infotime is True:
                ac.play_out_infotime = False
        else:
            msg_1 = None

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

    if time_now.second == 59:
        if ac.play_out_items is not None:
            msg_1 = "Add Items to Playlist..."
            msg_2 = ""
            mpd.connect()
            # cropping playlist-items
            mpd.exec_command("crop", None)
            for item in ac.play_out_items:
                msg_2 = msg_2 + item[2][21:] + "\n"
                mpd.exec_command("add", item[2][21:])
            mpd.disconnect()
        else:
            msg_1 = None

    ac.app_msg_1 = msg_1
    ac.app_msg_2 = msg_2


def prepare_mpd_magazine(time_now, minute_start, mg_number):
    """prepare mpd for magazine"""
    msg_1 = None
    msg_2 = None

    if time_now.second == 30:
        # loading items from db
        ac.play_out_items = load_play_out_items(
                    minute_start, "Playlist Magazin " + str(mg_number))

        if ac.play_out_items is not None:
            msg_1 = ("Load Magazine-Item "
                            + str(mg_number) + " from DB..." + "\n")
            msg_1 = msg_1 + ac.play_out_items[0][2][20:] + "\n"
            log_message = ("Magazin vorbereitet: "
                                        + ac.play_out_items[0][2][20:])
        else:
            msg_1 = "No Magazine-Item from DB...nothing to do" + "\n"
            log_message = ("Play-Out Magazin: nicht vorgesehen")

        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")

    if time_now.second == 59:
        # schedule items for player
        if ac.play_out_items is not None:
            mpd.connect()
            msg_1 = "Add Magazine-Item " + str(mg_number) + " to Playlist..."
            msg_2 = ""
            # cropping playlist-items
            mpd.exec_command("crop", None)
            msg_2 = msg_2 + ac.play_out_items[0][2][20:] + "\n"
            # adding playlist-item
            mpd.exec_command("add", ac.play_out_items[0][2][20:])
            mpd.disconnect()
            log_message = ("Play-Out Magazin: " + ac.play_out_items[0][2][20:])
            db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        else:
            msg_1 = None

    ac.app_msg_1 = msg_1
    ac.app_msg_2 = msg_2


class my_form(Frame):
    """Form"""
    def __init__(self, master=None):
        """Elemente der Form kreieren"""
        Frame.__init__(self, master)
        self.pack()
        #self.createWidgets()
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
        """Schedule in Form zur Anzeige bringen """
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
        #lib_cm.message_write_to_console(ac, u"lets rock")
        time_now = datetime.datetime.now()
        ac.app_counter += 1
        minute_start = db.ac_config_times[3]

        # prepare play_out top of the hour
        if time_now.minute == 58:
            prepare_mpd_0(time_now, minute_start)

        # now play top of the hour
        if time_now.minute == 59:
            if time_now.second == 58:
                if ac.play_out_items is not None:
                    ac.app_msg_1 = "Playing..."
                    mpd.exec_command("next", None)
                #mpd.disconnect()

            if time_now.second == 59:
                # disconnect at the end of loop for top of the hour
                mpd.disconnect()
                # free for next run
                ac.play_out_items = None

        # prepare play_out 5x
        #if time_now.minute == 4:
        if time_now.minute == int(db.ac_config_times[4]) - 2:
            minute_start = db.ac_config_times[4]
            prepare_mpd_5x(time_now, minute_start)

        # now play 5x
        if time_now.minute == int(db.ac_config_times[4]):
            if time_now.second == 0:
                if ac.play_out_items is not None:
                    ac.app_msg_1 = "Playing..."
                    mpd.exec_command("next", None)
            if time_now.second == 59:
                mpd.disconnect()
                # free for next run
                ac.play_out_items = None

        # prepare play_out 30x
        #if time_now.minute == 28:
        if time_now.minute == int(db.ac_config_times[5]) - 2:
            minute_start = db.ac_config_times[5]
            prepare_mpd_5x(time_now, minute_start)

        # schedule magazines
        if time_now.minute == 14:
            prepare_mpd_magazine(time_now, minute_start, 1)
        if time_now.minute == 29:
            prepare_mpd_magazine(time_now, minute_start, 2)
        if time_now.minute == 44:
            prepare_mpd_magazine(time_now, minute_start, 3)

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
