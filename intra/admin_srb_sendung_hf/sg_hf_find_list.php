<?php
/** 
* sendung liste anzeigen 
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
require "../../cgi-bin/admin_srb_libs/lib_sess.php";
$message = "";
$action_ok = false;
$find_limit_skip = "no";
$condition_delivery = "no";

// info ausgabebegrenzung auf 25 datensaetze:
// der abfrage_condition wird das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den naechsten satzen wird die skip-anzahl in der url zugrechnet: (ausgabebegrenzung 2) und dann in die abfrage uebernommen (// ausgabebegrenzung 1)
// fuer die option find muss dazu feld und inhalt neu uebergeben werden ( ausgabebegrenzung 3)

// check action
if ( isset($_GET['action'] ) ) {
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action'] ) ) { 
	$action = $_POST['action']; 
	$action_ok = true;
}

if ( $action_ok == false ) { 
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

// check condition_delivery (outputlimit)
if ( isset($_GET['condition'] ) ) {
	$c_query_condition = rawurldecode($_GET['condition']);
	$condition_delivery = "yes";
}	

// ausgabebegrenzung			
// limit  via limitweiterschaltung

if ( isset($_GET['find_limit_skip'] ) ) { 
	$find_limit_skip = $_GET['find_limit_skip'];
}

if ( $condition_delivery != "yes" ) {
	 	
	// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
	// also hier nur pruefen in welchem feld was ist

	if ( isset($_POST['sg_titel'] ) ) {
		if ( $_POST['sg_titel'] !="" ) { 
			$c_field_desc = "SG_HF_CONT_TITEL";
			$c_field_value = $_POST['sg_titel']; 
		}
	}

	if ( isset($_POST['sg_untertitel'] ) ) {
		if ( $_POST['sg_untertitel'] !="") { 
			$c_field_desc = "SG_HF_CONT_UNTERTITEL";
			$c_field_value = $_POST['sg_untertitel']; 
		}
	}

	if ( isset($_POST['sg_stichwort'] ) ) {
		if ( $_POST['sg_stichwort'] !="") { 
			$c_field_desc = "SG_HF_CONT_STICHWORTE";
			$c_field_value = $_POST['sg_stichwort']; 
		}
	}

	if ( isset($_POST['sg_regieanweisung'] ) ) {
		if ( $_POST['sg_regieanweisung'] !="") { 
			$c_field_desc = "SG_HF_CONT_REGIEANWEISUNG";
			$c_field_value = $_POST['sg_regieanweisung']; 
		}
	}

	if ( isset($_POST['sg_dateiname'] ) ) {
		if ( $_POST['sg_dateiname'] !="") { 
			$c_field_desc = "SG_HF_CONT_FILENAME";
			$c_field_value = $_POST['sg_dateiname']; 
		}
	}

	if ( isset($_POST['sg_genre'] ) ) {
		if ( $_POST['sg_genre'] !="") { 
			$c_field_desc = "SG_HF_CONT_GENRE_ID";
			$c_field_value = db_query_load_id_by_value("SG_GENRE", "SG_GENRE_DESC", $_POST['sg_genre']);

			if ( $c_field_value =="" ) {
				$action_ok = "no";
				$message .= "Gengre nicht gefunden... "; 	
			}
		}
	}

	if ( isset($_POST['sg_quelle'] ) ) {
		if ( $_POST['sg_quelle'] !="") { 
			$c_field_desc = "A.SG_HF_SOURCE_ID";
			$c_field_value = db_query_load_id_by_value("SG_HF_SOURCE", "SG_HF_SOURCE_DESC", $_POST['sg_quelle']);

			if ( $c_field_value =="" ) {
				$action_ok = false;
				$message .= "Quelle nicht gefunden... "; 	
			}
		}
	}

	if ( isset($_POST['sg_datum'] ) ) {
		if ( $_POST['sg_datum'] !="" ) { 
			$c_field_desc = "A.SG_HF_TIME";
			$c_field_value = get_date_format_sql($_POST['sg_datum']); 
		}
	}

	if ( isset($_POST['sg_magazin'] ) ) {
		if ( $_POST['sg_magazin'] !="" ) { 
			$find_option = "exact";
			$c_field_desc = "A.SG_HF_MAGAZINE";
			$c_field_value = $_POST['sg_magazin']; 
		}
	}

	if ( isset($_POST['sg_live'] ) ) {
		if ( $_POST['sg_live'] !="" ) { 
			$find_option = "exact";
			$c_field_desc = "A.SG_HF_LIVE";
			$c_field_value = $_POST['sg_live']; 
		}
	}

	if ( isset($_POST['sg_cont_id'] ) ) {
		if ( $_POST['sg_cont_id'] !="" ) { 
			$c_field_desc = "A.SG_HF_CONTENT_ID";
			$c_field_value = $_POST['sg_cont_id']; 
		}
	}

	// check condition
	$find_option_ok = false;
	if ( isset($_GET['find_option'] ) ) {	
		$find_option = $_GET['find_option'];
		$find_option_ok = true;
	}
	if ( isset($_POST['find_option'] ) ) { 
		$find_option = $_POST['find_option']; 	
		$find_option_ok = true;
	}		

	if ( $find_option_ok == true and $action_ok == true ) {
		switch ( $action ) {
		case "find": 
			if ( $find_option == "begin" ) {
		    	$c_query_condition = "UPPER(".$c_field_desc.") LIKE UPPER(_iso8859_1'".utf8_decode($c_field_value)."%' collate de_de)";
				$message_find_string = $c_field_desc. " beginnt mit: " .$c_field_value.", neueste zuerst" ;
			} elseif ( $find_option == "in" ) {
				$c_query_condition =  "UPPER(".$c_field_desc.") LIKE UPPER(_iso8859_1'%".utf8_decode($c_field_value)."%' collate de_de)";
				$message_find_string = $c_field_desc. " enthält: " .$c_field_value.", neueste zuerst"  ;
			} elseif ( $find_option == "exact" ) {
				$c_query_condition = $c_field_desc." = '".$c_field_value."'";
				// upper geht hier irgendwie nicht, koenne nicht transliterieren
				//$c_query_condition = "UPPER(".$c_field_desc.") = UPPER(_iso8859_1 '".$c_field_value."' collate de_de)";
				$message_find_string = $c_field_desc. " ist exakt: " .$c_field_value." neueste zuerst"  ;
			} elseif ( $find_option == "datum" ) {
				$c_query_condition = "SUBSTRING( ".$c_field_desc." FROM 1 FOR 10) = '".$c_field_value."'";
				$message_find_string = $c_field_desc. " ist Datum: " .$c_field_value.", neueste zuerst"  ;
			}
				
			// append sort
			$c_query_condition .= " AND SG_HF_FIRST_SG = 'T' ORDER BY SG_HF_CONT_ID DESC";
			break;
			
		case "list": 
			switch ( $find_option ) {
			case "broadcaster":
				$c_query_condition = "SG_HF_CONT_AD_ID = '".$_GET['broadcaster_id']."' AND SG_HF_FIRST_SG = 'T' ORDER BY SG_HF_CONT_ID DESC";
				$message_find_string = "Sendungen zur Adresse - Autor der Sendung";
				break;

			case "editor":
				$c_query_condition = "SG_HF_CONT_EDITOR_AD_ID = '".$_GET['editor_id']."' AND SG_HF_FIRST_SG = 'T' ORDER BY SG_HF_CONT_ID DESC";
				$message_find_string = "Sendungen zur Adresse - Redaktionell verantwortet";
				break;
				
			case "prepared_all":
				$c_query_condition = "A.SG_HF_ON_AIR = 'F' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Vorbereitete Sendungen";
				break;
			
			case "info_time_today":
				$c_query_condition = "A.SG_HF_INFOTIME = 'T' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".date("Y-m-d")."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Info-Time heute - ".get_german_day_name(date("l")).", ".get_date_format_deutsch(date("Y-m-d"))." - ES und WH ";
				break;
				
			case "info_time_yesterday":
				$day = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')-1, date('Y')));
				$day_name = date('l', mktime(0, 0, 0, date('m'), date('d')-1, date('Y')));
				$c_query_condition = "A.SG_HF_INFOTIME = 'T' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$day."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Info-Time gestern - ".get_german_day_name($day_name).", ".get_date_format_deutsch($day)." - ES und WH ";
				break;
					
			case "info_time_tomorrow":
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')+1, date('Y')));
				$dayname = date('l', mktime(0, 0, 0, date('m'), date('d')+1, date('Y')));
				$c_query_condition = "A.SG_HF_INFOTIME = 'T' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Info-Time morgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;

			case "info_time_after_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+2, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+2, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'T' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Info-Time übermorgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;
				
			case "info_time_after_after_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+3, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+3, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'T' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Info-Time über-übermorgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;
				
			case "info_time_four_days":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+4, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+4, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'T' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Info-Time 4 Tage vor - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;
														
			case "magazine_yesterday":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_gestern = date('Y-m-d', mktime(0, 0, 0, $m, $d-1, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d-1, $j));
				$c_query_condition = "A.SG_HF_MAGAZINE = 'T' AND A.SG_HF_INFOTIME = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_gestern."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Magazin gestern - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_gestern)." - ES und WH ";
				break;
					
			case "magazine_today":
				$c_query_condition = "A.SG_HF_MAGAZINE = 'T' AND A.SG_HF_INFOTIME = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".date("Y-m-d")."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Magazin heute - ".get_german_day_name(date("l")).", ".get_date_format_deutsch(date("Y-m-d"))." - ES und WH Magazin heute";
				break;

			case "magazine_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+1, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+1, $j));
				//$query_main_table = "SG_HF_MAIN";
				$c_query_condition = "A.SG_HF_MAGAZINE = 'T' AND A.SG_HF_INFOTIME = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Magazin morgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;
					
			case "magazine_after_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+2, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+2, $j));
				$c_query_condition = "A.SG_HF_MAGAZINE = 'T' AND A.SG_HF_INFOTIME = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Magazin übermorgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;

			case "magazine_after_after_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+3, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+3, $j));
				$c_query_condition = "A.SG_HF_MAGAZINE = 'T' AND A.SG_HF_INFOTIME = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Magazin über-übermorgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;

			case "magazine_four_days":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+4, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+4, $j));
				$c_query_condition = "A.SG_HF_MAGAZINE = 'T' AND A.SG_HF_INFOTIME = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Magazin 4 Tage vor - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;
				
			case "broadcast_yesterday":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_gestern = date('Y-m-d', mktime(0, 0, 0, $m, $d-1, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d-1, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_gestern."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Sendungen ohne Info-Time und Magazin gestern - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_gestern)." - ES und WH ";
				break;
					
			case "broadcast_today":
				$c_query_condition = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".date("Y-m-d")."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Sendungen ohne Info-Time und Magazin heute - ".get_german_day_name(date("l")).", ".get_date_format_deutsch(date("Y-m-d"))." - ES und WH ";
				break;

			case "broadcast_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+1, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+1, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Sendungen ohne Info-Time und Magazin morgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;

			case "broadcast_after_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+2, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+2, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Sendungen ohne Info-Time und Magazin übermorgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;
				
			case "broadcast_after_after_tomorrow":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+3, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+3, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Sendungen ohne Info-Time und Magazin über-übermorgen - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;

			case "broadcast_four_days":
				$j = date('Y');
				$m = date('m');
				$d = date('d');
				$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+4, $j));
				$dayname = date('l', mktime(0, 0, 0, $m, $d+4, $j));
				$c_query_condition = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY A.SG_HF_TIME";
				$message_find_string = "Sendungen ohne Info-Time und Magazin 4 Tage vor - ".get_german_day_name($dayname).", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
				break;
																															
			case"show_hour":
				if ( isset($_GET['sg_time'])) {
					$c_query_condition = " SUBSTRING( A.SG_HF_TIME FROM 12 FOR 2 ) = '".substr($_GET['sg_time'], 11, 2)."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10 ) = '".substr($_GET['sg_time'], 0, 10)."' ORDER BY A.SG_HF_TIME";
					$message_find_string = " vom ".get_date_format_deutsch(substr($_GET['sg_time'], 0, 10)). " in der ".substr($_GET['sg_time'], 11, 2).". Stunde";
				}
				break;
			//endswitch; // $find_option
			}
			break;
		//endswitch; // $action
		}
	} else {
		$message = "Keine Suchbedingung! Kann nichts tun... "; 
	} //$find_option_ok == true 
	
} else {// $condition_delivery != "yes"
	$message_find_string = $_GET['find_string'] ;
} // $condition_delivery != "yes"

// ausgabebegrenzung 1
if ( 	$find_limit_skip == "no" ) {			
	$db_result = db_query_sg_ad_list_items_limit_1($c_query_condition, "FIRST 25");
	$z = 0;
} else {
	$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
	$db_result = db_query_sg_ad_list_items_limit_1($c_query_condition, $c_limit);
	$z = $find_limit_skip;
} 

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Sendung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");  </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>	
	
</head>
<body>
 
<div class="main">
<?php 
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
require "parts/sg_hf_menu.inc";	
user_display();
echo "</div> <!--class=column_left-->";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n";
echo "</div>";
require "parts/sg_hf_toolbar.inc";
echo "<div class='content' id='jq_slide_by_click'>";		

if ( $action_ok == false ) { 
	return;
} 

$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 		
	if ( 	$db_result	) {	
		foreach ($db_result as $item ) {
			$z +=1;
			// Listcolors
			// Auf Sende-Dauer aufmerksam machen
			switch ( $item['SG_HF_DURATION'] ) {
			case "00:00:00":
				$div_class_a_1 = "<div class='content_row_a_4 blink' title='Bitte Sendedauer ueberpruefen'>";
				$div_class_a_2 = "<div class='content_row_a_7 blink' title='Bitte Sendedauer ueberpruefen'>";
				$div_class_b_1 = "<div class='content_row_b_4 blink' title='Bitte Sendedauer ueberpruefen'>";
				$div_class_b_2 = "<div class='content_row_b_7 blink' title='Bitte Sendedauer ueberpruefen'>";
				break;
			case "00:01:00":
				$div_class_a_1 = "<div class='content_row_a_4 blink' title='Bitte Sendedauer ueberpruefen'>";
				$div_class_a_2 = "<div class='content_row_a_7 blink' title='Bitte Sendedauer ueberpruefen'>";
				$div_class_b_1 = "<div class='content_row_b_4 blink' title='Bitte Sendedauer ueberpruefen'>";
				$div_class_b_2 = "<div class='content_row_b_7 blink' title='Bitte Sendedauer ueberpruefen'>";
				break;
			default:
				$div_class_a_1 = "<div class='content_row_a_4'>";
				$div_class_a_2 = "<div class='content_row_a_7'>";
				$div_class_b_1 = "<div class='content_row_b_4'>";
				$div_class_b_2 = "<div class='content_row_b_7'>";
			}

			if ( $z % 2 != 0 ) { 
				if ( rtrim($item['SG_HF_ON_AIR']) == "T" ) { 
					echo $div_class_a_1;
				} else {
					echo $div_class_a_2;
				}						
			} else { 
				if ( rtrim($item['SG_HF_ON_AIR']) == "T" ) {
					echo $div_class_b_1;
				} else {
					echo $div_class_b_2;
				}	
			}
			
			if ( $action == "list" ) {
				echo html_sg_state_a(rtrim($item['SG_HF_FIRST_SG']), rtrim($item['SG_HF_ON_AIR']), rtrim($item['SG_HF_CONT_FILENAME']))." " ;
			}
			echo "<a href='sg_hf_detail.php?action=display&amp;sg_id=".$item['SG_HF_ID']."' class='c_box'>".get_date_format_deutsch(substr($item['SG_HF_TIME'], 0, 10))." - ".substr($item['SG_HF_TIME'], 11, 8)." - ".substr($item['SG_HF_CONT_TITEL'], 0, 50)." - ".substr($item['AD_NAME'], 0, 12)."</a>";
			echo "</div>";
		
			echo "<div class='content_row_toggle_head_2'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
			if ( $action == "list" ) {
				echo "<div class='content_row_toggle_body_2'>";
			} else {
				echo "<div class='content_row_toggle_body_2'>";
			}
			if ( $item['SG_HF_CONT_UNTERTITEL'] != "" ) { 
				echo $item['SG_HF_CONT_UNTERTITEL']."<br>";
			}
			echo $item['SG_HF_CONT_FILENAME'];
			if ( $item['SG_HF_CONT_REGIEANWEISUNG'] != "" ) { 
				echo "<br>".$item['SG_HF_CONT_REGIEANWEISUNG'];
			}
			echo "<br> Länge: ".$item['SG_HF_DURATION'];
			echo "<br>  <a href='sg_hf_reg_form.php?action=print&amp;sg_id=".$item['SG_HF_ID']."&amp;ad_id=".$item['SG_HF_CONT_AD_ID']."' target='_blank'>Sendeanmeldung drucken</a> ";
			echo "</div>\n";					
		}
	}

	if ( $z == 0 ) {	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$x = $z / 25;
		$zz = $z+1;	
		echo "<div class='content_footer'>Gefunden: ".$z. " ::: "; 		
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			//echo " >> <a href='sg_hf_find_list.php?action=".$action."&amp;table=".$query_main_table."&amp;condition=".rawurlencode($c_query_condition)."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a>";
			echo " >> <a href='sg_hf_find_list.php?action=".$action."&amp;condition=".rawurlencode($c_query_condition)."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a>";
		}							
		echo "<br> Vorbereitete Sendungen sind rosa markiert";
		echo "</div>";								
	}
	//echo "x".print_r($_POST);
} // user_rights

echo "</div><!--content-->";
echo "</div><!--column_right-->";
?>

</div><!--main-->
<div id="back-to-top">Scroll Top</div>
</body>
</html>