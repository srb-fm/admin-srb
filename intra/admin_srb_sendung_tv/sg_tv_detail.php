<?php


/** 
* sendung details anzeigen 
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
$error_message = "";
if ( isset( $_GET['message'] ) ) { 
	$message .= $_GET['message'];
}
if ( isset( $_POST['message'] ) ) { 
	$message .= $_POST['message'];
}
if ( isset( $_GET['error_message'] ) ) { 
	$error_message .= $_GET['error_message'];
}
if ( isset( $_POST['error_message'] ) ) { 
	$error_message .= $_POST['error_message'];
}
	
$action_ok = "no";
// Dateipruefung ja/nein
$file_exist_check = "yes";
	
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
	if ( isset( $_GET['sg_id'] ) ) {
		$id = $_GET['sg_id'];
	}
	if ( isset( $_POST['sg_id'] ) ) {	
		$id = $_POST['sg_id'];
	}
		
	// Audiodatei pruefen oder nicht (nach Neuaufnahme nicht pruefen, da ja noch keine da sein kann)
	if ( isset($_GET['check_file']) ) {	
		$file_exist_check = $_GET['check_file'];
	}
			
	// action switchen
	if ( $id !="" ) { 
		switch ( $action ) {
		case "display":		
			$message = "Sendung-TV-Details anzeigen. ";
			break;

		case "show_dubs":		
			$message = "Sendung-TV-Details und Wiederholungen anzeigen. ";
			break;
			
		case "change_filename":
			$tbl_fields_values = "SG_TV_CONT_FILENAME='".replace_umlaute_sonderzeichen($_POST['form_sg_filename'])."'";
			$error_message = db_query_update_item_a("SG_TV_CONTENT", $tbl_fields_values, "SG_TV_CONT_ID =".$_POST['sg_content_id']);		
			//$message .= "Sendung-TV-Details anzeigen. ";
			header("Location: sg_tv_detail.php?action=display&sg_id=".$_POST['sg_id']."&error_message=".$error_message);
			break;
					
		//	endswitch;
		}
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

// Ende $action_ok == "yes" 
// $action_ok kann auf "no" gesetzt worden sein, deshalb weiter mit neuer Prüfung
		
if ( $action_ok == "yes" ) {
	$tbl_row_sg = db_query_sg_tv_display_item($id);
	if ( !$tbl_row_sg ) { 
		$message = "Fehler bei Abfrage Sendung-TV!"; 
		$action_ok = "no";
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung-TV</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
</head>
<body>
<?php
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message; 
if ( $action_ok == "yes" ) { 
	html_sg_state($tbl_row_sg->SG_TV_FIRST_SG, $tbl_row_sg->SG_TV_ON_AIR, $tbl_row_sg->SG_TV_CONT_FILENAME);
}
echo "</div>";
		 
echo "<div class='content'>\n";
if ( $action_ok == "no" ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Datum/ Zeit/ Länge</div>";
	echo "<div class='content_column_4'>" .get_date_format_deutsch(substr($tbl_row_sg->SG_TV_TIME, 0, 10)).  "</div>";
	echo "<div class='content_column_4'>" .substr($tbl_row_sg->SG_TV_TIME, 11, 8). " </div>";
	echo "<div class='content_column_4'>" .rtrim($tbl_row_sg->SG_TV_DURATION). " </div>";
	echo "</div>\n";

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Titel</div>";
	echo "<div class='content_column_2'>" .$tbl_row_sg->SG_TV_CONT_TITEL. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Untertitel</div>";
	echo "<div class='content_column_2'>" .$tbl_row_sg->SG_TV_CONT_UNTERTITEL. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Sendeverantwortlich</div>";
	echo "<div class='content_column_2'>" .$tbl_row_sg->AD_VORNAME." " .$tbl_row_sg->AD_NAME.", ".$tbl_row_sg->AD_ORT. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Stichworte</div>";
	echo "<div class='content_column_2'>" .$tbl_row_sg->SG_TV_CONT_STICHWORTE."</div>" ;
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Internet</div>";
	echo "<div class='content_column_2'>" .$tbl_row_sg->SG_TV_CONT_WEB. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class=content_column_1>Genre/ Sprache</div>";
	echo "<div class='content_column_3'>" .db_query_load_value_by_id("SG_TV_GENRE", "SG_TV_GENRE_ID", rtrim($tbl_row_sg->SG_TV_CONT_GENRE_ID)). "</div>";
	echo "<div class='content_column_3'>" .rtrim($tbl_row_sg->SG_TV_CONT_SPEECH). "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Einstellungen</div>";
	echo "<div class='content_column_5'>" ;
	if ( rtrim($tbl_row_sg->SG_TV_ON_AIR) == "T") {
		echo "<input type='checkbox' name='form_sg_on_air' value='T' checked='checked' title='Wird gesendet'> Auf Sendung ";
	} else { 
		echo "<input type='checkbox' name='form_sg_on_air' value='T' title='v'> Auf Sendung ";
	}
	if ( rtrim($tbl_row_sg->SG_TV_INFOTIME) == "T" ) {
		echo "<input type='checkbox' name='form_sg_infotime' value='T' checked='checked' title='InfoTime'> InfoTime ";
	} else { 
		echo "<input type='checkbox' name='form_sg_infotime' value='T' title='InfoTime'> InfoTime ";
	}
	if ( rtrim($tbl_row_sg->SG_TV_CONT_TEAMPRODUCTION) == "T") {
		echo "<input type='checkbox' name='form_sg_teamprod' value='T' checked='checked' title='Teamproduktion'> Teamp.";
	} else { 
		echo "<input type='checkbox' name='form_sg_teamprod' value='T' title='Teamproduktion'> Teamp.";
	}
	echo "</div></div>\n";
			
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Datenträger</div>";
	echo "<div class='content_column_4'>" .rtrim($tbl_row_sg->SG_TV_CONT_CARRIER_NR).  "</div>";
	echo "<div class='content_column_4'>" .rtrim($tbl_row_sg->SG_TV_CONT_AR_TC_BEGIN). " </div>";
	echo "<div class='content_column_4'>" .rtrim($tbl_row_sg->SG_TV_CONT_AR_TC_END). " </div>";

	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Content/ Sendung-Nr</div>";
	echo "<div class='content_column_4'>" .rtrim($tbl_row_sg->SG_TV_CONT_NR). "</div>";
	echo "<div class='content_column_4'>" .rtrim($tbl_row_sg->SG_TV_NR). "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>"; 
	echo "<div class='content_column_1'>Regieanweisung</div>";
	echo "<div class='content_column_2'>" .$tbl_row_sg->SG_TV_CONT_REGIEANWEISUNG. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Dateiname</div>";
	echo "<form name='form1' id='sg_edit_form' action='sg_tv_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
	echo "<input type='hidden' name='action' value='change_filename'>";
	echo "<input type='hidden' name='sg_id' value='".rtrim($tbl_row_sg->SG_TV_NR)."'>";	
	echo "<input type='hidden' name='sg_content_id' value='".$tbl_row_sg->SG_TV_CONT_ID."'>";	

	if ( $tbl_row_sg->SG_TV_CONT_FILENAME !="" ) {
		//echo "<div class='content_column_2'>" .$tbl_row_sg->SG_TV_CONT_FILENAME. "</div>";
		echo "<input type='text' name='form_sg_filename' class='text_1' maxlength='100' value='".$tbl_row_sg->SG_TV_CONT_FILENAME."' >";
	} else {
		echo "<input type='text' name='form_sg_filename' class='text_1 blink' maxlength='100' value='".rtrim($tbl_row_sg->SG_TV_CONT_NR)."_".replace_umlaute_sonderzeichen($tbl_row_sg->AD_NAME)."_Filename_fehlt' >";
	}
	if ( $_SESSION["log_rights"] <= "B" ) {
		echo "<div style='float: left'><input type='submit' value='Speichern' ></div>";
	} else {
		echo "<div style='float: left'>Keine Berechtigung zum Ändern</div>";
	} 
	echo "</form>";
	echo "</div>\n";

	echo "<br>\n<span class='error_message'>".$error_message."</span>";
						
	echo "<br>\n<span class='txt'> </span>"; // dummy damit warning_div nicht ueberlappt 
	echo "\n</div><!--content wieder zu-->"; 
} // user_rights
echo "</div><!--class=column_right-->";
?>
</body>
</html>