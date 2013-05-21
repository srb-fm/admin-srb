<?php

/** 
* Einstellungen Details
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
	if ( isset( $_GET['special_id'] ) ) {	
		$id = $_GET['special_id'];
	}
	if ( isset( $_POST['special_id'] ) ) { 	
		$id = $_POST['special_id'];
	}
		
	// action switchen
	if ( $id !="" ) { 
		switch ( $action ) {
		case "display":		
			$message .= "Einstellungen-Details anzeigen. ";
			$query_condition = "USER_SP_ID = ".$id;
			break;

		case "check_delete":		
			$message .= "Einstellung zum Löschen prüfen! ";
			$query_condition = "USER_SP_ID = ".$id;
			header("Location: user_special_detail.php?action=delete&special_id=".$id);
			break;

		case "delete":		
			$message .= "Einstellung wirklich löschen? ";
			$query_condition = "USER_SP_ID = ".$id;
			break;

		case "kill":		
			$message .= "Einstellung löschen. ";
			// pruefen ob bestätigung passt
			$c_kill = db_query_load_item("USER_SECURITY", 0);

			if ( $_POST['form_kill_code'] == trim($c_kill)) {
				$_ok = db_query_delete_item("USER_SPECIALS", "USER_SP_ID", $id);
				if ( $_ok == "true" ) {
					$message = "Einstellung gelöscht!";
					$action_ok = "no";	
				} else { 
					$message .= "Löschen fehlgeschlagen";
					$query_condition = "USER_SP_ID = ".$id;
				}
			} else { 
				$message .= "Keine Löschberechtigung!";
				$query_condition = "USER_SP_ID = ".$id;
			}	
			break;
			//endswitch;
		}
	}		
} else {
	$message .= "Keine Anweisung. Nichts zu tun..... "; 
}
	
// Alles ok, Daten holen
if ( $action_ok == "yes" ) {
	$tbl_row = db_query_display_item_1("USER_SPECIALS", $query_condition);
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-User-Specials</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");   </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
</head>
<body>
 
<div class="main">
<?php 
require "../parts/site_elements/header_srb_2.inc";
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
echo $message; 
echo "</div>";
echo "<div class='content'>";
if ( $action_ok == "no" ) { 
	return;
}
if ( !$tbl_row ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
		
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "A");
if ( $user_rights == "yes" ) { 	
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Bezeichnung</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_DESC. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Bezeichnung intern</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_SPECIAL.  "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 1</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_1. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 2</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_2. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 3</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_3. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 4</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_4. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 5</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_5. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 6</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_6. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 7</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_7. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 8</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_8. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 9</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_9. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 10</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_10. "</div>";
	echo "</div>\n";					
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 11</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_11. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 12</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_PARAM_12. "</div>";
	echo "</div>\n";									
	echo "<div class='content_row_a_8'>";
	echo "<div class='content_column_1'>Text</div>";
	//echo "<textarea class='textarea_2' name='form_sp_text' rows=80 cols=10>" .ltrim( $tbl_row->USER_SP_TEXT ). "</textarea>";
	echo "<div class='textbox_2' name='form_sp_text'>".ltrim($tbl_row->USER_SP_TEXT)."</div>";
	echo "</div>\n";			
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>ID</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_SP_ID. "</div>";	
	echo "</div>\n";			
	echo "<div class='line_a'> </div>\n";
			
	if ( $action == "delete" ) { 
		echo "<script>";
		echo '$( "#dialog-form" ).dialog( "open" )';
		echo "</script>";
		echo "<div id='dialog-form' title='Löschen dieser Einstellung bestätigen'>";
		echo "<p>Diese Einstellung kann erst durch Eingabe des Berechtigungscodes gelöscht werden!</p>";
		echo "<form name='form1' action='user_special_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
		echo "<input type='hidden' name='action' value='kill'>";
		echo "<input type='hidden' name='special_id' value=".$tbl_row->USER_SP_ID.">";	
		echo "<input type='password' name='form_kill_code' value=''>"; 
		echo "<input type='submit' value='Jetzt löschen'></form></div>";				
	}
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";		
	echo "<li><a href='user_special_edit.php?action=edit&amp;special_id=".$tbl_row->USER_SP_ID."'>Bearbeiten</a> ";
	if ( $action == "display" ) { 
		echo "<li><a href='user_special_detail.php?action=check_delete&amp;special_id=".$tbl_row->USER_SP_ID."'>Löschen</a> ";
	}
	echo "</ul>\n</div><!--menu_bottom-->"; 
} // user_rights	
echo "</div>\n";
?>
</div>
</div>
</body>
</html>