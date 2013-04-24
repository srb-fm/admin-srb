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
	if ( isset( $_GET['ad_id'] ) ) {
		$id = $_GET['ad_id'];
	}
	if ( isset( $_POST['ad_id'] ) ) {
		$id = $_POST['ad_id'];
	}
	if ( $id !="" ) { 
		switch ( $action ) {
		case "new":
			$message .=   "Adresse eintragen";
			$form_input_type = "add"; //form action einstellen
			$tbl_row = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id);
			break;

		case "add":
			// fields
			$tbl_fields = "AD_ID, ";
			$tbl_fields .= "AD_ANREDE_ID, AD_TITEL_ID, AD_LAND_ID, AD_DATUM_GEBURT, AD_NR_PA, AD_NR_PASS, ";
			$tbl_fields .= "AD_VORNAME, AD_NAME, AD_FIRMA,  AD_STRASSE, AD_PF, AD_PLZ, AD_ORT, ";
			$tbl_fields .= "AD_TEL_1, AD_TEL_2, AD_FAX, AD_EMAIL, AD_WEB, ";
			$tbl_fields .= "AD_STICHWORT, AD_BILDUNG, AD_BERUF, AD_TEXT, ";
			$tbl_fields .= "AD_USER_OK_HF, AD_USER_OK_TV, AD_USER_OK_PRIVAT, AD_USER_OK_ORG, AD_VEREIN, AD_VEREINSNAH, AD_ACTIVE, AD_USER_OK_AKTIV, AD_USER_OK_PRAKTIKANT ";

			// check or load values, lookups
			$main_id = db_generator_main_id_load_value();
			$value_anrede = db_query_load_id_by_value("AD_ANREDE", "AD_ANREDE_DESC", $_POST['form_ad_anrede']);				
			$value_titel = db_query_load_id_by_value("AD_TITEL", "AD_TITEL_DESC", $_POST['form_ad_titel']);
			$value_land = db_query_load_id_by_value("AD_COUNTRY", "AD_COUNTRY_DESC", $_POST['form_ad_land']);
			$value_geb_dat = get_date_format_sql($_POST['form_ad_datum_geburt']);
				
			// checkboxen
			if ( isset( $_POST['form_ad_user_ok_hf']) ) { 
				$value_user_ok_hf = $_POST['form_ad_user_ok_hf']; 
			} else { 
				$value_user_ok_hf = "F" ;
			}				
			if ( isset( $_POST['form_ad_user_ok_tv']) ) { 
				$value_user_ok_tv = $_POST['form_ad_user_ok_tv']; 
			} else { 
				$value_user_ok_tv = "F" ;
			}				
			if ( isset( $_POST['form_ad_privat']) ) { 
				$value_privat = $_POST['form_ad_privat']; 
			} else { 
				$value_privat = "F" ;
			}
			if ( isset( $_POST['form_ad_org']) ) { 
				$value_org = $_POST['form_ad_org']; 
			} else { 
				$value_org = "F" ;
			}				
			if ( isset( $_POST['form_ad_verein']) ) { 
				$value_verein = $_POST['form_ad_verein']; 
			} else { 
				$value_verein = "F" ;
			}
			if ( isset( $_POST['form_ad_vereinsnah']) ) { 
				$value_ver_nah = $_POST['form_ad_vereinsnah']; 
			} else { 
				$value_ver_nah = "F" ;
			}
			if ( isset( $_POST['form_ad_active'] ) ) { 
				$value_active = $_POST['form_ad_active']; 
			} else { 
				$value_active = "F" ;
			}
			if ( isset( $_POST['form_ad_active_user'] ) ) { 
				$value_active_user = $_POST['form_ad_active_user']; 
			} else { 
				$value_active_user = "F" ;
			}
			if ( isset( $_POST['form_ad_praktikant'] ) ) {
				$value_praktikant = $_POST['form_ad_praktikant']; 
			} else { 
				$value_praktikant = "F" ;
			}
				
			$a_values = array( $main_id,
  				$value_anrede, $value_titel, $value_land, $value_geb_dat,
  				$_POST['form_ad_nr_pa'],
  				$_POST['form_ad_nr_pass'],
  				$_POST['form_ad_vorname'], $_POST['form_ad_name'],
  				$_POST['form_ad_firma'], $_POST['form_ad_strasse'],
  				$_POST['form_ad_pf'], $_POST['form_ad_plz'], $_POST['form_ad_ort'],
  				$_POST['form_ad_tel_1'], $_POST['form_ad_tel_2'],
  				$_POST['form_ad_fax'], $_POST['form_ad_email'], $_POST['form_ad_web'],
  				$_POST['form_ad_stichwort'], $_POST['form_ad_bildung'], $_POST['form_ad_beruf'],
  				$_POST['form_ad_text'],
  				$value_user_ok_hf,  $value_user_ok_tv, $value_privat, $value_org, $value_verein, $value_ver_nah, $value_active, $value_active_user,  $value_praktikant ); 
				
			$insert_ok = db_query_add_item_b("AD_MAIN", $tbl_fields, "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?", $a_values);
		    		 
			header("Location: adress_detail.php?action=display&ad_id=".$main_id);
			break;
				
		case "edit":
			$message .=   "Adress-Details bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id);
			break;
				
		case "update":
			$fields_params = "AD_ANREDE_ID=?, AD_TITEL_ID=?, AD_LAND_ID=?, AD_DATUM_GEBURT=?, ";
			$fields_params .= "AD_VORNAME=?, AD_NAME=?, AD_FIRMA=?, AD_STRASSE=?,  AD_PF=?, AD_PLZ=?, AD_ORT=?, ";
			$fields_params .= "AD_TEL_1=?, AD_TEL_2=?, AD_FAX=?, AD_EMAIL=?, AD_WEB=?, AD_STICHWORT=?, AD_BILDUNG=?, AD_BERUF=?, "; 
			$fields_params .= "AD_NR_PA=?, AD_NR_PASS=?, AD_TEXT=?, ";
			$fields_params .= "AD_USER_OK_HF=?, AD_USER_OK_TV=?, AD_USER_OK_PRIVAT=?, AD_USER_OK_ORG=?, AD_VEREIN=?, AD_VEREINSNAH=?, AD_ACTIVE=?, AD_USER_OK_AKTIV=?, AD_USER_OK_PRAKTIKANT=?";  

			$value_anrede = db_query_load_id_by_value("AD_ANREDE", "AD_ANREDE_DESC", $_POST['form_ad_anrede']);				
			$value_titel = db_query_load_id_by_value("AD_TITEL", "AD_TITEL_DESC", $_POST['form_ad_titel']);
			$value_land = db_query_load_id_by_value("AD_COUNTRY", "AD_COUNTRY_DESC", $_POST['form_ad_land']);
			$value_geb_dat = get_date_format_sql($_POST['form_ad_datum_geburt']);
			if ( isset( $_POST['form_ad_user_ok_hf'] ) ) { 
				$value_user_ok_hf = $_POST['form_ad_user_ok_hf']; 
			} else { 
				$value_user_ok_hf = "F" ;
			}				
			if ( isset( $_POST['form_ad_user_ok_tv'] ) ) { 
				$value_user_ok_tv = $_POST['form_ad_user_ok_tv']; 
			} else { 
				$value_user_ok_tv = "F" ;
			}				
			if ( isset( $_POST['form_ad_privat'] ) ) { 
				$value_privat = $_POST['form_ad_privat']; 
			} else { 
				$value_privat = "F" ;
			}
			if ( isset( $_POST['form_ad_org'] ) ) { 
				$value_org = $_POST['form_ad_org']; 
			} else { 
				$value_org = "F" ;
			}				
			if ( isset( $_POST['form_ad_verein'] ) ) { 
				$value_verein = $_POST['form_ad_verein']; 
			} else { 
				$value_verein = "F" ;
			}
			if ( isset( $_POST['form_ad_vereinsnah'] ) ) { 
				$value_ver_nah = $_POST['form_ad_vereinsnah']; 
			} else { 
				$value_ver_nah = "F" ;
			}
			if ( isset( $_POST['form_ad_active'] ) ) { 
				$value_active = $_POST['form_ad_active']; 
			} else { 
				$value_active = "F" ;
			}
			if ( isset( $_POST['form_ad_active_user'] ) ) { 
				$value_active_user = $_POST['form_ad_active_user']; 
			} else { 
				$value_active_user = "F" ;
			}
			if ( isset( $_POST['form_ad_praktikant'] ) ) { 
				$value_praktikant = $_POST['form_ad_praktikant']; 
			} else { 
				$value_praktikant = "F" ;
			}
				
			$a_values = array($value_anrede, $value_titel,	$value_land, $value_geb_dat,
    			$_POST['form_ad_vorname'], $_POST['form_ad_name'],
    			$_POST['form_ad_firma'], $_POST['form_ad_strasse'],
    			$_POST['form_ad_pf'], $_POST['form_ad_plz'], $_POST['form_ad_ort'],
    			$_POST['form_ad_tel_1'], $_POST['form_ad_tel_2'],
    			$_POST['form_ad_fax'], $_POST['form_ad_email'], $_POST['form_ad_web'],
    			$_POST['form_ad_stichwort'], $_POST['form_ad_bildung'], $_POST['form_ad_beruf'],
    			$_POST['form_ad_nr_pa'], $_POST['form_ad_nr_pass'], $_POST['form_ad_text'],
    			$value_user_ok_hf,  $value_user_ok_tv, $value_privat, $value_org, $value_verein, $value_ver_nah, $value_active, $value_active_user,  $value_praktikant );

			$update_ok = db_query_update_item_b("AD_MAIN", $fields_params, "AD_ID =".$id, $a_values);											
			header("Location: adress_detail.php?action=display&ad_id=".$id);
			break;
			//endswitch;
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
	<title>Admin-SRB-Adresse</title>
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
			$("#ad_edit_form").validationEngine() 
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
if ( ! isset($tbl_row->AD_ID )) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	
	echo "<form name='form1' id='ad_edit_form' action='adress_edit.php' method='POST' enctype='application/x-www-form-urlencoded'>";
	echo "<input type='hidden' name='action' value='".$form_input_type."'>";
	echo "<input type='hidden' name='ad_id' value='".$tbl_row->AD_ID."'>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Anrede/ Titel</div>";
	echo html_dropdown_from_table_1("AD_ANREDE", "AD_ANREDE_DESC", "form_ad_anrede", "text_2", rtrim($tbl_row->AD_ANREDE_ID));			
	echo html_dropdown_from_table_1("AD_TITEL", "AD_TITEL_DESC", "form_ad_titel", "text_2", rtrim($tbl_row->AD_TITEL_ID));
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Vorname/ Name</div>";
	echo "<input type='text' name='form_ad_vorname' class='text_2' maxlength='20' value='".$tbl_row->AD_VORNAME."' >";
	echo "<input type='text' name='form_ad_name' id ='ad_name' class='validate[required,length[3,100]] text_2' maxlength='40' value='".$tbl_row->AD_NAME."' >";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Firma</div>";
	echo "<input type='text' name='form_ad_firma' class='text_1' maxlength='60' value='".$tbl_row->AD_FIRMA."' >";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Straße/ Postfach</div>";
	echo "<input type='text' name='form_ad_strasse' class='text_2' maxlength='40' value='".$tbl_row->AD_STRASSE."' >";
	echo "<input type='text' name='form_ad_pf' class='text_2' maxlength='20' value='".$tbl_row->AD_PF."' >";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Land</div>";
	echo html_dropdown_from_table_1("AD_COUNTRY", "AD_COUNTRY_DESC", "form_ad_land", "text_1", rtrim($tbl_row->AD_LAND_ID));
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>PLZ/ Ort</div>";
	echo "<input type='text' name='form_ad_plz' class='text_2' maxlength='6' value='".rtrim($tbl_row->AD_PLZ)."'>";
	echo "<input type='text' name='form_ad_ort' class='text_2' maxlength='30' value='".$tbl_row->AD_ORT."'>";
	echo "</div>";			
	echo "<div class='line'> </div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Telefon 1/ 2</div>";
	echo "<input type='text' name='form_ad_tel_1' class='text_2' maxlength='20' value='".$tbl_row->AD_TEL_1."'>";
	echo "<input type='text' name='form_ad_tel_2' class='text_2' maxlength='20' value='".$tbl_row->AD_TEL_2."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>"; 
	echo "<div class='content_column_1'>FAX</div>";
	echo "<input type='text' name='form_ad_fax' class='text_1' maxlength='20' value='".$tbl_row->AD_FAX."'>";
	echo "</div>";	
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>eMail</div>";
	echo "<input type='text' name='form_ad_email' id='ad_email' class='text_1' maxlength='50' value='".$tbl_row->AD_EMAIL."'>";
	echo "</div>";	
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Internet</div>";
	echo "<input type='text' name='form_ad_web' class='text_1' maxlength='100' value='".$tbl_row->AD_WEB."'>";
	echo "</div>";	
	echo "<div class='line'> </div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Geburtstag/ Stichwort</div>";
	echo "<input type='text' name='form_ad_datum_geburt' id='ad_datum_geburt' class='validate[required,custom[date_ge]] text_2' value='".get_date_format_deutsch($tbl_row->AD_DATUM_GEBURT)."'>";
	echo "<input type='text' name='form_ad_stichwort' class='text_2' value='".$tbl_row->AD_STICHWORT."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Bildung/ Beruf</div>";
	// bildung pa und pass nur kommen immer mit leerzeichen, deshal trim
	echo "<input type='text' name='form_ad_bildung' class='text_2' maxlength='20' value='".trim(rtrim($tbl_row->AD_BILDUNG)). "'>";
	echo "<input type='text' name='form_ad_beruf' class='text_2' maxlength='40' value='".$tbl_row->AD_BERUF."'>";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>PA-Nr/ Pass-Nr</div>";
	echo "<input type='text' name='form_ad_nr_pa' class='text_2' maxlength='10' value='".trim(rtrim($tbl_row->AD_NR_PA))."'>";
	echo "<input type='text' name='form_ad_nr_pass' class='text_2' maxlength='31' value='".trim(rtrim($tbl_row->AD_NR_PASS))."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Nutzer/ Privat</div>";
	echo "<div class='content_column_5'>";
	if ( rtrim($tbl_row->AD_USER_OK_HF) == "T" ) {
		echo "<input type='checkbox' name='form_ad_user_ok_hf' value='T' checked='checked'> Nutzer-HF";
	} else { 
		echo "<input type='checkbox' name='form_ad_user_ok_hf' value='T'> Nutzer-HF";
	}
	if ( rtrim($tbl_row->AD_USER_OK_TV) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_user_ok_tv' value='T' checked='checked'> Nutzer-TV";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_user_ok_tv' value='T'> Nutzer-TV";
	}
	if ( rtrim($tbl_row->AD_USER_OK_PRIVAT) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_privat' value='T' checked='checked'> Privat";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_privat' value='T'> Privat";
	}
	if ( rtrim($tbl_row->AD_USER_OK_ORG) == "T" ) {
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_org' value='T' checked='checked'> Org";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_org' value='T'> Org";
	}
	echo "</div>";
	echo "</div>\n";
			
	echo "<div class='content_row_a_1'>\n";
	echo "<div class='content_column_1'>Verein</div>";
	echo "<div class='content_column_5'>";
	if ( rtrim($tbl_row->AD_VEREIN) == "T") {
		echo "<input type='checkbox' name='form_ad_verein' value='T' checked='checked'> Vereinsmitglied";
	} else { 
		echo "<input type='checkbox' name='form_ad_verein' value='T'> Vereinsmitglied";
	}
	if ( rtrim($tbl_row->AD_VEREINSNAH) == "T") {
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_vereinsnah' value='T' checked='checked'> nahestehend";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_vereinsnah' value='T'> nahestehend";
	}
	echo "</div>";
	echo "</div>\n";
			
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Aktiv/ Praktikant </div>";
	echo "<div class='content_column_5'>";
	if ( rtrim($tbl_row->AD_ACTIVE) == "T" ) {
		echo "<input type='checkbox' name='form_ad_active' value='T' checked='checked'> Adresse aktiv ";
	} else { 
		echo "<input type='checkbox' name='form_ad_active' value='T'> Adresse aktiv ";
	}
	if ( rtrim($tbl_row->AD_USER_OK_AKTIV) == "T") {
		echo "<input type='checkbox' name='form_ad_active_user' value='T' checked='checked'> Macher aktiv ";
	} else { 
		echo "<input type='checkbox' name='form_ad_active_user' value='T'> Macher aktiv ";
	}
	if ( rtrim($tbl_row->AD_USER_OK_PRAKTIKANT) == "T") {
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_praktikant' value='T' checked='checked'> Praktikant";
	} else { 
		echo "&nbsp;&nbsp;<input type='checkbox' name='form_ad_praktikant' value='T'> Praktikant";
	}
	echo "</div>";
	echo "</div>\n";

	echo "<div class='content_row_a_3'>";
	echo "<div class='content_column_1'>Info</div>";
	echo "<textarea class='textarea_1' name='form_ad_text'> ";
	echo $tbl_row->AD_TEXT;
	echo "</textarea>";
	echo "</div>\n";
	echo "<div class='line_a'> </div>";
			
	echo "<input type='submit' value='Speichern'>";
	echo "</form>";
} // user_rights	
echo "</div>";
?>
	
</body>
</html>
