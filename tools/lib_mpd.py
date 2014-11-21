#! /usr/bin/env python
# -*- coding: utf-8 -*-
# lib for the music player daemon
# based on the example of Joerg Thalheim
# https://github.com/Mic92/python-mpd2

from mpd import MPDClient, MPDError, CommandError
import subprocess
import mpd_config


def mpc_client(command):
    mpd_server = mpd_config.mpd_pw + "@" + mpd_config.mpd_host
    p = subprocess.Popen(["/usr/bin/mpc",
                            "-h", mpd_server, "-p", mpd_config.mpd_port,
                        command],
                        stdout=subprocess.PIPE,
                        stderr=subprocess.PIPE).communicate()
    print p
    #lib_cm.message_write_to_console(ac, u"returncode 0")
    #lib_cm.message_write_to_console(ac, p[0])
    #lib_cm.message_write_to_console(ac, u"returncode 1")
    #lib_cm.message_write_to_console(ac, p[1])
    return p


class PollerError(Exception):
    """Fatal error in poller."""


class myMPD(object):
    def __init__(self):
        #self._host = host
        self._host = mpd_config.mpd_host
        #self._port = port
        self._port = mpd_config.mpd_port
        #self._password = password
        self._password = mpd_config.mpd_pw
        self._client = MPDClient()

    def connect(self):
        try:
            self._client.connect(self._host, self._port)
        # Catch socket errors
        except IOError as err:
            errno, strerror = err
            raise PollerError("Could not connect to '%s': %s" %
                              (self._host, strerror))

        # Catch all other possible errors
        # ConnectionError and ProtocolError are always fatal.  Others may not
        # be, but we don't know how to handle them here, so treat them as if
        # they are instead of ignoring them.
        except MPDError as e:
            raise PollerError("Could not connect to '%s': %s" %
                              (self._host, e))

        if self._password:
            try:
                self._client.password(self._password)

            # Catch errors with the password command (e.g., wrong password)
            except CommandError as e:
                raise PollerError("Could not connect to '%s': "
                                  "password commmand failed: %s" %
                                  (self._host, e))

            # Catch all other possible errors
            except (MPDError, IOError) as e:
                raise PollerError("Could not connect to '%s': "
                                  "error with password command: %s" %
                                  (self._host, e))

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

    def exec_command(self, command, value):
        result = None
        try:
            #self.connect()
            #song = self._client.currentsong()
            if command == "update":
                result = self._client.update()
            if command == "song":
                result = self._client.currentsong()
            if command == "crop":
                result = mpc_client("crop")
            if command == "add":
                result = self._client.add(value)
            if command == "next":
                result = mpc_client("next")
            #self.disconnect()

        # Couldn't get the current song, so try reconnecting and retrying
        except (MPDError, IOError):
            # No error handling required here
            # Our disconnect function catches all exceptions, and therefore
            # should never raise any.
            self.disconnect()

            try:
                self.connect()

            # Reconnecting failed
            except PollerError as e:
                raise PollerError("Reconnecting failed: %s" % e)

            #try:

            # Failed again, just give up
            #except (MPDError, IOError) as e:
            #    raise PollerError("Couldn't retrieve current song: %s" % e)

        # Hurray!  We got the current song without any errors!
        print "Res_mpd:"
        print result
        return result
