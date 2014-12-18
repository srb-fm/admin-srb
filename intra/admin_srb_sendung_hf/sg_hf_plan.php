<?php

/** 
* Sendung Plan bearbeiten 
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
$option = "";
$displ_dateform = "no";

// action pruefen	
$action_ok = "no";
if ( isset( $_GET['action'] ) ) {	
	$action = $_GET['action'];	
	$action_ok = "yes";
}
if ( isset( $_POST['action'] ) ) {	
	$action = $_POST['action'];
	$action_ok = "yes";
}
			
// Bedingung pruefen	
$find_option_ok = "no";
if ( isset( $_GET['find_option'] ) ) {	
	$find_option = $_GET['find_option'];
	$find_option_ok = "yes";
}
if ( isset( $_POST['find_option'] ) ) {
	$find_option = $_POST['find_option'];
	$find_option_ok = "yes";
}		
	
if ( $find_option_ok = "yes" ) {
	switch ( $action ) {
	case "list": 
		$query_main_table = "SG_HF_MAIN";
		$j = date('Y');
		$m = date('m');
		$d = date('d');
			
		switch ( $find_option ) {
		case "info_time_yesterday_a":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_gestern = date('Y-m-d', mktime(0, 0, 0, $m, $d-1, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d-1, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_gestern."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time gestern - ". get_german_day_name($d_word). ", ". get_date_format_deutsch($d_gestern)." ES und WH - Serie A";
			$option = "infotime";
			break;
				
		case "info_time_yesterday_b":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_7", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_gestern = date('Y-m-d', mktime(0, 0, 0, $m, $d-1, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d-1, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_gestern."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time gestern - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_gestern)." ES und WH - Serie B";
			$option = "infotime";
			break;
					
		case "info_time_today_a":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_word = date('l');
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".date("Y-m-d")."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time heute - ".get_german_day_name($d_word). ", ".get_date_format_deutsch(date("Y-m-d"))." - ES und WH - Serie A";
			$option = "infotime";
			break;

		case "info_time_today_b":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_7", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_word = date('l');
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".date("Y-m-d")."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time heute - ".get_german_day_name($d_word). ", ".get_date_format_deutsch(date("Y-m-d"))." - ES und WH - Serie B";
			$option = "infotime";
			break;

		case "info_time_tomorrow_a":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+1, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+1, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time morgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_tomorrow)." - ES und WH - Serie A";
			$option = "infotime";
			break;					
				
		case "info_time_tomorrow_b":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_7", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+1, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+1, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time morgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_tomorrow)." - ES und WH - Serie B";
			$option = "infotime";
			break;					
								
		case "info_time_after_tomorrow_a":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+2, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+2, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time übermorgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH - Serie A";
			$option = "infotime";
			break;
				
		case "info_time_after_tomorrow_b":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_7", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+2, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+2, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time übermorgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH - Serie B";
			$option = "infotime";
			break;
				
		case "info_time_after_after_tomorrow_a":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+3, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+3, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time über-übermorgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH - Serie A";
			$option = "infotime";
			break;
					
		case "info_time_four_days_a":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+4, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+4, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time 4 Tage vor - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH - Serie A";
			$option = "infotime";
			break;
				
		case "info_time_after_after_tomorrow_b":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_7", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+3, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+3, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time über-übermorgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH - Serie B";
			$option = "infotime";
			break;
					
		case "info_time_four_days_b":
			$it_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_7", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+4, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+4, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_INFOTIME = 'T' AND SG_HF_MAGAZINE = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' AND SUBSTRING( SG_HF_TIME FROM 12 FOR 2) ='".$it_start_hour."' ORDER BY SG_HF_TIME";
			$message_find_string = "Info-Time 4 Tage vor - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH - Serie B";
			$option = "infotime";
			break;
					
		case "magazine_yesterday":
			$d_gestern = date('Y-m-d', mktime(0, 0, 0, $m, $d-1, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d-1, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_MAGAZINE = 'T' AND SG_HF_INFOTIME = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_gestern."' ORDER BY SG_HF_TIME";
			$message_find_string = "Magazin gestern - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_gestern)." - ES und WH ";
			$option = "magazin";
			break;
					
		case "magazine_today":
			$d_word = date('l');
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_MAGAZINE = 'T' AND SG_HF_INFOTIME = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".date("Y-m-d")."' ORDER BY SG_HF_TIME";
			$message_find_string = "Magazin heute - ".get_german_day_name($d_word). ", ".get_date_format_deutsch(date("Y-m-d"))." - ES und WH ";
			$option = "magazin";
			break;

		case "magazine_tomorrow":
			$d_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+1, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+1, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_MAGAZINE = 'T' AND SG_HF_INFOTIME = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_tomorrow."' ORDER BY SG_HF_TIME";
			$message_find_string = "Magazin morgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_tomorrow)." - ES und WH ";
			$option = "magazin";
			break;
				
		case "magazine_after_tomorrow":
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+2, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+2, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_MAGAZINE = 'T' AND SG_HF_INFOTIME = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' ORDER BY SG_HF_TIME";
			$message_find_string = "Magazin übermorgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH ";
			$option = "magazin";
			break;

		case "magazine_after_after_tomorrow":
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+3, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+3, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_MAGAZINE = 'T' AND SG_HF_INFOTIME = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' ORDER BY SG_HF_TIME";
			$message_find_string = "Magazin über-übermorgen - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH ";
			$option = "magazin";
			break;
					
		case "magazine_four_days":
			$d_a_tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d+4, $j));
			$d_word = date('l', mktime(0, 0, 0, $m, $d+4, $j));
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_MAGAZINE = 'T' AND SG_HF_INFOTIME = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_a_tomorrow."' ORDER BY SG_HF_TIME";
			$message_find_string = "Magazin 4 Tage vor - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_a_tomorrow)." - ES und WH ";
			$option = "magazin";
			break;
		
		case "magazine_date":
			$displ_dateform = "yes";
			$d_date_dest = date('Y-m-d');
			$d_word = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j));
			// Pruefen ob Datum uebergeben, sonst aktuelles nehmen
			if ( isset( $_POST['form_k_datum'] ) ) {
				if ( $_POST['form_k_datum'] != "" ) {
					$d_date_dest = get_date_format_sql($_POST['form_k_datum']);
					$j = substr($d_date_dest, 0, 4);
					$m = substr($d_date_dest, 5, 2);
					$d = substr($d_date_dest, 8, 2);
					$d_date_dest = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j));
					$d_word = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j));
				}
			}
			// wenn von plan_update
			if ( isset( $_GET['form_k_datum'] ) ) {	
				$d_date_dest = $_GET['form_k_datum'];
			}
			$c_query_condition = "SG_HF_ON_AIR = 'T' AND SG_HF_MAGAZINE = 'T' AND SG_HF_INFOTIME = 'F' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$d_date_dest."' ORDER BY SG_HF_TIME";
			$message_find_string = "Magazin nach Datum - ".get_german_day_name($d_word). ", ".get_date_format_deutsch($d_date_dest)." - ES und WH ";
			$option = "magazin";
			break;		
			//endswitch;
		}
		break;
	//endswitch;
	}
} else {
	$message = "Keine Suchbedingung! Kann nichts tun... "; 
}

if ( $action_ok !="no" ) { 
	$db_result = db_query_sg_ad_list_items_1($c_query_condition);				
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" language="javascript" src="../parts/jquery/jquery_tools/jquery.tablednd_0_5.js"></script>

	<script type="text/javascript">
	var c_table_sort
	$(document).ready(function() {
	
	// Initialise the table specifying a dragClass and an onDrop function that will display an alert
	$("#table_sg_plan").tableDnD(
		{
        onDrop: function(table, row) {
			//$("p").append( document.createTextNode("<input type='hidden' name='table_sort' value='"+$.tableDnD.serialize()+"'>")).css("color","red");
			c_table_sort = $.tableDnD.serialize();
			$("div.button_save").css( "display","block");
        }
    });
	});
	
	function chk_formular () {
		document.forms["form1"].table_sort.value = c_table_sort;
		}
	</script>

</head>
<body>
<?php	 
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message.$message_find_string; 	
echo "</div>";
echo "<div class='content'>"; 
if ( $action_ok == "no" ) { 
	return;
} 
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	 
	$z = 0;
	$sum_duration = 0;
	$c_date ="";
	// Auswahl datum
	echo "<form name='form0' action='sg_hf_plan.php' method='POST' onsubmit='return chk_formular()' enctype='application/x-www-form-urlencoded'>";
	echo "<input type='hidden' name='action' value='".$action."'>\n";
	echo "<input type='hidden' name='find_option' value='".$find_option."'>\n";
	
	if ( $displ_dateform == "yes" ) {
		echo "Datum: <input type='TEXT' id='datepicker' name='form_k_datum' value='' size='10' maxlength='10'>";
	}
	echo "<input type='submit' value='Anzeigen'></form><br>";
	
	echo "<br>";
	echo "<table id='table_sg_plan' cellspacing='0' cellpadding='5'>";
	if ( $db_result	) {			
		foreach ( $db_result as $item ) {
			$z += 1;
			$sum_duration += get_seconds_from_hms($item['SG_HF_DURATION']);
			if ( $z % 2 != 0 ) { 
				echo "<tr id='sg_id_".$item['SG_HF_ID']."' class='content_row_a_1'><td class='text_1'> ".substr($item['SG_HF_TIME'], 11, 8)." </td><td class='text_1'>".$item['SG_HF_DURATION']."</td><td class='text_2'>".substr($item['SG_HF_CONT_TITEL'], 0, 40)." - ".substr($item['AD_NAME'], 0, 15)."</td></tr>\n";
			} else {
				echo "<tr id='sg_id_".$item['SG_HF_ID']."' class='content_row_b_1'><td class='text_1'> ".substr($item['SG_HF_TIME'], 11, 8)." </td><td class='text_1'>".$item['SG_HF_DURATION']."</td><td class='text_2'>".substr($item['SG_HF_CONT_TITEL'], 0, 35)." - ".substr($item['AD_NAME'], 0, 15)."</td></tr>\n";
			}
			// fuer uebergabe an update:
			$c_date = substr($item['SG_HF_TIME'], 0, 10);
		}
	}	
	echo "</table>";	
			
	if ( $z == 0 ) { 	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		echo "<br><b>Gesamtlänge: ".get_time_in_hms($sum_duration)." ::: Anzahl der Beiträge: ".$z."</b>" ; 
	}
		
	echo "<br>";
	echo "<div id='line_a'> </div>";
	echo "<form name='form1' action='sg_hf_plan_update.php' method='POST' onsubmit='return chk_formular()' enctype='application/x-www-form-urlencoded'>";
		 
	switch( $option ) {
	case "infotime":
		echo "<input type='hidden' name='action' value='infotime_update'>";
		echo "<input type='hidden' name='it_start_hour' value='".$it_start_hour."'>";	
		break;
								
	case "magazin":
		echo "<input type='hidden' name='action' value='magazin_update'>";	
		break;
		//endswitch;
	}
		
	echo "<input type='hidden' name='find_option' value='".$find_option."'>";
	echo "<input type='hidden' name='sendungen_anzahl' value='".$z."'>";
	echo "<input type='hidden' name='sendungen_date' value='".$c_date."'>";
	echo "<input type='hidden' name='table_sort' value=''>";
	echo "<p><div class='button_save' style='display:none'; ><input type='submit' value='Speichern'><div id='line_a'> </div></p></div>";			
	echo "</form>";
	if ( $z != 0 ) {		
		echo "<p>";
		echo "Reihenfolge der Sendungen kann mit Maus verschoben werden. <br>Anschließend speichern klicken.<br>";
		echo "Vorbereitete Sendungen werden in diesem Plan nicht angezeigt!"; 
		echo "</p>";
	}
} // user_rights
echo "</div><!--content-->";
echo "</div><!--column_right-->";	
?>
</body>
</html>