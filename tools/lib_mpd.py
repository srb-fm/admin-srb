#! /usr/bin/env python
# -*- coding: utf-8 -*-
# lib for the music player daemon
# based on the example of Joerg Thalheim
# https://github.com/Mic92/python-mpd2

from mpd import MPDClient, MPDError, CommandError, ConnectionError
import subprocess
import mpd_config


def mpc_client(db, ac, command, value):
    """execute mpd-commands via mpc-client"""
    mpd_server = mpd_config.mpd_pw + "@" + mpd_config.mpd_host
    try:
        if value is None:
            p = subprocess.Popen(["/usr/bin/mpc",
                            "-h", mpd_server, "-p", mpd_config.mpd_port,
                        command],
                        stdout=subprocess.PIPE,
                        stderr=subprocess.PIPE).communicate()
        else:
            p = subprocess.Popen(["/usr/bin/mpc",
                            "-h", mpd_server, "-p", mpd_config.mpd_port,
                        command, value],
                        stdout=subprocess.PIPE,
                        stderr=subprocess.PIPE).communicate()
    except Exception, e:
        db.write_log_to_db_a(ac, "MPD-Error: %s" % str(e), "x",
                                             "write_also_to_console")
        return None
    return p


class RunError(Exception):
    """Fatal error """
    pass


class myMPD(object):
    def __init__(self):
        self._host = mpd_config.mpd_host
        self._port = mpd_config.mpd_port
        self._password = mpd_config.mpd_pw
        self._client = MPDClient()

    def connect(self, db, ac):
        try:
            self._client.connect(self._host, self._port)
            return True
        # Catch socket errors
        except IOError as err:
            errno, strerror = err
            db.write_log_to_db_a(ac, "MPD-IO-Error:'%s': %s" %
                    (self._host, strerror), "x", "write_also_to_console")

        except ConnectionError as e:
            db.write_log_to_db_a(ac, "MPD-Conn-Error 1: %s" % str(e), "x",
                                             "write_also_to_console")
            return None

        # Catch all other possible errors
        # ConnectionError and ProtocolError are always fatal.  Others may not
        # be, but we don't know how to handle them here, so treat them as if
        # they are instead of ignoring them.
        except MPDError as e:
            #raise RunError("Could not connect to '%s': %s" %
            #                  (self._host, e))
            db.write_log_to_db_a(ac, "MPD-Error-1: %s" % str(e), "x",
                                             "write_also_to_console")
            return None

        if self._password:
            try:
                self._client.password(self._password)
                return True
            except ConnectionError as e:
                db.write_log_to_db_a(ac, "MPD-Conn-Error 2: %s" % str(e), "x",
                                             "write_also_to_console")
                return None
            # Catch errors with the password command (e.g., wrong password)
            except CommandError as e:
                #raise RunError("Could not connect to '%s': "
                #                  "password commmand failed: %s" %
                #                  (self._host, e))
                #db.write_log_to_db_a(ac, "MPD-PW-Error: %s" % str(e), "x",
                #                             "write_also_to_console")
                return None

            # Catch all other possible errors
            except (MPDError, IOError) as e:
                #raise RunError("Could not connect to '%s': "
                #                  "error with password command: %s" %
                #                  (self._host, e))
                db.write_log_to_db_a(ac, "MPD-Error-2: %s" % str(e), "x",
                                             "write_also_to_console")
            return None

    def disconnect(self):
        # Try to tell MPD we're closing the connection first
        try:
            self._client.close()

        # If that fails, don't worry, just ignore it and disconnect
        except (MPDError, IOError):
            pass

        try:
            self._client.disconnect()

        # Disconnecting failed, so use a new client object instead
        # This should never happen.  If it does, something is seriously broken,
        # and the client object shouldn't be trusted to be re-used.
        except (MPDError, IOError):
            self._client = MPDClient()

    def exec_command(self, db, ac, command, value):
        result = None
        try:
            if command == "play":
                result = self._client.play()
            if command == "update":
                result = self._client.update()
            if command == "song":
                result = self._client.currentsong()
            if command == "status":
                result = self._client.status()
            if command == "add":
                result = self._client.add(value)
            if command == "consume":
                result = self._client.consume(value)
            if command == "crossfade":
                result = self._client.crossfade(value)
            if command == "seek":
                result = self._client.seek(value)
            if command == "repeat":
                result = self._client.repeat(value)
            if command == "random":
                result = self._client.random(value)
            if command == "single":
                result = self._client.single(value)
            if command == "replay_gain_mode":
                result = self._client.replay_gain_mode(value)
            if command == "next":
                result = self._client.next()
            if command == "setvol":
                result = self._client.setvol(value)
            # via mpc-client
            if command == "crop":
                result = mpc_client(db, ac, "crop", value)
            if command == "vol":
                result = mpc_client(db, ac, "volume", value)
            return result

        # Couldn't get the current cmd, so try reconnecting and retrying
        except (MPDError, IOError) as e:
            # No error handling required here
            # Our disconnect function catches all exceptions, and therefore
            # should never raise any.
            db.write_log_to_db_a(ac, "MPD-Error-3: %s" % str(e), "x",
                                             "write_also_to_console")
            if value is not None:
                db.write_log_to_db_a(ac, "MPD-E-3 cmd-value: " + value, "x",
                                             "write_also_to_console")
            if str(e) == "Not connected":
                db.write_log_to_db_a(ac, "MPD-E-3: try reconnect", "x",
                                             "write_also_to_console")
                self.connect(db, ac)
                self.exec_command(db, ac, command, value)
            else:
                self.disconnect()
            return None
