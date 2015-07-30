<?php

/** 
* Sendung - automatisierte Sendungen lsiten 
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
	
// info ausgabebegrenzung auf 25 datensaetze:
// hier anders als sonst!!
// der abfrage_condition wird beim ersten aufruf das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den nächsten satzen wird die skip-anzahl in der url zugrechnet: (ausgabebegrenzung 2) 
// beim naechsten aufruf wird dann das limit aus dem get in die abfrage uebernommen (ausgabebegrenzung 3)
	
// check action	
if ( isset($_GET['action'] ) ) {	
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action'] ) ) { 
	$action = $_POST['action']; 
	$action_ok = true;
}
		
if ( $action_ok != true ) { 
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
	
// ausgabebegrenzung
if ( isset( $_GET['find_limit_skip'] ) ) {
	$find_limit_skip = $_GET['find_limit_skip'];
} else {
	$find_limit_skip = "no";
}	
if ( 	$find_limit_skip == "no" ) {
	// ausgabebegrenzung 1
	$c_limit = "FIRST 25";			
	$z = 0;
} else {
	// ausgabebegrenzung 3
	$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
	$z = $find_limit_skip;
} 
		
// check condition	
$find_option_ok = "no";
if ( isset($_GET['find_option'] ) ) {
	$find_option = $_GET['find_option'];	
	$find_option_ok = "yes";
}
if ( isset($_POST['find_option'] ) ) {
	$find_option = $_POST['find_option'];	
	$find_option_ok = "yes";
}		
	
if ( $find_option_ok = "yes" ) {
	switch ( $action ) {			
	case "list": 
		switch ( $find_option ) {
		case "sg_titel":
		  	$c_query_condition = "upper( SG_HF_ROB_TITEL ) >= 'A' ORDER BY SG_HF_ROB_TITEL";
			$message_find_string = "Sendung-Automationen Gesamtliste alphabetisch";
			$tbl =  "SG_HF_ROBOT";						
			$tbl_fields = "SG_HF_ROB_ID, SG_HF_ROB_TITEL, SG_HF_ROB_VP_IN, SG_HF_ROB_DUB";
			break;
					
		//endswitch; // $find_option
		}
		break;
		//	endswitch; // $action
	}
} else {
	$message = "Keine Suchbedingung! Kann nichts tun... "; 
} //$find_option_ok = "yes" 
	
	
$db_result = db_query_list_items_limit_1($tbl_fields, $tbl, $c_query_condition, $c_limit);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung-Automationen</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>
	
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>	
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
	
	<script>
		$(document).ready(function() {
			$(".c_box").colorbox({width:"835px", height:"520px", opacity:"0.33", overlayClose:false, iframe:true,
			onClosed:function() { location.reload(true); } });
			});			
		
	</script>
</head>
<body>
 
<div class="main">
<?php 
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
require "parts/sg_hf_menu.inc"; 		
user_display();		
echo "</div>";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n";
echo "</div>";
echo "<div class='content' id='jq_slide_by_click'>";
if ( $action_ok == false ) { 
	return;
} 
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	
	if ($db_result ) {
		foreach ($db_result as $item ) {	
			$z +=1;
			// Listcolors					
			if ( $z % 2 != 0 ) { 
				echo "<div class='content_row_a_4'>";
			} else { 
				echo "<div class='content_row_b_4'>";
			}
			echo "<a href='../admin_srb_sendung_hf/sg_hf_robot_detail.php?action=display&amp;sg_robot_id=".$item['SG_HF_ROB_ID']."&amp;lu_item=".$find_option."' class='c_box'>".$item['SG_HF_ROB_ID']." - ".$item['SG_HF_ROB_TITEL']."</a>";		     	
			echo "</div>";
		}
	}

	if ( $z == 0 ) {	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$x = $z / 25;
		$zz = $z+1;	
		echo "<div class='content_footer'> Gefunden: ".$z. " ::: "; 	
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			echo " >> <a href='sg_hf_robot_find_list.php?action=".$action."&amp;find_option=".$find_option."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a>";					
		}
		echo "</div>";						
	}
} // user_rights
?>
</div><!--content-->
&nbsp;
</div><!--column_right-->
</div><!--main-->
</body>
</html>