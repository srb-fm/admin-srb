<?php

/** 
* Inventar Kategorien anzeigen 
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
$action_ok = false;
$find_limit_skip = "no";
$condition_delivery = "no";
$look_up_field ="no";
	
	
// info ausgabebegrenzung auf 25 datensaetze:
// der abfrage_condition wird das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den nächsten satzen wird die skip-anzahl in der url zugrechnet: (ausgabebegrenzung 2) und dann in die abfrage uebernommen (// ausgabebegrenzung 1)
// fuer die option find muss dazu feld und inhalt neu uebergeben werden ( ausgabebegrenzung 3)
	
// action pruefen	
if ( isset( $_GET['action'] ) ) {
		$action = $_GET['action'];
		$action_ok = true;
}
		
if ( isset( $_POST['action'] ) ) {
		$action = $_POST['action'];
		$action_ok = true;
}
		
if ( $action_ok != "yes" ) {	
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
			
// condition_delivery pruefen (	ausgabelimit)
if ( isset( $_GET['condition'] ) ) {
	$c_query_condition = rawurldecode($_GET['condition']);
	$condition_delivery = "yes";
}	
	
// ausgabebegrenzung
// limit  ueber limitweiterschaltung 		
if ( isset( $_GET['find_limit_skip'] ) ) { 
	$find_limit_skip = $_GET['find_limit_skip'];
}
		
if ( $condition_delivery != "yes" ) {
	// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
	// also hier nur pruefen in welchem feld was ist
	
		
	// Bedingung pruefen	
	$find_option_ok = "no";
	if ( isset( $_GET['find_option'] ) ) {
		$find_option = $_GET['find_option'];
		$find_option_ok = "yes";
	}
	if ( isset( $_POST['find_option'] ) ) {
		$find_option = $_POST['find_option'];
		$find_option_ok = "yes";
	}		
	
	if ( $find_option_ok = "yes" ) {

		switch ( $find_option ) {
		case "gesamt":
			$c_query_condition = "upper( IV_KAT_DESC ) >= 'A' AND IV_KAT_ID <> '00' ORDER BY IV_KAT_DESC";
			$message_find_string = "Gesamtliste alphabetisch";
			break;
		//endswitch; // $find_option
		}
	} else {
		$message = "Keine Suchbedingung! Kann nichts tun... "; 
	} //$find_option_ok = "yes" 
	
} else {// $condition_delivery != "yes"
	$message_find_string = $_GET['find_string'] ;
} // $condition_delivery != "yes"
	

// ausgabebegrenzung 1

if ( $find_limit_skip == "no" ) {			
	$db_result = db_query_list_items_limit_1("IV_KAT_ID, IV_KAT_DESC", "IV_KATEGORIE", $c_query_condition, "FIRST 25");
	$z = 0;
} else {
	$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
	$db_result = db_query_list_items_limit_1("IV_KAT_ID, IV_KAT_DESC", "IV_KATEGORIE", $c_query_condition, $c_limit);
	$z = $find_limit_skip;
} 
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Inventar</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");  </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>
	
	<script src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script src="../parts/colorbox/jquery.colorbox.js"></script>	
	<script src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
</head>
<body>
 
<?php 
echo "<div class='main'>";
include  "../parts/site_elements/header_srb_2.inc";
include "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
include "parts/iv_menu.inc";
user_display();
echo "</div> <!--class=column_left-->";

echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n"; 
echo "	</div>";
include "parts/iv_toolbar.inc";
echo "<div class='content' id='jq_slide_by_click'>";
if ( $action_ok == false ) { 
		return;
} 
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) {
	
	foreach ( $db_result as $item ) {	
		$z += 1;
		// Listcolors					
		if ( $z % 2 != 0 ) { 
			echo "<div class='content_row_a_4'>";
		} else { 
			echo "<div class='content_row_b_4'>";
		}
						
		echo $item['IV_KAT_DESC'];
     	echo "</div>";
				
		echo "<div class='content_row_toggle_head_3'><img src='../parts/pict/form.gif' title='Objekte anzeigen' alt='Zusatz'></div>";
		echo "<div class='content_row_toggle_body_3'>";
		// zugehoeriges invantar holen
		$c_query_condition_1 = "IV_KATEGORIE_ID = ".$item['IV_KAT_ID']." ORDER BY IV_OBJEKT";
		$db_result_1 = db_query_list_items_1("IV_ID, IV_KATEGORIE_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_VERLIEHEN", "IV_MAIN", $c_query_condition_1);
		foreach ( $db_result_1 as $item_1 ) {	
			echo $item_1['IV_OBJEKT']." - ".$item_1['IV_TYP']."<br>";
		}
		echo "</div>\n";		
	}

	if ( $z == 0 ) {	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$x = $z / 25;
		$zz = $z + 1;	
		echo "<div class='content_footer'> Gefunden: ".$z. " ::: "; 	
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			echo " >> <a href='iv_kat_list.php?action=".$action."&amp;condition=".rawurlencode($c_query_condition)."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a>";					
		}
		echo "</div>";								
	}
} // user_rights
echo "</div><!--content-->";
echo "&nbsp;";
echo "</div><!--column_right-->";
echo "</div><!--main-->";
?>
</body>
</html>