<?php

/** 
* Sendung Formular Sendeanmeldung 
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
	if ( $_GET['action'] == "print" ) {
		if ( isset( $_GET['sg_id'] ) ) {
			if ( $_GET['sg_id'] !="" ) { 
				$action_ok = "yes";
			}
		}
	}	
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
		
if ( $action_ok == "yes" ) {
	$tbl_row_sg = db_query_sg_display_item_1($_GET['sg_id']);
	if ( !$tbl_row_sg ) { 
		$message .= "Fehler bei Abfrage Sendung!"; 
		$action_ok = "no";
	} else {
		$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$tbl_row_sg->SG_HF_CONT_AD_ID);
		if ( !$tbl_row_ad ) { 
			$message .= "Fehler bei Abfrage Adresse!"; 
			$action_ok = "no";
		}
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung - Formular Sendeanmeldung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_print.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
</head>
<body onload="javascript:window.print(); return true;">
<?php 
if ( $action_ok == "no" ) { 
	return;
}
if ( !$tbl_row_sg ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 		
	echo html_header_srb_print_a("Sendeanmeldung");
	echo "<div class='content'>";
	echo "<div class='content_column_b_1'>Datum/ Zeit/ Länge</div>";
	echo "<div class='content_column_b_4'>" .get_german_day_name_1(substr($tbl_row_sg->SG_HF_TIME, 0, 10)).", ".get_date_format_deutsch(substr($tbl_row_sg->SG_HF_TIME, 0, 10)).  "</div>";
	echo "<div class='content_column_b_4'>" .substr($tbl_row_sg->SG_HF_TIME, 11, 8). " </div>";
	echo "<div class='content_column_b_4'>" .$tbl_row_sg->SG_HF_DURATION. " </div>";
	echo "<br>\n";

	echo "<div class='content_column_a_1'>Titel</div>";
	echo "<div class='content_column_a_3'>" .$tbl_row_sg->SG_HF_CONT_TITEL. "</div>";
	echo "<br>\n";
	echo "<div class='content_column_b_1'>Untertitel</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row_sg->SG_HF_CONT_UNTERTITEL. "</div>";
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Sendeverantwortlich</div>";
	echo "<div class='content_column_a_3'>" .$tbl_row_ad->AD_VORNAME." " .$tbl_row_ad->AD_NAME.", ".$tbl_row_ad->AD_ORT. "</div>";
	echo "<br>\n";
	echo "<div class='line'> </div>";
	echo "<div class=content_column_b_1>Stichworte</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row_sg->SG_HF_CONT_STICHWORTE."</div>" ;
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Internet</div>";
	echo "<div class='content_column_a_3'>" .$tbl_row_sg->SG_HF_CONT_WEB. "</div>";
	echo "<br>\n";
	echo "<div class=content_column_b_1>Genre/ Sprache</div>";
	echo "<div class='content_column_b_3'>" .db_query_load_value_by_id("SG_GENRE", "SG_GENRE_ID", $tbl_row_sg->SG_HF_CONT_GENRE_ID). "/ ";
	echo db_query_load_value_by_id("SG_SPEECH", "SG_SPEECH_ID", $tbl_row_sg->SG_HF_CONT_SPEECH_ID). "</div>";
	echo "<br>\n";
	echo "<div class='line'> </div>";
	echo "<div class=content_column_a_1>Einstellungen/ Quelle</div>";
	echo "<div class='content_column_a_3'>" ;
	if ( rtrim($tbl_row_sg->SG_HF_INFOTIME) == "T" ) {
		echo "InfoTime - Sendung";
	}
	if ( rtrim($tbl_row_sg->SG_HF_MAGAZINE) == "T" ) {
		echo "Magazin - Sendung ";
	}	
	if ( rtrim($tbl_row_sg->SG_HF_INFOTIME) != "T" and rtrim($tbl_row_sg->SG_HF_MAGAZINE) != "T" ) {
		echo "Normal - Sendung ";
	}
	echo "/  ".db_query_load_value_by_id("SG_HF_SOURCE", "SG_HF_SOURCE_ID", rtrim($tbl_row_sg->SG_HF_SOURCE_ID));
	echo "</div>\n";
	echo "<br>\n";
	echo "<div class=content_column_a_1>Content/ Sendung-Nr</div>";
	echo "<div class='content_column_a_2'>" .$tbl_row_sg->SG_HF_CONT_ID. "</div>";
	echo "<div class='content_column_a_2'>" .$tbl_row_sg->SG_HF_ID. "</div>";
	echo "<br>\n";
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Dateiname</div>";
	echo "<div class='content_column_a_3'>" .$tbl_row_sg->SG_HF_CONT_FILENAME. "</div>";

	echo "<div class='space_line'> </div>\n";

	// user_specials
	$tbl_row_a = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'Sendung_Anmeldung'");	
	echo "<textarea class='textarea_a_4' name='ad_text'>";
	echo ltrim($tbl_row_a->USER_SP_TEXT);
	echo "</textarea>";
	echo "<div class='space_line'> </div>";
	echo "<div class='space_line'> </div>";
			
	$tbl_row_b = db_query_display_item_from_one_row_table_1("USER_DATA");
	echo "<div class='space_line'> </div>\n";
	echo "<div class='content_column_a_1'>........................................<br> Unterschrift Nutzer/in</div>";
	echo "<div class='content_column_a_2'>&nbsp;</div>";
	echo "<div class='content_column_a_5'>........................................<br> Unterschrift ". $tbl_row_b->USER_AD_NAME_SHORT. "</div>";
	echo "<br>\n";

	echo "</div>"; // content wieder zu
} // user_rights
?>
</body>
</html>