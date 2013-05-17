<?php

/** 
* sendung wochenkalender anzeigen 
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
$message = "Sendungen ";

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
if ( $action_ok != "yes" ) { 
	$message = "Keine Anweisung. Nichts zu tun..... "; 
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
	switch ( $find_option ) {
		
	case "broadcast_week_date_all":
		$d_date_dest = date('Y-m-d');
		// Pruefen ob Datum uebergeben, sohnst aktuelles nehmen					
		if ( isset( $_POST['form_k_datum'] ) ) {
			if ( $_POST['form_k_datum'] != "" ) { 
				$d_date_dest = get_date_format_sql($_POST['form_k_datum']);	
			}
		}

		$j = substr($d_date_dest, 0, 4);
		$m = substr($d_date_dest, 5, 2);
		$d = substr($d_date_dest, 8, 2);
		$timestamp_dest = mktime(0, 0, 0, $m, $d, $j);
					
		//mit dem aktuellen Wochentag Montag und Sonntag errechnen:
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) +1, date("Y", $timestamp_dest)));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (7 -  date('w', $timestamp_dest))), date("Y", $timestamp_dest)));
		$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);				
		break;
					
	case "broadcast_week_all":
		// mit dem aktuellen Wochentag Montag und Sonntag errechnen:
		// Tag des Monats(d) minus Tag der Woche(w) = Montag der Woche 
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") - date('w')) +1, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") + (7 - date('w'))), date("Y")));
		$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
		break;
				
	case "broadcast_week_next_all":
		// mit dem aktuellen Wochentag Montag und Sonntag errechnen:
		// Tag des Monats(d) minus Tag der Woche(w) plus eine Woche = Montag der Woche 
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") - date('w')) + 8, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") + ( 14 - date('w'))), date("Y")));
		$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
		break;
					
	case "broadcast_week_previous_all":
		// mit dem aktuellen Wochentag Montag und Sonntag errechnen:
		// Tag des Monats(d) minus Tag der Woche(w) minus eine Woche = Montag der Woche 
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") - date('w')) + 1) - 7, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") + ( 7 - date('w')))-7), date("Y")));
		$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
		break;

		//endswitch;
	}
} else {
		$message = "Keine Suchbedingung! Kann nichts tun... "; 
}

if ( $action_ok !="no" ) { 
	$wochentage = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");	
	$c_query_condition_it = "A.SG_HF_INFOTIME = 'T' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."' ORDER BY A.SG_HF_TIME";
	$c_query_condition_mg = "A.SG_HF_MAGAZINE = 'T' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."' ORDER BY A.SG_HF_TIME";
	$c_query_condition_sg = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."' ORDER BY A.SG_HF_TIME";
	$db_result_it = db_query_sg_ad_list_items_1($c_query_condition_it);
	$db_result_mg = db_query_sg_ad_list_items_1($c_query_condition_mg);
	$db_result_sg = db_query_sg_ad_list_items_1($c_query_condition_sg);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Sendung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css" > @import url("../parts/style/style_srb_cal.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>

</head>
<body>
 
<?php 
echo "<div class='cal_main'>";
echo "<div class='cal_head_item'>";

echo $message;
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 	 
	echo "<div style='margin-top: -5px; float: right'>";
	echo "<form name='form1' action='sg_hf_cal_week.php' method='POST' enctype='application/x-www-form-urlencoded'>\n";
	echo "<input type='hidden' name='action' value='".$action."'>\n";				
	echo "<input type='hidden' name='find_option' value='broadcast_week_date_all'>\n";	
	echo "Datum: <input type='TEXT' id='datepicker' name='form_k_datum' value='' size='10' maxlength='10'>\n";
	echo "<input type='submit' value='Anzeigen'></form>\n";
	echo "</div>\n";
	echo "</div>\n";
	echo "<div class='cal_content'>";

	if ( $action_ok == "no" ) { 
		return;
	} 

	// Und siehe: Erster Tag
	$date_day = $date_begin;			
						
	for ($count = 1; $count < 8; $count++) {
		// Tag beginnen
		echo "<div class='cal_day'>\n";
		echo get_german_day_name_1($date_day);
								
		if ( 	$db_result_it	) {	
			foreach ($db_result_it as $item_it ) {
				// Infotime					
				if ( $date_day == substr($item_it['SG_HF_TIME'], 0, 10)) {
					if ( $item_it['SG_HF_FIRST_SG'] == "T" ) {
						echo "<div class='cal_content_row_a_it'>";								
					} else {
						echo "<div class='cal_content_row_b_it'>";	
					}
					echo substr($item_it['SG_HF_TIME'], 11, 8)." - ".substr($item_it['SG_HF_CONT_TITEL'], 0, 35);
					echo "</div>\n";
				}
			}
		}
						
		if ( $db_result_mg ) {
			echo "<br>";	
			foreach ($db_result_mg as $item_mg ) {
				// Magazine	
				if ( $date_day == substr($item_mg['SG_HF_TIME'], 0, 10)) {
					if ( $item_mg['SG_HF_FIRST_SG'] == "T" ) {
						echo "<div class='cal_content_row_a_mg'>";								
					} else {
						echo "<div class='cal_content_row_b_mg'>";	
					}			
					echo substr($item_mg['SG_HF_TIME'], 11, 8)." - ".substr($item_mg['SG_HF_CONT_TITEL'], 0, 35);
		     		echo "</div>\n";
				}
			}
		}

		if ( $db_result_sg ) {
			echo "<br>";	
			foreach ($db_result_sg as $item_sg ) {
				// Sendung normal											
				if ( $date_day == substr($item_sg['SG_HF_TIME'], 0, 10)) {
					if ( $item_sg['SG_HF_FIRST_SG'] == "T" ) {
						echo "<div class='cal_content_row_a'>";								
					} else {
						echo "<div class='cal_content_row_b'>";	
					}			
					echo substr($item_sg['SG_HF_TIME'], 11, 8)." - ".substr($item_sg['SG_HF_CONT_TITEL'], 0, 35);
					echo "</div>\n";
				}
			}
		}

		echo "</div>\n";	
		// Tag Ende
		// Einen Tag weiterzählen
		$j = substr($date_day, 0, 4);
		$m = substr($date_day, 5, 2);
		$d = substr($date_day, 8, 2);
		$date_day = date('Y-m-d', mktime(0, 0, 0, $m, $d + 1, $j));			
	}
} // user_rights
echo "</div>";
echo "</div>";	
?>
</body>
</html>