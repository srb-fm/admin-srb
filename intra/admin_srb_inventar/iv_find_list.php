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
	$action_ok = "yes";
}
if ( isset( $_POST['action'] ) ) {
	$action = $_POST['action'];
	$action_ok = "yes";
}
		
if ( $action_ok != "yes" ) {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
			
// condition_delivery pruefen (ausgabelimit)
if ( isset($_GET['condition']) ) {
	$c_query_condition = rawurldecode($_GET['condition']);
	$condition_delivery = "yes";
}	
	
// ausgabebegrenzung
// limit ueber limitweiterschaltung


if ( isset($_GET['find_limit_skip']) ) { 
	$find_limit_skip = $_GET['find_limit_skip'];
}
	
if ( $condition_delivery != "yes" ) {
	// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
	// also hier nur pruefen in welchem feld was ist
	if ( isset( $_POST['iv_objekt'] ) ) {
		if ( $_POST['iv_objekt'] !="") { 
			$c_field_desc = "IV_OBJEKT";
			$c_field_value = $_POST['iv_objekt']; 
		}
	}

	if ( isset( $_POST['iv_typ'] ) ) {
		if ( $_POST['iv_typ'] !="") { 
			$c_field_desc = "IV_TYP";
			$c_field_value = $_POST['iv_typ']; 
		}
	}

	if ( isset( $_POST['iv_hersteller'] ) ) {
		if ( $_POST['iv_hersteller'] !="") { 
			$c_field_desc = "IV_HERSTELLER";
			$c_field_value = $_POST['iv_hersteller']; 
		}
	}

	if ( isset( $_POST['iv_rechnung'] ) ) {
		if ( $_POST['iv_rechnung'] !="") { 
			$c_field_desc = "IV_RECHNUNG_NR";
			$c_field_value = $_POST['iv_rechnung']; 
		}
	}
	
	if ( isset( $_POST['iv_id'] ) ) {
		if ( $_POST['iv_id'] !="") { 
			$c_field_desc = "IV_ID";
			$c_field_value = $_POST['iv_id']; 
		}
	}
			
		
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
		switch ( $action ) {
		case "find": 
			if ( $find_option == "begin" ) {
		    	//$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '".$c_field_value."%' collate de_de) ";
		    	$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '".utf8_decode($c_field_value)."%' collate de_de) ";
				$message_find_string = $c_field_desc. " beginnt mit " .$c_field_value ;
			} elseif ( $find_option == "in" ) {
				//$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".$c_field_value."%' collate de_de) ";
				$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".utf8_decode($c_field_value)."%' collate de_de) ";
				$message_find_string = $c_field_desc. " enthält " .$c_field_value  ;
			} elseif ( $find_option == "exact" ) {
				$c_query_condition = "upper(".$c_field_desc.") = '".$c_field_value."'";
				$message_find_string = $c_field_desc. " ist exakt " .$c_field_value  ;
			} elseif ( $find_option == "datum" ) {
				$c_query_condition = "SUBSTRING( ".$c_field_desc." FROM 1 FOR 10) = '".$c_field_value."'";
				$message_find_string = $c_field_desc. " ist datum " .$c_field_value  ;
			}
				
			// Sortierung anhaengen
			if ( $look_up_field == "no" ) {
				$c_query_condition .= " ORDER BY ".$c_field_desc;
			} else {
				$c_query_condition .= " ORDER BY IV_OBJEKT";
			}			
			break;

		case "list": 
			switch ( $find_option ) {
			case "gesamt":
			  	$c_query_condition = "upper( IV_OBJEKT ) >= 'A' ORDER BY IV_OBJEKT";
				$message_find_string = "Gesamtliste alphabetisch";
				break;
		
			case "gesamt_datum":
				$c_query_condition = "upper( IV_OBJEKT ) >= 'A' ORDER BY IV_DATUM_ANSCHAFFUNG DESC";
				$message_find_string = "Gesamtliste nach Datum, neueste zuerst";
				break;
						
			case "verliehen_gesamt":
				$c_query_condition = "IV_VERLIEHEN = 'T' ORDER BY IV_OBJEKT";
				$message_find_string = "Gesamtliste verliehene Objekte alphabetisch";
				break;
					
			case "defekt_gesamt":
				$c_query_condition = "IV_DEFEKT = 'T' ORDER BY IV_OBJEKT";
				$message_find_string = "Gesamtliste defekte Objekte alphabetisch";
				break;
					
			case "ausgemustert_gesamt":
				$c_query_condition = "IV_AUSGEMUSTERT = 'T' ORDER BY IV_OBJEKT";
				$message_find_string = "Gesamtliste ausgemusterte Objekte alphabetisch";
				break;	
				//endswitch; // $find_option
			}
			break;
			//endswitch; // $action
		}
	} else {
		$message = "Keine Suchbedingung! Kann nichts tun... "; 
	} //$find_option_ok = "yes" 
	
} else {// $condition_delivery != "yes"
		$message_find_string = $_GET['find_string'] ;
} // $condition_delivery != "yes"
	

// ausgabebegrenzung 1
if ( 	$find_limit_skip == "no" ) {			
	$db_result = db_query_list_items_limit_1("IV_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_VERLIEHEN, IV_AUSGEMUSTERT", "IV_MAIN", $c_query_condition, "FIRST 25");
	$z = 0;
} else {
	$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
	$db_result = db_query_list_items_limit_1("IV_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_VERLIEHEN, IV_AUSGEMUSTERT", "IV_MAIN", $c_query_condition, $c_limit);
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
include "../parts/site_elements/header_srb_2.inc";
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

if ( $action_ok == "no" ) { 
	return;
} 
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	
	if ( $db_result ) {
		foreach ( $db_result as $item ) {
			$z +=1;
			// Listcolors					
			if ( $z % 2 != 0 ) {
				if ( rtrim($item['IV_AUSGEMUSTERT'])=='T') {
					echo "<div class='content_row_a_7'>";
				} else {					
					echo "<div class='content_row_a_4'>";
				}						
			} else { 
				if ( rtrim($item['IV_AUSGEMUSTERT'])=='T') {
					echo "<div class='content_row_b_7'>";
				} else {						
					echo "<div class='content_row_b_4'>";
				}
			}
			echo "<a href='iv_detail.php?action=display&amp;iv_id=".$item['IV_ID']."' class='c_box_2'>".substr($item['IV_OBJEKT'], 0, 40)." - ".substr($item['IV_TYP'], 0, 40)."</a>";
		  	echo "</div>";
				
			echo "<div class='content_row_toggle_head_2'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
			echo "<div class='content_row_toggle_body_2'>";
			echo $item['IV_HERSTELLER'];
			echo "</div>\n";
		}
	}
					
	if ( $z == 0 ) {	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$x = $z / 25;
		$zz = $z+1;	
		echo "<div class='content_footer'> Gefunden: ".$z. " ::: "; 	
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			echo " >> <a href='iv_find_list.php?action=".$action."&amp;condition=".rawurlencode($c_query_condition)."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a>";					
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