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
	
// check action	
if ( isset($_GET['action']) ) {
	if ( $_GET['action'] == "print" ) {
		if ( isset($_GET['ad_id']) ) {
			$ad_id = $_GET['ad_id'];
			// check id
			if ( ! filter_var( $ad_id, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000 )) ) ) {
				$action_ok = "no";
				$ad_id = "";
			}
			if ( $ad_id !="" )	{ 
				$action_ok = "yes";
				$c_query_condition = "AD_ID = ".$_GET['ad_id'];	
			}	
		}	
	}	
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
	
// Alles ok, Daten holen
if ( $action_ok == "yes" ) {
	$tbl_row = db_query_display_item_1("AD_MAIN", $c_query_condition);
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<title>Admin-SRB-Adresse - Formular Nutzeranmeldung</title>
	<style type="text/css">	   @import url("../parts/style/style_srb_print.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");    </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
</head>
<body onload="javascript:window.print(); return true;">
 
<?php 
if ( $action_ok == "no" ) {
	echo "Fehler bei Übergabe: ".$action;
	return;
}
if ( !$tbl_row ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 			
	echo html_header_srb_print_a("Eintrag ins Nutzerverzeichnis");
	echo "<div class='content'>";		
	echo "<div class='content_column_b_1'>Anrede/ Titel</div>";
	$c_anrede = db_query_load_value_by_id("AD_ANREDE", "AD_ANREDE_ID", $tbl_row->AD_ANREDE_ID);
	$c_titel  = db_query_load_value_by_id("AD_TITEL", "AD_TITEL_ID", $tbl_row->AD_TITEL_ID);
	echo "<div class='content_column_b_3'>" .$c_anrede. " ".$c_titel. " </div>";
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Vorname/ Name</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_VORNAME." ".$tbl_row->AD_NAME. "</div>";
	echo "<br>\n";
	echo "<div class='content_column_b_1'>Firma</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_FIRMA. "</div>";
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Straße/ Postfach</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_STRASSE;
	if ( $tbl_row->AD_PF != "" ) { 
		echo "/ ".$tbl_row->AD_PF. " </div>"; 
	} else { 
		echo "</div>";
	}			
	echo "<br>\n";
	echo "<div class=content_column_b_1>Land/ PLZ/ Ort</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_LAND_ID. " - ".$tbl_row->AD_PLZ." ".$tbl_row->AD_ORT. "</div>";
	echo "<div class='line'> </div>\n";
	echo "<div class='content_column_a_1'>Telefon 1/ 2</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_TEL_1; 
	if ( $tbl_row->AD_TEL_2 != "" ) { 
		echo "/ ".$tbl_row->AD_TEL_2. "</div>";
	} else { 
		echo "</div>";
	} 
	echo "<br>\n";
	echo "<div class=content_column_b_1>FAX</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_FAX. "</div>";
	echo "<br>\n";
	echo "<div class='content_column_a_1'>eMail</div>";
	echo "<div class='content_column_a_3'>" .$tbl_row->AD_EMAIL. "</div>";
	echo "<br>\n";
	echo "<div class=content_column_b_1>Internet</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_WEB. "</div>";
	echo "<div class='line'> </div>";
	echo "<div class='content_column_a_1'>Geburtstag</div>";
	echo "<div class='content_column_b_3'>" .get_date_format_deutsch($tbl_row->AD_DATUM_GEBURT)."</div>"; 
	echo "<br>&nbsp;<br>\n";
	echo "<div class=content_column_b_1>Bildung/ Beruf</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_BILDUNG;
	if ( $tbl_row->AD_BERUF != "" ) { 
		echo "/ ".$tbl_row->AD_BERUF. "</div>";
	} else { 
		echo "</div>";
	} 
	echo "<br>\n";
	echo "<div class='content_column_a_1'>PA-Nr/ Pass-Nr </div>";
	echo "<div class='content_column_b_3'>" .$tbl_row->AD_NR_PA;
	if ( $tbl_row->AD_NR_PASS != "" ) { 
		echo "/ ".$tbl_row->AD_NR_PASS. "</div>";
	} else { 
		echo "</div>";
	} 
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Registriert unter Nr.</div>";
	echo "<div class='content_column_a_3'>" .$_GET['ad_id']. "</div>";
	echo "<br>\n";
	echo "<div class='space_line'> </div>";
			
	// user_specials
	$tbl_row_a = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'Nutzer_Anmeldung'");
	echo "<textarea class='textarea_a_5' name='ad_text'>";
	echo $tbl_row_a->USER_SP_TEXT;
	echo "</textarea>";
	echo "<div class='space_line'> </div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='space_line'> </div>";
			
	$tbl_row_b = db_query_display_item_from_one_row_table_1("USER_DATA");
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Aufnahmedatum: ".get_date_format_deutsch($tbl_row->AD_TIME_AUFNAHME)."</div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='content_column_a_1'>........................................<br> Unterschrift Nutzer/in</div>";
	echo "<div class='content_column_a_2'>&nbsp;</div>";
	echo "<div class='content_column_a_5'>........................................<br> Unterschrift ". $tbl_row_b->USER_AD_NAME_SHORT. "</div>";
	echo "<br>\n</div>";
	
	
	
} // user_rights
?>
	
</body>
</html>