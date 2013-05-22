<?php
/** 
* Verleih Details
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
if ( isset( $_POST['message'] ) ) { 
	$message = $_POST['message'];
}
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
	if ( isset( $_GET['vl_id'] ) ) {	
		$id = $_GET['vl_id'];
	}
	if ( isset( $_POST['vl_id'] ) ) {
		$id = $_POST['vl_id'];
	}		
		
	// action switchen
	if ( $id !="" ) { 
		switch ( $action ) {
		case "display":		
			$message .= "Verleih-Details anzeigen. ";
			$c_query_condition = "VL_ID = ".$id;
			break;

		case "check_delete":		
			$message .= "Verleih zum Löschen prüfen! ";
			header("Location: vl_detail.php?action=delete&vl_id=".$id."&kill_possible=True");
			break;

		case "delete":	
			$message .= "Verleih wirklich löschen? ";
			$c_query_condition = "VL_ID = ".$id;
			if ( isset( $_GET['kill_possible'] ) ) {	
				$kill_possible = $_GET['kill_possible'];
			}
			break;

		case "kill":		
			$message .= "Verleih löschen. ";
			// pruefen ob bestaetigung passt
			$c_kill = db_query_load_item("USER_SECURITY", 0);

			if ( $_POST['form_kill_code'] == trim($c_kill)) {
				// items holen					
				$db_result = db_query_list_items_1("VL_ITEM_IV_ID, VL_ITEM_ID", "VL_ITEMS", "VL_MAIN_ID = ".$id);
				// items zurueckbuchen, in iv_main und vl_items, wenn welche da sind
				if ($db_result) {					
					foreach ( $db_result as $item_id ) {	
						db_query_update_item_a("IV_MAIN", "IV_VERLIEHEN = 'F'", "IV_ID =".$item_id['VL_ITEM_IV_ID']);
						$c_condition = "VL_ITEM_IV_ID = ".$item_id['VL_ITEM_IV_ID']." AND VL_MAIN_ID = ".$id;	
						db_query_update_item_a("VL_ITEMS", "VL_ITEM_IV_BACK = 'T'", $c_condition);	
					}
						
					// items aus verleih loeschen
					foreach ( $db_result as $item_id ) {	
						db_query_delete_item("VL_ITEMS", "VL_ITEM_ID", $item_id['VL_ITEM_ID']);
					}
				}
				$_ok = db_query_delete_item("VL_MAIN", "VL_ID", $id);
				if ( $_ok == "true" ) {
					$message = "Verleih gelöscht!";
					$action_ok = "no";	
				} else { 
					$message .= "Löschen fehlgeschlagen";
					$c_query_condition = "VL_ID = ".$id;
				}
			} else { 
				$message .= "Keine Löschberechtigung!";
				$c_query_condition = "VL_ID = ".$id;
			}	
			break;
										
		case "delete_objekt":
			$delete_ok = db_query_delete_item("VL_ITEMS", "VL_ITEM_ID", $_GET['vl_item_id']);
			if ( $delete_ok ) {
				// items zurueckbuchen, in iv_main und vl_items 
				db_query_update_item_a("IV_MAIN", "IV_VERLIEHEN = 'F'", "IV_ID=".$_GET['vl_item_iv_id']);	
				$c_condition = "VL_ITEM_IV_ID = ".$_GET['vl_item_iv_id']." AND VL_MAIN_ID = ".$id;	
				db_query_update_item_a("VL_ITEMS", "VL_ITEM_IV_BACK = 'T'", $c_condition);
			}
			header("Location: vl_detail.php?action=display&vl_id=".$id);
			break;
				
		case "delete_kat":
			// items der kat holen					
			$db_result = db_query_list_items_1("VL_ITEM_IV_ID", "VL_ITEMS", "VL_ITEM_IV_KAT_ID=".$_GET['vl_kat_id']." AND VL_MAIN_ID = ".$_GET['vl_id']);
			// objekte auf nicht verliehen setzen
			foreach ( $db_result as $item_id ) {	
				db_query_update_item_a("IV_MAIN", "IV_VERLIEHEN = 'F'", "IV_ID =".$item_id['VL_ITEM_IV_ID']);
				$c_condition = "VL_ITEM_IV_ID = ".$item_id['VL_ITEM_IV_ID']." AND VL_MAIN_ID = ".$id;	
				db_query_update_item_a("VL_ITEMS", "VL_ITEM_IV_BACK = 'T'", $c_condition);				
		    }
			// items der kat aus verleih loeschen
			db_query_delete_item("VL_ITEMS", "VL_ITEM_IV_KAT_ID", $_GET['vl_kat_id']." AND VL_MAIN_ID = ".$_GET['vl_id']);    			
			header("Location: vl_detail.php?action=display&vl_id=".$id);
			break;

		case "new_objekt":
			$insert_ok = false;
			// id holen
			$main_id = db_generator_main_id_load_value();
			$a_values = array($main_id, $id, $_GET['iv_id']);	
			$insert_ok = db_query_add_item_b("VL_ITEMS", "VL_ITEM_ID, VL_MAIN_ID, VL_ITEM_IV_ID", "?,?,?", $a_values);
			// verleih in inventar eintragen		    		
			if ( $insert_ok ) { 
				db_query_update_item("IV_MAIN", "IV_ID", $_GET['iv_id'], "IV_VERLIEHEN = 'T'");	
			}
			header("Location: vl_detail.php?action=display&vl_id=".$id);				
			break;
									
		case "new_kat":
			// fields
			$tbl_fields = "VL_ITEM_ID, VL_MAIN_ID, VL_ITEM_IV_KAT_ID, VL_ITEM_IV_ID";
			$db_result = db_query_list_items_1("IV_ID, IV_KATEGORIE_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_VERLIEHEN", "IV_MAIN", "IV_KATEGORIE_ID=".$_GET['iv_kat_id']." AND IV_VERLIEHEN = 'F'");
			foreach ( $db_result as $item_id ) {	
				// check or load values
				$main_id = db_generator_main_id_load_value();
				$tbl_field_values =  "'".$main_id ."', '".$id."', '".$_GET['iv_kat_id']."', '". $item_id['IV_ID']."'" ;
				$insert_ok = false;
		    	$insert_ok = db_query_add_item("VL_ITEMS", $tbl_fields, $tbl_field_values);
		    	// verleih in inventar eintragen		    		
		    	if ( $insert_ok ) { 
		    		db_query_update_item("IV_MAIN", "IV_ID", $item_id['IV_ID'], "IV_VERLIEHEN = 'T'");
		    	}
		    }
			header("Location: vl_detail.php?action=display&vl_id=".$id);				
			break;
					
		case "come_back_all":
			// items holen					
			$db_result = db_query_list_items_1("VL_ITEM_IV_ID", "VL_ITEMS", "VL_MAIN_ID = ".$_GET['vl_id']);
			// items zurueckbuchen, in iv_main und vl_items
			foreach ( $db_result as $item_id ) {	
				db_query_update_item_a("IV_MAIN", "IV_VERLIEHEN = 'F'", "IV_ID = ".$item_id['VL_ITEM_IV_ID']);						
				$c_condition = "VL_ITEM_IV_ID = ".$item_id['VL_ITEM_IV_ID']." AND VL_MAIN_ID = ".$id;
				db_query_update_item_a("VL_ITEMS", "VL_ITEM_IV_BACK = 'T'", $c_condition);										
			}
			db_query_update_item_a("VL_MAIN", "VL_DATUM_END = '".date("Y-m-d")."'", "VL_ID=".$_GET['vl_id']);
			header("Location: vl_detail.php?action=display&vl_id=".$id);
			break;

		case "come_back_kat":
			// items holen					
			$db_result = db_query_list_items_1("VL_ITEM_IV_ID", "VL_ITEMS", "VL_MAIN_ID = ".$_GET['vl_id']." AND VL_ITEM_IV_KAT_ID ='".$_GET['vl_item_iv_kat_id']."'");
			// items zurueckbuchen, in iv_main und vl_items
			foreach ( $db_result as $item_id ) {	
				db_query_update_item_a("IV_MAIN", "IV_VERLIEHEN = 'F'", "IV_ID = ".$item_id['VL_ITEM_IV_ID']);	
				$c_condition = "VL_ITEM_IV_ID = ".$item_id['VL_ITEM_IV_ID']." AND VL_MAIN_ID = ".$id;	
				db_query_update_item_a("VL_ITEMS", "VL_ITEM_IV_BACK = 'T'", $c_condition);				
			}
			header("Location: vl_detail.php?action=display&vl_id=".$id);
			break;
				
		case "come_back_item":
			// items zurueckbuchen, in iv_main und vl_items
			db_query_update_item_a("IV_MAIN", "IV_VERLIEHEN = 'F'", "IV_ID = ".$_GET['vl_item_iv_id']);
			$c_condition = "VL_ITEM_IV_ID = ".$_GET['vl_item_iv_id']." AND VL_MAIN_ID = ".$id;	
			db_query_update_item_a("VL_ITEMS", "VL_ITEM_IV_BACK = 'T'", $c_condition);
			header("Location: vl_detail.php?action=display&vl_id=".$id);
			break;	
								
			//endswitch;
		}
	}
} else {
	$message .= "Keine Anweisung. Nichts zu tun..... "; 
}
	
// Alles ok, Daten holen
if ( $action_ok == "yes" ) {
	$tbl_row_vl = db_query_display_item_1("VL_MAIN", "VL_ID = " .$id);
	if ( !$tbl_row_vl ) { 
		$message .= "Fehler bei Abfrage Verleih!"; 
		$action_ok = "no";
	} else {
		$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$tbl_row_vl->VL_AD_ID);
		if ( !$tbl_row_ad ) { 
			$message .= "Fehler bei Abfrage Adresse!"; 
			$action_ok = "no";
		} else {
			$c_condition_items = 	"VL_MAIN_ID = " .$tbl_row_vl->VL_ID." AND VL_ITEM_IV_KAT_ID = '0'";		
			$db_result_vl_items =  db_query_vl_iv_list_item_items($c_condition_items);
			$c_condition_kats = "VL_MAIN_ID = ".$tbl_row_vl->VL_ID." AND VL_ITEM_IV_KAT_ID <> '0' ORDER BY VL_ITEM_IV_KAT_ID";				
			$db_result_vl_kats =  db_query_vl_iv_list_kat_items($c_condition_kats);
		}
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Verleih</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");    </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
	
	<script type="text/javascript">
	function chk_delete () {
		return confirm("Objekt oder Kategorie wirklich aus dieser Ausleihe entfernen?");
	}
	function chk_back () {
		return confirm("Objekt oder Kategorie wirklich zurückbuchen?");
	}
	</script>
	
	
</head>
<body>
<div class="column_right">
<div class="head_item_right">	<?php echo $message; ?>	</div>
<div class="content" id="jq_slide_by_click">
<?php 
if ( $action_ok == "no" ) { 
	return;
}
if ( !$tbl_row_vl ) { 
	echo "Fehler bei Abfrage Verleih!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) {
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Verleih an</div>";
	echo "<div class='content_column_2'>" .$tbl_row_ad->AD_VORNAME." " .$tbl_row_ad->AD_NAME.", ".$tbl_row_ad->AD_ORT. "</div>";
	echo "</div>\n";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Verleih von/ bis</div>";
	echo "<div class='content_column_3'>" .get_date_format_deutsch($tbl_row_vl->VL_DATUM_START). "</div>";
	echo "<div class='content_column_3'>" .get_date_format_deutsch($tbl_row_vl->VL_DATUM_END). "</div>";
	echo "</div>\n";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Projekt</div>";
	echo "<div class='content_column_2'>" .$tbl_row_vl->VL_PROJEKT. "</div>";
	echo "</div>\n";
	
	echo "<div class='content_row_b_3'>";
	echo "<div class='content_column_1'>Bemerkungen</div>";
	echo "<textarea class='textarea_1' name='vl_text' rows=80 cols=10>";
	echo $tbl_row_vl->VL_TEXT;
	echo "</textarea>";
	echo "</div><br>\n";
		
	if ( $action == "delete" ) { 
		// wird in anderen Modulen verwendet
		if ( $kill_possible = "True" ) {
			// ist frei
			echo "<script>";
			echo '$( "#dialog-form" ).dialog( "open" )';
			echo "</script>";
			echo "<div id='dialog-form' title='Löschen dieser Ausleihe bestätigen'>";
			echo "<p>Diese Ausleihe kann erst durch Eingabe des Berechtigungscodes gelöscht werden!</p>";
			echo "<form action='vl_detail.php' method='POST' enctype='application/x-www-form-urlencoded'>";
			echo "<input type='hidden' name='action' value='kill'>";
			echo "<input type='hidden' name='vl_id' value=".$tbl_row_vl->VL_ID.">";	
			echo "<input type='password' name='form_kill_code' value=''>"; 
			echo "<input type='submit' value='Jetzt löschen'></form></div>";
		}
	}
			
	if ( $message == "Verleih löschen. Keine Löschberechtigung!" ) { 
		echo "<script>";
		echo '$( "#dialog-form" ).dialog( "open" )';
		echo "</script>";
		echo "<div id='dialog-form' title='Löschen dieser Ausleihe fehlgeschlagen'>";
		echo "<p>Der Berechtigungscode zu Löschen dieser Ausleihe ist nicht korrekt!</p></div>";
	}
						
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";	
	echo "<li><a href='vl_edit.php?action=edit&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;ad_id=".$tbl_row_vl->VL_AD_ID."'>Bearbeiten</a> ";
	if ( $action == "display" ) {
		if ( $tbl_row_vl->VL_ID != "0" ) {
			// verleih mit nr 0 darf nicht geloescht werden, ist vorlage fuer neue 
			echo "<li><a href='vl_detail.php?action=check_delete&amp;vl_id=".$tbl_row_vl->VL_ID."' title='Verleih gesamt löschen'>Löschen</a> ";
		}
	}

	echo "<li><a href='vl_reg_form.php?action=print&amp;vl_id=".$tbl_row_vl->VL_ID."' title='Verleih drucken' target='_blank'>Druck</a> ";
	//echo "<li><a href=javascript:http://srb-speicher/admin_srb_verleih/vl_reg_form.php?action=print&amp;vl_id=".$tbl_row_vl->VL_ID.".print()>Druck</a> ";
	echo "<li><a href='../admin_srb_inventar/iv_vl_find.php?vl_id=".$tbl_row_vl->VL_ID."&amp;ad_id=".$tbl_row_vl->VL_AD_ID."'>Objekt ausleihen</a> ";
	echo "<li><a href='../admin_srb_inventar/iv_vl_find.php?vl_id=".$tbl_row_vl->VL_ID."&amp;ad_id=".$tbl_row_vl->VL_AD_ID."&amp;vl_kat=yes'>Kategorie ausleihen</a> ";
	if ($db_result_vl_kats || $db_result_vl_items) {	
		echo "<li><a href='vl_detail.php?action=come_back_all&amp;vl_id=".$tbl_row_vl->VL_ID."' title='Alle Objekte zurückbuchen' >Alles zurückbuchen</a>";
	}			
	echo "</ul>\n</div><!--menu_bottom_row-->";  
	echo "<br>\n";			
			
	// Kats listen und deren Objekte listen
	$kat_id_a = "x"; // zum Umschalten auf neue Kat
	$z =0;
	if ($db_result_vl_kats) { 
		// wenn nix gefunden, schleife nicht erst durchgehen
		foreach ($db_result_vl_kats as $item ) {	
			$z +=1;				
			$kat_id_b = rtrim($item['VL_ITEM_IV_KAT_ID']);
			if ( $kat_id_a != $kat_id_b ) {
				// Eintrag fuer neue Kat starten     							
				if ( $z != 1 ) {
					// divs abschliessen					
					echo "</div><!--content_row_body_a_2-->";
					echo "</div><!--content_row_a_1-->\n";
				}	

				// Listcolors	
				if ( $z % 2 != 0 ) { 	
					echo "<div class='content_row_a_1'>";	
				} else { 
					echo "<div class='content_row_b_1'>";	
				}		      	
				// Kats listen
				echo "<div class='content_column_1'>".get_date_format_deutsch($item['VL_ITEM_DATUM_START'])."</div>";
				echo "<div class='content_column_7'>";
				echo $item['IV_KAT_DESC'];						
				$kat_id_a = rtrim($item['VL_ITEM_IV_KAT_ID']);
				echo "</div>\n";	

				echo "<div class='content_column_tool_img_1'>";
				echo "<a href='vl_detail.php?action=delete_kat&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_kat_id=".rtrim($item['VL_ITEM_IV_KAT_ID'])."' onClick='return chk_delete();'><img src='../parts/pict/remove-ticket.png' width='16px' height='15px' title='Alle Objekte der Kategorie löschen' alt='Alle Objekte der Kategorie löschen'></a> ";
				if ( rtrim($item['VL_ITEM_IV_BACK']) =="F" ) {
					echo "<a href='vl_detail.php?action=come_back_kat&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_item_iv_kat_id=".rtrim($item['VL_ITEM_IV_KAT_ID'])."' onClick='return chk_back();'><img src='../parts/pict/clock__arrow.png' width='16px' height='15px' title='Diese Kategorie zurückbuchen' alt='Diese Kategorie zurückbuchen'></a>";						
				} else {
					echo "<img src='../parts/pict/clock-history-frame.png' width='16px' height='15px' title='Diese Kategorie ist zurückgebucht' alt='Diese Kategorie ist zurückgebucht'></a>";
				}												
				echo "</div><!--content_column_tool_img_1-->";
						
				// Erstes Objekt der Kat listen
				echo "<div class='content_row_toggle_head_1'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>";
				echo "<div class='content_row_toggle_body_1'>";
					 
				echo "<div class='content_column_8'>".$item['IV_OBJEKT']." - ".$item['IV_TYP']."</div>";
				echo "<div class='content_column_tool_img_1'>";
				echo "<a href='vl_detail.php?action=delete_objekt&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_item_id=".$item['VL_ITEM_ID']."&amp;vl_item_iv_id=".$item['VL_ITEM_IV_ID']."' onClick='return chk_delete();'><img src='../parts/pict/remove-ticket.png' width='16px' height='15px' title='Objekt löschen' alt='Objekt löschen'></a> ";
				if ( rtrim($item['VL_ITEM_IV_BACK']) =="F" ) {
					echo "<a href='vl_detail.php?action=come_back_item&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_item_iv_id=".$item['VL_ITEM_IV_ID']."' onClick='return chk_back();'><img src='../parts/pict/clock__arrow.png' width='16px' height='15px' title='Dieses Objekt zurückbuchen' alt='Dieses Objekt zurückbuchen'></a>";						
				} else {
					echo "<img src='../parts/pict/clock-history-frame.png' width='16px' height='15px' title='Dieses Objekt ist zurückgebucht' alt='Dieses Objekt ist zurückgebucht'></a>";
				}
				echo "</div><!--content_column_tool_img_1--><br>";
						
			} else { 
				// Weitere Objekte der begonnenen Kat listen
				echo "<div class='content_column_8'>".$item['IV_OBJEKT']." - ".$item['IV_TYP']."</div>";
				echo "<div class='content_column_tool_img_1'>";
				echo "<a href='vl_detail.php?action=delete_objekt&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_item_id=".$item['VL_ITEM_ID']."&amp;vl_item_iv_id=".$item['VL_ITEM_IV_ID']."' onClick='return chk_delete();'><img src='../parts/pict/remove-ticket.png' width='16px' height='15px' title='Objekt löschen' alt='Objekt löschen'></a> ";							
				if ( rtrim($item['VL_ITEM_IV_BACK']) =="F" ) {
					echo "<a href='vl_detail.php?action=come_back_item&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_item_iv_id=".$item['VL_ITEM_IV_ID']."' onClick='return chk_back();'><img src='../parts/pict/clock__arrow.png' width='16px' height='15px' title='Dieses Objekt zurückbuchen' alt='Dieses Objekt zurückbuchen'></a>";						
				} else {
					echo "<img src='../parts/pict/clock-history-frame.png' width='16px' height='15px' title='Dieses Objekt ist zurückgebucht' alt='Dieses Objekt ist zurückgebucht'></a>";
				}
				echo "</div><!--content_column_tool_img_1--><br>";	
			}	// if	  
		}	// for
	} // if 
				
	if ( $z != 0 ) {					
		echo "</div><!--content_row_body_a_2-->";
		echo "</div><!--content_row_a_1-->\n";
	}	
			
	// Einzeln verliehene Objekte listen (die nicht zu einer Kat gehoeren)
	$za =0;
	if ($db_result_vl_items) { // wenn nix gefunden, schelife nicht erst durchgehen
		foreach ($db_result_vl_items as $item ) {
	     	$za +=1;
			// Listcolors					
			if ( $za % 2 != 0 ) { 	
				echo "<div class='content_row_a_1'>";	
			} else { 
				echo "<div class='content_row_b_1'>";	
			}				
					
			// IV holen 
			$tbl_row_iv = db_query_display_item_1("IV_MAIN", "IV_ID = ".$item['VL_ITEM_IV_ID']);
			if ( !$tbl_row_iv ) { 
				$iv_item = "Sorry, Inventar-Objekt nicht gefunden";				
			} else { 
				$iv_item = 	$tbl_row_iv->IV_OBJEKT." - ".substr($tbl_row_iv->IV_TYP, 0, 12);
			}
						 					
			echo "<div class='content_column_1'>".get_date_format_deutsch($item['VL_ITEM_DATUM_START'])."</div>";
			echo "<div class='content_column_7'>";
			echo $iv_item."</div> ";
			echo "<div class='content_column_tool_img_1'>";
			echo "<a href='vl_detail.php?action=delete_objekt&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_item_id=".$item['VL_ITEM_ID']."&amp;vl_item_iv_id=".$item['VL_ITEM_IV_ID']."' onClick='return chk_delete();'><img src='../parts/pict/remove-ticket.png' width='16px' height='15px' title='Objekt löschen' alt='Objekt löschen'></a> ";
			if ( rtrim($item['VL_ITEM_IV_BACK']) =="F" ) {
				echo "<a href='vl_detail.php?action=come_back_item&amp;vl_id=".$tbl_row_vl->VL_ID."&amp;vl_item_iv_id=".$item['VL_ITEM_IV_ID']."' onClick='return chk_back();'><img src='../parts/pict/clock__arrow.png' width='16px' height='15px' title='Dieses Objekt zurückbuchen' alt='Dieses Objekt zurückbuchen'></a>";						
			} else {
				echo "<img src='../parts/pict/clock-history-frame.png' width='16px' height='15px' title='Dieses Objekt ist zurückgebucht' alt='Dieses Objekt ist zurückgebucht'></a>";
			}										
			echo "</div><!--content_column_tool_img_1-->";
			echo "</div><!--content_row_a_1-->";	
		}						
	}
	if ( $z == 0 AND $za == 0  ) { 
		echo "<div class='content_row_a_1'>Keine Verleih-Positionen gefunden...</div>";
	}
	echo "<div class='space_line'> </div>";
} // user_rights	
?>
</div>
</div>
</body>
</html>
