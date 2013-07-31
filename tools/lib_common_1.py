#!/usr/bin/env python
# -*- coding: utf-8 -*-
# bibo fuer srb-tools

import sys
import kinterbasdb
import datetime
import string
import re
import os
import htmlentitydefs
import db_config


class dbase(object):
    """Datenbankzugriff"""

    def __init__(self):
        """db Zugang"""
        self.db_name = db_config.db_name
        self.db_user = db_config.db_user
        self.db_pw = db_config.db_pw
        self.db_con = None
        """db Zugang log"""
        self.db_log_name = db_config.db_log_name
        self.db_log_user = db_config.db_log_user
        self.db_log_pw = db_config.db_log_pw
        self.db_log_con = None

    def dbase_connect(self, ac):
        """db connect"""
        message_write_to_console(ac, "dbase_connect")
        try:
            self.db_con = kinterbasdb.connect(
                dsn=self.db_name, user=self.db_user, password=self.db_pw,
                charset='UTF8')
        except Exception, e:
            err_message = "db_connect Error: %s" % str(e)
            error_write_to_file(ac, err_message)
            message_write_to_console(ac, err_message)
        return

    def dbase_log_connect(self, ac):
        """db-log connect"""
        message_write_to_console(ac, "dbase_log_connect")
        try:
            self.db_log_con = kinterbasdb.connect(
                dsn=self.db_log_name, user=self.db_log_user,
                password=self.db_log_pw, charset='UTF8')
        except Exception, e:
            err_message = "db_log_connect Error: %s" % str(e)
            error_write_to_file(ac, err_message)
            message_write_to_console(ac, err_message)
        return

    def load_gen_id(self, ac, db):
        """Neue ID aus db holen"""
        message_write_to_console(ac, "load_generator_id: " + ac.app_desc)
        sql_string = "SELECT GEN_ID(GENERATOR_MAIN_ID, 1) FROM RDB$DATABASE"

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "load_generator_id: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            gen_id = db_cur.fetchone()

            # wenn kein satz vorhanden
            if gen_id is None:
                log_message = "load_generator_id fehlgeschlagen.."
                db.write_log_to_db(ac, log_message, "x")
                return None

        except Exception, e:
            self.db_con.close()
            log_message = "load_generator_id Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None
        else:
            message_write_to_console(ac, str(gen_id))
            self.db_con.close()
            return gen_id[0]

    def count_rows(self, ac, db, table, condition):
        """Datensaetze zaehlen"""
        message_write_to_console(ac, "count_rows: " + ac.app_desc)
        sql_string = "SELECT COUNT (*) FROM " + table + " WHERE " + condition

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "count_rows Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            row_counter = db_cur.fetchone()

            # wenn kein satz vorhanden
            if row_counter is None:
                log_message = "count_rows fehlgeschlagen.."
                db.write_log_to_db(ac, log_message, "x", ac.app_id)
                return None

        except Exception, e:
            self.db_con.close()
            log_message = "count_rows Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x", ac.app_id)
            return None
        else:
            message_write_to_console(ac, str(row_counter))
            self.db_con.close()
            return row_counter[0]

    def exec_sql(self, ac, db, sql_command):
        """sql-statement ausfuehren"""
        message_write_to_console(ac, "exec_sql: " + ac.app_desc)

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "DB-Connect fehlgeschlagen"
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            db_cur.execute(sql_command)
            self.db_con.commit()
            self.db_con.close()
        except Exception, e:
            log_message = "exec_sql Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            #self.db_con.rollback()
            #self.db_con.close()
            return None
        return "ok"

    def params_load_1(self, ac, db):
        """Config-Parameter aus Voreinstellungen holen und in Liste schreiben"""
        # Pramssuche abhaenging davon ob entwickler oder normal-version
        if ac.app_develop == "yes":
            ac.app_config_params_desc = ac.app_config_develop
        else:
            ac.app_config_params_desc = ac.app_config

        message_write_to_console(
            ac, "params_load: " + ac.app_config_params_desc)

        self.dbase_connect(ac)
        if self.db_con is None:
            return

        try:
            db_cur = self.db_con.cursor()
            SELECT = ("SELECT USER_SP_SPECIAL, "
                "USER_SP_PARAM_1, USER_SP_PARAM_2, "
                "USER_SP_PARAM_3, USER_SP_PARAM_4, "
                "USER_SP_PARAM_5, USER_SP_PARAM_6, "
                "USER_SP_PARAM_7, USER_SP_PARAM_8, "
                "USER_SP_PARAM_9, USER_SP_PARAM_10, "
                "USER_SP_PARAM_11, USER_SP_PARAM_12 from USER_SPECIALS "
                "where USER_SP_SPECIAL='" + ac.app_config_params_desc + "'")
            db_cur.execute(SELECT)
            params_list = db_cur.fetchone()

            # wenn kein satz vorhanden
            if params_list is None:
                log_message = ("Parameter nicht in config gefunden: "
                                 + ac.app_config_params_desc)
                db.write_log_to_db(ac, log_message, "x")
                message_write_to_console(ac, log_message)

        except Exception, e:
            self.db_con.close()
            log_message = "read_params_from_config: Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")

        else:
            message_write_to_console(ac, params_list)
            return params_list
            self.db_con.close()

    def params_load_1a(self, ac, db, config_params_desc):
        """Zusaetzliche Config-Parameter aus Voreinstellungen holen
        und in Liste schreiben"""
        # Pramssuche abhaenging davon ob entwickler oder normal-version
        if ac.app_develop == "yes":
            ac.app_config_params_desc = ac.app_config_develop
        else:
            ac.app_config_params_desc = ac.app_config

        message_write_to_console(ac, "params_load: " + config_params_desc)

        self.dbase_connect(ac)
        if self.db_con is None:
            return

        try:
            db_cur = self.db_con.cursor()
            SELECT = ("SELECT USER_SP_SPECIAL, "
                "USER_SP_PARAM_1, USER_SP_PARAM_2, "
                "USER_SP_PARAM_3, USER_SP_PARAM_4, "
                "USER_SP_PARAM_5, USER_SP_PARAM_6, "
                "USER_SP_PARAM_7, USER_SP_PARAM_8, "
                "USER_SP_PARAM_9, USER_SP_PARAM_10, "
                "USER_SP_PARAM_11, USER_SP_PARAM_12 from USER_SPECIALS "
                "where USER_SP_SPECIAL='" + config_params_desc + "'")
            db_cur.execute(SELECT)
            params_list = db_cur.fetchone()

            # wenn kein satz vorhanden
            if params_list is None:
                log_message = ("Parameter nicht in config gefunden: "
                                + config_params_desc)
                db.write_log_to_db(ac, log_message, "x")
                message_write_to_console(ac, log_message)

        except Exception, e:
            self.db_con.close()
            log_message = "read_params_from_config: Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")

        else:
            message_write_to_console(ac, params_list)
            return params_list
            self.db_con.close()

    def write_log_to_db(self, ac, log_message, log_icon):
        """Logmeldungen in DB-log schreiben"""
        message_write_to_console(ac, "write_log_to_db_log")
        # Hochkomma ersetzen sonst knallts im db.execute.string
        message = re.sub("'", "-", log_message)

        ACTION = "INSERT INTO USER_LOGS ( USER_LOG_ACTION, USER_LOG_ICON, USER_LOG_MODUL_ID ) "
        ACTION += "VALUES ( '" + message + "', '" + log_icon + "', '" + ac.app_id + "')"

        self.dbase_log_connect(ac)
        if self.db_log_con is None:
            err_message = log_message + "Error 1 write_log_to_db_log: %s" % str(e)
            error_write_to_file(ac, err_message)
            return

        try:
            db_cur = self.db_log_con.cursor()
            db_cur.execute(ACTION)
            self.db_log_con.commit()
            self.db_log_con.close()
        except Exception, e:
            message_write_to_console(
                ac, log_message + "write_log_to_db Error: %s</p>" % str(e) )
            err_message = log_message + "Error 2 write_log_to_db: %s" % str(e)
            error_write_to_file(ac, err_message)

    def write_log_to_db_a(self, ac, log_message, log_icon, log_console):
        """Logmeldungen in DB-log schreiben"""
        message_write_to_console(ac, "write_log_to_db_log")
        # gleiche log_message in console schreiben
        if log_console is not None:
            message_write_to_console(ac, log_message)

        # Hochkomma ersetzen sonst knallts im db.execute.string
        message = re.sub("'", "-", log_message)

        ACTION = ("INSERT INTO USER_LOGS ( USER_LOG_ACTION, USER_LOG_ICON, USER_LOG_MODUL_ID ) "
            "VALUES ( '" + message + "', '" + log_icon + "', '" + ac.app_id + "')")

        self.dbase_log_connect(ac)
        if self.db_log_con is None:
            err_message = log_message + "Error 1 write_log_to_db: %s" % str(e)
            error_write_to_file(ac, err_message)
            return

        try:
            db_cur = self.db_log_con.cursor()
            db_cur.execute(ACTION)
            self.db_log_con.commit()
            self.db_log_con.close()
        except Exception, e:
            message_write_to_console(
                ac, log_message + "write_log_to_db_log Error: %s</p>" % str(e))
            err_message = log_message + "Error 2 write_log_to_db_log: %s" % str(e)
            error_write_to_file(ac, err_message)

    def write_twitter_log_to_db_1(self, ac, log_id, log_console):
        """Twitter-Log-IDs DB-log schreiben"""
        message_write_to_console(ac, "write_twitter_log_to_db_log")
        # gleiche log_message in console schreiben
        if log_console is not None:
            message_write_to_console(ac, log_id)

        ACTION = "INSERT INTO TWITTER_LOGS(TW_USER_LOG_ID) VALUES ('" + str(log_id) + "')"

        self.dbase_log_connect(ac)
        if self.db_log_con is None:
            err_message = log_message + "Error 1 write_twitter_log_to_db: %s" % str(e)
            error_write_to_file(ac, err_message)
            return

        try:
            db_cur = self.db_log_con.cursor()
            db_cur.execute(ACTION)
            self.db_log_con.commit()
            self.db_log_con.close()
        except Exception, e:
            message_write_to_console(ac, log_message + "write_twitter_log_to_db_log Error: %s</p>" % str(e) )
            err_message = log_message + "Error 2 write_twitter_log_to_db_log: %s" % str(e)
            error_write_to_file(ac, err_message)

    def read_tbl_rows_with_cond_log(self, ac, db, table, fields, condition):
        """ Zeilen nach uebergebener Bedingung aus Tabelle in db-log lesen """
        message_write_to_console(ac, u"read_tbl_rows_with_condition_log: ")
        sql_string =  "SELECT " + fields +  " FROM " + table + " WHERE "+ condition

        self.dbase_log_connect(ac)
        if self.db_log_con is None:
            rows = None
            return rows

        try:
            db_cur = self.db_log_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                rows = None
                log_message = u"read_tbl_rows_with_cond_log: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                # logmeldung zu lang und zu häufig:
                #db.write_log_to_db( ac, log_message, "x", "003" )

        except Exception, e:
            self.db_log_con.close()
            log_message = u"read_tbl_rows_with_cond_log Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
        else:
            message_write_to_console(ac, rows)
            return rows
            self.db_log_con.close()

    def read_tbl_row_with_cond_log(self, ac, db, table, fields, condition):
        # zeile aus db-log lesen
        row = None
        message_write_to_console(ac, "read_tbl_row_with_condition_log: ")
        sql_string =  "SELECT " + fields +  " FROM " + table + " WHERE "+ condition

        self.dbase_log_connect(ac)
        if self.db_log_con is None:
            return row

        try:
            db_cur = self.db_log_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            result = db_cur.fetchone()

            # wenn kein satz vorhanden
            if result is None:
                row = None
                log_message = "read_tbl_row_with_cond: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                # logmeldung zu lang und zu häufig:

        except Exception, e:
            self.db_log_con.close()
            log_message = "read_tbl_row_with_cond_ad Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
        else:
            row = result
            message_write_to_console(ac, row)
            self.db_log_con.close()
            return row

    def read_tbl_row_with_cond(self, ac, db, table, fields, condition):
        # zeile aus tabelle sendung lesen
        row = None
        message_write_to_console(ac, "read_tbl_row_with_condition: ")
        sql_string =  "SELECT " + fields +  " FROM " + table + " WHERE "+ condition

        self.dbase_connect(ac)
        if self.db_con is None:
            return row

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            result = db_cur.fetchone()

            # wenn kein satz vorhanden
            if result is None:
                row = None
                log_message = "read_tbl_row_with_cond: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                # logmeldung zu lang und zu häufig:

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_row_with_cond_ad Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
        else:
            row = result
            message_write_to_console(ac, row)
            self.db_con.close()
            return row

    def read_tbl_rows_with_cond(self, ac, db, table, fields, condition):
        """ Zeilen nach uebergebener Bedingung aus Tabelle lesen """
        message_write_to_console(ac, u"read_tbl_rows_with_condition: ")
        sql_string =  "SELECT " + fields +  " FROM " + table + " WHERE "+ condition

        self.dbase_connect(ac)
        if self.db_con is None:
            rows = None
            return rows

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                rows = None
                log_message = u"read_tbl_rows_with_cond: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                # logmeldung zu lang und zu häufig:
                #db.write_log_to_db( ac, log_message, "x", "003" )

        except Exception, e:
            self.db_con.close()
            log_message = u"read_tbl_rows_with_cond_ad Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
        else:
            message_write_to_console(ac, rows)
            return rows
            self.db_con.close()

    def read_tbl_rows_sg_cont_ad_with_cond(self, ac, db, condition):
        # zeile aus tabelle sendung lesen
        message_write_to_console(ac, "read_tbl_rows_sg_cont_ad_condition: ")

        sql_string =  "SELECT A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, A.SG_HF_DURATION, A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
        sql_string += "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, B.SG_HF_CONT_TITEL, B.SG_HF_CONT_FILENAME, "
        sql_string += "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
        sql_string += "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
        sql_string += "ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
        sql_string += "LEFT JOIN AD_MAIN C "
        sql_string += "ON B.SG_HF_CONT_AD_ID = C.AD_ID "
        sql_string += "WHERE " + condition + "ORDER BY A.SG_HF_TIME"

        self.dbase_connect(ac)
        if self.db_con is None:
            rows = "nix"
            return rows

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                rows = "nix"
                log_message = "read_tbl_rows_sg_cont_ad: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                # logmeldung zu lang und zu häufig:
                #db.write_log_to_db( ac, log_message, "x" )

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_rows_sg_cont_ad Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
        else:
            message_write_to_console(ac, rows)
            return rows
            self.db_con.close()

    def read_tbl_row_sg_cont_ad_with_cond(self, ac, db, condition):
        """ Zeile aus Tabelle Sendung entspr. der Bedingung lesen """
        message_write_to_console(ac, "read_tbl_row_sg_cont_ad_condition: ")

        sql_string =  ("SELECT A.SG_HF_ID, A.SG_HF_CONT_ID, A.SG_HF_TIME, A.SG_HF_MAGAZINE, A.SG_HF_ON_AIR, A.SG_HF_SOURCE_ID, "
            "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, B.SG_HF_CONT_TITEL, B.SG_HF_CONT_FILENAME, "
            "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
            "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
            "ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
            "LEFT JOIN AD_MAIN C "
            "ON B.SG_HF_CONT_AD_ID = C.AD_ID "
            "WHERE " + condition)

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "DB-Connect fehlgeschlagen"
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            row = db_cur.fetchone()

            # wenn kein satz vorhanden
            if row is None:
                log_message = "read_tbl_row_sg_cont_ad: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                self.db_con.close()
                return None

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_row_sg_cont_ad_with_cond_1 Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None
        else:
            message_write_to_console(ac, row)
            self.db_con.close()
            return row

    def read_tbl_rows_sg_cont_ad_with_cond_a(self, ac, db, condition):
        """ Zeilen aus Tabelle Sendung entspr. der Bedingung lesen """
        message_write_to_console(ac, "read_tbl_rows_sg_cont_ad_condition: ")

        sql_string = ("SELECT A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, A.SG_HF_DURATION, "
            "A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
            "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, "
            "B.SG_HF_CONT_TITEL, B.SG_HF_CONT_FILENAME, "
            "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
            "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
            "ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
            "LEFT JOIN AD_MAIN C "
            "ON B.SG_HF_CONT_AD_ID = C.AD_ID "
            "WHERE " + condition + "ORDER BY A.SG_HF_TIME")

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "DB-Connect fehlgeschlagen"
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                log_message = "read_tbl_rows_sg_cont_ad_with_cond_1: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                self.db_con.close()
                return None

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_rows_sg_cont_ad_with_cond_1 Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None
        else:
            message_write_to_console(ac, rows)
            self.db_con.close()
            return rows

    def read_tbl_rows_sg_cont_ad_with_cond_b(self, ac, db, condition):
        """ Zeilen aus Tabelle Sendung entspr. der Bedingung lesen """
        message_write_to_console(ac, "read_tbl_rows_sg_cont_ad_condition_2: ")

        sql_string = ("SELECT A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, A.SG_HF_DURATION, "
            "A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
            "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, "
            "B.SG_HF_CONT_TITEL, B.SG_HF_CONT_FILENAME, B.SG_HF_CONT_STICHWORTE, "
            "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
            "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
            "ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
            "LEFT JOIN AD_MAIN C "
            "ON B.SG_HF_CONT_AD_ID = C.AD_ID "
            "WHERE " + condition + "ORDER BY A.SG_HF_TIME")

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "DB-Connect fehlgeschlagen"
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                log_message = "read_tbl_rows_sg_cont_ad_with_cond_1: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                self.db_con.close()
                return None

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_rows_sg_cont_ad_with_cond_2 Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None
        else:
            message_write_to_console(ac, rows)
            self.db_con.close()
            return rows

    def read_tbl_rows_sg_cont_ad_with_cond_c(self, ac, db, condition):
        """ Zeilen aus Tabelle Sendung entspr. der Bedingung lesen """
        message_write_to_console( ac, "read_tbl_rows_sg_cont_ad_condition_3: " )
        sql_string = ("SELECT A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, "
            "A.SG_HF_DURATION, A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
            "A.SG_HF_SOURCE_ID, A.SG_HF_REPEAT_PROTO, A.SG_HF_FIRST_SG, "
            "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, B.SG_HF_CONT_TITEL, "
            "B.SG_HF_CONT_FILENAME, B.SG_HF_CONT_STICHWORTE, "
            "B.SG_HF_CONT_GENRE_ID, B.SG_HF_CONT_SPEECH_ID, B.SG_HF_CONT_TEAMPRODUCTION, "
            "B.SG_HF_CONT_UNTERTITEL, B.SG_HF_CONT_REGIEANWEISUNG,B.SG_HF_CONT_WEB, "
            "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
            "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
            "ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
            "LEFT JOIN AD_MAIN C "
            "ON B.SG_HF_CONT_AD_ID = C.AD_ID "
            "WHERE " + condition + "ORDER BY A.SG_HF_TIME")

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "DB-Connect fehlgeschlagen"
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                log_message = "read_tbl_rows_sg_cont_ad_with_cond_1: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                self.db_con.close()
                return None

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_rows_sg_cont_ad_with_cond_2 Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None
        else:
            message_write_to_console(ac, rows)
            self.db_con.close()
            return rows

    def read_tbl_rows_sg_cont_ad_with_cond_and_order(self, ac, db, condition, order):
        # zeile aus tabelle sendung lesen
        message_write_to_console( ac, "read_tbl_rows_sg_cont_ad_condition: ")

        sql_string =  "SELECT A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, A.SG_HF_DURATION, A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
        sql_string += "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, B.SG_HF_CONT_TITEL, B.SG_HF_CONT_FILENAME, "
        sql_string += "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
        sql_string += "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
        sql_string += "ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
        sql_string += "LEFT JOIN AD_MAIN C "
        sql_string += "ON B.SG_HF_CONT_AD_ID = C.AD_ID "
        sql_string += "WHERE " + condition + "ORDER BY " + order

        self.dbase_connect(ac)
        if self.db_con is None:
            rows = "nix"
            return rows

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                rows = "nix"
                log_message = "read_tbl_rows_sg_cont_ad: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                # logmeldung zu lang und zu häufig:
                #db.write_log_to_db( ac, log_message, "x" )

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_rows_sg_cont_ad Error: %s" % str(e)
            message_write_to_console(ac, log_message )
            db.write_log_to_db(ac, log_message, "x")
        else:
            message_write_to_console(ac, rows)
            return rows
            self.db_con.close()


    def read_tbl_rows_sg_cont_ad_with_limit_cond_and_order(self, ac, db, limit, condition, order):
        # zeile aus tabelle sendung lesen
        message_write_to_console(ac, "read_tbl_rows_sg_cont_ad_condition: ")

        sql_string = ( "SELECT " + limit + "A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, "
            "A.SG_HF_DURATION, A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_PODCAST, A.SG_HF_ON_AIR, "
            "B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, B.SG_HF_CONT_TITEL, "
            "B.SG_HF_CONT_FILENAME, B.SG_HF_CONT_GENRE_ID, "
            "C.AD_ID, C.AD_VORNAME, C.AD_NAME "
            "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
            "ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
            "LEFT JOIN AD_MAIN C "
            "ON B.SG_HF_CONT_AD_ID = C.AD_ID "
            "WHERE "+ condition + "ORDER BY " + order)

        self.dbase_connect(ac)
        if self.db_con is None:
            log_message = "DB-Connect fehlgeschlagen"
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
            return None

        try:
            db_cur = self.db_con.cursor()
            SELECT = sql_string
            db_cur.execute(SELECT)
            rows = []
            result = db_cur.fetchall()

            z = 0
            for row in result:
                rows.append(row)
                z += 1

            # wenn kein satz vorhanden
            if z == 0:
                #rows ="nix"
                log_message = "read_tbl_rows_sg_cont_ad: nichts gefunden..." + condition
                message_write_to_console(ac, log_message)
                # logmeldung zu lang und zu häufig:
                #db.write_log_to_db( ac, log_message, "x" )
                return None

        except Exception, e:
            self.db_con.close()
            log_message = "read_tbl_rows_sg_cont_ad Error: %s" % str(e)
            message_write_to_console(ac, log_message)
            db.write_log_to_db(ac, log_message, "x")
        else:
            message_write_to_console(ac, rows)
            self.db_con.close()
            return rows


def convert_to_unicode(my_string):
    """String in unicode konvertieren"""
    encodings = ('utf-8', 'cp1252', 'iso-8859-7')
    for enc in encodings:
        try:
            #print "s " + enc
            # try to decode the file with the first encoding
            # from the tuple.
            # if it succeeds then it will reach break, so we
            # will be out of the loop (something we want on
            # success).
            # the data variable will hold our decoded text
            data = unicode(my_string, enc)
            break
        except Exception:
            # if the first encoding fail, then with the continue
            # keyword will start again with the second encoding
            # from the tuple an so on.... until it succeeds.
            # if for some reason it reaches the last encoding of
            # our tuple without success, then exit the program.
            if enc == encodings[-1]:
                return None
        continue

    return data


def params_read_1(ac, db):
    # params_desc fuer suche in db abhaengigkeit von entwicklung oder normal
    if ac.app_develop == "yes":
        ac.app_config_params_desc = ac.app_config_develop
    else:
        ac.app_config_params_desc = ac.app_config

    message_write_to_console(ac, "params_read_1: " + ac.app_config_params_desc)

    # prams aus db holen
    db.ac_config_1 = db.params_load(ac, db)

    if db.ac_config_1 is None:
        err_message = "Exit nach Error 000 - keine Parameter gefunden fuer: " + ac.app_config_params_desc
        error_write_to_file(ac, err_message)
        message_write_to_console(
            ac, ac.app_desc + ": execution because of errors stopped")
        sys.exit()
    else:
        params_check(ac, db.ac_config_1)
    return


def params_check_1(ac, db):
    """ Pruefen ob Params geladen wurden und richtigen Typ haben"""
    message_write_to_console(ac, "check_params ")

    #if db.ac_config_1 is not None:
    #    message_write_to_console(ac, "params_check not none")
    for i in range(ac.app_config_params_range):
        param_check = params_check_type(
            ac, db, ac.app_params_type_list[i], db.ac_config_1[i])
        if param_check is None:
            err_message = ac.app_errorslist[0] + " " + db.ac_config_1[i]
            message_write_to_console(ac, err_message)
            db.write_log_to_db(ac, err_message, "x")
            break

    message_write_to_console(ac, param_check)
    return param_check


def params_check_a(ac, db, params_range, params_type_list, params_list):
    """ Fuer Zusatzparams:
    Pruefen ob Params geladen wurden und richtigen Typ haben"""
    message_write_to_console(ac, "check_params ")

    #if db.ac_config_2 is not None:
     #   message_write_to_console(ac, "params_check not none")
    for i in range(params_range):
        param_check = params_check_type(
            ac, db, params_type_list[i], params_list[i])
        if param_check is None:
            err_message = ac.app_errorslist[0] + " " + params_list[i]
            message_write_to_console(ac, err_message)
            db.write_log_to_db(ac, err_message, "x")
            break

    message_write_to_console(ac, param_check)
    return param_check


def params_check_type(ac, db, param_typ, param_value):
    """ Pruefen ob in Param-Feldern entsprechender Typ eingetragen"""
    message_write_to_console(ac, "check_params_type ")

    if param_typ == "p_string":
        pm = re.match(r".+", param_value)
    elif param_typ == "p_int":
        # Zahlen suchen, wenn nicht gefunden dann None
        pm = re.match(r"\d", param_value)
        # Nichtzahlen suchen, wenn gefunden dann None, weil das darf nicht sein
        pma = re.search(r"[^0-9]", param_value)
        if pma is not None:
            pm = None
    elif param_typ == "p_url":
        pm = re.match(r"http://", param_value)

    return pm


def error_write_to_file(ac, err_message):
    # fehler in datei schreiben, wenn nicht in db möglich
    message_write_to_console(ac, "error_write_to_file")
    message = str(datetime.datetime.now()) + " - " + err_message + "\n"
    message_write_to_console(ac, message)
    try:
        f_errors = open(ac.app_errorfile, 'a')
    except IOError as (errno, strerror):
        message_write_to_console(
            ac, "I/O error({0}): {1}".format(errno, strerror))
        message_write_to_console(ac, message)
    else:
        f_errors.write(message)
        f_errors.close
    return


def message_write_to_console(ac, debug_message):
    """Meldung auf Console ausgeben"""
    if ac.app_debug_mod == "yes":
        print debug_message
    return


def replace_uchar_with_html(unistr):
    """unicode-Zeichen durch html-Entities ersetzen"""
    # noetig: import htmlentitydefs
    #return "&%s;" % htmlentitydefs.codepoint2name[ord(character)]
    escaped = ""
    for char in unistr:
        if ord(char) in htmlentitydefs.codepoint2name:
            name = htmlentitydefs.codepoint2name.get(ord(char))
            entity = htmlentitydefs.name2codepoint.get(name)
            escaped += "&#" + str(entity)
        else:
            escaped += char
    return escaped


def replace_sonderzeichen_with_latein(my_string):
    """Sonderzeichen (Umlaute) ersetzen"""
    #print "replace_sonderzeichen"
    x = my_string.replace(u"ä", "ae")
    x = x.replace(u"Ä", "Ae")

    x = x.replace(u"ö", "oe")
    x = x.replace(u"Ö", "Oe")
    x = x.replace(u"ü", "ue")
    x = x.replace(u"Ü", "Ue")

    x = x.replace(u"ß", "sz")
    return x


def replace_uchar_sonderzeichen_with_latein(my_string):
    """Sonderzeichen (Umlaute) ersetzen"""
    #print "replace_sonderzeichen"
    x = my_string.replace(u"\xe4", "ae")
    x = x.replace(u"\xc4", "Ae")

    x = x.replace(u"\xf6", "oe")
    x = x.replace(u"\xd6", "Oe")
    x = x.replace(u"\xfc", "ue")
    x = x.replace(u"\xdc", "Ue")

    x = x.replace(u"\xdf", "sz")
    return x


def read_file_first_line(ac, db, filename):
    """Erste Zeile einer Datei lesen"""
    message_write_to_console(ac, u"read_file_first_line " + filename)
    try:
        # mit "with" wird file autom geschlossen
        with open(filename) as f:
            line = f.readline()
    except IOError as (errno, strerror):
        line = None
        log_message = "read_file_first_line: {2} - I/O error({0}): {1}".format(errno, strerror, filename)
        db.write_log_to_db_1(ac, log_message, "x", "write_also_to_console")
    return line


def upload_data(ac, db, url, data_upload):
    """Webseite holen, bzw. Daten uebertragen """
    website = None

    import urllib2
    from urllib2 import Request, urlopen, URLError, HTTPError

    req = urllib2.Request(url, data_upload)

    try:
        response = urllib2.urlopen(req)
    except HTTPError, e:
        log_message = "connect_url_1 The server couldnot fulfill the request..."+url
        message_write_to_console(ac, log_message)
        #db.write_log_to_db( ac, log_message, "x" )
    except URLError, e:
        log_message = "connect_url_2 We failed to reach a server.."
        #db.write_log_to_db( ac, log_message, "x" )
    except Exception, e:
        log_message = "connect_url_3 Fehler.."
        #db.write_log_to_db( ac, log_message, "x" )
    else:
        # everything is fine
        website = response.read()
        response.close()
        ##print website

    return website


def download_website(ac, db, url):
    # webseite holen
    import urllib2
    from urllib2 import Request, urlopen, URLError, HTTPError
    website = None
    req = Request(url)

    try:
        response = urlopen(req)
    except HTTPError, e:
        log_message = "connect_url_1 The server couldnot fulfill the request..."+url
        message_write_to_console(ac, log_message)
        db.write_log_to_db(ac, log_message, "x")
    except URLError, e:
        log_message = "connect_url_2 We failed to reach a server.."
        db.write_log_to_db(ac, log_message, "x")
    else:
        # everything is fine
        website = response.read()
        response.close()

    return website


def erase_file(ac, db, path_filename):
    message_write_to_console(ac, "erase_file: " + path_filename)

    try:
        if os.path.isfile(path_filename):
            os.remove(path_filename)
            message_write_to_console(ac, "Datei geloescht: " + path_filename)
            log_message = u"Datei gelöscht: " + path_filename
            db.write_log_to_db(ac, log_message, "k")

    except OSError, msg:
        message_write_to_console(
            ac, "erase_file: " + "%r: %s" % (msg, path_filename))
        log_message = "erase_file: " + "%r: %s" % (msg, path_filename)
        db.write_log_to_db(ac, log_message, "x")

    return


def erase_file_a(ac, db, path_filename, msg_done):
    """Datei loeschen"""
    message_write_to_console(ac, "erase_file: " + path_filename)

    try:
        if os.path.isfile(path_filename):
            os.remove(path_filename)
            message_write_to_console(ac, msg_done + path_filename)
            log_message = msg_done + path_filename
            db.write_log_to_db(ac, log_message, "k")

    except OSError, msg:
        message_write_to_console(
            ac, "erase_file: " + "%r: %s" % (msg, path_filename))
        log_message = "erase_file: " + "%r: %s" % (msg, path_filename)
        db.write_log_to_db(ac, log_message, "x")
        return None

    return path_filename


def check_slashes(ac, path_to_check):
    """Back oder Slashes bei Pfad hintendran machen"""
    if ac.app_windows == "yes":
        # pfad anpassen, win-backslash hinten dran:
        if path_to_check[-1] != "\\":
            path_to_check += "\\"
    else:
        if path_to_check[-1] != "/":
            path_to_check += "/"

    return path_to_check


def extract_filename(ac, path_filename):
    """filename rechts von slash extrahieren"""
    if ac.app_windows == "no":
        filename = path_filename[string.rfind(path_filename, "/") + 1:]
    else:
        filename = path_filename[string.rfind(path_filename, "\\") + 1:]
    return filename