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
$vl_id = "no";
$find_kategorie = "no";
	
// info ausgabebegrenzung auf 25 datensaetze:
// der abfrage_condition wird das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den naechsten satzen wird die skip-anzahl in der url zugrechnet: (ausgabebegrenzung 2) und dann in die abfrage uebernommen (// ausgabebegrenzung 1)
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
	
// kommt von verleih neues objekt
if ( isset( $_POST['vl_id'] ) ) { 
	$vl_id = $_POST['vl_id'];	
}
if ( isset( $_GET['vl_id'] ) ) { 
	$vl_id = $_GET['vl_id'];	
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
// kat listen oder objekt
if ( isset( $_GET['find_kat'] ) ) { 
	$find_kategorie = $_GET['find_kat'];
}
		
if ( $condition_delivery != "yes" ) {
	 	
	// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
	// also hier nur pruefen in welchem feld was ist
	
	if ( isset( $_POST['iv_kat'] ) ) {
		if ( $_POST['iv_kat'] !="") { 
			$find_kategorie = "yes";
			$c_field_desc = "IV_KAT_DESC";
			$c_field_value =  $_POST['iv_kat'];	
		}
	}	
	
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
				$message_find_string = $c_field_desc. " enthÃ¤lt " .$c_field_value  ;
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
if ( $find_limit_skip == "no" ) {			
	if ( $find_kategorie == "no" ) {
	  	$db_result = db_query_list_items_limit_1("IV_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_VERLIEHEN", "IV_MAIN", $c_query_condition, "FIRST 25");
	} else {
		$db_result_kat = db_query_list_items_limit_1("IV_KAT_ID, IV_KAT_DESC", "IV_KATEGORIE", $c_query_condition, "FIRST 25");
	}
	$z = 0;
} else {
	$c_limit = "FIRST 25 SKIP ".$find_limit_skip;
	if ( $find_kategorie == "no" ) {		
		$db_result = db_query_list_items_limit_1("IV_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_VERLIEHEN", "IV_MAIN", $c_query_condition, $c_limit);
	} else {
		$db_result_kat = db_query_list_items_limit_1("IV_KAT_ID, IV_KAT_DESC", "IV_KATEGORIE", $c_query_condition, $c_limit);
	}
	$z = $find_limit_skip;
} 
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Inventar-Verleih</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");  </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
	
</head>
<body>
 
<?php 
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n"; 
echo "	</div>";
echo "<div class='content' id='jq_slide_by_click'>";
if ( $action_ok == "no" ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	
		 
	if ( $find_kategorie == "no" ) {
		if ( $db_result ) {	
			// objekte
			foreach ( $db_result as $item ) {
				$z += 1;
				// Listcolors					
				if ( $z % 2 != 0 ) { 
					echo "<div class='content_row_a_1'>";	
				} else { 
					echo "<div class='content_row_b_1'>";
				}
				echo "<div class='content_column_8_a'>".substr($item['IV_OBJEKT'], 0, 40)." - ".substr($item['IV_TYP'], 0, 40)."</div>";
	     		echo "<div class='content_column_tool_img_1'>";
				if ( $vl_id != "no" ) { 
					if ( rtrim($item['IV_VERLIEHEN']) == 'F') {
						echo "<a href='../admin_srb_verleih/vl_detail.php?action=new_objekt&amp;vl_id=".$vl_id."&amp;iv_id=".$item['IV_ID']."'><img src='../parts/pict/1281368175_limited-edition.png' width='16px' height='15px' title='Geräte ausleihen' alt='Geräte ausleihen'></a> ";
					} else {
						echo "<img src='../parts/pict/1281368175_limited-edition_gray.png' width='16px' height='15px' title='bereits verliehen' alt='bereits verliehen'> ";
					}	
				}
				echo "</div>";											 				
				echo "<div class='content_row_toggle_head_1'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
				echo "<div class='content_row_toggle_body_1'>";
				echo $item['IV_HERSTELLER']."</div>\n";
				echo "</div><!--content_row_a_1-->\n";
			}
		}
	} else {
		// kategorie
		if ( $db_result_kat ) {
			foreach ( $db_result_kat as $item ) {
				$z += 1;
				// Listcolors					
				if ( $z % 2 != 0 ) { 
					echo "<div class='content_row_a_1'>";	
				} else { 
					echo "<div class='content_row_b_1'>";
				}
				echo "<div class='content_column_8_a'>".$item['IV_KAT_DESC']."</div>";				
				// pruefen ob mind ein oder alle objekte der kat verliehen sind
				$iv_verliehen = "no";
				$iv_items_in_kat = 0;
				$iv_verliehen_z = 0;
				$iv_objects= array();
				$db_result = db_query_list_items_1("IV_ID, IV_KATEGORIE_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_VERLIEHEN", "IV_MAIN", "IV_KATEGORIE_ID=".$item['IV_KAT_ID']);
				if ( $db_result ) {
					foreach ( $db_result as $item_iv ) {
						$iv_items_in_kat += 1;
						if ( rtrim($item_iv['IV_VERLIEHEN']) == 'T') { 
							$iv_verliehen_z += 1;
							array_push($iv_objects, "<div><strike>".substr($item_iv['IV_OBJEKT'], 0, 40)." - ".substr($item_iv['IV_TYP'], 0, 40)."</strike></div>");
						} else {
							array_push($iv_objects, "<div>".substr($item_iv['IV_OBJEKT'], 0, 40)." - ".substr($item_iv['IV_TYP'], 0, 40)."</div>");	
						}
					}
				}// if $db_result
					
				if ( $iv_verliehen_z !=0 ) {
					// mindesten eins ist verliehen
					$iv_verliehen = "one";
					if ( $iv_items_in_kat == 	$iv_verliehen_z ) {
						// alle verliehen
						$iv_verliehen = "all";
					}
				}
															
				echo "<div class='content_column_tool_img_1'>";
				if ( $vl_id != "no" ) { 
					if ( $iv_verliehen == "no" ) {
						echo "<a href='../admin_srb_verleih/vl_detail.php?action=new_kat&amp;vl_id=".$vl_id."&amp;iv_kat_id=".$item['IV_KAT_ID']."'><img src='../parts/pict/1281368175_limited-edition.png' width='16px' height='15px' title='Kategorie ausleihen' alt='Kategorie ausleihen'></a> ";
					} elseif ( $iv_verliehen == "all" ) {
						echo "<img src='../parts/pict/1281368175_limited-edition_gray.png' width='16px' height='15px' title='bereits alles dieser Kategorie verliehen' alt='bereits alles dieser Kategorie verliehen'></a> ";
					} elseif ( $iv_verliehen == "one" ) {
						echo "<a href='../admin_srb_verleih/vl_detail.php?action=new_kat&amp;vl_id=".$vl_id."&amp;iv_kat_id=".$item['IV_KAT_ID']."'><img src='../parts/pict/1281368175_limited-edition_half.png' width='16px' height='15px' title='Verfügbares dieser Kategorie ausleihen' alt='Verfügbares dieser Kategorie ausleihen'></a> ";
					}
				}
				echo "</div>";				
				echo "<div class='content_row_toggle_head_3'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
				echo "<div class='content_row_toggle_body_3'>";
				foreach ( $iv_objects as $iv_object ) {
					echo $iv_object;
				}
				echo "</div>\n";
				echo "</div><!--content_row_a_1-->\n";
			}// while  $tbl_row_kat
		}// if $db_result_kat
			
	}// kat == no
			
			
	if ( $z == 0 ) {	
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$x = $z / 25;
		$zz = $z + 1;	
		echo "<div class='content_footer'>Gefunden: ".$z. " ::: "; 	
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			echo " >> <a href='iv_vl_find_list.php?action=".$action."&amp;vl_id=".$vl_id."&amp;condition=".rawurlencode($c_query_condition)."&amp;find_limit_skip=".$z."&amp;find_string=".$message_find_string."&amp;find_kat=".$find_kategorie."'>Weitere Anzeige ab Datensatz ".$zz."</a>";
		}							
		echo "</div>";	
	}
} // user_rights

	echo "</div><!--content-->";
	echo "</div><!--column_right-->";
?>
</body>
</html>