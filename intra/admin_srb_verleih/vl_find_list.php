<?php
/** 
* Verleih listen
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
	$query_main_table = "VL_MAIN";
}

if ( $condition_delivery != "yes" ) {
	 	
	// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
	// also hier nur pruefen in welchem feld was ist
	
	if ( isset( $_POST['vl_projekt'] ) ) {
		if ( $_POST['vl_projekt'] !="") { 
			$c_field_desc = "VL_PROJEKT";
			$c_field_value = $_POST['vl_projekt']; 
		}
	}

	if ( isset( $_POST['vl_text'] ) ) {
		if ( $_POST['vl_text'] !="") { 
			$c_field_desc = "VL_TEXT";
			$c_field_value = $_POST['vl_text']; 
		}
	}

	if ( isset( $_POST['vl_datum'] ) ) {
		if ( $_POST['vl_datum'] !="") { 
			$c_field_desc = "VL_DATUM_START";
			$c_field_value = get_date_format_sql($_POST['vl_datum']); 
		}
	}

	if ( isset( $_POST['vl_id'] ) ) {
		if ( $_POST['vl_id'] !="") { 
			$c_field_desc = "VL_ID";
			$c_field_value = $_POST['vl_id']; 
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
				$message_find_string = $c_field_desc. " beginnt mit " .$c_field_value." neueste zuerst" ;
			} elseif ( $find_option == "in" ) {
				//$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".$c_field_value."%' collate de_de)";
				$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".utf8_decode($c_field_value)."%' collate de_de)";
				$message_find_string = $c_field_desc. " enthält " .$c_field_value." neueste zuerst"  ;
			} elseif ( $find_option == "exact" ) {
				$c_query_condition = $c_field_desc." = '".$c_field_value."'";
				$message_find_string = $c_field_desc. " ist exakt " .$c_field_value." neueste zuerst"  ;
			} elseif ( $find_option == "datum" ) {
				$c_query_condition = $c_field_desc." = '".$c_field_value."'";
				$message_find_string = $c_field_desc. " ist datum " .$c_field_value." neueste zuerst"  ;
			}
				
			// Sortierung anhaengen
			if ( $query_main_table == "VL_MAIN" ) {
				$c_query_condition .= " ORDER BY VL_DATUM_START DESC";
			} else {
				$c_query_condition .= " ORDER BY VL_ITEM_ID DESC";
			}
				
			break;
			
		case "list": 
			switch ( $find_option ) {
			case "vl_new_items":
				$c_query_condition = "VL_DATUM_START <= '".date("Y-m-d")."' AND VL_ID <> 0 ORDER BY VL_DATUM_START DESC";
				$message_find_string = "Neueste Ausleihe nach Datum absteigend";
				break;					
				
			case "vl_open_items":
				$query_main_table = "VL_ITEMS";
				$c_query_condition = "VL_ITEM_IV_BACK = 'F' ORDER BY VL_ITEM_DATUM_START DESC";
				$message_find_string = "Offener Verleih nach Datum absteigend";
				break;
			
			case "vl_this_month":
				$c_query_condition = "SUBSTRING(VL_DATUM_START FROM 1 FOR 7) = '".date("Y-m")."'";
				$message_find_string = "Verleih diesen Monat";
				break;
					
			case "vl_adress":
			  	$c_query_condition = "VL_AD_ID = ".$_GET['vl_ad_id']." ORDER BY VL_DATUM_START DESC";
				$message_find_string = "Verleih zu Adresse nach Datum absteigend";
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
	

if ( $query_main_table == "VL_MAIN" ) {
	// ausgabebegrenzung 1
	if ( 	$find_limit_skip == "no" ) {			
		//$db_result = db_query_list_items_limit_1("VL_ID, VL_AD_ID, VL_DATUM_START, VL_DATUM_END, VL_PROJEKT, VL_TEXT", "VL_MAIN", $c_query_condition, "FIRST 25");
		$db_result = db_query_vl_ad_list_items_limit($c_query_condition, "FIRST 25");
		$z = 0;
	} else {
		$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
		//$db_result = db_query_list_items_limit_1("VL_ID, VL_AD_ID, VL_DATUM_START, VL_DATUM_END, VL_PROJEKT, VL_TEXT", "VL_MAIN", $c_query_condition, $c_limit);
		$db_result = db_query_vl_ad_list_items_limit($c_query_condition, $c_limit);
		$z = $find_limit_skip;
	} 
} else { 
	// ausgabebegrenzung 1
	if ( $find_limit_skip == "no" ) {			
		//$db_result = db_query_list_items_limit_1("VL_ITEM_ID, VL_MAIN_ID, VL_ITEM_IV_ID, VL_ITEM_IV_BACK", "VL_ITEMS", $c_query_condition, "FIRST 25");
		$db_result = db_query_vl_item_ad_iv_list_items_limit($c_query_condition, "FIRST 25");
		$z = 0;
	} else {
		$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
		//$db_result = db_query_list_items_limit_1("VL_ITEM_ID, VL_MAIN_ID, VL_ITEM_IV_ID, VL_ITEM_IV_BACK", "VL_ITEMS", $c_query_condition, $c_limit);
		$db_result = db_query_vl_item_ad_iv_list_items_limit($c_query_condition, $c_limit);
		$z = $find_limit_skip;
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Verleih</title>
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
 
<div class="main">
<?php 
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
require "parts/vl_menu.inc";	
user_display();	
echo "</div> <!--class=column_left-->";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n";
echo "</div>";
require "parts/vl_toolbar.inc";
echo "<div class='content' id='jq_slide_by_click'>";

if ( $action_ok == "no" ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 	  
	if ( $db_result	) {		
		if ( $query_main_table == "VL_MAIN" ) {
			$z =0;
			foreach ($db_result as $item ) {	
				$z +=1;
				// Listcolors					
				if ( $z % 2 != 0 ) { 
					echo "<div class='content_row_a_4'>"; 	
				} else {	
					echo "<div class='content_row_b_4'>";
				}
				//Adresse IV holen 
				//$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = ".$item['VL_AD_ID']);			
				//echo "<a href='vl_detail.php?action=display&amp;vl_id=".$item['VL_ID']."' class='c_box'>".get_date_format_deutsch($item['VL_DATUM_START'])." - ".$tbl_row_ad->AD_NAME." - ".substr($item['VL_PROJEKT'], 0, 40)."</a>";
				echo "<a href='vl_detail.php?action=display&amp;vl_id=".$item['VL_ID']."' class='c_box'>".get_date_format_deutsch($item['VL_DATUM_START'])." - ".$item['AD_NAME']." - ".substr($item['VL_PROJEKT'], 0, 40)."</a>";
				echo "</div>";
		
				echo "<div class='content_row_toggle_head_2'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
				echo "<div class='content_row_toggle_body_2'>";
				if ( $item['VL_TEXT'] != "" ) { 
					echo $item['VL_TEXT']."<br>";
				}
				echo "</div>\n";	
			}
		} else {
			$z =0;
			foreach ( $db_result as $item ) {
				$z +=1;
				// Listcolors					
				if ( $z % 2 != 0 ) { 
					echo "<div class='content_row_a_4'>"; 
				} else { 
					echo "<div class='content_row_b_4'>";
				}											
				// Verleih holen
				//$tbl_row_2 = db_query_display_item_1("VL_MAIN", "VL_ID = ".$item['VL_MAIN_ID']);
				// Adresse holen
				//$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = ".$tbl_row_2->VL_AD_ID);
				// Inventar holen
				//$tbl_row_iv = db_query_display_item_1("IV_MAIN", "IV_ID = ".$item['VL_ITEM_IV_ID']);
				//echo "<a href='vl_detail.php?action=display&amp;vl_id=".$tbl_row_2->VL_ID."' class='c_box'>".get_date_format_deutsch($tbl_row_2->VL_DATUM_START)." - ".$tbl_row_ad->AD_NAME." - ".substr($tbl_row_2->VL_PROJEKT, 0, 40)." - ".substr($tbl_row_iv->IV_OBJEKT, 0, 40)."</a>";
				echo "<a href='vl_detail.php?action=display&amp;vl_id=".$item['VL_ID']."' class='c_box'>".get_date_format_deutsch($item['VL_DATUM_START'])." - ".$item['AD_NAME']." - ".substr($item['VL_PROJEKT'], 0, 40)." - ".substr($item['IV_OBJEKT'], 0, 40)."</a>";
				echo "</div>\n";	
				echo "<div class='content_row_toggle_head_2'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
				echo "<div class='content_row_toggle_body_2'>";
//				if ( $tbl_row_2->VL_TEXT != "" ) { 
				if ( $item['VL_TEXT'] != "" ) {
					echo $item['VL_TEXT']."<br>";
				}
				echo "</div>\n";	
			}	
		}
	}
	if ( $z == 0 ) {	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$x = $z / 25;
		$zz = $z+1;	
		echo "<div class='content_footer'>Gefunden: ".$z. " ::: "; 	
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			echo " >> <a href='vl_find_list.php?action=".$action."&amp;table=".$query_main_table."&amp;condition=".rawurlencode($c_query_condition)."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."'>Weitere Anzeige ab Datensatz ".$zz."</a>";					
		}	
		echo "</div>";															
	}
} // user_rights
echo "</div><!--content-->";
echo "</div><!--column_right-->";
?>

</div><!--main-->
</body>
</html>