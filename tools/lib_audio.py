#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103
# lib for audio file editing

import os
import subprocess
import string
import mutagen
#from mutagen.id3 import ID3, TPE1, TIT2
#from mutagen.id3 import ID3NoHeaderError
from mutagen.easyid3 import EasyID3


def add_id3(ac, db, lib_cm, sendung_data, path_file):
    """write id3-tag in mp3-file"""
    lib_cm.message_write_to_console(ac, "id3-Tag in mp3-File schreiben")

    if os.path.isfile(path_file) is False:
        log_message = "tagging file with id3 " + "%r: %s" % (
                                        "Datei nicht vorhanden: ", path_file)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    id3_tag_present = False
    try:
        #audio_id3_tag = ID3(path_file)
        audio_id3_tag = EasyID3(path_file)
        id3_tag_present = True
    #except ID3NoHeaderError:
    except mutagen.id3.ID3NoHeaderError:
        audio_id3_tag = mutagen.File(path_file, easy=True)
        audio_id3_tag.add_tags()
        db.write_log_to_db_a(ac, "Kein ID3 Tag vorhanden: "
                        + sendung_data[12], "t", "write_also_to_console")

    if id3_tag_present:
        db.write_log_to_db_a(ac, "ID3 Tag vorhanden: "
                        + sendung_data[12], "t", "write_also_to_console")
        audio_id3_tag.delete()
        db.write_log_to_db_a(ac, "ID3 Tag geloescht: "
                        + sendung_data[12], "p", "write_also_to_console")
    #else:
        #audio_id3_tag = ID3(path_file)

    id3_author_value_uni = sendung_data[15] + " " + sendung_data[16]
    #print id3_author_value_uni
    #print type(audio_id3_tag)
    try:
        #audio_id3_tag.add(TPE1(encoding=3, text=id3_author_value_uni))
        audio_id3_tag["artist"] = id3_author_value_uni
        #audio_id3_tag.add(TIT2(encoding=3, text=sendung_data[11]))
        audio_id3_tag["title"] = sendung_data[11]
        audio_id3_tag.save()
        db.write_log_to_db_a(ac, "Audiodatei mit ID3 getaggt: "
                        + sendung_data[12], "k", "write_also_to_console")
    except Exception, e:
        log_message = u"ID3 Tagging Error: %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
    return True


def add_replaygain(ac, db, lib_cm, path_file):
    """mp3-Gain"""
    lib_cm.message_write_to_console(ac, u"mp3-File Gainanpassung")
    # use the right char-encoding for supprocesses
    #c_mp3gain = db.ac_config_etools[5].encode(ac.app_encode_out_strings)
    #c_source_file = path_file_dest.encode(ac.app_encode_out_strings)
    #lib_cm.message_write_to_console(ac, c_source_file)
    # start subprocess
    try:
        p = subprocess.Popen(["replaygain", path_file],
                stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = u"replaygain Error: %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        log_message = u"replaygain File: %s" % path_file
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    #lib_cm.message_write_to_console(ac, u"returncode 1")
    #lib_cm.message_write_to_console(ac, p[1])

    # search for suchess msg, if not found: -1
    replaygain_output = string.find(p[0], "Done")
    replaygain_output_1 = string.find(p[0], "Nothing to do")

    #lib_cm.message_write_to_console(ac, replaygain_output)
    #lib_cm.message_write_to_console(ac, replaygain_output_1)
    # wenn gefunden, position, sonst -1
    if replaygain_output != -1:
        log_message = u"replaygain angepasst: " + path_file
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")

    if replaygain_output_1 != -1:
        db.write_log_to_db_a(ac, u"mp3gain offenbar nicht noetig: "
                             + path_file, "p", "write_also_to_console")
    return True