#! /usr/bin/env python
# -*- coding: utf-8 -*-
# lib for serial controller of audio switch

import config_serial
import serial
import time


class mySERIAL(object):
    def __init__(self):
        self.app_ser_port = config_serial.ser_port
        self.app_ser_baudrate = config_serial.ser_baudrate
        self.app_ser_bytesize = config_serial.ser_bytesize
        self.app_ser_parity = config_serial.ser_parity
        self.app_ser_stopbits = config_serial.ser_stopbits
        self.app_ser_timeout = config_serial.ser_timeout

    def set_port(self, ac, db):
        """setting port"""
        try:
            ser_port = serial.Serial(port=ac.ser_port,
                         baudrate=int(db.ac_config_1[3]),
                         bytesize=int(db.ac_config_1[4]),
                         parity=db.ac_config_1[5],
                         stopbits=int(db.ac_config_1[6]),
                         timeout=int(db.ac_config_1[7]))
            if not ser_port.isOpen():
                ser_port.open()
                #print "opening port"
        except Exception as e:
            db.write_log_to_db_a(ac,
                    ac.app_desc + " Fehler beim Port-Setting: %s" % str(e), "x",
                                             "write_also_to_console")
            ser_port = False
        return ser_port

    def get_status(self, ac, db, param, option):
        """get status"""
        switch_status = None
        port = self.set_port(ac, db)
        if not port:
            return

        try:
            port.write(option)
            time.sleep(0.1)
            switch_respond = port.read(10)
            time.sleep(0.1)
            port.close
            if switch_respond:
                switch_status = switch_respond.split()
            #log_message = "Status: " + ', '.join(switch_status)
            #db.write_log_to_db_a(ac, log_message, "p",
            #                                 "write_also_to_console")
            #print log_message
        except Exception as e:
            db.write_log_to_db_a(ac,
                ac.app_desc + " Fehler beim Lesen des Status: " + str(e), "x",
                                             "write_also_to_console")
            port.close
        if not switch_status:
            log_message = ac.app_desc + " Keine Verbindung zu Audio-Switch?"
            db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
            print "Keine Verbindung zu Audio-Switch?"
        return switch_status

    def reset_gain(self, ac, db, param):
        """setting gain"""
        switch_status = None
        port = self.set_port(ac, db)
        if not port:
            return
        switch_cmd = param + "*0G"
        try:
            db.write_log_to_db_a(ac, "Reset Gain for " + param, "e",
                                                "write_also_to_console")
            port.write(switch_cmd)
            time.sleep(0.1)
            switch_status = self.get_status(ac, db, "-s", "V" + param + "G")
            if switch_status is None:
                return
            port.close
        except Exception as e:
            db.write_log_to_db_a(ac,
                    ac.app_desc + " Fehler beim Gain-Reset: " + str(e), "x",
                                             "write_also_to_console")
            port.close
        return switch_status

    def read_switch_respond(self, ac, db, switch_status):
        """read active input"""
        #print "read resp"
        #print switch_status
        switch_respond = None
        try:
            if switch_status[0][:2] == "In":
                switch_respond = switch_status[0][2:4]
            if switch_status[0] == "Vx":
                switch_respond = switch_status[1][2:3]
            if switch_status[0] == "Amt1":
                switch_respond = "muted Audio"
            if switch_status[0] == "Vmt1":
                switch_respond = "muted Video"
            if switch_status[0] == "Amt0":
                switch_respond = "unmuted Audio"
            if switch_status[0] == "Vmt0":
                switch_respond = "unmuted Video"
            if switch_status[0] == "Exe1":
                switch_respond = "locked"
            if switch_status[0] == "Exe0":
                switch_respond = "unlocked"
            if switch_status[0] == "F1":
                switch_respond = "normal"
            if switch_status[0] == "F2":
                switch_respond = "auto"
            if switch_status[0] == "Zpa":
                switch_respond = "reset audio"
            if switch_status[0] == "Zpx":
                switch_respond = "reset system"
        except Exception as e:
            db.write_log_to_db_a(ac,
            ac.app_desc + " Fehler bei read_switch_respond: " + str(e), "x",
            "write_also_to_console")
        return switch_respond
