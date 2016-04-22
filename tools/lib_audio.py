#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103
# lib for audio file editing

import os
from mutagen.id3 import ID3, TPE1, TIT2
from mutagen.id3 import ID3NoHeaderError


def add_id3(ac, db, lib_cm, sendung_data, path_file):
    """write id3-tag in mp3-file"""
    lib_cm.message_write_to_console(ac, "id3-Tag in mp3-File schreiben")

    if os.path.isfile(path_file) is False:
        log_message = "tag file with id3 " + "%r: %s" % (
                                        "Datei nicht vorhanden: ", path_file)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    id3_tag_present = False
    try:
        audio_id3_tag = ID3(path_file)
        id3_tag_present = True
    except ID3NoHeaderError:
        db.write_log_to_db_a(ac, "Kein ID3 Tag vorhanden: "
                        + sendung_data[12], "t", "write_also_to_console")

    if id3_tag_present:
        db.write_log_to_db_a(ac, "ID3 Tag vorhanden: "
                        + sendung_data[12], "t", "write_also_to_console")
        audio_id3_tag.delete()
        db.write_log_to_db_a(ac, "ID3 Tag geloescht: "
                        + sendung_data[12], "t", "write_also_to_console")

    id3_author_value_uni = sendung_data[15] + " " + sendung_data[16]
    audio_id3_tag.add(TPE1(encoding=3, text=id3_author_value_uni))
    audio_id3_tag.add(TIT2(encoding=3, text=sendung_data[11]))
    audio_id3_tag.save()

    db.write_log_to_db_a(ac, "Audiodatei mit ID3 getaggt: "
                        + sendung_data[12], "k", "write_also_to_console")

    return True