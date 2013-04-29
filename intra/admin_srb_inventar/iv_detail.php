<?php

/** 
* inventar-details anzeigen 
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

if ( isset( $_GET['message'] ) ) { 
	$message = $_GET['message'];
}
if ( isset( $_POST['message'] ) ) { 
	$message = $_POST['message'];
}

$action_ok = "no";
	
// action pruefen	
if ( isset( $_GET['action'] ) ) {
	$action = $_GET['action'];	$action_ok = "yes";
}

if ( isset( $_POST['action'] ) ) { 
	$action = $_POST['action']; 
	$action_ok = "yes";
}
			
if ( $action_ok == "yes" ) {
	if ( isset( $_GET['iv_id'] ) ) {	
		$id = $_GET['iv_id'];
	}
	if ( isset( $_POST['iv_id'] ) ) {
		$id = $_POST['iv_id'];
	}
		
	// action switchen
	if ( $id !="" ) { 
		switch ( $action ){

		case "display":		
			$message .= "Inventar-Details anzeigen. ";
			$c_query_condition = "IV_ID = ".$id;
			break;

		case "check_delete":		
			$message .= "Inventar zum Löschen prüfen! ";
			// pruefen ob iv_id in anderen Tabellen vorhanden, wird die iv_id gefunden, liefert die func eine id des satzes, der die ad_id enthaelt
			// bei case delete wird geprueft ob kill_possible eine id enthaelt, wenn ja dann loeschen nicht moeglich
			$c_query_condition = "IV_ID = ".$id;
			$iv_in_other_modul = db_query_load_value_n_by_id("VL_ITEMS", "VL_ITEM_IV_ID", $id, 0);
			if ( $iv_in_other_modul != $id ) {
				header("Location: iv_detail.php?action=delete&iv_id=".$id."&kill_possible=".$iv_in_other_modul);
			}
			break;

		case "delete":		
			$message .= "Inventar wirklich löschen? ";
			$c_query_condition = "IV_ID = ".$id;
			if ( isset( $_GET['kill_possible'] ) ) { 
				$kill_possible = $_GET['kill_possible'];
			}
			break;

		case "kill":		
			$message .= "Inventar löschen. ";
			// pruefen ob bestätigung passt
			$c_kill = db_query_load_item("USER_SECURITY", 0);

			if ( $_POST['form_kill_code'] == trim($c_kill) ) {
				$_ok = db_query_delete_item("IV_MAIN", "IV_ID", $id);
				if ( $_ok == "true" ) {
					$message = "Inventar gelöscht!";
					$action_ok = "no";	
				} else { 
					$message .= "Löschen fehlgeschlagen";
					$c_query_condition = "IV_ID = ".$id;
				}
			} else { 
				$message .= "Keine Löschberechtigung!";
				$c_query_condition = "IV_ID = ".$id;
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
	$tbl_row = db_query_display_item_1("IV_MAIN", $c_query_condition);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Inventar</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
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
echo "</div>";	
echo "<div class='content'>";
if ( $action_ok == "no" ) { 
	return;
}
if ( !$tbl_row ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");

if ( $user_rights == "yes" ) { 	
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Objekt</div>";
	echo "<div class='content_column_2'>" .$tbl_row->IV_OBJEKT. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Typ</div>";
	echo "<div class='content_column_2'>" .$tbl_row->IV_TYP. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Hersteller</div>";
	echo "<div class='content_column_2'>" .$tbl_row->IV_HERSTELLER. "</div>";
	echo "</div>\n ";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Serien-Nr./ Inventar-Nr.</div>";
	echo "<div class='content_column_3'>" .$tbl_row->IV_SERIEN_NR. "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->IV_ID. "</div>";
	echo "</div>\n";			
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Lagerort/ Kategorie</div>";
	$c_ort = db_query_load_value_by_id("IV_ORT", "IV_ORT_ID", rtrim($tbl_row->IV_ORT_ID));
	$c_kategorie  = db_query_load_value_by_id("IV_KATEGORIE", "IV_KAT_ID", rtrim($tbl_row->IV_KATEGORIE_ID));
	echo "<div class='content_column_3'>" .$c_ort.  "</div>";
	echo "<div class='content_column_3'>" .$c_kategorie. " </div>";
	echo "</div>\n";
			
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Sponsor/ Eigentümer</div>";
	echo "<div class='content_column_3'>" .$tbl_row->IV_SPONSOR. "</div>";
	$c_eigentuemer  = db_query_load_value_by_id("IV_EIGENTUEMER", "IV_EIG_ID", rtrim($tbl_row->IV_EIGENTUEMER_ID));
	echo "<div class='content_column_3'>" .$c_eigentuemer. " </div>";
	echo "</div>\n";
	
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Wert/ Rech./ Anschaff </div>";
	echo "<div class='content_column_4'>" .$tbl_row->IV_WERT. "</div>";
	echo "<div class='content_column_4'>" .$tbl_row->IV_RECHNUNG_NR. "</div>";
	echo "<div class='content_column_4'>" .get_date_format_deutsch($tbl_row->IV_DATUM_ANSCHAFFUNG). "</div>";
	echo "</div>\n";
	
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Merkmale </div>";
	echo "<div class='content_column_4'>";
	if ( rtrim($tbl_row->IV_VERLIEHEN) == "T") {
		echo "<input type='checkbox' name='iv_verliehen' value='true' checked='checked'> Objekt verliehen ";
	} else { 
		echo "<input type='checkbox' name='iv_verliehen' value='false'> Objekt verliehen ";
	}
	echo "</div><div class='content_column_4'>";
	if ( rtrim($tbl_row->IV_DEFEKT) == "T") {
		echo "<input type='checkbox' name='iv_defekt' value='true' checked='checked'>defekt ";
	} else { 
		echo "<input type='checkbox' name='iv_defekt' value='false'> defekt ";
	}
	echo "</div><div class='content_column_4'>";
	if ( rtrim($tbl_row->IV_AUSGEMUSTERT) == "T") {
		echo "<input type='checkbox' name='iv_ausgemustert' value='true' checked='checked'> ausgemustert";
	} else { 
		echo "<input type='checkbox' name='iv_ausgemustert' value='false'> ausgemustert";
	}
	echo "</div>";
	echo "</div>\n";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Ausm.grund/ Datum </div>";
	echo "<div class='content_column_3'>" .$tbl_row->IV_AUS_GRUND. "</div>";
	echo "<div class='content_column_3'>" .get_date_format_deutsch($tbl_row->IV_AUS_DATUM). "</div>";
	echo "</div>\n";
	
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Ausmusterungsziel</div>";
	echo "<div class='content_column_2'>" .$tbl_row->IV_AUS_ZIEL. "</div>";
	echo "</div>\n";
			
	echo "<div class='content_row_a_3'>";
	echo "<div class='content_column_1'>Info</div>";
	echo "<textarea class='textarea_1' name='iv_text' rows=80 cols=10> ";
	echo $tbl_row->IV_TEXT;
	echo "</textarea>";
	echo "</div>\n";
			
	if ( $action == "delete" ) { 
		// wird in anderen Modulen verwendet
		if ( $kill_possible != "" ) {
			//echo "<div class='error_message'> Objekt wird in anderen Modulen verwendet, löschen nicht möglich!</div>";
			echo "<script>";
			echo '$( "#dialog-form" ).dialog( "open" )';
			echo "</script>";
			echo "<div id='dialog-form' title='Löschen dieses Objektes fehlgeschlagen'>";
			echo "<p>Objekt wird in anderen Modulen verwendet, löschen nicht möglich!</p></div>";			
		} else {
			// ist frei
			echo "<script>";
			echo '$( "#dialog-form" ).dialog( "open" )';
			echo "</script>";
			echo "<div id='dialog-form' title='Löschen dieses Objektes bestätigen'>";
			echo "<p>Dieses Objekte kann erst durch Eingabe des Berechtigungscodes gelöscht werden!</p>";
			echo "<form action='iv_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
			echo "<input type='hidden' name='action' value='kill'>";
			echo "<input type='hidden' name='iv_id' value=".$tbl_row->IV_ID.">";	
			echo "<input type='password' name='form_kill_code' value=''>"; 
			echo "<input type='submit' value='Jetzt löschen'></form></div>";				
		}
	}
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";					
	echo "<li><a href='iv_edit.php?action=edit&amp;iv_id=".$tbl_row->IV_ID."'>Bearbeiten</a> ";
	if ( $action == "display" ) { 
		if ( $tbl_row->IV_ID != "1" ) {
			// inventar mit nr 1 darf nicht geloescht werden, ist vorlage fuer neue					 
			echo "<a href='iv_detail.php?action=check_delete&amp;iv_id=".$tbl_row->IV_ID."'>Löschen</a> ";
		}
	}			
	echo "<li><a href='iv_edit.php?action=dublikate_new&amp;iv_id=".$tbl_row->IV_ID."'>Duplizieren</a> ";
	if ( rtrim($tbl_row->IV_AUSGEMUSTERT) == "T") {
		// bei ausgemustert Protokolldruck zulassen 
		echo "<a href='iv_pdf_ausmusterung.php?action=pdf_ausmusterung&amp;iv_id=".$tbl_row->IV_ID."' target='_blank'>Ausmusterungsprotokoll drucken</a> ";
	}
	echo "</ul>\n</div><!--menu_bottom-->";  			
} // user_rights	
echo "</div>";
echo "</div>";
?>
</body>
</html>
