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
            ser_port = serial.Serial(port=self.app_ser_port,
                         baudrate=self.app_ser_baudrate,
                         bytesize=self.app_ser_bytesize,
                         parity=self.app_ser_parity,
                         stopbits=self.app_ser_stopbits,
                         timeout=self.app_ser_timeout)
            if not ser_port.isOpen():
                ser_port.open()
                print "opening port"
        except Exception as e:
            print ("Fehler beim Port-Setting..: " + str(e))
            ser_port = False
        return ser_port

    def get_status(self, ac, db, param, option):
        """get status"""
        print "status " + param
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
            switch_status = switch_respond.split()
            print switch_status
        except Exception as e:
            print ("Fehler beim lesen des ser. status..: " + str(e))
            port.close
        return switch_status

    def reset_gain(self, ac, db, param):
        """setting gain"""
        port = self.set_port(ac, db)
        if not port:
            return
        switch_cmd = param + "*0G"
        try:
            #print "write"
            port.write(switch_cmd)
            time.sleep(0.1)
            switch_status = self.get_status(ac, db, "-s", "V" + param + "G")
            if switch_status is None:
                print "Fehler bei Statusabfrage bei push"
            return
            #print switch_status
            port.close
        except Exception as e:
            print ("Fehler beim push..: " + str(e))
            port.close

    def read_switch_respond(self, ac, db, switch_status):
        """read active input"""
        print "read resp"
        print switch_status[0]
        switch_respond = None
        if switch_status[0][:2] == "In":
            switch_respond = switch_status[0][2:4]
        if switch_status[0] == "Vx":
            switch_respond = switch_status[1][2:3]
        if switch_status[0] == "Amt1":
            switch_respond = "muted"
        if switch_status[0] == "Amt0":
            switch_respond = "unmuted"
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
        print switch_respond
        return switch_respond
