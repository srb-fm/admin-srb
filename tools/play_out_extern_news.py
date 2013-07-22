#!/usr/bin/env python
# -*- coding: utf-8 -*-
# pylint: disable-msg=C0103

"""
Play Out extern News 
Autor: Joerg Sorge
Org: SRB - Das Buergerradio
www.srb.fm

Distributed under the terms of GNU GPL version 2 or later
Copyright (C) Joerg Sorge joergsorge at googell
2011-09-30

Dieses Script....

Dateiname Script: play_out_extern_news.py
Schluesselwort fuer Einstellungen: PO_News_extern_Config_1
Benoetigt: lib_common.py im gleichen Verzeichnis
Bezieht Daten aus: Firebird-Datenbank

Fehlerliste:
Error 000 Parameter-Typ oder Inhalt stimmt nich
Error 001 
Error 002 

Parameterliste:
Param 1: On/Off Switch
Param 2: Pfad/Programm fuer Download
Param 3: Pfad/Programm fuer Audio-Bearbeitung
Param 4: Pfad/Programm fuer Audio-Informationen
Param 5: Pfad/Programm fuer ID3-Tags
Param 6: URL
Param 7: Benutzername
Param 8: Passwort
Param 9: Pfad zu Layout-Dateien
Param 10: Pfad zu Play-Out-IT

Externe Tools:
Folgende libs werden ueblicherweise auf dem system benoetigt:
wget
sox
libsox-fmt-mp3
soxi
id3v2

Dieses Script wird zeitgesteuert 
in der Stunde vor der Ausstrahlung 
und nach Bereitstellung der News ausgefuehrt.
In der Regel also ca. 11:51 Uhr

"""

import sys
import os
import string
import shutil
import subprocess
import datetime
import lib_common_1 as lib_cm


class app_config( object ):
    """Application-Config"""
    def __init__(self):
        """Einstellungen"""
        # app_config
        self.app_id = "018"
        self.app_desc = u"play_out_news_extern"
        # schluessel fuer config in db
        self.app_config = u"PO_News_extern_Config_1"
        self.app_config_develop = u"PO_News_extern_Config_1_e"
        # anzahl parameter
        self.app_config_params_range = 10
        self.app_errorfile = "error_play_out_news_extern.log"
        # errorlist
        self.app_errorslist = []
        self.app_errorslist.append(u"Error 000 "
            "Parameter-Typ oder Inhalt stimmt nicht ")
        self.app_errorslist.append(u"Error 001 "
            "beim Herunterladen externer News: ")
        self.app_errorslist.append(u"Error 002 "
            "beim Stille enfernen externer News: ")
        self.app_errorslist.append(u"Error 003 "
            "beim Komprimieren der Sprache externer News: ")
        self.app_errorslist.append(u"Error 004 "
            "beim ermitteln der Laenge externer News")
        self.app_errorslist.append(u"Error 005 "
            "beim Trimmen des Soundbeds fuer externe News")
        self.app_errorslist.append(u"Error 006 "
            "beim Mixen der externen News")
        self.app_errorslist.append(u"Error 007 "
            "beim Verketten von Layout und externer News")
        self.app_errorslist.append(u"Error 008 "
            "beim Schreiben von id3Tags in externe News")
        self.app_errorslist.append(u"Error 009 "
            "beim Aktualisieren der Sendebuchung der externen News")
        # params-type-list, typ entsprechend der params-liste in der config
        self.app_params_type_list = []
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_url")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        self.app_params_type_list.append("p_string")
        
        # entwicklungsmodus (andere parameter, z.b. bei verzeichnissen)
        self.app_develop = "no"
        # meldungen auf konsole ausgeben
        self.app_debug_mod = "yes"
        self.app_windows = "no"
        self.app_encode_out_strings = "cp1252"
        
        # das script laeuft 11:52 uhr, hier zeit einstellen
        self.time_target_start = (datetime.datetime.now() 
                            + datetime.timedelta(hours=+1))
    
        self.app_file_bed = "News_ext_Automation_Bed.wav"
        self.app_file_bed_trim = "News_ext_Automation_Bed_trimmed.wav"
        self.app_file_intro = "News_ext_Automation_Intro.wav"
        self.app_file_closer = "News_ext_Automation_Closer.wav"

def load_sg():
    """IT-Sendung suchen"""
    lib_cm.message_write_to_console(ac, u"IT-Sendung suchen")

    db_tbl_condition = ("A.SG_HF_ON_AIR = 'T' "
        "AND SUBSTRING(A.SG_HF_TIME FROM 1 FOR 13) = '"
        + ac.time_target_start.strftime("%Y-%m-%d %H") + "' " 
        "AND A.SG_HF_INFOTIME = 'T'")
    sendung_data = db.read_tbl_rows_sg_cont_ad_with_cond_b(ac, 
        db, db_tbl_condition)

    if sendung_data is None:
        log_message = (u"Keine externen News "
            "fuer diese Zeit vorgesehen: " 
            + ac.time_target_start.strftime("%Y-%m-%d %H") + " Uhr")
        db.write_log_to_db_a(ac, log_message, "t", "write_also_to_console")
        return sendung_data
    return sendung_data

def fetch_media():
    """mp3-File von Server holen"""
    lib_cm.message_write_to_console(ac, u"mp3-File von Server holen")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[2].encode(ac.app_encode_out_strings)
    #cmd = "wget"
    #lib_cm.message_write_to_console(ac, cmd )
    url_source_file = db.ac_config_1[6].encode(ac.app_encode_out_strings)
    url_user = "--user=" + db.ac_config_1[7].encode(ac.app_encode_out_strings)
    url_pw = "--password=" + db.ac_config_1[8].encode(ac.app_encode_out_strings)
    # subprozess starten
    try:
        p = subprocess.Popen([cmd, url_user, url_pw, url_source_file], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[1] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return 

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])
    
    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    cmd_output_1 = string.find( p[1], "100%" )
    lib_cm.message_write_to_console(ac, cmd_output_1)
    # wenn gefunden, position, sonst -1
    if cmd_output_1 != -1:
        log_message = u"Externe News heruntergeladen... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[1] 
            + u"100% beim Download nicht erreicht...", 
            "x", "write_also_to_console")
        return None
                             

def trim_silence():
    """Stille am Anfang und Ende entfernen"""
    lib_cm.message_write_to_console(ac, u"Stille am Anfang und Ende entfernen")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[3].encode(ac.app_encode_out_strings )
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, cmd )
    source_file = lib_cm.extract_filename(ac, db.ac_config_1[6])
    dest_file = lib_cm.extract_filename(ac, db.ac_config_1[6]).replace("mp3", "wav")
    lib_cm.message_write_to_console(ac, source_file )
    # subprozess starten
    #silence 1 0.1 1% reverse silence 1 0.1 1% reverse
    try:
        p = subprocess.Popen([cmd, u"-S", source_file, dest_file, 
            u"silence", u"1", u"0.1", u"1%", u"reverse", u"silence", u"1", u"0.1", u"1%", u"reverse"], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[2] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    #lib_cm.message_write_to_console(ac, u"returncode 0" )
    #lib_cm.message_write_to_console(ac, p[0] )
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])
    
    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    cmd_output_1 = string.find( p[1], "100%")
    lib_cm.message_write_to_console(ac, cmd_output_1)
    
    if cmd_output_1 != -1:
        log_message = u"Audio getrimmt... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[2] 
            + u"100% beim Trimmen nicht erreicht...", 
            "x", "write_also_to_console")
        return None

def trim_bed(c_lenght):
    """Soundbed auf Laenge der News trimmen"""
    lib_cm.message_write_to_console(ac, u"Soundbed auf News trimmen")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[3].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, c_lenght)
    source_path = lib_cm.check_slashes(ac, db.ac_config_1[9])
    source_file = source_path + ac.app_file_bed
    dest_file = ac.app_file_bed_trim
    # subprozess starten
    #silence 1 0.1 1% reverse silence 1 0.1 1% reverse
    try:
        p = subprocess.Popen([cmd, u"-S", source_file, dest_file, 
            u"trim", u"0", str(c_lenght) ], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[5] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0" )
    lib_cm.message_write_to_console(ac, p[0] )
    lib_cm.message_write_to_console(ac, u"returncode 1" )
    lib_cm.message_write_to_console(ac, p[1] )
    
    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    cmd_output_1 = string.find( p[1], c_lenght[0:8] )
    if cmd_output_1 != -1:
        log_message = u"Bed getrimmt... " + c_lenght[0:8]
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[5] 
            + u"Erforderliche Laenge beim Trimmen nicht erreicht...", 
            "x", "write_also_to_console")
        return None

def compand_voice():
    """Sprache komprimieren"""
    lib_cm.message_write_to_console(ac, u"Sprache komprimieren")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[3].encode(ac.app_encode_out_strings )
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, cmd )
    #source_file = lib_cm.extract_filename(ac, db.ac_config_1[6])
    source_file = lib_cm.extract_filename(ac, db.ac_config_1[6]).replace("mp3", "wav")
    dest_file = lib_cm.extract_filename(ac, db.ac_config_1[6]).replace(".mp3", "_comp.wav")
    lib_cm.message_write_to_console(ac, source_file )
    # subprozess starten
    #compand 0.3,1 6:-70,-60,-20 -5 -90
    # 
    try:
        p = subprocess.Popen([cmd, u"-S", source_file, dest_file, 
            #u"compand", u"0.3,1","6:-70,-60,-20", u"-12", u"-90", u"0.2"], 
            u"compand", u"0.3,0.6","-80,-60,-75,-16", u"-18", u"-80", u"0.2"], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[2] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    #lib_cm.message_write_to_console(ac, u"returncode 0" )
    #lib_cm.message_write_to_console(ac, p[0] )
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])
    
    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    cmd_output_1 = string.find( p[1], "100%")
    lib_cm.message_write_to_console(ac, cmd_output_1)
    
    if cmd_output_1 != -1:
        log_message = u"Sprache komprimiert... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[3] 
            + u"100% beim Komprimieren nicht erreicht...", 
            "x", "write_also_to_console")
        return None

def check_lenght(source_file):
    """Laenge der News ermitteln"""
    lib_cm.message_write_to_console(ac, u"Laenge der News ermitteln")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[4].encode(ac.app_encode_out_strings )
    #cmd = "soxi"
    lib_cm.message_write_to_console(ac, cmd )
    # subprozess starten
    try:
        p = subprocess.Popen([cmd, u"-d", source_file], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[4] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0" )
    lib_cm.message_write_to_console(ac, p[0] )
    
    log_message = u"Laenge: " + p[0]
    db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
    return p[0]

def mix_bed():
    """Soundbed drunter legen"""
    lib_cm.message_write_to_console(ac, u"Soundbed drunter legen")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[3].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    lib_cm.message_write_to_console(ac, cmd)
    news_file = lib_cm.extract_filename(ac, db.ac_config_1[6]).replace(".mp3", "_comp.wav")
    news_file_temp = news_file.replace("_comp.wav", "_temp.wav")
    # subprozess starten
    #silence 1 0.1 1% reverse silence 1 0.1 1% reverse
    try:
        #p = subprocess.Popen([cmd, u"-S", u"-m", u"News_ext_Automation_Bed_trimmed.wav", u"mXm_News_12.wav", 
        p = subprocess.Popen([cmd, u"-S", u"-m", ac.app_file_bed_trim, news_file, 
            news_file_temp], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[6] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return 

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0] )
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1] )
    
    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    cmd_output_1 = string.find( p[1], "100%" )
    #cmd_output_1 = string.find( p[1], "written" )
    #lib_cm.message_write_to_console(ac, cmd_output )
    #lib_cm.message_write_to_console(ac, cmd_output_1 )
    if cmd_output_1 != -1:
        log_message = u"Bed mixed... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[6] 
            + u"100% beim Mixen nicht erreicht...", 
            "x", "write_also_to_console")
        return None

def concatenate_media(filename):
    """mp3-Files kombinieren"""
    lib_cm.message_write_to_console(ac, u"mp3-Files kombinieren")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[3].encode(ac.app_encode_out_strings)
    #cmd = "sox"
    news_file = lib_cm.extract_filename(ac, db.ac_config_1[6]).replace("mp3", "wav")
    news_file_temp = news_file.replace(".wav", "_temp.wav")
    source_path = lib_cm.check_slashes(ac, db.ac_config_1[9])
    #source_file_intro = source_path + "News_ext_Automation_Intro.wav"
    source_file_intro = source_path + ac.app_file_intro
    #source_file_closer = source_path + "News_ext_Automation_Closer.wav"
    source_file_closer = source_path + ac.app_file_closer
    dest_path = lib_cm.check_slashes(ac, db.ac_config_1[10])
    dest_path_file = dest_path + filename
    lib_cm.message_write_to_console(ac, cmd )
    #c_source_file = path_file.encode(ac.app_encode_out_strings )
    #lib_cm.message_write_to_console(ac, c_source_file )
    # subprozess starten
    #ffmpeg -i input1.mp3 -i input2.mp3 -filter_complex amerge -c:a libmp3lame -q:a 4 output.mp3
    try:
        p = subprocess.Popen([cmd, u"-S", 
            source_file_intro, news_file_temp, source_file_closer, 
            u"-C" u"192.2", dest_path_file], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[7] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0" )
    lib_cm.message_write_to_console(ac, p[0] )
    lib_cm.message_write_to_console(ac, u"returncode 1" )
    lib_cm.message_write_to_console(ac, p[1] )
    
    # erfolgsmeldung suchen, wenn nicht gefunden: -1
    cmd_output_1 = string.find( p[1], "100%" )
    if cmd_output_1 != -1:
        log_message = u"Externe News bearbeitet und in Play-Out bereitgestellt... "
        db.write_log_to_db_a(ac, log_message, "i", "write_also_to_console")
        return "ok"
    else:
        db.write_log_to_db_a(ac, ac.app_errorslist[7] 
            + u"100% beim Kombinieren nicht erreicht...", 
            "x", "write_also_to_console")
        return None

def add_id3(sendung_data):
    """id3-Tag in mp3-File schreiben"""
    lib_cm.message_write_to_console(ac, u"id3-Tag in mp3-File schreiben")
    # damit die uebergabe der befehle richtig klappt,
    # muessen alle cmds im richtigen zeichensatz encoded sein
    cmd = db.ac_config_1[5].encode(ac.app_encode_out_strings)
    #cmd = "id3v2"
    dest_path = lib_cm.check_slashes(ac, db.ac_config_1[10])
    dest_path_file = dest_path + sendung_data[12]
    c_author = sendung_data[15].encode(ac.app_encode_out_strings) + " " + sendung_data[16].encode(ac.app_encode_out_strings)
    c_title = sendung_data[11].encode(ac.app_encode_out_strings)
    lib_cm.message_write_to_console(ac, cmd)
    # subprozess starten
    try:
        p = subprocess.Popen([cmd, u"-a", 
            c_author, u"-t", c_title, 
            dest_path_file], 
            stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
    except Exception, e:
        log_message = ac.app_errorslist[8] + u": %s" % str(e)
        db.write_log_to_db_a(ac, log_message, "x", "write_also_to_console")
        return None

    lib_cm.message_write_to_console(ac, u"returncode 0")
    lib_cm.message_write_to_console(ac, p[0])
    lib_cm.message_write_to_console(ac, u"returncode 1")
    lib_cm.message_write_to_console(ac, p[1])
    
    # error?
    cmd_output_1 = p[1]
    if cmd_output_1 !="":
        lib_cm.message_write_to_console(ac, cmd_output_1)
        db.write_log_to_db_a(ac, ac.app_errorslist[8], "x", "write_also_to_console")
        return None
    else:
        log_message = u"ID3-Tags in Externe News geschrieben... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        return "ok"

def collect_garbage(garbage_counter):
    """ aufraeumen """
    if garbage_counter >= 2:
        temp_file = lib_cm.extract_filename(ac, db.ac_config_1[6])
        delete_temp_ok = lib_cm.erase_file_a(ac, db, temp_file, 
            u"Externe News-mp3-Datei geloescht ")
    
    if garbage_counter >= 2:
        temp_file_1 = temp_file.replace("mp3", "wav")
        delete_temp_ok = lib_cm.erase_file_a(ac, db, temp_file_1, 
            u"Externe News-wav-Datei geloescht ")
    
    if garbage_counter >= 3:
        temp_file_2 = temp_file_1.replace(".wav", "_comp.wav")
        delete_temp_ok = lib_cm.erase_file_a(ac, db, temp_file_2, 
            u"Externe News-comp-Datei geloescht ")

    if garbage_counter >= 4:
        delete_temp_ok = lib_cm.erase_file_a(ac, db, ac.app_file_bed_trim, 
            u"Externe News-Bed-Datei geloescht ")
    
    if garbage_counter == 5:
        temp_file_2 = temp_file_1.replace(".wav", "_temp.wav")
        delete_temp_ok = lib_cm.erase_file_a(ac, db, temp_file_2, 
            u"Externe News-temp-Datei geloescht ")

def lets_rock():
    """Hauptfunktion """
    print "lets_rock "
    
    sendung_data = load_sg()
    if sendung_data is None:
        return
    
    download_ok = fetch_media()
    if download_ok is None:
        return
        
    trim_ok = trim_silence()
    if trim_ok is None:
        collect_garbage(2)
        return
    
    compand_ok = compand_voice()
    if compand_ok is None:
        collect_garbage(2)
        return
    
    source_file = lib_cm.extract_filename(ac, db.ac_config_1[6]).replace("mp3", "wav")
    lenght_news = check_lenght(source_file)
    if lenght_news is None:
        return
    
    trim_bed_ok = trim_bed(lenght_news)
    if trim_bed_ok is None:
        collect_garbage(2)
        return

    mix_bed_ok = mix_bed()
    if mix_bed_ok is None:
        collect_garbage(3)
        return
        
    concatenate_ok = concatenate_media(sendung_data[0][12])
    if concatenate_ok is None:
        collect_garbage(4)
        return
    
    id3_ok = add_id3(sendung_data[0])
    if id3_ok is None:
        return
    
    dest_path = lib_cm.check_slashes(ac, db.ac_config_1[10])
    source_file = dest_path + sendung_data[0][12]
    lenght_news = check_lenght(source_file)
    if lenght_news is None:
        return
    
    # Laenge eintragen
    sql_command = ("UPDATE SG_HF_MAIN "
        + "SET SG_HF_DURATION='" + lenght_news[0:8] + "' "
        + "WHERE SG_HF_ID='" + str(sendung_data[0][0])+"'")
    db_ok = db.exec_sql(ac, db, sql_command)
    if db_ok is None:
        db.write_log_to_db_a(ac, ac.app_errorslist[9], "x", 
            "write_also_to_console")
    else:
        log_message = u"Laenge der externen News in Buchung aktualisiert... "
        db.write_log_to_db_a(ac, log_message, "k", "write_also_to_console")
        
    collect_garbage(5)

if __name__ == "__main__":
    db = lib_cm.dbase()
    ac = app_config()
    print  "lets_work: " + ac.app_desc 
    # losgehts
    db.write_log_to_db(ac, ac.app_desc + u" gestartet", "a")
    # Config_Params 1
    db.ac_config_1 = db.params_load_1(ac,  db)
    if db.ac_config_1 is not None:
        param_check = lib_cm.params_check_1(ac, db) 
        # alles ok: weiter
        if param_check is not None: 
            if db.ac_config_1[1] == "on":
                lets_rock()
            else:
                db.write_log_to_db_a(ac, ac.app_desc + " ausgeschaltet", "e", 
                    "write_also_to_console")

    # fertsch
    db.write_log_to_db(ac,  ac.app_desc + u" gestoppt", "s")
    print "lets_lay_down"
    sys.exit()
