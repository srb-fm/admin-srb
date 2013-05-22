<?php
/** 
* Verleih Formulardruck
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
$action_ok = "no";
	
// action pruefen	
if ( isset( $_GET['action'] ) ) {
	if ( $_GET['action'] == "print" ) {
		if ( isset( $_GET['vl_id'] ) ) {	
			if ( $_GET['vl_id'] !="" ) { 
				$action_ok = "yes";
			}	
		}	
	}	
} else {
	$message .= "Keine Anweisung. Nichts zu tun..... "; 
}
		
// Alles ok, Daten holen
if ( $action_ok == "yes" ) {
	$tbl_row_vl = db_query_display_item_1("VL_MAIN", "VL_ID = " .$_GET['vl_id']);
	if ( !$tbl_row_vl ) { 
		$message .= "Fehler bei Abfrage Verleih!"; 
		$action_ok = "no";
	} else {
		$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$tbl_row_vl->VL_AD_ID);
		if ( !$tbl_row_ad ) { 
			$message .= "Fehler bei Abfrage Adresse!"; 
			$action_ok = "no";
		} else {
			$db_result_vl_items = db_query_list_items_1("VL_ITEM_ID, VL_MAIN_ID, VL_ITEM_IV_ID, VL_ITEM_DATUM_START", "VL_ITEMS", "VL_MAIN_ID = " .$tbl_row_vl->VL_ID);
		}
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Verleih - Formular Ausleihe</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_print.css");    </style>
</head>
<body onload="javascript:window.print(); return true;">
 
<?php 
if ( $action_ok == "no" ) { 
	return;
}
if ( !$tbl_row_vl ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	
	echo html_header_srb_print_a("Leihschein");
	echo "<div class='content'>";
	echo "<div class='content_column_b_1'>Verleih an</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row_ad->AD_VORNAME." " .$tbl_row_ad->AD_NAME.", ".$tbl_row_ad->AD_STRASSE.", ".$tbl_row_ad->AD_PLZ.", ".$tbl_row_ad->AD_ORT. "</div>";
	echo "<br>\n";
	echo "<div class='content_column_a_1'>Verleih von bis</div>";
	echo "<div class='content_column_b_3'>" .get_date_format_deutsch($tbl_row_vl->VL_DATUM_START). " - " .get_date_format_deutsch($tbl_row_vl->VL_DATUM_END). " </div>";
	echo "<div class='content_column_a_1'>Projekt</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row_vl->VL_PROJEKT. "</div>";
	echo "<div class='content_column_b_1'>Bemerkungen</div>";
	echo "<div class='content_column_b_3'>" .$tbl_row_vl->VL_TEXT. "</div>";
	echo "<div class='space_line'> </div>";
			
	// user_specials
	$tbl_row_a = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'Verleih_Leihschein'");	
	echo "<textarea class='textarea_a_5' name='vl_text'>";
	echo ltrim($tbl_row_a->USER_SP_TEXT);
	echo "</textarea>";

	echo "<div class='head_item_a_2'>Geräteliste</div>";
			
	$z = 0;
	foreach ($db_result_vl_items as $item) {
		$z +=1;
		if ( $z % 2 != 0 ) { 
		 	echo "<div class='content_column_a_3'>";
		} else { 
			echo "<div class='content_column_b_3'>";	
		}				
				
		// IV holen
		$tbl_row_iv = db_query_display_item_1("IV_MAIN", "IV_ID = ".$item['VL_ITEM_IV_ID']);
		echo $z.". ".$tbl_row_iv->IV_OBJEKT." - ".$tbl_row_iv->IV_TYP."</div>";
	} 
			
	echo "<div class='line'> </div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='space_line'> </div>";
	echo "<div class='space_line'> </div>";
	$tbl_row_b = db_query_display_item_from_one_row_table_1("USER_DATA");
	echo "<div class='content_column_a_1'>.......................................................&nbsp;&nbsp;&nbsp;<br> Unterschrift Macher/in</div>";
	echo "<div class='content_column_a_5'>.......................................................&nbsp;&nbsp;&nbsp;<br> Unterschrift ". $tbl_row_b->USER_AD_NAME_SHORT. "</div>";
	echo "<div class='content_column_a_1'>.......................................................<br> Zurückgenommen</div>";
	echo "</div>"; // content wieder zu
} // user_rights
?>
</body>
</html>