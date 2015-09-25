<?php

/** 
* Sendung exchange ftp list 
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
		
if ( $action_ok == true ) {	
	switch ( $action ) {
	case "list": 
		$message = "Übrnahmen "; 
		break;
		//endswitch;
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
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
	case "list": 
		switch ( $find_option ) {
		case "all":
			$time_back = time() - (3660);
			$s_date_back = date('Y-m-d H:i:s', $time_back);
			$c_query_condition = "SUBSTRING( EX_LOG_TIME FROM 1 FOR 19) >= '".$s_date_back."' ORDER BY EX_LOG_TIME";
			$message = "Alle Übernahmen aktuell";
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
	$db_result = db_log_query_list_items_1("EX_LOG_ID, EX_LOG_TIME, EX_LOG_FILE ", "EXCHANGE_LOGS", $c_query_condition);
	$db_result_Station = db_query_list_items_1("AD_NAME, AD_FIRMA ", "AD_MAIN", "AD_STICHWORT='Station-ID' ORDER BY AD_NAME");
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung Übernahmen</title>
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

	$z = 0;
	if ($db_result) {	
		foreach ($db_result_Station as $item_station) {	
			echo "<div class='content_row_1'>".$item_station["AD_FIRMA"]." (".$item_station["AD_NAME"].")</div>";	
			
			foreach ($db_result as $item) {
				$filename = new SplFileInfo($item['EX_LOG_FILE']);
				$fileext = $filename->getExtension();
				$z += 1;
				// item display
				if (substr($item['EX_LOG_FILE'], 11, 3) == $item_station["AD_NAME"] ) {
				
				if ( $z % 2 != 0 ) {
					echo "<div class='content_row_a_4'>";
				} else { 
					echo "<div class='content_row_b_4'>";
				}

				if ($fileext == "mp3") {
					echo "<img src='../parts/pict/speaker.png' width='16px' height='16px' alt='Datei herunterladen und vorhören'> ";
					echo "<a href='sg_hf_exchange_detail.php?action=play&amp;sg_file=".$item['EX_LOG_FILE']."' class='c_box' Title='Datei herunterladen und vorhören'>";				
				}
				if ($fileext == "txt") {
					echo "<img src='../parts/pict/1279186092_reports.png' width='16px' height='16px' alt='Metadaten anzeigen'> ";
					echo "<a href='sg_hf_exchange_detail.php?action=display&amp;sg_file=".$item['EX_LOG_FILE']."' class='c_box' Title='Metadaten anzeigen'>";		
				}
				echo $item['EX_LOG_FILE']."</a>";
				echo "</div>";
					
				}
			}
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