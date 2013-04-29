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
	if ( isset( $_GET['iv_id'] ) ) {	
		$id = $_GET['iv_id'];
	}
	if ( isset( $_POST['iv_id'] ) ) {
		$id = $_POST['iv_id'];
	}
	if ( $id !="" ) { 
		switch ( $action ) {
		case "new":
			$message .=  "Inventar eintragen";
			$form_input_type = "add"; //form action einstellen
			$tbl_row = db_query_display_item_1("IV_MAIN", "iv_id = " .$id);
			break;

		case "add":
			// fields
			$tbl_fields = "IV_ID, ";
			$tbl_fields .= "IV_OBJEKT, IV_TYP, IV_HERSTELLER, IV_SERIEN_NR, ";
			$tbl_fields .= "IV_ORT_ID, IV_KATEGORIE_ID, IV_EIGENTUEMER_ID, ";
			$tbl_fields .= "IV_SPONSOR, IV_DATUM_ANSCHAFFUNG,  IV_WERT, IV_RECHNUNG_NR, ";
			$tbl_fields .= "IV_VERLIEHEN, IV_DEFEKT, IV_AUSGEMUSTERT, ";
			$tbl_fields .= "IV_AUS_GRUND, IV_AUS_DATUM, IV_AUS_ZIEL, IV_TEXT"; 
				
			// check or load values, lookups
			$main_id = db_generator_main_id_load_value();
											
			$value_ort = db_query_load_id_by_value("IV_ORT", "IV_ORT_DESC", $_POST['form_iv_ort']);				
			$value_kategorie = db_query_load_id_by_value("IV_KATEGORIE", "IV_KAT_DESC", $_POST['form_iv_kategorie']);
			$value_eigentuemer = db_query_load_id_by_value("IV_EIGENTUEMER", "IV_EIG_DESC", $_POST['form_iv_eigentuemer']);
			$value_dat_anschaffung = get_date_format_sql($_POST['form_iv_datum_anschaffung']);
			// Ausmusterungsdatum vordefinieren, wenn nicht eingetragen				
			if ($_POST['form_iv_aus_datum'] != "") {
				$value_dat_ausmusterung = get_date_format_sql($_POST['form_iv_aus_datum']);	
			} else {
				$value_dat_ausmusterung ="1900-01-01";
			}
				
			// Wert auf Inhalt pruefen, Komma in Punkt wandlen
			if ( isset( $_POST['form_iv_wert'] )) {
				if ( $_POST['form_iv_wert'] != "" ) {
					$value_wert = str_replace(",", ".", $_POST['form_iv_wert']);
				} else { 
					$value_wert = "0.0";
				}
			} else { 
				$value_wert = "0.0";
			}				
												
			// checkboxen
			if ( isset( $_POST['form_iv_verliehen'] ) ) { 
				$value_verliehen = "T"; 
			} else { 
				$value_verliehen = "F" ;
			}				
			if ( isset( $_POST['form_iv_defekt'] ) ) { 
				$value_defekt = "T"; 
			} else { 
				$value_defekt = "F" ;
			}				
			if ( isset( $_POST['form_iv_ausgemustert'] ) ) { 
				$value_ausgemustert ="T"; 
			} else { 
				$value_ausgemustert = "F" ;
			}
				
			$a_values = array($main_id, $_POST['form_iv_objekt'], $_POST['form_iv_typ'], $_POST['form_iv_hersteller'], $_POST['form_iv_serien_nr'] ,    					
    					$value_ort, $value_kategorie, $value_eigentuemer, 
    					$_POST['form_iv_sponsor'], $value_dat_anschaffung, $value_wert, $_POST['form_iv_rechnung_nr'],
    					$value_verliehen, $value_defekt, $value_ausgemustert, 
    					$_POST['form_iv_aus_grund'], $value_dat_ausmusterung, $_POST['form_iv_aus_ziel'], $_POST['form_iv_text']);
    					
			$insert_ok = db_query_add_item_b("IV_MAIN", $tbl_fields, "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?", $a_values);
				
			header("Location: iv_detail.php?action=display&iv_id=".$main_id);
			break;
				
		case "edit":
			$message .=   "Inventar-Details bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row = db_query_display_item_1("IV_MAIN", "iv_id = " .$id);
			break;
				
		case "dublikate_new":
			// Satz laden und spaeter neue ID verpassen
			$message .=   "Objekt duplizieren";
			$form_input_type = "add"; //form action einstellen
			$tbl_row = db_query_display_item_1("IV_MAIN", "iv_id = " .$id);
			break;
								
				
		case "update":
			$fields_params = "IV_OBJEKT=?, IV_TYP=?, IV_HERSTELLER=?, IV_SERIEN_NR=?, ";
			$fields_params .= "IV_ORT_ID=?, IV_KATEGORIE_ID=?, IV_EIGENTUEMER_ID=?, ";
			$fields_params .= "IV_SPONSOR=?, IV_DATUM_ANSCHAFFUNG=?,  IV_WERT=?, IV_RECHNUNG_NR=?, ";
			$fields_params .= "IV_VERLIEHEN=?, IV_DEFEKT=?, IV_AUSGEMUSTERT=?, ";
			$fields_params .= "IV_AUS_GRUND=?, IV_AUS_DATUM=?, IV_AUS_ZIEL=?, IV_TEXT=?"; 
		  
			$value_ort = db_query_load_id_by_value("IV_ORT", "IV_ORT_DESC", $_POST['form_iv_ort']);				
			$value_kategorie = db_query_load_id_by_value("IV_KATEGORIE", "IV_KAT_DESC", $_POST['form_iv_kategorie']);
			$value_eigentuemer = db_query_load_id_by_value("IV_EIGENTUEMER", "IV_EIG_DESC", $_POST['form_iv_eigentuemer']);

			// Anschaffungsdatum vordefinieren, wenn nicht eingetragen				
			if ( $_POST['form_iv_datum_anschaffung'] != "") {
				$value_dat_anschaffung = get_date_format_sql($_POST['form_iv_datum_anschaffung']);	
			} else { 
				$value_dat_anschaffung ="1900-01-01";
			}
				
			// Ausmusterungsdatum vordefinieren, wenn nicht eingetragen				
			if ( $_POST['form_iv_aus_datum'] != "") {
				$value_dat_ausmusterung = get_date_format_sql($_POST['form_iv_aus_datum']);	
			} else { 
				$value_dat_ausmusterung ="1900-01-01";
			}
				
			// Wert auf Inhalt pruefen, Komma in Punkt wandlen
			if ( isset( $_POST['form_iv_wert'] )) {
				if ( $_POST['form_iv_wert'] != "" ) {
					$value_wert = str_replace(",", ".", $_POST['form_iv_wert']);
				} else { 
					$value_wert = "0.0";
				}
			} else { 
				$value_wert = "0.0";
			}

			if ( isset( $_POST['form_iv_verliehen'] ) ) { 
				$value_verliehen = "T"; 
			} else { 
				$value_verliehen = "F" ;
			}							
			if ( isset( $_POST['form_iv_defekt'] ) ) { 
				$value_defekt = "T"; 
			} else { 
				$value_defekt = "F" ;
			}
			if ( isset( $_POST['form_iv_ausgemustert'] ) )	{ 
				$value_ausgemustert = "T"; 
			} else { 
				$value_ausgemustert = "F" ;
			}
				
			$a_values = array($_POST['form_iv_objekt'], $_POST['form_iv_typ'], $_POST['form_iv_hersteller'], $_POST['form_iv_serien_nr'], 
				$value_ort, $value_kategorie, $value_eigentuemer, 
    			$_POST['form_iv_sponsor'], $value_dat_anschaffung, $value_wert, $_POST['form_iv_rechnung_nr'],
    			$value_verliehen, $value_defekt, $value_ausgemustert, 
    			$_POST['form_iv_aus_grund'], $value_dat_ausmusterung, $_POST['form_iv_aus_ziel'], $_POST['form_iv_text']);	
				
			$update_ok = db_query_update_item_b("IV_MAIN", $fields_params, "IV_ID =".$id, $a_values);
								
			header("Location: iv_detail.php?action=display&iv_id=".$id);
			break;
			//	endswitch;
		}
	} else {
		$message .= "Keine ID enthalten. Nichts zu tun..... "; 
	}
} else {
	$message .= "Keine Anweisung. Nichts zu tun..... "; 
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Inventar</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_form_validator/css/validationEngine.jquery.css");    </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine-ge.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine.js"></script>
	
	<script type="text/javascript">
		$(document).ready(function() {
			$("#IV_edit_form").validationEngine() 
		})
		
	</script>
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
if ( ! isset($tbl_row->IV_ID )) { 
	echo "Fehler bei Abfrage!"; 
	return;
}

$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	
	echo "<form name='form1' id='iv_edit_form' action='iv_edit.php' method='POST' enctype='application/x-www-form-urlencoded'>";
	echo "<input type='hidden' name='action' value='".$form_input_type."'>";
	echo "<input type='hidden' name='iv_id' value='".$id."'>";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Objekt</div>";
	echo "<input type='text' name='form_iv_objekt' class='text_1' maxlength='60' value='".$tbl_row->IV_OBJEKT."' >";
	echo "</div>";

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Typ</div>";
	echo "<input type='text' name='form_iv_typ' class='text_1' maxlength='100' value='".$tbl_row->IV_TYP."'>";
	echo "</div>";	

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Hersteller</div>";
	echo "<input type='text' name='form_iv_hersteller' class='text_1' maxlength='60' value='".$tbl_row->IV_HERSTELLER."'>";
	echo "</div>";
			
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Serien-Nr.</div>";
	echo "<input type='text' name='form_iv_serien_nr' class='text_1' maxlength='100' value='".$tbl_row->IV_SERIEN_NR."'>";
	echo "</div>";	
	echo "<div class='space_line'> </div>";			
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Lagerort/ Kategorie</div>";

	echo html_dropdown_from_table_1("IV_ORT", "IV_ORT_DESC", "form_iv_ort", "text_2", rtrim($tbl_row->IV_ORT_ID));
	echo html_dropdown_from_table_1("IV_KATEGORIE", "IV_KAT_DESC", "form_iv_kategorie", "text_2", rtrim($tbl_row->IV_KATEGORIE_ID));				
	echo "</div>";

	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Sponsor/ Eigentümer</div>";			
	echo "<input type='text' name='form_iv_sponsor' class='text_2' value='".$tbl_row->IV_SPONSOR."'>";
	echo html_dropdown_from_table_1("IV_EIGENTUEMER", "IV_EIG_DESC", "form_iv_eigentuemer", "text_2", rtrim($tbl_row->IV_EIGENTUEMER_ID));
	echo "</div>";
			
	echo "<div class='space_line'> </div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Wert/ Rech./ Anschaff.</div>";
	echo "<input type='text' name='form_iv_wert' class='text_3' maxlength='40' value='".$tbl_row->IV_WERT."' >";
	echo "<input type='text' name='form_iv_rechnung_nr' class='text_3' maxlength='20' value='".$tbl_row->IV_RECHNUNG_NR."' >";
	echo "<input type='text' name='form_iv_datum_anschaffung' id='datepicker' class='validate[required,custom[date_ge]] text_3' value='".get_date_format_deutsch($tbl_row->IV_DATUM_ANSCHAFFUNG)."'>";
	echo "</div>";
			
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Merkmale</div>";
	echo "<div class='content_column_4'>";

	if ( rtrim($tbl_row->IV_VERLIEHEN) == "T" ) {
		echo "<input type='checkbox' name='form_iv_verliehen' value='true' checked='checked'> Objekt verliehen ";
	} else { 
		echo "<input type='checkbox' name='form_iv_verliehen' value='T'> Objekt verliehen ";
	}
	echo "</div><div class='content_column_4_a'>";
	if ( rtrim($tbl_row->IV_DEFEKT) == "T") {
		echo "<input type='checkbox' name='form_iv_defekt' value='true' checked='checked'> defekt ";
	} else { 
		echo "<input type='checkbox' name='form_iv_defekt' value='T'> defekt ";
	}
	echo "</div><div class='content_column_4_a'>";
	if ( rtrim($tbl_row->IV_AUSGEMUSTERT) == "T" ) {
		echo "<input type='checkbox' name='form_iv_ausgemustert' value='true' checked='checked'> ausgemustert";
	} else { 
		echo "<input type='checkbox' name='form_iv_ausgemustert' value='T'> ausgemustert";
	}
		
	echo "</div>";
	echo "</div>";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Ausm.grund/ Datum</div>";
	echo "<input type='text' name='form_iv_aus_grund' class='text_2' maxlength='40' value='".$tbl_row->IV_AUS_GRUND."' >";
	echo "<input type='text' name='form_iv_aus_datum' class='validate[required,custom[date_ge]] text_2' value='".get_date_format_deutsch($tbl_row->IV_AUS_DATUM)."' >";
	echo "</div>";
			
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Ausmusterungsziel</div>";
	echo "<input type='text' name='form_iv_aus_ziel' class='text_1' maxlength='60' value='".$tbl_row->IV_AUS_ZIEL."' >";
	echo "</div>";
			
	echo "<div class='content_row_a_3'>";
	echo "<div class='content_column_1'>Info</div>";
	echo "<textarea class='textarea_1_e' name='form_iv_text'>".$tbl_row->IV_TEXT."</textarea>";
	echo "</div>";
	echo "<div class='line_a'> </div>";
			
	echo "<input type='submit' value='Speichern'>";
	echo "</form>";
} // user_rights	
echo "</div>";
echo "</div>";
?>
</body>
</html>
