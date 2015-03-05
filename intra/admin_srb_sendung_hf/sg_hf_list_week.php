<?php

/** 
* Sendung Wochenliste anzeigen 
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
$displ_dateform = "no";

// check action	
$action_ok = false;
if ( isset( $_GET['action'] ) ) {
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset( $_POST['action'] ) ) { 
	$action = $_POST['action'];
	$action_ok = true;
}
		
if ( $action_ok == true ) {	
	switch ( $action ) {
	case "list": 
		$message = "Sendungen "; 
		break;
		//endswitch;
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
			
// check condition	
$find_option_ok = false;
if ( isset( $_GET['find_option'] ) ) { 
	$find_option = $_GET['find_option']; 
	$find_option_ok = true;
}
if ( isset( $_POST['find_option'] ) ) {
	$find_option = $_POST['find_option']; 
	$find_option_ok = true;
}		
	
if ( $find_option_ok == true and $action_ok == true ) {
	switch ( $action ) {	
	case "list": 
		switch ( $find_option ) {
		case "infotime_week_date_all":
			$d_date_dest = date('Y-m-d');
			$displ_dateform = "yes";
					
			// Pruefen ob Datum uebergeben, sonst aktuelles nehmen					
			if ( isset($_POST['form_k_datum']) ) {
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
			$c_query_condition = "A.SG_HF_INFOTIME = 'T' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."' ORDER BY A.SG_HF_TIME";
			$message_find_string = "Magazin-Sendungen der Woche";
			$message .= "Infotime-Beiträge der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
			// for prev and next buttons					
			// calc with monday day from prev week:
			$date_week_prev = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) -1, date("Y", $timestamp_dest))));
			// calc with sunday in upcoming week:				
			$date_week_next = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (8 -  date('w', $timestamp_dest))), date("Y", $timestamp_dest))));
			break;
									
		case "magazine_week_date_all":
			$d_date_dest = date('Y-m-d');
			$displ_dateform = "yes";
				
			// check if date transfered, otherwise current					
			if ( isset( $_POST['form_k_datum'] ) ) {
				if ( $_POST['form_k_datum'] != "" ) { 
					$d_date_dest = get_date_format_sql($_POST['form_k_datum']);
				}
			}
			$j = substr($d_date_dest, 0, 4);
			$m = substr($d_date_dest, 5, 2);
			$d = substr($d_date_dest, 8, 2);
		
			$timestamp_dest = mktime(0, 0, 0, $m, $d, $j);
			// calc with current weekday monday and sunday
			$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) +1, date("Y", $timestamp_dest)));
			$date_end = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (7 - date('w', $timestamp_dest))), date("Y", $timestamp_dest)));
			$c_query_condition = "A.SG_HF_MAGAZINE = 'T' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."' ORDER BY A.SG_HF_TIME";
			$message_find_string = "Magazin-Sendungen der Woche";
			$message .= "Magazin-Beiträge der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);				
			// for prev and next buttons					
			// mit Montag Tag in der vergangenen Woche berechnen:
			$date_week_prev = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) -1, date("Y", $timestamp_dest))));
			// mit Sonntag Tag in der kommenden Woche berechnen:				
			$date_week_next = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (8 - date('w', $timestamp_dest))), date("Y", $timestamp_dest))));					
			break;

		case "broadcast_normal_week_date_all":
			$d_date_dest = date('Y-m-d');
			$displ_dateform = "yes";
								
			if ( isset( $_POST['form_k_datum'] ) ) {
				if ( $_POST['form_k_datum'] != "" ) { 
					$d_date_dest = get_date_format_sql($_POST['form_k_datum']);	
				}
			}

			$j = substr($d_date_dest, 0, 4);
			$m = substr($d_date_dest, 5, 2);
			$d = substr($d_date_dest, 8, 2);
		
			$timestamp_dest = mktime(0, 0, 0, $m, $d, $j);		
			$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) +1, date("Y", $timestamp_dest)));
			$date_end = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (7 -  date('w', $timestamp_dest))), date("Y", $timestamp_dest)));
			$c_query_condition = "A.SG_HF_INFOTIME = 'F' AND A.SG_HF_MAGAZINE = 'F' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."' ORDER BY A.SG_HF_TIME";
			$message_find_string = "Normale Sendungen der Woche";
			$message .= "ohne Infotime und Magazin der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);				

			$date_week_prev = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) -1, date("Y", $timestamp_dest))));
				
			$date_week_next = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (8 - date('w', $timestamp_dest))), date("Y", $timestamp_dest))));
			break;
					
		case "broadcast_week_date_all":
			$d_date_dest = date('Y-m-d');
			$displ_dateform = "yes";
								
			if ( isset( $_POST['form_k_datum'] ) ) {
				if ( $_POST['form_k_datum'] != "" ) { 
					$d_date_dest = get_date_format_sql($_POST['form_k_datum']);
				}
			}

			$j = substr($d_date_dest, 0, 4);
			$m = substr($d_date_dest, 5, 2);
			$d = substr($d_date_dest, 8, 2);
		
			$timestamp_dest = mktime(0, 0, 0, $m, $d, $j);
			$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) +1, date("Y", $timestamp_dest)));
			$date_end = date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (7 - date('w', $timestamp_dest))), date("Y", $timestamp_dest)));
			$c_query_condition = "SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."' ORDER BY A.SG_HF_TIME";
			$message_find_string = "ES und WH der Woche";
			$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);

			$date_week_prev = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) - date('w', $timestamp_dest)) -1, date("Y", $timestamp_dest))));
				
			$date_week_next = get_date_format_deutsch(date('Y-m-d', mktime(0, 0, 0, date("m", $timestamp_dest), (date("d", $timestamp_dest) + (8 - date('w', $timestamp_dest))), date("Y", $timestamp_dest))));				
			break;
								
		case "broadcast_week_all":

			$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") - date('w')) +1, date("Y")));
			$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") + (7 - date('w'))), date("Y")));
			$c_query_condition = "SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."'ORDER BY A.SG_HF_TIME";
			$message_find_string = "ES und WH diese Woche";
			$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
			break;
				
		case "broadcast_week_next_all":

			$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") - date('w')) +8, date("Y")));
			$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ( date("d") + (14 - date('w'))), date("Y")));
			$c_query_condition = "SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."'ORDER BY A.SG_HF_TIME";
			$message_find_string = "ES und WH diese Woche";
			$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
			break;
					
		case "broadcast_week_previous_all":

			$date_begin = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") - date('w')) +1)-7, date("Y")));
			$date_end = date('Y-m-d', mktime(0, 0, 0, date("m"), ((date("d") + (7 - date('w')))-7), date("Y")));
			$c_query_condition = "SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) >= '".$date_begin."' AND SUBSTRING( A.SG_HF_TIME FROM 1 FOR 10) <= '".$date_end."'ORDER BY A.SG_HF_TIME";
			$message_find_string = "ES und WH diese Woche";
			$message .= "der Woche vom ".get_date_format_deutsch($date_begin)." bis ".get_date_format_deutsch($date_end);
			break;

			//endswitch;
		}
		break;
	// endswitch;
	}
} else {
	$message = "Keine Suchbedingung! Kann nichts tun... "; 
}

if ( $action_ok == true ) { 
	$db_result = db_query_sg_ad_list_items_1($c_query_condition);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css"); </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");</style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>	
	<script type="text/javascript">
		function week_date_next(date_next) {
			document.form1.form_k_datum.value=date_next;
			document.getElementById('form1').submit();
		}
		function week_date_prev(date_prev) {
			//alert(date_begin);
			document.form1.form_k_datum.value=date_prev;
			document.getElementById('form1').submit();
		}
	</script>
</head>
<body>
 

<?php 
echo "<div class='main'>";
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
require "parts/sg_hf_menu.inc";	
user_display();
echo "</div> <!--class=column_left-->";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message."\n";
echo "</div>";
require "parts/sg_hf_toolbar.inc";
echo "<div class='content' id='jq_slide_by_click'>";		

if ( $action_ok == false ) { 
	return;
}	
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) {
	// display dateform
	if ( $displ_dateform == "yes" ) {
		echo "<form id='form1' name='form1' action='sg_hf_list_week.php' method='POST' enctype='application/x-www-form-urlencoded'>\n";
		echo "<input type='hidden' name='action' value='".$action."'>\n";				
		echo "<input type='hidden' name='find_option' value='".$find_option."'>\n";	
		echo "Datum: <input type='TEXT' id='datepicker' name='form_k_datum' value='' size='10' maxlength='10'>\n";
		echo "<input type='submit' name='form_fire' value='Anzeigen'> <input type='button' name='next' value='<< Woche' onClick='week_date_prev(\"".$date_week_prev."\")'> <input type='button' name='next' value='Woche >>' onClick='week_date_next(\"".$date_week_next."\")'></form>\n";
		echo "<div class='line_a'> </div>\n";
	}
	// dateform end

	$z = 0;
	if ($db_result) {				
		foreach ($db_result as $item) {
			$z += 1;
			// ueberschrift mit wochentag
			$c_date = substr($item['SG_HF_TIME'], 0, 10);
			$timestamp = mktime(0, 0, 0, substr($c_date, 5, 2), substr($c_date, 8, 2), substr($c_date, 0, 4));
			$c_date_new = date("d.n.Y", $timestamp);
			$c_day_name = get_german_day_name(date("l", $timestamp));
			    
			if ( isset( $c_date_current )) {
			  	if ( $c_date_current != $c_date_new ) { 
			  		echo "<div class='content_row_1'>".$c_day_name.", ".$c_date_new."</div>";
			  		$c_date_current = $c_date_new;
			  	}
			} else {
			  	echo "<div class='content_row_1'>".$c_day_name.", ".$c_date_new."</div>";
			  	$c_date_current = $c_date_new;
			}
			    
			// Listcolors
			// attantion of duration
			switch (rtrim($item['SG_HF_DURATION'])) {
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
								
			// item display				
			echo "<a href='sg_hf_detail.php?action=display&amp;sg_id=".$item['SG_HF_ID']."' class='c_box'>";
			echo html_sg_state_a(trim($item['SG_HF_FIRST_SG']), rtrim($item['SG_HF_ON_AIR']), rtrim($item['SG_HF_CONT_FILENAME']))."</a>";
			echo " ".substr($item['SG_HF_TIME'], 11, 8)." - ".substr($item['SG_HF_CONT_TITEL'], 0, 50)." - ".substr($item['AD_NAME'], 0, 15)."";
			echo "</div>";

			echo "<div class='content_row_toggle_head_3'><img src='../parts/pict/form.gif' title='Erweiterte Informationen' alt='Zusaetze'></div>\n";
			echo "<div class='content_row_toggle_body_3'>";
			if ( $item['SG_HF_CONT_UNTERTITEL'] != "" ) { 
					echo substr($item['SG_HF_CONT_UNTERTITEL'], 0, 40)."<br>"; 
			}
				echo $item['AD_VORNAME']." ".$item['AD_NAME']."<br>";				
				echo $item['SG_HF_CONT_FILENAME']."<br>";
				echo "Länge: ".$item['SG_HF_DURATION'];
				echo "<br>  <a href='sg_hf_reg_form.php?action=print&amp;sg_id=".$item['SG_HF_ID']."&amp;ad_id=".$item['SG_HF_CONT_AD_ID']."' target='_blank'>Sendeanmeldung drucken</a> ";
				echo "</div>\n";					
		}
	}
	echo "<div class='content_footer'>";
	if ( $z == 0 ) { 	
		echo "Keine Übereinstimmung gefunden...";
	} else {		
		echo "Gefunden: ".$z. " ::: "; 
	}
	echo "</div>";
} // user_rights

echo "</div><!--content-->";
echo "</div><!--column_right-->";
echo "</div><!--class=main-->";
?>

<div id="back-to-top">Scroll Top</div>
</body>
</html>