<?php

/** 
* sendung wochenkalender drucken 
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

// check action	
$action_ok = false;

if ( isset($_GET['action']) ) {
	$action = $_GET['action']; 
	$action_ok = true;
}
if ( isset($_POST['action']) ) {
	$action = $_POST['action']; 
	$action_ok = true;
}

// check condition	
$find_option_ok = false;

if ( isset($_GET['find_option']) ) {
	$find_option = $_GET['find_option'];
	$find_option_ok = true;
}
if ( isset($_POST['find_option']) ) {
	$find_option = $_POST['find_option'];
	$find_option_ok = true;
}

if ( $find_option_ok == true and $action_ok == true ) {
	switch ( $action ) {
			
	// mit dem aktuellen Wochentag Montag und Sonntag errechnen:
	// Tag des Monats(d) minus Tag der Woche(w) minus eine Woche = Montag der Woche 
	// $date_query nimmt aktuellen datum als unix-timestamp auf
	case "list_date": 
		$date_query = strtotime(get_date_format_sql($_POST['sg_datum']));
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m", $date_query), (date("d", $date_query) - date('w', $date_query)) +1, date("Y", $date_query)));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m", $date_query), ( date("d", $date_query) + ( 7 - date('w', $date_query))), date("Y", $date_query)));
		$dayback = 0;
		$dayforwards = 0;
		// find_option von datums-auswahl-form auf optionen aus menue anpassen 
		switch($find_option) {
		case "Info-Time":
			$find_option = "print_week_infotime" ;
			break;
		case "Magazin":
			$find_option = "print_week_magazine" ;
			break;
		case "Sendungen":
			$find_option = "print_week_broadcast" ;
			break;					
		}
		break;			
	
	case "list_dayback_7":
		$date_query = strtotime(date('Y-m-d')); 
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") - date('w')) +1) -7, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") + ( 7 - date('w'))) -7), date("Y")));
		$dayback = 7;
		$dayforwards = 0;
		break;

	case "list_dayback_14":
		$date_query = strtotime(date('Y-m-d')); 
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") - date('w')) +1)-14, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") + ( 7 - date('w')))-14), date("Y")));
		$dayback = 14;
		$dayforwards = 0;
		break;

	case "list_forwards_7":
		$date_query = strtotime(date('Y-m-d')); 
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") - date('w')) +1)+7, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") + ( 7 - date('w')))+7), date("Y")));
		$dayback = 0;
		$dayforwards = 7;
		break;

	case "list_forwards_14":
		$date_query = strtotime(date('Y-m-d')); 
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") - date('w')) +1)+14, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") + ( 7 - date('w')))+14), date("Y")));
		$dayback = 0;
		$dayforwards = 14;
		break;

	case "list_forwards_21": 
		$date_query = strtotime(date('Y-m-d'));
		$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") - date('w')) +1)+21, date("Y")));
		$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") + ( 7 - date('w')))+21), date("Y")));
		$dayback = 0;
		$dayforwards = 21;
		break;

		//endswitch;
	}
					
	switch ( $find_option ) {
	case "print_week_infotime":
		$message = "Sendungen Info-Time, Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
		$c_query_condition_sendeart = "SG_HF_INFOTIME = 'T'";
		break;
				
	case "print_week_magazine":
		$message = "Sendungen Magazin, Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
		$c_query_condition_sendeart = "SG_HF_MAGAZINE = 'T'";
		break;
					
	case "print_week_broadcast":
		$message = "Sendungen normal, Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
		$c_query_condition_sendeart = "SG_HF_INFOTIME = 'F' AND SG_HF_MAGAZINE = 'F'";
		break;
	//endswitch;
	}
} else {
	$message = "Keine Suchbedingung! Kann nichts tun... "; 
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Sendung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css" > @import url("../parts/style/style_srb_cal_print.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
</head>
<body onload="javascript:window.print(); return true;">
<?php
echo "<div class='head_line'>\n";
echo "<div class='logo'><img src='../../parts/pict/logo_user.jpg' width='145' heigth='40'></div>";
echo "<div class='head_box'>". $message ."</div>\n";		
echo "<div class='us_box'>".date("d.m.Y")."</div>\n";		
echo html_header_srb_print_b();
echo "</div>";		
echo "<div class='cal_content'>\n";
					
if ( $action_ok == false ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 		
	$z = 0;
	$wochentage = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
		
	for ($count = 1; $count < 8; $count++) {
		echo "<div class='cal_day'>\n";
		// wochentags-name
		echo "<b>".$wochentage[date("w", mktime(0, 0, 0, date("m", $date_query), (date("d", $date_query) - date('w', $date_query)) + $count - $dayback + $dayforwards, date("Y", $date_query)))]."</b>";
		// wochentags-datum 
		$date_day = date('Y-m-d', mktime(0, 0, 0, date("m", $date_query), (date("d", $date_query) - date('w', $date_query)) + $count - $dayback + $dayforwards, date("Y", $date_query)));
												
		$c_query_condition = $c_query_condition_sendeart. " AND SUBSTRING( SG_HF_TIME FROM 1 FOR 10) = '".$date_day."' ORDER BY SG_HF_TIME";
		$db_result = db_query_sg_ad_list_items_1($c_query_condition);
		// wochentags-inhalt
		if ($db_result) {
			foreach ($db_result as $item ) {				
				$z += 1;
				if ( $item['SG_HF_FIRST_SG'] == "T" ) {
					echo "<div class='cal_content_row_a'>\n";	
				} else {
					echo "<div class='cal_content_row_b'>\n";	
				}
				echo substr($item['SG_HF_TIME'], 11, 8)." - ".$item['AD_NAME']."<br>".substr($item['SG_HF_CONT_TITEL'], 0, 25);
				echo "</div>\n";
			}
		}

		echo "</div>\n";
	}	
	echo "<div class='line_break'><br> </div>\n<div class='footer'>".$message. ", Gesamtzahl: ".$z."</div>\n";	
} // user_rights
?>
</body>
</html>