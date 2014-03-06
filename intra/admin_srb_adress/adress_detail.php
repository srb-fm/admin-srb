<?php
/** 
* adress-details anzeigen 
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
	$message .= $_GET['message'];
}
if ( isset( $_POST['message'] ) ) { 
	$message .= $_POST['message'];
}

$action_ok = "no";
	
// check action	
if ( isset( $_GET['action'] ) ) {
	$action    =    $_GET['action'];	
	$action_ok = "yes";
}

if ( isset( $_POST['action'] ) ) { 
	$action    =    $_POST['action'];
	$action_ok = "yes";
}
			
if ( $action_ok == "yes" ) {
	if ( isset( $_GET['ad_id'] ) ) {
		$id = $_GET['ad_id'];
	}

	if ( isset( $_POST['ad_id'] ) ) {
		$id = $_POST['ad_id'];
	}
	
	// check id
	if ( ! filter_var( $id, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000 )) ) ) {
		$id = "";
		$action_ok = "no";
	}
			
	// switch action 
	if ( $id !="" ) { 
		switch ( $action ){

		case "display":		
			$message .= "Adress-Details anzeigen. ";
			$c_query_condition = "AD_ID = ".$id;
			break;

		case "check_delete":		
			$message .= "Adresse zum Löschen prüfen! ";
			// pruefen ob ad_id in anderen Tabellen vorhanden, 
			// wird die ad_id gefunden, liefert die func eine id des satzes, 
			// der die ad_id enthaelt
			// bei case delete wird geprueft ob kill_possible eine id enthaelt, 
			// wenn ja dann loeschen nicht moeglich
			$c_query_condition = "AD_ID = ".$id;
			$ad_in_other_modul = db_query_load_value_n_by_id("SG_HF_CONTENT", "SG_HF_CONT_AD_ID", $id, 0);
			// weitere an erste belegung von $ad_in_other_modul dranhaengen, 
			// sonst wird z.b. verwendet in sendung ueberschrieben 
			// mit nicht verwendet in verleih
			$ad_in_other_modul .= db_query_load_value_n_by_id("VL_MAIN", "VL_AD_ID", $id, 0);
			if ( $ad_in_other_modul != $id ) {
				header("Location: adress_detail.php?action=delete&ad_id=".$id."&kill_possible=".$ad_in_other_modul);
			}
			break;

		case "delete":		
			$message .= "Adresse wirklich löschen? ";
			$c_query_condition = "AD_ID = ".$id;
			if ( isset( $_GET['kill_possible'] ) ) {
				$kill_possible = $_GET['kill_possible'];
			}
			break;

		case "kill":		
			$message .= "Adresse löschen. ";
			// pruefen ob bestaetigung passt
			$c_kill = db_query_load_item("USER_SECURITY", 0);

			if ( $_POST['form_kill_code'] == trim($c_kill) ) {
				$_ok = db_query_delete_item("AD_MAIN", "AD_ID", $id);
				if ( $_ok == "true" ) {
					$message = "Adresse gelöscht!";
					$action_ok = "no";	
				} else { 
					$message .= "Löschen fehlgeschlagen";
					$c_query_condition = "AD_ID = ".$id;
				}
			} else { 
				$message .= "Keine Löschberechtigung!";
				$c_query_condition = "AD_ID = ".$id;
			}	
			break;
												
		}
	}
} else {
	$message .= "Keine Anweisung. Nichts zu tun..... "; 
}
	
// Alles ok, Daten holen
if ( $action_ok == "yes" ) {
	$tbl_row	= db_query_display_item_1("AD_MAIN", $c_query_condition);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Adresse</title>
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
echo "</div>\n";
echo "<div class='content'>"; 
if ( $action_ok == "no" ) {
	echo "Fehler bei Übergabe: ".$action;
	return;
}
if ( !$tbl_row ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
	
if ( $user_rights == "yes" ) {
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Anrede/ Titel</div>";
	$c_anrede = db_query_load_value_by_id("AD_ANREDE", "AD_ANREDE_ID", $tbl_row->AD_ANREDE_ID);
	$c_titel  = db_query_load_value_by_id("AD_TITEL", "AD_TITEL_ID", $tbl_row->AD_TITEL_ID);
	echo "<div class='content_column_3'>" .$c_anrede.  "</div>";
	echo "<div class='content_column_3'>" .$c_titel. " </div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Vorname/ Name</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_VORNAME. "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_NAME. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Firma</div>";
	echo "<div class='content_column_2'>" .$tbl_row->AD_FIRMA. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Straße/ Postfach</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_STRASSE. "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_PF. " </div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Land/ PLZ/ Ort</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_LAND_ID. " - ";
	echo $tbl_row->AD_PLZ. "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_ORT. "</div>";
	echo "</div>\n ";
	echo "<div class='line'> </div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Telefon 1/ 2</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_TEL_1. "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_TEL_2. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>FAX</div>";
	echo "<div class='content_column_2'>" .$tbl_row->AD_FAX. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>eMail</div>";
	echo "<div class='content_column_2'>" .$tbl_row->AD_EMAIL. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Internet</div>";
	echo "<div class='content_column_2'>" .$tbl_row->AD_WEB. "</div>";
	echo "</div>\n ";
	echo "<div class='line'> </div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Geburtstag/ Stichwort</div>";
 	echo "<div class='content_column_3'>" .get_date_format_deutsch($tbl_row->AD_DATUM_GEBURT). "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_STICHWORT. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Bildung/ Beruf</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_BILDUNG. "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_BERUF. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>PA-Nr/ Pass-Nr </div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_NR_PA. "</div>";
	echo "<div class='content_column_3'>" .$tbl_row->AD_NR_PASS. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Nutzer/ Privat</div>";
	echo "<div class='content_column_5'>";
	if ( rtrim($tbl_row->AD_USER_OK_HF) == "T" ) {
		echo "<input type='checkbox' name='ad_user_ok_hf' value='true' checked='checked'> Nutzer-HF";
	} else { 
		echo "<input type='checkbox' name='ad_user_ok_hf' value='false'> Nutzer-HF";
	}
	if ( rtrim($tbl_row->AD_USER_OK_TV) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_user_ok_tv' value='true' checked='checked'> Nutzer-TV";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_user_ok_tv' value='false'> Nutzer-TV";
	}
	if ( rtrim($tbl_row->AD_USER_OK_PRIVAT) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_privat' value='true' checked='checked'> Privat";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_privat' value='false'> Privat";
	}
	if ( rtrim($tbl_row->AD_USER_OK_ORG) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_org' value='T' checked='checked'> Org";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_org' value='T'> Org";
	}
	echo "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Verein</div>";
	echo "<div class='content_column_5'>";
	if ( rtrim($tbl_row->AD_VEREIN) == "T" ) {
		echo "<input type='checkbox' name='ad_verein' value='true' checked='checked'> Vereinsmitglied";
	} else { 
		echo "<input type='checkbox' name='ad_verein' value='false'> Vereinsmitglied";
	}
	if ( rtrim($tbl_row->AD_VEREINSNAH) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_vereinsnah' value='true' checked='checked'> nahestehend";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_vereinsnah' value='false'> nahestehend";
	}
	echo "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Aktiv/ Praktikant </div>";
	echo "<div class='content_column_5'>";
	if ( rtrim($tbl_row->AD_ACTIVE) == "T" ) {
		echo "<input type='checkbox' name='ad_active' value='true' checked='checked'> Adresse aktiv ";
	} else { 
		echo "<input type='checkbox' name='ad_active' value='false'> Adresse aktiv ";
	}
	if ( rtrim($tbl_row->AD_USER_OK_AKTIV) == "T" ) {
		echo "<input type='checkbox' name='ad_active' value='true' checked='checked'> Macher aktiv ";
	} else {
		echo "<input type='checkbox' name='ad_active' value='false'> Macher aktiv ";
	}
	if ( rtrim($tbl_row->AD_USER_OK_PRAKTIKANT) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_praktikant' value='true' checked='checked'> Praktikant";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='ad_praktikant' value='false'> Praktikant";
	}
	echo "</div>";
	echo "</div>\n";
			
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Aufnahme-Datum</div>";
	echo "<div class='content_column_2'>" .get_date_format_deutsch($tbl_row->AD_TIME_AUFNAHME). "</div>";
	echo "</div>\n";
		
	echo "<div class='content_row_a_3'>";
	echo "<div class='content_column_1'>Info</div>";
	echo "<textarea class='textarea_1' name='ad_text' rows=80 cols=10> ";
	echo $tbl_row->AD_TEXT;
	echo "</textarea>";
	echo "</div>\n<br>";
				
	if ( $action == "delete" ) { 
		// wird in anderen Modulen verwendet
		if ( $kill_possible != "" ) {
			display_message("Löschen dieser Adresse fehlgeschlagen", "Adresse wird in anderen Modulen verwendet, löschen nicht möglich!");				
		} else {
			// ist frei
			echo "<script>";
			echo '$( "#dialog-form" ).dialog( "open" )';
			echo "</script>";
			echo "<div id='dialog-form' title='Löschen dieser Adresse bestätigen'>";
			echo "<p>Diese Adresse kann erst durch Eingabe des Berechtigungscodes gelöscht werden!</p>";
			echo "<form action='adress_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
			echo "<input type='hidden'	name='action' value='kill'>";
			echo "<input type='hidden'	name='ad_id' value=".$tbl_row->AD_ID.">";	
			echo "<input type='password' name='form_kill_code' value=''>"; 
			echo "<input type='submit'	value='Jetzt löschen'></form></div>";				
		}
	}
			
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";	
			
	if ( $_SESSION["log_rights"] <= "B" ) {
		echo "<li><a href='adress_edit.php?action=edit&amp;ad_id=".$tbl_row->AD_ID."'>Bearbeiten</a> ";

		if ( $action == "display" ) { 
			if ( $tbl_row->AD_ID != "0" ) {
				// Adresse mit nr 0 darf nicht geloescht werden, 
				// ist vorlage fuer neue 
				echo "<a href='adress_detail.php?action=check_delete&amp;ad_id=".$tbl_row->AD_ID."'>Löschen</a> ";
			}
		}

		// Prüfen ob user egal ob HF oder TV
		$c_user = "no";
		if ( rtrim($tbl_row->AD_USER_OK_HF) == "T" ) { 
			$c_user ="yes";
		}
		if ( rtrim($tbl_row->AD_USER_OK_TV) == "T" ) { 
			$c_user ="yes";
		}	
		if ( $c_user == "yes" ) { 
			echo "<a href='adress_user_reg_form.php?
			action=print&amp;ad_id=".$tbl_row->AD_ID.
			"' title='Nutzeranmeldung drucken' target='_blank'>Nutzeranmeldung</a> ";
		}
		if ( $c_user == "yes" ) { 
			echo "<a href='adress_user_reg_form_einwilligung.php?action=print&amp;ad_id=".$tbl_row->AD_ID."' title='Einwilligungserklärung drucken' target='_blank'>Einwilligungserkl.</a> ";
		}
		if ( rtrim($tbl_row->AD_USER_OK_HF) == "T" ) { 
			echo "<a href='../admin_srb_sendung_hf/sg_hf_edit.php?action=new&amp;ad_id=".$tbl_row->AD_ID."' title='Sendung anmelden'>HF-Sendung</a> ";
		}
		if ( rtrim($tbl_row->AD_USER_OK_TV) == "T" ) { 
			echo "<a href='../admin_srb_sendung_tv/sg_tv_edit.php?action=new&amp;ad_id=".$tbl_row->AD_ID."' title='Sendung anmelden'>TV-Sendung</a> ";
		}
		if ( $c_user == "yes" ) { 
			echo "<a href='../admin_srb_verleih/vl_edit.php?action=new&amp;ad_id=".$tbl_row->AD_ID."&amp;vl_id=0'  title='Geräte ausleihen'>Verleih</a> ";
		}				
	} else {
		echo "<a title='Keine Berechtigung'>Bearbeiten </a>";
		echo "<a title='Keine Berechtigung'>Löschen </a>";
		echo "<a title='Keine Berechtigung'>Nutzeranmeldung</a>";
		echo "<a title='Keine Berechtigung'>Einwilligungserkl.</a>";
		echo "<a title='Keine Berechtigung'>HF-Sendung</a>";
		echo "<a title='Keine Berechtigung'>TV-Sendung</a>";
		echo "<a title='Keine Berechtigung'>Verleih</a>";
	}	

	echo "</ul>\n</div><!--menu_bottom-->";  
			
} // user_rights	
echo "</div>";
echo "</div>";
?>
</body>
</html>
