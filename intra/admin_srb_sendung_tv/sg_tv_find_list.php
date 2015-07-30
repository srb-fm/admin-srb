<?php

/** 
* sendung suchergebnisse anzeigen 
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
	
// info ausgabebegrenzung auf 25 datensaetze:
// der abfrage_condition wird das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den nächsten satzen wird die skip-anzahl in der url zugrechnet: (ausgabebegrenzung 2) und dann in die abfrage uebernommen (// ausgabebegrenzung 1)
// fuer die option find muss dazu feld und inhalt neu uebergeben werden ( ausgabebegrenzung 3)
	
// action pruefen	
if ( isset($_GET['action']) ) {
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action']) ) { 
	$action = $_POST['action']; 
	$action_ok = true;
}
	
if ( $action_ok == false ) { 
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
		
// Hauptabfrage-Tabelle, durch Neubelegung dieser var umschaltbar
if ( isset( $_GET['table'] ) ) {
	$query_main_table = $_GET['table'];
} else {
	$query_main_table = "SG_TV_CONTENT";
}

if ( $condition_delivery != "yes" ) {
	// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
	// also hier nur pruefen in welchem feld was ist

	if ( isset($_POST['sg_titel']) ) {
		if ( $_POST['sg_titel'] !="") { 
			$c_field_desc = "SG_TV_CONT_TITEL";
			$c_field_value = $_POST['sg_titel']; 
		}
	}

	if ( isset($_POST['sg_untertitel']) ) {
		if ( $_POST['sg_untertitel'] !="") { 
			$c_field_desc = "SG_TV_CONT_UNTERTITEL";
			$c_field_value = $_POST['sg_untertitel']; 
		}
	}

	if ( isset($_POST['sg_stichwort']) ) {
		if ( $_POST['sg_stichwort'] !="") { 
			$c_field_desc = "SG_TV_CONT_STICHWORTE";
			$c_field_value = $_POST['sg_stichwort']; 
		}
	}

	if ( isset($_POST['sg_dateiname']) ) {
		if ( $_POST['sg_dateiname'] !="") { 
			$c_field_desc = "SG_TV_CONT_FILENAME";
			$c_field_value = $_POST['sg_dateiname']; 
		}
	}

	if ( isset($_POST['sg_cass_nr']) ) {
		if ( $_POST['sg_cass_nr'] !="") { 
			$c_field_desc = "SG_TV_CONT_CARRIER_NR";
			$c_field_value = $_POST['sg_cass_nr']; 
		}
	}
			
	if ( isset($_POST['sg_datum']) ) {
		if ( $_POST['sg_datum'] !="") { 
			$query_main_table = "SG_TV_MAIN";
			$c_field_desc = "SG_TV_TIME";
			$c_field_value = get_date_format_sql($_POST['sg_datum']); 
		}
	}

	// check condition
	$find_option_ok = false;
	if ( isset($_GET['find_option']) ) {	
		$find_option = $_GET['find_option'];
		$find_option_ok = true;
	}
	if ( isset($_POST['find_option']) ) { 
		$find_option = $_POST['find_option']; 
		$find_option_ok = true;
	}		

	if ( $find_option_ok == true and $action_ok == true ) {
		switch ( $action ) {
		case "find": 
			if ( $find_option == "begin" ) {
			  	//$c_query_condition = "UPPER(".$c_field_desc.") LIKE UPPER(_iso8859_1'".$c_field_value."%' collate de_de)";
			  	$c_query_condition = "UPPER(".$c_field_desc.") LIKE UPPER(_iso8859_1'".utf8_decode($c_field_value)."%' collate de_de)";
				$message_find_string = $c_field_desc. " beginnt mit " .$c_field_value." neueste zuerst" ;
			} elseif ( $find_option == "in" ) {
				//$c_query_condition =  "UPPER(".$c_field_desc.") LIKE UPPER(_iso8859_1'%".$c_field_value."%' collate de_de)";
				$c_query_condition =  "UPPER(".$c_field_desc.") LIKE UPPER(_iso8859_1'%".utf8_decode($c_field_value)."%' collate de_de)";
				$message_find_string = $c_field_desc. " enthält " .$c_field_value." neueste zuerst"  ;
			} elseif ( $find_option == "exact" ) {
				$c_query_condition = $c_field_desc." = '".$c_field_value."'";
				// upper geht hier irgendwie nicht, koenne nicht transliterieren
				//$c_query_condition = "UPPER(".$c_field_desc.") = UPPER(_iso8859_1 '".$c_field_value."' collate de_de)";
				$message_find_string = $c_field_desc. " ist exakt " .$c_field_value." neueste zuerst"  ;
			} elseif ( $find_option == "datum" ) {
				$c_query_condition = "SUBSTRING( ".$c_field_desc." FROM 1 FOR 10) = '".$c_field_value."'";
				$message_find_string = $c_field_desc. " ist datum " .$c_field_value." neueste zuerst"  ;
			}
				
			// Sortierung anhaengen
			if ( $query_main_table == "SG_TV_CONT" ) {
				$c_query_condition .= " ORDER BY SG_TV_CONT_NR DESC";
			} else {
				$c_query_condition .= " ORDER BY SG_TV_CONT_NR DESC";
			}
				
			break;
			
		case "list": 
			switch ( $find_option ) {
			case "broadcaster":
				$c_query_condition = "SG_TV_CONT_AD_NR = '".$_GET['broadcaster_id']."' ORDER BY SG_TV_CONT_ID DESC";
				$message_find_string = "Sendungen zur Adresse";
				break;
			
			break;
			//endswitch; // $find_optio
			}
			break;
		//endswitch; // $action
		}
	} else {
		$message .= "Keine Suchbedingung! Kann nichts tun... "; 
	} //$find_option_ok == true 
	
} else {// $condition_delivery != "yes"
	$message_find_string = $_GET['find_string'] ;
} // $condition_delivery != "yes"
	

if ( $action_ok == true ) {
	if ( $query_main_table == "SG_TV_CONTENT" ) {
		// ausgabebegrenzung 1
		if ( $find_limit_skip == "no" ) {			
			$db_result = db_query_list_items_limit_1("SG_TV_NR, SG_TV_CONT_AD_NR, SG_TV_CONT_TITEL, SG_TV_CONT_UNTERTITEL, SG_TV_CONT_FILENAME, SG_TV_CONT_REGIEANWEISUNG", "SG_TV_CONTENT", $c_query_condition, "FIRST 25");
			$z = 0;
		} else {
			$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
			$db_result = db_query_list_items_limit_1("SG_TV_NR, SG_TV_CONT_AD_NR, SG_TV_CONT_TITEL, SG_TV_CONT_UNTERTITEL, SG_TV_CONT_FILENAME, SG_TV_CONT_REGIEANWEISUNG", "SG_TV_CONTENT", $c_query_condition, $c_limit);
			$z = $find_limit_skip;
		} 
	} else { 
		// ausgabebegrenzung 1
		if ( $find_limit_skip == "no" ) {			
			$db_result = db_query_list_items_limit_1("SG_TV_NR, SG_TV_CONT_NR, SG_TV_TIME, SG_TV_DURATION, SG_TV_INFOTIME, SG_TV_FIRST_SG, SG_TV_ON_AIR", "SG_TV_MAIN", $c_query_condition, "FIRST 25");
			$z = 0;
		} else {
			$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
			$db_result = db_query_list_items_limit_1("SG_TV_NR, SG_TV_CONT_NR, SG_TV_TIME, SG_TV_DURATION, SG_TV_INFOTIME, SG_TV_FIRST_SG, SG_TV_ON_AIR", "SG_TV_MAIN", $c_query_condition, $c_limit);
			$z = $find_limit_skip;
		}
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Sendung-TV</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");  </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>
	
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>	
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>	

</head>
<body>
 
<div class="main">
<?php 
include "../parts/site_elements/header_srb_2.inc";
include "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
include "parts/sg_tv_menu.inc";
user_display();	
echo "</div>";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n"; 
echo "</div>";
		
echo "<div class='content' id='jq_slide_by_click'>";
if ( $action_ok == false ) { 
	return;
} 
			
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) {	
	if ( $query_main_table == "SG_TV_CONTENT" ) {
		if ( $db_result ) {
			foreach ( $db_result as $item ) {	
				$z += 1;
				// Erstsendung holen
				$c_query_condition_1 = "A.SG_TV_NR = ".$item['SG_TV_NR']." AND A.SG_TV_FIRST_SG = 'T' "; 
				$tbl_row_1 = db_query_sg_tv_display_item_a($c_query_condition_1);

				// Listcolors					
				if ( $z % 2 != 0 ) { 
					echo "<div class='content_row_a_4'>";	
				} else { 
					echo "<div class='content_row_b_4'>";
				}
				echo "<a href='sg_tv_detail.php?action=display&amp;sg_id=".$item['SG_TV_NR']."' class='c_box_3'>".get_date_format_deutsch(substr($tbl_row_1->SG_TV_TIME, 0, 10))." - ".substr($tbl_row_1->SG_TV_TIME, 11, 8)." - ".substr($item['SG_TV_CONT_TITEL'], 0, 40)." - ".substr($tbl_row_1->AD_NAME, 0, 12)."</a>";
				echo "</div>";
		
				echo "<div class='content_row_toggle_head_2'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
				echo "<div class='content_row_toggle_body_2'>";
				if ( $item['SG_TV_CONT_UNTERTITEL'] != "" ) { 
					echo $item['SG_TV_CONT_UNTERTITEL']."<br>";
				}
				echo $item['SG_TV_CONT_FILENAME'];
				echo "<br> Länge: ".$tbl_row_1->SG_TV_DURATION;
				echo "</div>\n";	
			}
		}
	} else {
		if ( $db_result ) {
			foreach ( $db_result as $item ) {
				$z += 1;
				// Listcolors					
				if ( $z % 2 != 0 ) { 
					echo "<div class='content_row_a_4'>";	
				} else { 
					echo "<div class='content_row_b_4'>";
				}
						
				// main-sendung holen
				$c_query_condition_1 = "A.SG_TV_CONT_NR = ".$item['SG_TV_CONT_NR'];
				$tbl_row_1 = db_query_sg_tv_display_item_b($c_query_condition_1);

				echo "<a href='sg_tv_detail.php?action=display&amp;sg_id=".$item['SG_TV_NR']."' class='c_box'>".get_date_format_deutsch(substr($item['SG_TV_TIME'], 0, 10))." - ".substr($item['SG_TV_TIME'], 11, 8)." - ".substr($tbl_row_1->SG_TV_CONT_TITEL, 0, 40)." - ".substr($tbl_row_1->AD_NAME, 0, 12)."</a>";
				echo "</div>";
				
				echo "<div class='content_row_toggle_head_2'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
				echo "<div class='content_row_toggle_body_2'>";
				if ( $tbl_row_1->SG_TV_CONT_UNTERTITEL != "" ) { 
					echo $tbl_row_1->SG_TV_CONT_UNTERTITEL."<br>";
				}
				echo $tbl_row_1->SG_TV_CONT_FILENAME;
				echo "<br> Länge: ".$item['SG_TV_DURATION'];
				echo "</div>\n";	
			}
		}	
	}

	if ( $z == 0 ) {	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$x = $z / 25;
		$zz = $z + 1;	
		echo "<div class='content_footer'>Gefunden: ".$z. " ::: "; 	
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			echo " >> <a href='sg_tv_find_list.php?action=".$action."&amp;table=".$query_main_table."&amp;condition=".rawurlencode($c_query_condition)."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a></p>";					
		}							
		echo "</div>";			
								
	}
} // user_rights
echo "</div><!--content-->";
echo "&nbsp;";
echo "</div><!--column_right-->";
?>

</div><!--main-->
</body>
</html>