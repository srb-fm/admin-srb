<?php

/** 
* Sendung - Details fuer automatisierte Sendungen 
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
	
// check action	
if ( isset($_GET['action']) ) { 
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action']) ) { 
	$action = $_POST['action']; 
	$action_ok = true;
}
			
if ( $action_ok == true ) {
	if ( isset($_GET['sg_robot_id']) ) {	
		$id = $_GET['sg_robot_id'];
	}
	if ( isset($_POST['sg_robot_id']) ) { 
		$id = $_POST['sg_robot_id'];
	}
		
	// switch action
	if ( $id !="" ) {
		switch ( $action ) {
		case "display":		
			$message = "Automatisierte Sendung: Details anzeigen. ";
			$query_condition = "SG_HF_ROB_ID = ".$id;
			break;

		case "check_delete":		
			$message = "Automatisierte Sendung: zum Löschen prüfen! ";
			$query_condition = "SG_HF_ROB_ID = ".$id;
			header("Location: sg_hf_robot_detail.php?action=delete&sg_robot_id=".$id);
			break;

		case "delete":		
			$message = "Automatisierte Sendung wirklich löschen? ";
			$query_condition = "SG_HF_ROB_ID = ".$id;
			break;

		case "kill":		
			$message = "Automatisierte Sendung löschen. ";
			// check if confirmation is ok
			$c_kill = db_query_load_item("USER_SECURITY", 0);

			if ( $_POST['form_kill_code'] == trim($c_kill) ) {
				$_ok = db_query_delete_item("SG_HF_ROBOT", "SG_HF_ROB_ID", $id);
				if ( $_ok == "true" ) {
					$message = "Automatisierte Sendung gelöscht!";
					$action_ok = false;	
				} else { 
					$message = "Löschen fehlgeschlagen";
					$query_condition = "SG_HF_ROB_ID = ".$id;
				}
			} else { 
				$message = "Keine Löschberechtigung!";
				$query_condition = "SG_HF_ROB_ID = ".$id;
			}	
			break;
			//endswitch;
		}
	}

} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

// ok, retrieve data
if ( $action_ok == true ) {
	$tbl_row = db_query_display_item_1("SG_HF_ROBOT", $query_condition);
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung-Automatisierte Sendung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>

</head>
<body>
 
<div class="column_large">
	<div class="column_right">
<?php
echo "<div class='head_item_right'>";
echo $message; 
echo "</div>";
echo "<div class='content'>";
if ( $action_ok == false ) { 
	return;
}
if ( !$tbl_row ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 		
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Titel</div>";
	echo "<div class='content_column_2'>" .$tbl_row->SG_HF_ROB_TITEL. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Stichworte</div>";
	echo "<div class='content_column_2'>" .$tbl_row->SG_HF_ROB_STICHWORTE.  "</div>";
	echo "</div>\n";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Übernahme von extern</div>";
	echo "<div class='content_column_2'><label for='check1'>" ;
	if ( rtrim($tbl_row->SG_HF_ROB_VP_IN) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_vp' value='T' checked='checked' title='Wird übernommen' id='check1'> VP-Übernahme von extern";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_vp' value='T' title='Wird nicht übernommen' id='check1'> VP-Übernahme von extern";
	}				
	echo "</label></div></div>\n";			

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Pfad/Dateiname von extern</div>";
	echo "<div class='content_column_2'>" .$tbl_row->SG_HF_ROB_FILE_IN_DB. "</div>";
	echo "</div>\n";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Duplizierung</div>";
	echo "<div class='content_column_2'>" .db_query_load_value_by_id("SG_HF_ROB_DUB", "SG_HF_ROB_DUB_ID", $tbl_row->SG_HF_ROB_DUB_ID);
	echo " / Verschiebung zw. Erstsendung Lieferant und SRB: ".$tbl_row->SG_HF_ROB_SHIFT." Tage vor";	
	echo "</div>";
	echo "</div>\n";

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Übergabe nach extern</div>";
	echo "<div class='content_column_2'><label for='check2'>" ;
	if ( rtrim($tbl_row->SG_HF_ROB_VP_OUT) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_vp_out' value='T' checked='checked' title='Wird zur Verfügung gestellt' id='check2'> VP nach extern";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_vp_out' value='T' title='Wird nicht zur Verfügung gestellt' id='check2'> VP nach extern";
	}
	echo "</label> <label for='check3'>";
	if ( rtrim($tbl_row->SG_HF_ROB_OUT_DROPB) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_out_dropb' value='T' checked='checked' title='Via Dropbox' id='check3'> Dropbox";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_out_dropb' value='T' title='Nicht via Dropbox' id='check3'> Dropbox";
	}
	echo "</label> <label for='check4'>";
	if ( rtrim($tbl_row->SG_HF_ROB_OUT_FTP) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_out_ftp' value='T' checked='checked' title='Via ftp' id='check4'> FTP";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_out_ftp' value='T' title='Nicht via ftp' id='check4'> FTP";
	}

	echo "</label></div></div>\n";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Ordner Dropbox nach extern</div>";
	echo "<div class='content_column_2'>" .$tbl_row->SG_HF_ROB_FILE_OUT_DB. "</div>";
	echo "</div>\n";

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Ordner FTP nach extern</div>";
	echo "<div class='content_column_2'>" .$tbl_row->SG_HF_ROB_FILE_OUT_FTP. "</div>";
	echo "</div>\n";
	
	echo "<div class='line_a'> </div>\n";
		
	if ( $action == "delete" ) { 
		echo "<script>";
		echo '$( "#dialog-form" ).dialog( "open" )';
		echo "</script>";
		echo "<div id='dialog-form' title='Löschen dieser automatisierten Sendung bestätigen'>";
		echo "<p>Diese automatisierte Sendung kann erst durch Eingabe des Berechtigungscodes gelöscht werden!</p>";
		echo "<form name='form1' action='sg_hf_robot_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
		echo "<input type='hidden' name='action' value='kill'>";
		echo "<input type='hidden' name='sg_robot_id' value=".$tbl_row->SG_HF_ROB_ID.">";	
		echo "<input type='password' name='form_kill_code' value=''>"; 
		echo "<input type='submit' value='Jetzt löschen'></form></div>";				
	}
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";		
	echo "<li><a href='sg_hf_robot_edit.php?action=edit&amp;sg_robot_id=".$tbl_row->SG_HF_ROB_ID."'>Bearbeiten</a> ";
	if ( $action == "display" ) { 
		echo "<li><a href='sg_hf_robot_detail.php?action=check_delete&amp;sg_robot_id=".$tbl_row->SG_HF_ROB_ID."'>Löschen</a> ";
	}
	echo "</ul>\n</div><!--menu_bottom-->"; 
} // user_rights	
echo "</div>\n";
?>
</div>
</div>
</body>
</html>