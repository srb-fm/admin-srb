<?php

/** 
* LookUps listen
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
$action_ok = "no";
	
$look_up_field ="no";
	
// info ausgabebegrenzung auf 25 datensaetze:
// hier anders als sonst!!
// der abfrage_condition wird beim ersten aufruf das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den naechsten satzen wird die skip-anzahl in der url zugrechnet: (ausgabebegrenzung 2) 
// beim naechsten aufruf wird dann das limit aus dem get in die abfrage uebernommen (ausgabebegrenzung 3)
	
// action pruefen	
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
	switch( $action ) {		
	case "list": 

		switch( $find_option ) {
		case "ad_titel":
			$c_query_condition = "upper( AD_TITEL_DESC ) >= 'A' ORDER BY AD_TITEL_ID";
			$message_find_string = "Adresse Titel Gesamtliste alphabetisch";
			$tbl =  "AD_TITEL";						
			$tbl_fields = "AD_TITEL_ID, AD_TITEL_DESC";
			$c_field_id = "AD_TITEL_ID";
			$c_field_desc = "AD_TITEL_DESC";
			break;

		case "iv_eigentuemer":
			$c_query_condition = "upper( IV_EIG_DESC ) >= 'A' ORDER BY IV_EIG_ID";
			$message_find_string = "Inventar Eigentuemer Gesamtliste alphabetisch";
			$tbl =  "IV_EIGENTUEMER";
			$tbl_fields = "IV_EIG_ID, IV_EIG_DESC";						
			$c_field_id = "IV_EIG_ID";
			$c_field_desc = "IV_EIG_DESC";
			break;
														
		case "iv_kategorie":
			$c_query_condition = "upper( IV_KAT_DESC ) >= 'A' ORDER BY IV_KAT_ID";
			$message_find_string = "Inventar Kategorie Gesamtliste alphabetisch";
			$tbl =  "IV_KATEGORIE";
			$tbl_fields = "IV_KAT_ID, IV_KAT_DESC";						
			$c_field_id = "IV_KAT_ID";
			$c_field_desc = "IV_KAT_DESC";
			break;
					
		case "sg_genre":
			$c_query_condition = "upper( SG_GENRE_DESC ) >= 'A' ORDER BY SG_GENRE_ID";
			$message_find_string = "Sendung Genre Gesamtliste alphabetisch";
			$tbl =  "SG_GENRE";
			$tbl_fields = "SG_GENRE_ID, SG_GENRE_DESC";						
			$c_field_id = "SG_GENRE_ID";
			$c_field_desc = "SG_GENRE_DESC";
			break;
					
		case "sg_sprache":
			$c_query_condition = "upper( SG_SPEECH_DESC ) >= 'A' ORDER BY SG_SPEECH_ID";
			$message_find_string = "Sendung Sprache Gesamtliste alphabetisch";
			$tbl =  "SG_SPEECH";
			$tbl_fields = "SG_SPEECH_ID, SG_SPEECH_DESC";						
			$c_field_id = "SG_SPEECH_ID";
			$c_field_desc = "SG_SPEECH_DESC";
			break;					
			//endswitch; // $find_option
		}
		break;
			//endswitch; // $action
	}
} else {
	$message = "Keine Suchbedingung! Kann nichts tun... "; 
} //$find_option_ok = "yes" 
	
$db_result = db_query_list_items_limit_1($tbl_fields, $tbl, $c_query_condition, $c_limit);
		
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-User</title>
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
require  "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
echo "<div class='head_item_left'>";
echo "Einstellungen";
echo "</div>";
require "parts/admin_menu.inc";
user_display();
echo "</div>";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";	
echo $message_find_string."\n";
echo "</div>";
echo "<div class='content' id='jq_slide_by_click'>";
if ( $action_ok == "no" ) { 
	return;
} 
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 
	if ( $db_result ) {		
		foreach ( $db_result as $item ) {	
			$z += 1;
			$result_field_id = $item[$c_field_id];
			$result_field_desc = $item[$c_field_desc];
			// Listcolors					
			if ( $z % 2 != 0 ) { 
				echo "<div class='content_row_a_4'>";
			} else { 
				echo "<div class='content_row_b_4'>";
			}
			echo "<a href='../admin_srb_user/user_look_ups_detail.php?action=display&amp;id=".$result_field_id."&amp;lu_item=".$find_option."' class='c_box_3'>".$result_field_id." - ".$result_field_desc."</a>";					     	
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
			echo " >> <a href='user_look_ups_list.php?action=".$action."&amp;find_option=".$find_option."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a>";					
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