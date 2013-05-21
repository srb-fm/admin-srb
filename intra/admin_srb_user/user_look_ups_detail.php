<?php

/** 
* LookUps anzeigen 
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
$look_up_item = "no";
$look_up_desc = "";
if ( isset( $_GET['message'] ) ) { 
	$message .= $_GET['message'];
}
if ( isset( $_POST['message'] ) ) { 
	$message .= $_POST['message'];
}
if ( isset( $_GET['lu_item'] ) ) { 
	$look_up_item = $_GET['lu_item'];
}
if ( isset( $_POST['lu_item'] ) ) { 
	$look_up_item = $_POST['lu_item'];
}
if ( isset( $_GET['lu_desc'] ) ) { 
	$look_up_desc = $_GET['lu_desc'];
}
if ( isset( $_POST['lu_desc'] ) ) { 
	$look_up_desc = $_POST['lu_desc'];
}

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
	if ( isset( $_GET['id'] ) ) { 
		$id = $_GET['id'];
	}
	if ( isset( $_POST['id'] ) ) {
		$id = $_POST['id'];
	}
	
	/**
	* load_look_up
	*
	* Mit Tabellen verknoten   
	*
	* @param look_up_item $look_up_item Item halt
	* @param message      $message      Message
	* @param id           $id           ID
	*
	* @return array with tbl_row, field_id, field_description, look_up_desccription and message
	*
	*/	
	function load_look_up( $look_up_item , $message, $id  )
	{
		switch ( $look_up_item ) {
		case "ad_titel":
			$message = "Adresse Titel - Details anzeigen. ";
			$look_up_desc = "Titel";
			$c_query_condition = "AD_TITEL_ID = ".$id;
			$tbl_row = db_query_display_item_1("AD_TITEL", $c_query_condition);
			$result_field_id = $tbl_row->AD_TITEL_ID;	
			$result_field_desc = $tbl_row->AD_TITEL_DESC;
			break;

		case "iv_eigentuemer":
			$message = "Eigentuemer - Details anzeigen. ";
			$look_up_desc = "Eigentuemer";
			$c_query_condition = "IV_EIG_ID = ".$id;
			$tbl_row = db_query_display_item_1("IV_EIGENTUEMER", $c_query_condition);
			$result_field_id = $tbl_row->IV_EIG_ID;	
			$result_field_desc = $tbl_row->IV_EIG_DESC;
			break;
				
		case "iv_kategorie":
			$message = "Kategorie - Details anzeigen. ";
			$look_up_desc = "Kategorie";
			$c_query_condition = "IV_KAT_ID = ".$id;
			$tbl_row = db_query_display_item_1("IV_KATEGORIE", $c_query_condition);
			$result_field_id = $tbl_row->IV_KAT_ID;	
			$result_field_desc = $tbl_row->IV_KAT_DESC;
			break;

		case "sg_genre":
			$message = "Sendung Genre - Details anzeigen. ";
			$look_up_desc = "Genre";
			$c_query_condition = "SG_GENRE_ID = ".$id;
			$tbl_row = db_query_display_item_1("SG_GENRE", $c_query_condition);	
			$result_field_id = $tbl_row->SG_GENRE_ID;	
			$result_field_desc = $tbl_row->SG_GENRE_DESC;
			break;
							
		case "sg_sprache":
			$message = "Sendung Sprache - Details anzeigen. ";
			$look_up_desc = "Sprache";
			$c_query_condition = "SG_SPEECH_ID = ".$id;
			$tbl_row = db_query_display_item_1("SG_SPEECH", $c_query_condition);	
			$result_field_id = $tbl_row->SG_SPEECH_ID;	
			$result_field_desc = $tbl_row->SG_SPEECH_DESC;
			break;					
			//endswitch;
		}
		return array($tbl_row, $result_field_id, $result_field_desc, $look_up_desc, $message); 
	}
	
	/**
	* load_look_up_a
	*
	* Mit Tabellen verknoten   
	*
	* @param look_up_item $look_up_item Item halt
	*
	* @return array with table, field
	*
	*/
	function load_look_up_a( $look_up_item )	
	{
		switch ( $look_up_item ) {
		case "ad_titel":
			$tbl = "AD_MAIN";
			$field = "AD_TITEL_ID";
			break;

		case "iv_eigentuemer":
			$tbl = "IV_MAIN";
			$field = "IV_EIGENTUEMER_ID";
			break;
				
		case "iv_kategorie":
			$tbl = "IV_MAIN";
			$field = "IV_KATEGORIE_ID";
			break;

		case "sg_genre":
			$tbl = "SG_HF_CONTENT";
			$field = "SG_HF_CONT_SPEECH_ID";
			break;
							
		case "sg_sprache":
			$tbl = "SG_HF_CONTENT";
			$field = "SG_HF_CONT_SPEECH_ID";
			break;								
		//	endswitch;
		}	
		return array($tbl, $field );
	}
				
	/**
	* load_look_up_b
	*
	* Mit Tabellen verknoten   
	*
	* @param look_up_item $look_up_item Item halt
	*
	* @return array with table, field
	*
	*/
	function load_look_up_b( $look_up_item )	
	{
		switch ( $look_up_item ) {
		case "ad_titel":
			$tbl = "AD_TITEL";
			$field = "AD_TITEL_ID";
			break;

		case "iv_eigentuemer":
			$tbl = "IV_EIGENTUEMER";
			$field = "IV_EIG_ID";
			break;
				
		case "iv_kategorie":
			$tbl = "IV_KATEGORIE";
			$field = "IV_KAT_ID";
			break;

		case "sg_genre":
			$tbl = "SG_GENRE";
			$field = "SG_GENRE_ID";
			break;
							
		case "sg_sprache":
			$tbl = "SG_SPEECH";
			$field = "SG_SPEECH_ID";
			break;						
			//endswitch;
		}	
		return array($tbl, $field);
	}
				
				
	// action switchen
	if ( $id !="" ) { 	
		switch ( $action ) {
		case "display":	
			list($tbl_row, $result_field_id, $result_field_desc, $look_up_desc, $message) = load_look_up($look_up_item, $message, $id);
			break;
			
		case "check_delete":		
			// pruefen ob look_up in den haupttabellen verwendet, wird die id gefunden, dann loeschen nicht moeglich			
			$message .= $look_up_desc." zum Löschen prüfen! ";
			list($tbl, $field) = load_look_up_a($look_up_item);
			$db_value = db_query_load_value_n_by_id($tbl, $field, $id, 4);
			
			if ( $db_value != $id ) {
				// nicht gefunden, loeschen moeglich
				header("Location: user_look_ups_detail.php?action=delete&id=".$id."&kill_possible=yes&lu_item=".$look_up_item);
			} else {
				header("Location: user_look_ups_detail.php?action=delete&id=".$id."&kill_possible=no&lu_item=".$look_up_item);
			}
			break;

		case "delete":		
			list($tbl_row, $result_field_id, $result_field_desc, $look_up_desc, $message) = load_look_up($look_up_item, $message, $id);
			$message .= $look_up_desc." wirklich löschen? ";
			if ( isset( $_GET['kill_possible'] ) ) {	
				$kill_possible = $_GET['kill_possible'];
			}
			break;

		case "kill":		
			$message .= $look_up_desc." löschen! ";
			// pruefen ob bestaetigung passt
			$c_kill = db_query_load_item("USER_SECURITY", 0);

			if ( $_POST['form_kill_code'] == trim($c_kill)) {
				list($tbl, $field) = load_look_up_b($look_up_item);			
				$_ok = db_query_delete_item($tbl, $field, $id);
				
				if ( $_ok == "true" ) {
					$message = $look_up_desc." gelöscht!";
					$action_ok = "no";	
				} else { 
					$message .= "Löschen fehlgeschlagen";
				}
			} else { 
				$message .= "Keine Löschberechtigung!";	
			}	
			break;
												
			//endswitch;
		}
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-User</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	
</head>
<body>
<div class="column_large">
<div class="head_item_right">
<?php 
echo $message; 
echo "</div>\n";
echo "<div class='content'>";
if ( $action_ok == "no" ) { 
	return;
}
if ( !$tbl_row ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
			
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 	
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>".$look_up_desc."</div>";
	echo "<div class='content_column_2'>".$result_field_id." - ".$result_field_desc."</div>";
	echo "</div>\n";

	echo "<div class='line'> </div>\n";
			
	if ( $action == "delete" ) { 
		// wird verwendet
		if ( $kill_possible == "no" ) {
			echo "<div class='error_message'> ".$look_up_desc." wird in Haupttabelle verwendet, löschen nicht möglich!</div>";
		} else {
			// ist frei
			echo "<script>";
			echo '$( "#dialog-form" ).dialog( "open" )';
			echo "</script>";
			echo "<div id='dialog-form' title='Löschen dieser ".$look_up_desc." bestätigen'>";
			echo "<p>Diese ".$look_up_desc." kann erst durch Eingabe des Berechtigungscodes gelöscht werden!</p>";
			echo "<form action='user_look_ups_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
			echo "<input type='hidden' name='action' value='kill'>";
			echo "<input type='hidden' name='lu_item' value=".$look_up_item.">";
			echo "<input type='hidden' name='id' value=".$id.">";	
			echo "<input type='password' name='form_kill_code' value=''>"; 
			echo "<input type='submit' value='Jetzt löschen'></form></div>";				

		}
	}
			
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";
	if ( $_SESSION["log_rights"] == "A" ) {	
		echo "<li><a href='user_look_ups_edit.php?action=edit&amp;id=".$result_field_id."&amp;lu_item=".$look_up_item."'>Bearbeiten</a> ";
		if ( $action == "display" ) { 
			echo "<a href='user_look_ups_detail.php?action=check_delete&amp;id=".$result_field_id."&amp;lu_item=".$look_up_item."&amp;lu_desc=".$look_up_desc."'>Löschen</a> ";
		}
	}			
	echo "</ul>\n</div><!--menu_bottom-->";  
	echo "</div>\n";
} // user_rights
?>
</div>
</div>
</body>
</html>
