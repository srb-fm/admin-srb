<?php

/** 
* Sendung Details bearbeiten 
*
* PHP version 5
*
* @category Intranetsite
* @package  Admin-SRB
* @author   Joerg Sorge <joergsorge@googel.com>
* @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link     http://srb.fm
*/

require "../../cgi-bin/admin_srb_libs/lib_db.php";
require "../../cgi-bin/admin_srb_libs/lib.php";
require "parts/audio_libs/mp3_file_edits.php";
require "../../cgi-bin/admin_srb_libs/lib_sess.php";

$message = "";
$error_message = "";
$action_ok = false;

// check action
if ( isset($_GET['action']) ) {
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action']) ) { 
	$action = $_POST['action']; 
	$action_ok = true;
}

if ( isset($_GET['file_exist']) ) { 
	$file_exist = $_GET['file_exist'];
} else {
	$file_exist	= "no";
}

if ( $action_ok == true ) {	
	if ( isset($_GET['ad_id']) ) {	
		$id_ad = $_GET['ad_id'];
	}
	if ( isset($_POST['ad_id']) ) {
		$id_ad = $_POST['ad_id'];
	}

	if ( filter_var($id_ad, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000))) ) { 
		switch ( $action ) {

		case "new":
			$message =   "Sendung buchen";
			$form_input_type = "add"; //form action einstellen
			$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id_ad);
			$tbl_row_sg = db_query_sg_display_item_1(0);
			// heutiges Datum und Zeit vorbelegen
			$tbl_row_sg->SG_HF_TIME = date("Y-m-d")." 05:00:00";
			break;

		case "repeat_new":
			// Sendung laden und spaeter neue ID verpassen
			$message =   "Sendung wiederholen";
			$form_input_type = "repeat_add"; //form action einstellen
			$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id_ad);
			$tbl_row_sg = db_query_sg_display_item_1($_GET['sg_id']);

			// kann kein first sein				
			$tbl_row_sg->SG_HF_FIRST_SG = "F";	
			// On_Air zurueckschalten				
			$tbl_row_sg->SG_HF_ON_AIR = "F";
			// in der Regel ist eine wh vorproduziert				
			$tbl_row_sg->SG_HF_SOURCE_ID= "03";
			// und kann nicht live sein				
			$tbl_row_sg->SG_HF_LIVE = "F";
			// Dateiname leeren und Quelle auf VP wenn WH von livesendung
			if ( $tbl_row_sg->SG_HF_CONT_FILENAME == "Keine_Audiodatei" ) {
				// wiederholungen von livesendungen haben diesen eintrag, spaeter durch dateiname ersetzen	
				$tbl_row_sg->SG_HF_CONT_FILENAME = "";	
			}	
			break;

		case "dublikate_new":
			// Sendung laden und neue ID verpassen
			$message =   "Sendung duplizieren";
			$form_input_type = "add"; //form action einstellen
			$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id_ad);
			$tbl_row_sg = db_query_sg_display_item_1($_GET['sg_id']);

			// On_Air zurueckschalten				
			$tbl_row_sg->SG_HF_ON_AIR = "F";
			// nicht fuer austausch				
			$tbl_row_sg->SG_HF_VP_OUT = "F";
			// Dateiname leeren wenn kein http-stream
			if ( substr($tbl_row_sg->SG_HF_CONT_FILENAME, 0, 4) != "http") {
				$tbl_row_sg->SG_HF_CONT_FILENAME = "";
			}
			break;

		case "repeat_add":
			$tbl_fields_sg = "SG_HF_ID, SG_HF_CONTENT_ID, SG_HF_TIME, SG_HF_SOURCE_ID, ";
			$tbl_fields_sg .="SG_HF_INFOTIME, SG_HF_MAGAZINE, SG_HF_PODCAST, SG_HF_VP_OUT,";
			$tbl_fields_sg .= "SG_HF_REPEAT_PROTO, SG_HF_FIRST_SG, SG_HF_ON_AIR, SG_HF_DURATION ";

			// check or load values 
			$main_id_sg = db_generator_main_id_load_value();
			$tbl_values_sg = $main_id_sg.", ".$_POST['sg_content_id'].", ";
			$tbl_values_sg .= " '".get_date_format_sql($_POST['form_sg_date'])." ".$_POST['form_sg_time']."', ";
			// lookups
			$tbl_values_sg .= "'".db_query_load_id_by_value("SG_HF_SOURCE", "SG_HF_SOURCE_DESC", rtrim($_POST['form_sg_source']))."', ";
			// checkboxen
			// SG_HV_Live wird in db-trigger false gesetzt
			if ( isset($_POST['form_sg_infotime']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_infotime']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_magazine']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_magazine']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_podcast']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_podcast']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_vp_out']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_vp_out']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_repeat_proto']) )	{ 
				$tbl_values_sg .= "'".$_POST['form_sg_repeat_proto']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}

			$tbl_values_sg .= "'F', "; // FIRST_SG kann hier nur false sein;
			if ( isset($_POST['form_sg_on_air']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_on_air']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			// normal
			$tbl_values_sg .= "'".$_POST['form_sg_duration']."' ";
			// und ab into
			db_query_add_item("SG_HF_MAIN", $tbl_fields_sg, $tbl_values_sg);

			// content filename ergaenzen wenn noetig
			// bei wh von livesendungen zu norm wh wird filename generiert, es sei denn er ist schon eingetragen
			$source_id = db_query_load_id_by_value("SG_HF_SOURCE", "SG_HF_SOURCE_DESC", rtrim($_POST['form_sg_source']));
			if ( $_POST['form_sg_filename'] =="" ) { 
				// wenn play-out dann filename, sonst nichz					
				if ( $source_id  == "03" ) { 
					$file_ext = ".".trim(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'Sendung_Filetype'"));
					$tbl_fields_values_sg_cont = "SG_HF_CONT_FILENAME='".$_POST['sg_content_id']."_".replace_umlaute_sonderzeichen($_POST['ad_name'])."_".replace_umlaute_sonderzeichen(sg_extract_stichwort_for_filename($_POST['form_sg_stichworte'])).$file_ext."' ";
					//$value_filename = $_POST['sg_content_id']."_".replace_umlaute_sonderzeichen( $_POST['ad_name'])."_".replace_umlaute_sonderzeichen( sg_extract_stichwort_for_filename( $_POST['form_sg_stichworte'] )).$file_ext;
				} else {
					$tbl_fields_values_sg_cont = "SG_HF_CONT_FILENAME='Keine_Audiodatei' ";
				}		
			} else {  
				$tbl_fields_values_sg_cont = "SG_HF_CONT_FILENAME='".trim($_POST['form_sg_filename'])."' ";
			}

			if ( $_POST['form_sg_filename'] =="Keine_Audiodatei" ) { 
				// wenn play-out dann filename, sonst nichz					
				if ( $source_id  == "03" ) { 
					$file_ext = ".".trim(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'Sendung_Filetype'"));
					$tbl_fields_values_sg_cont = "SG_HF_CONT_FILENAME='".$_POST['sg_content_id']."_".replace_umlaute_sonderzeichen($_POST['ad_name'])."_".replace_umlaute_sonderzeichen(sg_extract_stichwort_for_filename($_POST['form_sg_stichworte'])).$file_ext."' ";
					//$value_filename = $_POST['sg_content_id']."_".replace_umlaute_sonderzeichen( $_POST['ad_name'])."_".replace_umlaute_sonderzeichen( sg_extract_stichwort_for_filename( $_POST['form_sg_stichworte'] )).$file_ext;		
				}
			} else {  
				$tbl_fields_values_sg_cont = "SG_HF_CONT_FILENAME='".$_POST['form_sg_filename']."' ";
				//$value_filename = $_POST['form_sg_filename'];	
			}

			// hier ist nicht mit sonderzeichen zu rechnen, deshalb einfaches update	
			db_query_update_item_a("SG_HF_CONTENT", $tbl_fields_values_sg_cont, "SG_HF_CONT_ID =".$_POST['sg_content_id']);
			header("Location: sg_hf_detail.php?action=display&sg_id=".$main_id_sg);
			exit;
			break;

		case "add":
			// Sg_main
			// fields
			$tbl_fields_sg = "SG_HF_ID, SG_HF_CONTENT_ID, SG_HF_TIME, SG_HF_SOURCE_ID, ";
			$tbl_fields_sg .= "SG_HF_INFOTIME,  SG_HF_MAGAZINE, SG_HF_PODCAST, SG_HF_VP_OUT, SG_HF_REPEAT_PROTO, ";
			$tbl_fields_sg .= "SG_HF_LIVE, SG_HF_FIRST_SG, SG_HF_ON_AIR, SG_HF_DURATION ";

			// check or load values 
			$main_id_sg = db_generator_main_id_load_value();
			$main_id_sg_cont = db_generator_main_id_load_value();				
			$tbl_values_sg = $main_id_sg.", ".$main_id_sg_cont.", ";
			$tbl_values_sg .= " '".get_date_format_sql($_POST['form_sg_date'])." ".$_POST['form_sg_time']."', ";
			// lookups
			$source_id = db_query_load_id_by_value("SG_HF_SOURCE", "SG_HF_SOURCE_DESC", $_POST['form_sg_source']);
			$tbl_values_sg.= "'".$source_id."', ";	
			// checkboxen
			if ( isset($_POST['form_sg_infotime']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_infotime']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_magazine']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_magazine']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_podcast']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_podcast']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_vp_out']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_vp_out']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_repeat_proto']) )	{ 
				$tbl_values_sg .= "'".$_POST['form_sg_repeat_proto']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			if ( isset($_POST['form_sg_live']) )	{ 
				$tbl_values_sg .= "'".$_POST['form_sg_live']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}

			$tbl_values_sg .= "'T', "; // FIRST_SG kann nur true sein;
			if ( isset($_POST['form_sg_on_air']) ) { 
				$tbl_values_sg .= "'".$_POST['form_sg_on_air']."', "; 
			} else { 
				$tbl_values_sg .= "'F', " ;
			}
			// normal
			$tbl_values_sg .= "'".$_POST['form_sg_duration']."' ";
			// und ab into
			db_query_add_item("SG_HF_MAIN", $tbl_fields_sg, $tbl_values_sg);

			// Sg__cont
			// fields, Anzahl: 12
			$tbl_fields_sg_cont = "SG_HF_CONT_ID, SG_HF_CONT_SG_ID, SG_HF_CONT_AD_ID, SG_HF_CONT_GENRE_ID, SG_HF_CONT_SPEECH_ID, SG_HF_CONT_TEAMPRODUCTION, ";
			$tbl_fields_sg_cont .= "SG_HF_CONT_TITEL, SG_HF_CONT_UNTERTITEL, SG_HF_CONT_STICHWORTE, SG_HF_CONT_REGIEANWEISUNG, SG_HF_CONT_FILENAME, SG_HF_CONT_WEB ";
			// check or load values
			$tbl_values_sg_cont = $main_id_sg_cont.", ".$main_id_sg.", ".$id_ad.", ";
			$value_genre = db_query_load_id_by_value("SG_GENRE", "SG_GENRE_DESC", $_POST['form_sg_genre']);
			$value_speech = db_query_load_id_by_value("SG_SPEECH", "SG_SPEECH_DESC", $_POST['form_sg_speech']);

			// checkboxen	
			if ( isset($_POST['form_sg_teamprod']) ) { 
				$value_teamprod= $_POST['form_sg_teamprod']; 
			} else { 
				$value_teamprod = "F" ;
			}				

			// normal				
			if ( $_POST['form_sg_filename'] =="" ) {
				// wenn play-out dann filename, sonst nichz					
				if ( $source_id  == "03" ) { 
					$file_ext = ".".trim(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'Sendung_Filetype'"));
					//$tbl_values_sg_cont .= "'".$main_id_sg_cont."_".replace_umlaute_sonderzeichen( $_POST['ad_name'])."_".replace_umlaute_sonderzeichen( sg_extract_stichwort_for_filename( $_POST['form_sg_stichworte'] )).$file_ext."', ";
					$value_filename = $main_id_sg_cont."_".replace_umlaute_sonderzeichen($_POST['ad_name'])."_".replace_umlaute_sonderzeichen(sg_extract_stichwort_for_filename($_POST['form_sg_stichworte'])).$file_ext;
				} else {
					//$tbl_values_sg_cont .= "'Keine_Audiodatei', ";
					$value_filename = "Keine_Audiodatei"; 
				}

			} else {  
				//$tbl_values_sg_cont .= "'".$_POST['form_sg_filename']."', ";
				$value_filename = trim($_POST['form_sg_filename']);
			}

			$a_values = array($main_id_sg_cont, $main_id_sg, $id_ad,
    					$value_genre, $value_speech,	$value_teamprod,
    					$_POST['form_sg_titel'], $_POST['form_sg_untertitel'], $_POST['form_sg_stichworte'], $_POST['form_sg_regie'], 
    					$value_filename,	$_POST['form_sg_web']); 	

			$insert_ok = db_query_add_item_b("SG_HF_CONTENT", $tbl_fields_sg_cont, "?,?,?,?,?,?,?,?,?,?,?,? ", $a_values);

			// sendung neu, user active schalten in ad_main
			$tbl_ad_fields_values = "AD_USER_OK_AKTIV='T'";
			db_query_update_item_a("AD_MAIN", $tbl_ad_fields_values, "AD_ID =".$_POST['ad_id']);
			// Audiodatei nicht pruefen lassen in sg_hf_detail     
			header("Location: sg_hf_detail.php?action=display&sg_id=".$main_id_sg."&check_file=no");
			exit;
			break;

		case "edit":
			$message =   "Sendung-Details bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id_ad);
			$tbl_row_sg = db_query_sg_display_item_1($_GET['sg_id']);
			break;
			
		case "editor_new":
			// reg editor-ad-id
			$tbl_ad_fields_values = "SG_HF_CONT_EDITOR_AD_ID=".$_GET['ad_id'];
			db_query_update_item_a("SG_HF_CONTENT", $tbl_ad_fields_values, "SG_HF_CONT_ID = ".$_GET['sg_cont_id']);
			header("Location: sg_hf_detail.php?action=display&sg_id=".$_GET['sg_id']."&error_message=".$error_message);
			exit;
			break;

		case "update":
			// sg
			$c_audio_length = $_POST['form_sg_duration'];
			//$c_audio_length_s = get_seconds_from_hms ( $c_audio_length );
			// check length if 
			if ( isset($_POST['form_sg_read_audio_length'])) {
				// Pfade fuer audios aus einstellungen
				//$tbl_row_config = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'INTRA_Sendung_HF_1'");
				$tbl_row_config = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'INTRA_Sendung_HF'");
				$tbl_row_config_serv = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings'");
				// are we on server-line A or B?
				if ( $tbl_row_config_serv->USER_SP_PARAM_3 == $_SERVER['SERVER_NAME'] ) {
					$tbl_row_config_A = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_a_A'");
					$tbl_row_config_B = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_b_A'");
				}
				if ( $tbl_row_config_serv->USER_SP_PARAM_4 == $_SERVER['SERVER_NAME'] ) {
					$tbl_row_config_A = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_a_B'");
					$tbl_row_config_B = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_b_B'");
				}

				// Pfad und Dateiname 
				if ( isset($_POST['form_sg_infotime']) or isset($_POST['form_sg_magazine']) ) {
					//$remotefilename = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config->USER_SP_PARAM_1.$_POST['form_sg_filename'];
					//$remotefilename_archiv = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config->USER_SP_PARAM_2.$_POST['form_sg_filename'];	
					//$patfilename = $tbl_row_config->USER_SP_PARAM_5.$_POST['form_sg_filename'];
					$remotefilename = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_6.$_POST['form_sg_filename'];
					$remotefilename_archiv = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_8.$_POST['form_sg_filename'];	
					$patfilename = $tbl_row_config_A->USER_SP_PARAM_5.$_POST['form_sg_filename'];
				} else {
					//$remotefilename = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config->USER_SP_PARAM_3.$_POST['form_sg_filename'];
					//$remotefilename_archiv = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config->USER_SP_PARAM_4.$_POST['form_sg_filename'];
					//$patfilename = $tbl_row_config->USER_SP_PARAM_7.$_POST['form_sg_filename'];
					$remotefilename = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_7.$_POST['form_sg_filename'];
					$remotefilename_archiv = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_1.$_POST['form_sg_filename'];
					$patfilename = $tbl_row_config_A->USER_SP_PARAM_6.$_POST['form_sg_filename'];
					// Sendung in Infotime
					//$remotefilename_bc_in_it = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config->USER_SP_PARAM_1.$_POST['form_sg_filename'];				
					//$remotefilename_bc_in_it_ar = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config->USER_SP_PARAM_2.$_POST['form_sg_filename'];
					$remotefilename_bc_in_it = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_6.$_POST['form_sg_filename'];				
					$remotefilename_bc_in_it_ar = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_8.$_POST['form_sg_filename'];
				}
				// wenn mediafile vorhanden, lange pruefen und uebernehmen
				//$c_audio_length = read_mp3_length ( $remotefilename );
				//$c_audio_length = read_length_write_tag ( $remotefilename, $_POST['form_sg_filename'], "\\\\Sende-server-hf\\media_hf_play_out\\HF_Play_Out_Infotime" );

				// mp3Gain pruefen wenn gewuenscht
				if ( isset($_POST['form_sg_mp3gain'])) {
					$set_mp3gain = "yes"; 
				} else {	
					$set_mp3gain = "no";	
				} 				
				//$c_audio_length = read_length_write_tag($remotefilename, $patfilename, replace_umlaute_sonderzeichen($_POST['form_ad_name']), replace_umlaute_sonderzeichen($_POST['form_sg_titel']), $c_audio_length, $set_mp3gain);
				$c_audio_length = read_length_write_tag($remotefilename, $patfilename, $_POST['id3_artist'], $_POST['form_sg_titel'], $c_audio_length, $set_mp3gain);
				$c_audio_length = get_time_in_hms($c_audio_length);
				if ( $c_audio_length == "00:00:00" ) { 
					$error_message .= "Fehler beim Hoerdauerabgleich "; 
				}						
			} // Ende Laenge pruefen wenn gewuenscht

			// mp3Gain pruefen wenn gewuenscht
			if ( isset($_POST['form_sg_mp3gain'])) {
				$set_mp3gain = "yes"; 
			} else {	 
				$set_mp3gain = "no";	
			} // Ende mp3Gain wenn gewuenscht					

			// load values
			$tbl_fields_values_sg = "SG_HF_TIME= '".get_date_format_sql($_POST['form_sg_date'])." ".$_POST['form_sg_time']."', ";

			// lookups
			$source_id = db_query_load_id_by_value("SG_HF_SOURCE", "SG_HF_SOURCE_DESC", $_POST['form_sg_source']);
			$tbl_fields_values_sg .= "SG_HF_SOURCE_ID='".$source_id."', ";
			// checkboxen
			if ( isset($_POST['form_sg_infotime'])) { 
				$tbl_fields_values_sg .= "SG_HF_INFOTIME='".$_POST['form_sg_infotime']."', "; 
			} else { 
				$tbl_fields_values_sg .= "SG_HF_INFOTIME='F', " ;
			}
			if ( isset($_POST['form_sg_magazine'])) { 
				$tbl_fields_values_sg .= "SG_HF_MAGAZINE='".$_POST['form_sg_magazine']."', "; 
			} else { 
				$tbl_fields_values_sg .= "SG_HF_MAGAZINE='F', " ;
			}
			if ( isset($_POST['form_sg_podcast']) ) { 
				$tbl_fields_values_sg .= "SG_HF_PODCAST='".$_POST['form_sg_podcast']."', "; 
			} else { 
				$tbl_fields_values_sg .= "SG_HF_PODCAST='F', " ;
			}
			if ( isset($_POST['form_sg_vp_out']) ) { 
				$tbl_fields_values_sg .= "SG_HF_VP_OUT='".$_POST['form_sg_vp_out']."', "; 
			} else { 
				$tbl_fields_values_sg .= "SG_HF_VP_OUT='F', " ;
			}
			if ( isset($_POST['form_sg_on_air'])) { 
				$tbl_fields_values_sg .= "SG_HF_ON_AIR='".$_POST['form_sg_on_air']."', "; 
			} else { 
				$tbl_fields_values_sg .= "SG_HF_ON_AIR='F', " ;
			}
			if ( isset($_POST['form_sg_live'])) { 
				$tbl_fields_values_sg .= "SG_HF_LIVE='".$_POST['form_sg_live']."', "; 
			} else { 
				$tbl_fields_values_sg .= "SG_HF_LIVE='F', " ;
			}
			if ( isset($_POST['form_sg_repeat_proto'])) { 
				$tbl_fields_values_sg .= "SG_HF_REPEAT_PROTO='".$_POST['form_sg_repeat_proto']."', "; 
			} else { 
				$tbl_fields_values_sg .= "SG_HF_REPEAT_PROTO='F', " ;
			}
			// normal
			$tbl_fields_values_sg .= "SG_HF_DURATION='".$c_audio_length."' ";	
			db_query_update_item_a("SG_HF_MAIN", $tbl_fields_values_sg, "SG_HF_ID =".$_POST['sg_id']);

			// sg_cont
			// fields
			$tbl_fields_sg_cont = "SG_HF_CONT_AD_ID=?, SG_HF_CONT_GENRE_ID=?, "
								."SG_HF_CONT_SPEECH_ID=?, SG_HF_CONT_TEAMPRODUCTION=?, "
								."SG_HF_CONT_TITEL=?, SG_HF_CONT_UNTERTITEL=?, "
								."SG_HF_CONT_STICHWORTE=?, SG_HF_CONT_REGIEANWEISUNG=?, "
								."SG_HF_CONT_FILENAME=?, SG_HF_CONT_WEB=? ,"
								."SG_HF_CONT_EDITOR_AD_ID=?";
			// load values
			// lookups				
			$value_genre = db_query_load_id_by_value("SG_GENRE", "SG_GENRE_DESC", $_POST['form_sg_genre']);
			$value_speech = db_query_load_id_by_value("SG_SPEECH", "SG_SPEECH_DESC", $_POST['form_sg_speech']);

			if ( $_POST['form_sg_filename'] =="" ) { 
				// wenn play-out dann filename, sonst nichz					
				if ( $source_id  == "03" ) { 
					$file_ext = ".".trim(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'Sendung_Filetype'"));
					//$tbl_fields_values_sg_cont .= "SG_HF_CONT_FILENAME='".$_POST['sg_content_id']."_".replace_umlaute_sonderzeichen( $_POST['ad_name'])."_".replace_umlaute_sonderzeichen( sg_extract_stichwort_for_filename( $_POST['form_sg_stichworte'] )).$file_ext."', ";
					$value_filename = $_POST['sg_content_id']."_".replace_umlaute_sonderzeichen($_POST['ad_name'])."_".replace_umlaute_sonderzeichen(sg_extract_stichwort_for_filename($_POST['form_sg_stichworte'])).$file_ext;
				} else {
					//$tbl_fields_values_sg_cont .= "SG_HF_CONT_FILENAME='Keine_Audiodatei', ";
					$value_filename = "Keine_Audiodatei";
				}

			} else {  
				//$tbl_fields_values_sg_cont .= "SG_HF_CONT_FILENAME='".$_POST['form_sg_filename']."', ";
				$value_filename = trim($_POST['form_sg_filename']);	
			}

			// checkboxen
			if ( isset($_POST['form_sg_teamprod']) ) { 
				$value_teamprod= $_POST['form_sg_teamprod']; 
			} else { 
				$value_teamprod = "F" ;
			}

			$a_values = array($_POST['ad_id'], $value_genre, $value_speech, 
					$value_teamprod, $_POST['form_sg_titel'], 
					$_POST['form_sg_untertitel'], $_POST['form_sg_stichworte'], 
					$_POST['form_sg_regie'], $value_filename,	$_POST['form_sg_web'],
					$_POST['sg_editor_ad_id']);

    		db_query_update_item_b("SG_HF_CONTENT", $tbl_fields_sg_cont, "SG_HF_CONT_ID =".$_POST['sg_content_id'], $a_values);
	
			// sendung geaendert, user active schalten in ad_main
			$tbl_ad_fields_values = "AD_USER_OK_AKTIV='T'";
			db_query_update_item_a("AD_MAIN", $tbl_ad_fields_values, "AD_ID =".$_POST['ad_id']);
			header("Location: sg_hf_detail.php?action=display&sg_id=".$_POST['sg_id']."&error_message=".$error_message);
			exit;
			break;

		} //endswitch
	} else {
		$message = "Keine ID. Nichts zu tun..... "; 
	}
	// read help
	$sg_help_1 = trim(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_TEXT", "USER_SP_SPECIAL = 'Sendung_Anmeldung_Hilfe_1'"));
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Sendung-Edit</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">

	<style type="text/css">@import url("../parts/style/style_srb_3.css");    </style>
	<style type="text/css">@import url("../parts/style/style_srb_jq_2.css");  </style>
	<style type="text/css">@import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css">@import url("../parts/jquery/jquery_form_validator/css/validationEngine.jquery.css");    </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine-ge.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine.js"></script>	
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	
	<script type="text/javascript">
	$(document).ready(function() {	
		$("#sg_edit_form").validationEngine();
		check_free_time();
		})
	</script>
	
	<script type="text/javascript">
	function chk_formular() {
		if (document.getElementById("chk_infotime").checked == true && document.getElementById("chk_magazine").checked == true ) {
			alert("Bitte entweder Infotime oder Magazin einstellen!")
			return false;
		}
		if (document.getElementById("check_id3") ) {
			if ( document.getElementById("check_id3").checked == true ) {
	    		<!--check length take time-->
				document.getElementById('wait').style.display = 'block';
				document.getElementById('save_button').style.display = 'none';
				return true;
			}		
		} else { 
			switch( document.getElementById("sg_duration").value ) {
				case "00:01:00":		  
		    	<!--predefined length not changed-->
				alert("Länge der Sendung beträgt 00:01:00 (hh:mm:ss)! \nIst das korrekt?");
				return true;
				break;
				
				case "00:00:00":		  
		    	<!--maybe incorrect-->
				alert("Länge der Sendung beträgt 00:00:00 (hh:mm:ss)! \nIst das korrekt?");
				return true;
				break;
			}
		}
	}
	</script>
	<script type="text/javascript">
	function check_free_time() {
		// wird gerufen von onChange: sg_time/ sg_date(bei manuellem edit)/ 
		// chk_infotime und chk_magazine/ kalender_sendung/ document_ready(direkter funktionsaufruf)
		// zeit und id in eine vari, uebergabe an ajax-validation
		var  c_timestamp;
		var mag;
		// = document.getElementById("chkmagazine").checked;
		var it;
		 		
		if (document.getElementById("chk_infotime").checked == true) {it="T";} else {it="F";}
		if (document.getElementById("chk_magazine").checked == true) {mag="T";} else {mag="F";}
		c_timestamp = document.form1.form_sg_date.value + " " + document.form1.form_sg_time.value;
		document.form1.sg_timestamp.value = c_timestamp + " " + document.form1.sg_id.value + " " + document.form1.sg_duration.value + " " + it + " " + mag;
		//alert(mag);
		$.validationEngine.loadValidation('#sg_timestamp');
	}
	</script>
	<script type="text/javascript">
	function delete_editor() {
		document.form1.sg_editor_ad_id.value = 0;
		document.getElementById("editor_name").innerHTML = "<div class='blink'> Redakteur wird gelöscht </div>";
		//alert("Redakteur ");
	}
	</script>
</head>
<body>
<?php
echo "<div class='column_right_1'>";
echo "<div class='head_item_right'>"; 
echo $message; 
if ( isset($tbl_row_sg->SG_HF_FIRST_SG )) {
	html_sg_state(rtrim($tbl_row_sg->SG_HF_FIRST_SG), rtrim($tbl_row_sg->SG_HF_ON_AIR), rtrim($tbl_row_sg->SG_HF_CONT_FILENAME));
}
echo "</div>";	
echo "<div class='content'>";			
if ( $action_ok == false ) { 
	return;
}

if ( ! isset($tbl_row_ad->AD_ID)) { 
	echo "Fehler bei Abfrage Adresse!"; 
	return;
}
if ( ! isset($tbl_row_sg->SG_HF_ID)) { 
	echo "Fehler bei Abfrage Sendung!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) {
	// if link to editor
	if ( $tbl_row_sg->SG_HF_CONT_EDITOR_AD_ID != "0") {	
		$tbl_row_ad_editor = db_query_display_item_1("AD_MAIN", "AD_ID = " .$tbl_row_sg->SG_HF_CONT_EDITOR_AD_ID);
		$editor_ad = $tbl_row_ad_editor->AD_VORNAME." " .$tbl_row_ad_editor->AD_NAME.", ".$tbl_row_ad_editor->AD_ORT;
	} else {
		$editor_ad = "Kein Redakteur zugeordnet";	
	}
	echo "<form name='form1' id='sg_edit_form' action='sg_hf_edit.php' method='POST' onsubmit='return chk_formular()' enctype='application/x-www-form-urlencoded'>";
	echo "<input type='hidden' name='action' value='".$form_input_type."'>";
	echo "<input type='hidden' name='sg_id' value='".$tbl_row_sg->SG_HF_ID."'>";	
	echo "<input type='hidden' name='sg_content_id' value='".$tbl_row_sg->SG_HF_CONTENT_ID."'>";	
	echo "<input type='hidden' name='ad_id' value='".$tbl_row_ad->AD_ID."'>";
	echo "<input type='hidden' name='ad_name' value='".trim($tbl_row_ad->AD_NAME)."'>";
	echo "<input type='hidden' name='id3_artist' value='".trim($tbl_row_ad->AD_VORNAME)." ".trim($tbl_row_ad->AD_NAME)."'>";
	echo "<input type='hidden' name='sg_editor_ad_id' value='".$tbl_row_sg->SG_HF_CONT_EDITOR_AD_ID."'>";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Datum/ Zeit/ Länge</div>";
	echo "<input type='text' name='form_sg_date' id='datepicker' class='validate[required,custom[date_ge]] text_4' onChange='check_free_time()' value='".get_date_format_deutsch(substr($tbl_row_sg->SG_HF_TIME, 0, 10))."'>	<input type='text' name='sg_timestamp' id='sg_timestamp' class='validate[required,ajax[ajax_sg_time]] text_dummy' value='".trim($tbl_row_sg->SG_HF_TIME)."'>";
	echo "<input type='text' name='form_sg_time' id='sg_time' class='validate[required,custom[c_time_day]] text_4' onChange='check_free_time()' value='".substr($tbl_row_sg->SG_HF_TIME, 11, 8)."' >";
	echo "<input type='text' name='form_sg_duration' id='sg_duration' class='validate[required,custom[c_time_duration]] text_4' onChange='check_free_time()' value='".rtrim($tbl_row_sg->SG_HF_DURATION)."' >";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Titel</div>";
	echo "<input type='text' name='form_sg_titel' id ='sg_titel' class='validate[required,length[3,100]] text_1' maxlength=100 value='".$tbl_row_sg->SG_HF_CONT_TITEL."' >";
	echo "</div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Untertitel</div>";
	echo "<input type='text' class='text_1' name='form_sg_untertitel' maxlength='100' value='".$tbl_row_sg->SG_HF_CONT_UNTERTITEL."' >";
	echo "</div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Sendev./ Redakteur</div>";
	echo "<div class='content_column_3'>".$tbl_row_ad->AD_VORNAME." " .$tbl_row_ad->AD_NAME.", ".$tbl_row_ad->AD_ORT."</div>";
	echo "<div class='content_column_3' id=editor_name>".$editor_ad ."</div>";
	//echo "<input type='text' name='form_ad_name' class='text_1' value='".$tbl_row_ad->AD_VORNAME." " .$tbl_row_ad->AD_NAME.", ".$tbl_row_ad->AD_ORT."' >";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Stichworte</div>";
	echo "<input type='text' name='form_sg_stichworte' class='text_1' maxlength='40' value='".$tbl_row_sg->SG_HF_CONT_STICHWORTE."' >";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Internet</div>";
	echo "<input type='text' name='form_sg_web' class='text_1' maxlength='100' value='".$tbl_row_sg->SG_HF_CONT_WEB."' >";
	echo "</div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Genre/ Sprache</div>";
	echo html_dropdown_from_table_1("SG_GENRE", "SG_GENRE_DESC", "form_sg_genre", "text_2", rtrim($tbl_row_sg->SG_HF_CONT_GENRE_ID));
	echo html_dropdown_from_table_1("SG_SPEECH", "SG_SPEECH_DESC", "form_sg_speech", "text_2", rtrim($tbl_row_sg->SG_HF_CONT_SPEECH_ID));
	echo "</div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Einstellungen</div>";
	echo "<div class='content_column_2'>";
	if ( rtrim($tbl_row_sg->SG_HF_ON_AIR) == "T" ) {
		echo "<input type='checkbox' name='form_sg_on_air' value='T' checked='checked' title='Wird gesendet'>Sendung ";
	} else { 
		echo "<input type='checkbox' name='form_sg_on_air' value='T' title='Wird gesendet'>Sendung ";
	}

	if ( rtrim($tbl_row_sg->SG_HF_INFOTIME) == "T" ) {
		echo "<input id='chk_infotime' type='checkbox' name='form_sg_infotime' value='T' checked='checked' title='InfoTime'>InfoTime ";
	} else { 
		echo "<input id='chk_infotime' type='checkbox' name='form_sg_infotime' value='T' title='InfoTime'>InfoTime ";
	}

	if ( rtrim($tbl_row_sg->SG_HF_MAGAZINE) == "T" ) {
		echo "<input id='chk_magazine' type='checkbox' name='form_sg_magazine' value='T' checked='checked' title='Magazin' onChange='check_free_time()'>Magazin ";
	} else { 
		echo "<input id='chk_magazine' type='checkbox' name='form_sg_magazine' value='T' title='Magazin' onChange='check_free_time()'>Magazin ";
	}

	if ( rtrim($tbl_row_sg->SG_HF_PODCAST) == "T" ) {
		echo "<input type='checkbox' name='form_sg_podcast' value='T' checked='checked' title='Podcast'>Podcast ";
	} else { 
		echo "<input type='checkbox' name='form_sg_podcast' value='T' title='Podcast'>Podcast ";
	}

	if ( rtrim($tbl_row_sg->SG_HF_VP_OUT) == "T" ) {
		echo "<input type='checkbox' name='form_sg_vp_out' value='T' checked='checked' title='VP extern zur Verfügung stellen'>VP-Out ";
	} else { 
		echo "<input type='checkbox' name='form_sg_vp_out' value='T' title='VP extern zur Verfügung stellen'>VP-Out ";
	}

	if ( rtrim($tbl_row_sg->SG_HF_FIRST_SG) != "T") {
		if ( rtrim($tbl_row_sg->SG_HF_REPEAT_PROTO) == "T") {
			echo "<input type='checkbox' name='form_sg_repeat_proto' value='T' checked='checked' title='Wiederholung von Audio-Protokoll\n Wenn dies aktiv, wird das Audioprotokoll in Play-Out kopiert'>WH Proto ";
		} else { 
			echo "<input type='checkbox' name='form_sg_repeat_proto' value='T' title='Wiederholung von Audio-Protokoll\n Wenn dies aktiv, wird das Audioprotokoll in Play-Out kopiert'>WH Proto ";
		}
	} else {
		if ( rtrim($tbl_row_sg->SG_HF_LIVE) == "T") {
			echo "<input type='checkbox' name='form_sg_live' value='T' checked='checked' title='Livesendung'>Live ";
		} else { 
			echo "<input type='checkbox' name='form_sg_live' value='T' title='Livesendung'>Live ";
		}
	}

	if ( rtrim($tbl_row_sg->SG_HF_CONT_TEAMPRODUCTION) == "T") {
		echo "<input type='checkbox' name='form_sg_teamprod' value='T' checked='checked' title='Teamproduktion'>Team";
	} else { 
		echo "<input type='checkbox' name='form_sg_teamprod' value='T' title='Teamproduktion'>Team";
	}

	echo "</div>";
	echo "</div>\n";
	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Quelle</div>";
	echo html_dropdown_from_table_1("SG_HF_SOURCE", "SG_HF_SOURCE_DESC", "form_sg_source", "text_2", rtrim($tbl_row_sg->SG_HF_SOURCE_ID));
	echo "</div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_b_1'>";			
	echo "<div class='content_column_1'>Regieanweisung</div>";
	echo "<input type='text' name='form_sg_regie' class='text_1' maxlength='100' value='".$tbl_row_sg->SG_HF_CONT_REGIEANWEISUNG."' >";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Dateiname</div>";
	if ( $tbl_row_sg->SG_HF_CONT_AD_ID != $tbl_row_ad->AD_ID ) {
		// Aufruf kommt von Aenderung Sendeverantwortlicher
		// Filename soll beim speichern neu zusammengesetzt werden
		echo "<input type='text' name='form_sg_filename' class='text_1' maxlength='100' value='' >";
	} else {	
		echo "<input type='text' name='form_sg_filename' class='text_1' maxlength='100' value='".$tbl_row_sg->SG_HF_CONT_FILENAME."' >";
	}
	echo "</div>";
	echo "<div class='content_footer'>"; 

	echo "<div id='save_button'>";
	echo "<div style='float: left'><input type='submit' value='Speichern' ></div>"; 
	if ( $action == "edit" ) {
		echo "<div style='float: left'>";
		if ( $file_exist == "yes" ) {
			if ( substr($tbl_row_sg->SG_HF_CONT_FILENAME, 0, 7) != "http://" ) {
				echo "<input type='checkbox' name='form_sg_read_audio_length' id='check_id3' value='T'> und Hoerdauer/ ID3-Tags abgleichen ";
				echo "<input type='button' class='button_1' value='Sendezeit prüfen' onClick='check_free_time()' >";
				echo "<div id='check_gain' style='display: none'><input type='checkbox' name='form_sg_mp3gain' value='T'> und mp3Gain abgleichen</div>";
			}
		}
		if ( $editor_ad != "Kein Redakteur zugeordnet" ) {
			echo "<input type='button' class='button_1' value='Redakteur löschen' onClick='delete_editor()' >";
		}
		echo "</div>";
	}
	echo "</div>";
	echo "</div>";
	echo "</form>";
	echo "<div id='wait' style='display:none;'><img src='../parts/pict/wait30trans.gif' width='30' height='30' border='0' alt='gleich gehts weiter'>...gleich gehts weiter...bitte dieses Fenster NICHT schließen</div>";
	echo "<br> <br>";

} // user_rights			
echo "<div class='space_line_1'> </div>";
echo "</div><!--class=column_right-->";
echo "</div>";

echo "<script>";
echo "<!-- wenn check_id3 geklickt, checkbox mp3gain anzeigen -->";
echo "$('#check_id3').click(function () {";
// $("#check_gain").toggle("slow"); 
echo "});";    
echo "</script>";
?>
</body>
</html>