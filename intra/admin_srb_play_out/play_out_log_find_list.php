<?php

/** 
* play_out_logs anzeigen 
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
$user_app_drop_down = "yes";
$displ_dateform = "no";
	
// action pruefen	
if ( isset( $_GET['action'] ) ) {	
	$action = $_GET['action'];	
	$action_ok = "yes";
}
if ( isset( $_POST['action'] ) ) { 
	$action = $_POST['action'];
	$action_ok = "yes";
}
			
if ( $action_ok == "yes" ) {	
	// App-ID ermitteln/ 000 gleich alle Apps
	if ( isset( $_POST['form_user_app'] ) ) {
		$tbl_value_app_id = db_query_load_id_by_value("USER_APPS", "USER_APP_DESC", $_POST['form_user_app']);			
	} else {
		$tbl_value_app_id = "000";	
	}
		
	switch ( $action ) {

	case "list_last_hour_all": 
		$n_time_one_hour_back = time() - 3600;
		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Logs letzte Stunde";
			$c_query_condition = "USER_LOG_TIME >= '".date("Y-m-d H:i:s", $n_time_one_hour_back). "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Logs letzte Stunde";
			$c_query_condition = "USER_LOG_TIME >= '".date("Y-m-d H:i:s", $n_time_one_hour_back). "' ORDER BY USER_LOG_ID";
		}
		break;

	case "list_last_hour_error": 
		$c_date = date("Y-m-d");
		$n_time_one_hour_back = time() - 3600;

		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Error-Logs des Tages";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 19) >= '".date("Y-m-d H:i:s", $n_time_one_hour_back). "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Error-Logs des Tages";
		    $c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 19) >= '".date("Y-m-d H:i:s", $n_time_one_hour_back). "' ORDER BY USER_LOG_ID";
		}
		break;

	case "list_date_all": 
		$d_date_dest = date("Y-m-d");
		$displ_dateform = "yes";
		// Pruefen ob Datum übergeben, sohnst aktuelles nehmen
		if ( isset( $_POST['form_k_datum'] ) ) {
			if ( $_POST['form_k_datum'] != "" ) {
				$d_date_dest = get_date_format_sql($_POST['form_k_datum']);
				$j = substr($d_date_dest, 0, 4);
				$m = substr($d_date_dest, 5, 2);
				$d = substr($d_date_dest, 8, 2);
				$d_date_dest = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j));
			}
		}

		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Logs ".$d_date_dest;
			$c_query_condition = "SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$d_date_dest. "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Logs ".$d_date_dest;
			$c_query_condition = "SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$d_date_dest. "' ORDER BY USER_LOG_ID";
		}
		break;
				
	case "list_today_all": 
		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Logs des Tages (ohne Errors)";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) <> 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".date("Y-m-d"). "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Logs des Tages (ohne Errors)";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) <> 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".date("Y-m-d"). "' ORDER BY USER_LOG_ID";
		}
		break;

	case "list_yesterday_all": 
		$date_back = time() - (1 * 24 * 60 * 60);
		$c_date = date("Y-m-d", $date_back);

		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Logs von gestern";
			$c_query_condition = "SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$c_date. "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Logs von gestern";
			$c_query_condition = "SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$c_date. "' ORDER BY USER_LOG_ID";
		}
		break;
				
	case "list_today_play_out": 
		$displ_dateform = "yes";
		$user_app_drop_down = "no";
		if ( isset( $_POST['form_k_datum'] ) ) {
			$c_date = get_date_format_sql($_POST['form_k_datum']);
		} else { 
			$c_date = date("Y-m-d"); 
		}
		$message = "Alle gespielten Titel des Tages: ".$c_date.", die neuesten zuerst" ;
		$c_query_condition = "SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$c_date. "' AND USER_LOG_MODUL_ID ='003' AND USER_LOG_ICON <> 'x' ORDER BY USER_LOG_ID DESC";
		break;

	case "list_date_error": 
		$displ_dateform = "yes";
		$d_date_dest = date('Y-m-d');
		// Pruefen ob Datum übergeben, sonst aktuelles nehmen
		if ( isset( $_POST['form_k_datum'] ) ) {
			if ( $_POST['form_k_datum'] != "" ) {
				$d_date_dest = get_date_format_sql($_POST['form_k_datum']);
				$j = substr($d_date_dest, 0, 4);
				$m = substr($d_date_dest, 5, 2);
				$d = substr($d_date_dest, 8, 2);
				$d_date_dest = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j));
			}
		}

		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Error-Logs ".$d_date_dest;
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$d_date_dest. "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Error-Logs des Tages";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$d_date_dest. "' ORDER BY USER_LOG_ID";
		}
		break;

	case "list_date_informations": 
		$displ_dateform = "yes";
		$d_date_dest = date('Y-m-d');
		// Pruefen ob Datum übergeben, sonst aktuelles nehmen
		if ( isset( $_POST['form_k_datum'] ) ) {
			if ( $_POST['form_k_datum'] != "" ) {
				$d_date_dest = get_date_format_sql($_POST['form_k_datum']);
				$j = substr($d_date_dest, 0, 4);
				$m = substr($d_date_dest, 5, 2);
				$d = substr($d_date_dest, 8, 2);
				$d_date_dest = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j));
			}
		}

		$message = "Alle Informations des Tages";
		$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'i' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$d_date_dest. "' ORDER BY USER_LOG_ID";
		break;
				
	case "list_date_notifications": 
		$displ_dateform = "yes";
		$d_date_dest = date('Y-m-d');
		// Pruefen ob Datum übergeben, sonst aktuelles nehmen
		if ( isset( $_POST['form_k_datum'] ) ) {
			if ( $_POST['form_k_datum'] != "" ) {
				$d_date_dest = get_date_format_sql($_POST['form_k_datum']);
				$j = substr($d_date_dest, 0, 4);
				$m = substr($d_date_dest, 5, 2);
				$d = substr($d_date_dest, 8, 2);
				$d_date_dest = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j));
			}
		}

		$message = "Alle Notifications des Tages";
		$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'n' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$d_date_dest. "' ORDER BY USER_LOG_ID";
		break;
				
	case "list_today_error": 
		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Error-Logs des Tages";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".date("Y-m-d"). "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Error-Logs des Tages";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".date("Y-m-d"). "' ORDER BY USER_LOG_ID";
		}
		break;
				
	case "list_yesterday_error": 
		$date_back = time() - (1 * 24 * 60 * 60);
		$c_date = date("Y-m-d", $date_back);
		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Error-Logs gestern";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$c_date. "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Error-Logs gestern";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$c_date."' ORDER BY USER_LOG_ID";
		}
		break;

	case "list_befor_yesterday_error": 
		$date_back = time() - (2 * 24 * 60 * 60);
		$c_date = date("Y-m-d", $date_back);

		if ( $tbl_value_app_id != "000" ) {
			$message = "Alle ".$_POST['form_user_app']." - Error-Logs vorgestern";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$c_date. "' AND USER_LOG_MODUL_ID ='".$tbl_value_app_id."' ORDER BY USER_LOG_ID";
		} else {
			$message = "Alle Error-Logs vorgestern";
			$c_query_condition = "SUBSTRING( USER_LOG_ICON FROM 1 FOR 1 ) = 'x' AND SUBSTRING( USER_LOG_TIME FROM 1 FOR 10) = '".$c_date."' ORDER BY USER_LOG_ID";
		}
		break;
				
			
			//endswitch
	}
	// Anzeige query
	$message_find_string = $c_query_condition;
			
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
		
$db_result = db_log_query_list_items_1("USER_LOG_ID, USER_LOG_TIME, USER_LOG_ACTION, USER_LOG_ICON, USER_LOG_MODUL_ID ", "USER_LOGS", $c_query_condition);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Play-Out-Logs</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css"> @import url("../parts/style/style_srb_2.css"); </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<!--muss zum schluss, sonst geht slidemenu nicht-->
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
</script>
</head>
<body>
 
<?php 
echo "<div class='main'>";
include "../parts/site_elements/header_srb_2.inc";
include "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
echo "<div class='head_item_left'>Administration</div>";
if ( isset( $_GET['call_from_sendung'] ) ) {
	if ( $_GET['call_from_sendung'] == "yes" ) {
		include "../admin_srb_sendung_hf/parts/sg_hf_menu.inc";
	}	
} else {
	include "../admin_srb_user/parts/admin_menu.inc" ;
}
user_display();
echo "</div> <!--class=column_left-->";	
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message;
echo "</div>";
if ( isset( $_GET['call_from_sendung'] ) ) {
	if ( $_GET['call_from_sendung'] == "yes" ) {
		include "../admin_srb_sendung_hf/parts/sg_hf_toolbar.inc";
	}
} else {
	include "parts/po_toolbar.inc";
}		

echo "<div class='content'>";	
	
if ( $action_ok == "no" ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 
	echo "<p>Einstellungen der Bedingung: " .$message_find_string. "</p> <div id='line'> </div>\n";
	// Auswahl App
	echo "<form name='form1' action='play_out_log_find_list.php' method='POST' enctype='application/x-www-form-urlencoded'>\n";
	echo "<input type='hidden' name='action' value='".$action."'>\n";
	if ( $user_app_drop_down == "yes" ) {
		if ( isset($tbl_value_app_id)) {
			echo html_dropdown_from_table_1("USER_APPS", "USER_APP_DESC", "form_user_app", "input_text_a_6", $tbl_value_app_id);
		} else {	
			echo html_dropdown_from_table_1("USER_APPS", "USER_APP_DESC", "form_user_app", "input_text_a_6", "000");
		}
	}
			
	// Auswahl datum
	if ( $displ_dateform == "yes" ) {
		echo "Datum: <input type='TEXT' id='datepicker' name='form_k_datum' value='' size='10' maxlength='10'>";
	}

	echo "<input type='submit' value='Anzeigen'></form><br>";
	echo "<div class='line_a'> </div>\n";
		
	$z = 0;
	if ( $db_result) {		
		foreach ( $db_result as $item ) {
			$z += 1;
			if ( $z % 2 != 0 ) { 
				echo "<div class='content_row_a_2'>";	
			} else { 
				echo "<div class='content_row_b_2'>";
			}
			echo "<div style='height:40px; padding-right:2px; float:left;'><img src='../parts/pict/".rtrim($item['USER_LOG_ICON']).".ico' width='10' height='10' alt='Icon'> </div>" .$item['USER_LOG_TIME']." - ". rtrim($item['USER_LOG_MODUL_ID']). "<br>" .$item['USER_LOG_ACTION'];
			echo "</div>\n";
		}
		if ( $z == 0 ) { 
			echo "Keine Übereinstimmung gefunden...";
		} else {
			echo "<div class='line_a'> </div>\n<p>Gefunden: ".$z."</p>";
		}
	}
	if ( $z == 0 ) { 
		echo "Keine Übereinstimmung gefunden...";
	}
} // user_rights
echo "</div>";
echo "</div>";
echo "</div>";
?>
</body>
</html>