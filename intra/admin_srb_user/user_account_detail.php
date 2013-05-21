<?php

/** 
* User Details anzeigen 
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
	if ( isset( $_GET['account_id'] ) ) {	
		$id = $_GET['account_id'];
	}
	if ( isset( $_POST['account_id'] ) ) { 
		$id = $_POST['account_id'];
	}
		
	// action switchen
	if ( $id !="" ) { 
		switch( $action ) {
		case "display":		
			$message = "Benutzer-Details anzeigen. ";
			$query_condition = "USER_ID = ".$id;
			break;

		case "check_delete":		
			$message = "Benutzer zum Löschen prüfen! ";
			$query_condition = "USER_ID = ".$id;
			header("Location: user_account_detail.php?action=delete&account_id=".$id);
			break;

		case "delete":		
			$message = "Benutzer wirklich löschen? ";
			$query_condition = "USER_ID = ".$id;
			break;

		case "kill":		
			$message = "Benutzer löschen. ";
			// pruefen ob bestaetigung passt
			$c_kill = db_query_load_item("USER_SECURITY", 0);

			if ( $_POST['form_kill_code'] == trim($c_kill) ) {
				$_ok = db_query_delete_item("USER_ACCOUNT", "USER_ID", $id);
				if ( $_ok == "true" ) {
					$message = "Benutzer gelöscht!";
					$action_ok = "no";	
				} else { 
					$message .= "Löschen fehlgeschlagen";
					$query_condition = "USER_ID = ".$id;
				}
			} else { 
				$message .= "Keine Löschberechtigung!";
				$query_condition = "USER_ID = ".$id;
			}	
			break;
			//endswitch;
		}
	}
			
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
	
// Alles ok, Daten holen
if ( $action_ok == "yes" ) {
	$tbl_row = db_query_display_item_1("USER_ACCOUNT", $query_condition);
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-User</title>
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
	echo "<div class='content_column_1'>Benutzer-Name</div>";
	echo "<div class='content_column_2'>" .$tbl_row->USER_NAME. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Benutzer-Name kurz</div>";
	echo "<div class='content_column_2'>" .rtrim($tbl_row->USER_NAME_SHORT). "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Benutzer-Rechte</div>";
	echo "<div class='content_column_2'>" .rtrim($tbl_row->USER_RIGHTS). "</div>";
	echo "</div>\n";			
	echo "<div class='line_a'> </div>\n";
			
	if ( $action == "delete" ) { 
		echo "<script>";
		echo '$( "#dialog-form" ).dialog( "open" )';
		echo "</script>";
		echo "<div id='dialog-form' title='Löschen dieses Benutzers bestätigen'>";
		echo "<p>Diese Benutzer kann erst durch Eingabe des Berechtigungscodes gelöscht werden!</p>";
		echo "<form name='form1' action='user_account_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
		echo "<input type='hidden' name='action' value='kill'>";
		echo "<input type='hidden' name='account_id' value=".$tbl_row->USER_ID.">";	
		echo "<input type='password' name='form_kill_code' value=''>"; 
		echo "<input type='submit' value='Jetzt löschen'></form></div>";				
	}
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";		
	echo "<li><a href='user_account_edit.php?action=edit&amp;account_id=".$tbl_row->USER_ID."'>Bearbeiten</a> ";
	if ( $action == "display" ) { 
		echo "<li><a href='user_account_detail.php?action=check_delete&amp;account_id=".$tbl_row->USER_ID."'>Löschen</a> ";
	}
	echo "</ul>\n</div><!--menu_bottom-->"; 
} // user_rights	
echo "</div>\n";
?>
</div>
</div>
</body>
</html>