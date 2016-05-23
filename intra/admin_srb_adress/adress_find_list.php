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
$action_ok = false;
$find_option_ok = false;
$display_firma = false;
$display_option = "normal";
$find_limit_skip = "first";

// info ausgabebegrenzung auf 25 datensaetze:
// der abfrage_condition wird das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den naechsten saetzen wird die skip-anzahl in der url zugrechnet: (ausgabebegrenzung 2) 
// und dann in die abfrage uebernommen (// ausgabebegrenzung 1)
// fuer die option find muss dazu feld und inhalt neu uebergeben werden ( ausgabebegrenzung 3)

// check action
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

// pruefen display, limit
if ( isset($_POST['display_firma']) ) { 
	$display_firma = true; 
}
if ( isset($_GET['find_limit_skip']) ) {
	$find_limit_skip = $_GET['find_limit_skip'];
}
	
// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
// also hier nur pruefen in welchem feld was ist

if ( isset($_POST['ad_name']) ) {
	if ( $_POST['ad_name'] !="") {
		$c_field_message ="Name";
		$c_field_desc = "AD_NAME";
		$c_field_value = $_POST['ad_name'];
	}
}

if ( isset($_POST['ad_vorname']) ) {
	if ( $_POST['ad_vorname'] !="") {
		$c_field_message ="Vorname";
		$c_field_desc = "AD_VORNAME";
		$c_field_value = $_POST['ad_vorname'];
	}
}

if ( isset($_POST['ad_firma']) ) {
	if ( $_POST['ad_firma'] !="") {
		$c_field_message ="Firma";
		$c_field_desc = "AD_FIRMA";
		$display_firma = true;
		$c_field_value = $_POST['ad_firma'];
	}
}
	
if ( isset($_POST['ad_stichwort']) ) {
	if ( $_POST['ad_stichwort'] !="") {
		$c_field_message ="Stichwort";
		$c_field_desc = "AD_STICHWORT";
		$c_field_value = $_POST['ad_stichwort'];
	}
}

if ( isset($_POST['ad_ort']) ) {
	if ( $_POST['ad_ort'] !="") {
		$c_field_message ="Ort";
		$c_field_desc = "AD_ORT";
		$c_field_value = $_POST['ad_ort'];
	}
}

if ( isset($_POST['ad_email']) ) {
	if ( $_POST['ad_email'] !="") {
		$c_field_message ="eMail";
		$c_field_desc = "AD_EMAIL";
		$c_field_value = $_POST['ad_email'];
	}
}

// ausgabebegrenzung 3			
//wenn unber limitweiterschaltung kommt find field und value per get:
if ( isset($_GET['field_desc']) ) {
	$c_field_desc = $_GET['field_desc'];
}
if ( isset($_GET['field_value']) ) {
	$c_field_value = $_GET['field_value'];
}


// check condition
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
			//$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '".$c_field_value."%' collate de_de) ORDER BY ".$c_field_desc;
			$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '".utf8_decode($c_field_value)."%' collate de_de) ORDER BY ".$c_field_desc;
			$message_find_string = $c_field_message. " beginnt mit \"" .$c_field_value. "\" sortiert nach ".$c_field_message ;
		} elseif ( $find_option == "in" ) {
			//$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".$c_field_value."%' collate de_de) ORDER BY AD_NAME";
			$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".utf8_decode($c_field_value)."%' collate de_de) ORDER BY AD_NAME";
			$message_find_string = $c_field_message. " enthält \"" .$c_field_value. "\" sortiert nach Name" ;
		} elseif ( $find_option == "exact" ) {
			$c_query_condition = $c_field_desc." = '".$c_field_value."'";
			$message_find_string = $c_field_message. " ist exakt " .$c_field_value ;
		}
		$message_find_string .= " " .$action." " .$find_option;
		break;

	case "list_adress": 
		$c_query_condition = rawurldecode($_GET['condition']);
		$display_option = "adress";		
		$message_find_string = "Adress-Liste";
		break;
				
	case "list_email": 
		$c_query_condition = rawurldecode($_GET['condition']);
		$display_option = "email";		
		$message_find_string = "eMail-Liste";
		break;
			
	case "list_telefon": 
		$c_query_condition = rawurldecode($_GET['condition']);
		$display_option = "telefon";		
		$message_find_string = "Telefon-Liste";
		break;
			
	case "list": 

		switch ( $find_option ) {
			
		case "active_user":
			$c_query_condition = "AD_USER_OK_AKTIV = 'T' ORDER BY AD_NAME";
			$message_find_string = "Aktive Macher in diesem Quartal";
			$display_option = "normal";
			break;
					
		case "user_hf":
			$c_query_condition = "AD_USER_OK_HF = 'T' ORDER BY AD_NAME";
			$message_find_string = "Alle Radiomacher";
			$display_option = "normal";
			break;
					
		case "user_tv":
			$c_query_condition = "AD_USER_OK_TV = 'T' ORDER BY AD_NAME";
			$message_find_string = "Alle Fernsehnutzer";
			$display_option = "normal";
			break;
					
		case "user_tv_and_hf":
			$c_query_condition = "AD_USER_OK_TV = 'T' AND AD_USER_OK_HF = 'T' ORDER BY AD_NAME";
			$message_find_string = "Alle gleichzeitigen Fernseh- und Radionutzer";
			$display_option = "normal";
			break;
					
		case "birthday_this_month":
			// 1900-01-01 ist Vorbelegung wenn kein Datum eingetragen, deshalb ausblenden
			$c_query_condition = "SUBSTRING( AD_DATUM_GEBURT FROM 6 FOR 2 ) = '".date('m')."' AND SUBSTRING( AD_DATUM_GEBURT FROM 1 FOR 10 ) <> '1900-01-01' ORDER BY AD_DATUM_GEBURT";
			$message_find_string = "Geburtstage diese Woche";
			$display_option = "geburt";
			break;
					
		case "birthday_next_month":
			$c_month = date('m')+1;
			if ( strlen($c_month) == 1 ) { 
				$c_month = "0".$c_month; 
			}
			$c_query_condition = "SUBSTRING( AD_DATUM_GEBURT FROM 6 FOR 2 ) = '".$c_month."' AND SUBSTRING( AD_DATUM_GEBURT FROM 1 FOR 10 ) <> '1900-01-01' ORDER BY AD_DATUM_GEBURT";
			$message_find_string = "Geburtstage diese Woche";
			$display_option = "geburt";
			break;
		}
			//	endswitch;
		break;
		//endswitch;
	}								
		
} else { 
	//$find_option_ok == true
	$message = "Keine Suchbedingung! Kann nichts tun... "; 
}

// ausgabebegrenzung 1
if ( 	$find_limit_skip == "first" ) {
	// in der regel der erste durchlauf		
	$db_result = db_query_list_items_limit_1(
		"AD_ID, AD_NR, AD_NAME, AD_VORNAME, AD_FIRMA, AD_STRASSE, AD_PLZ, AD_ORT, 
		AD_EMAIL, AD_TEL_1, AD_TEL_2, AD_DATUM_GEBURT, AD_USER_OK_HF, AD_AUTOR ", 
		"AD_MAIN", $c_query_condition, "FIRST 25");
	$z = 0;
} elseif ( 	$find_limit_skip == "no" ) {
	// fuer spezielle listen und export 			
	$db_result = db_query_list_items_1(
		"AD_ID, AD_NR, AD_NAME, AD_VORNAME, AD_FIRMA, AD_STRASSE, AD_PLZ, AD_ORT, 
		AD_EMAIL, AD_TEL_1, AD_TEL_2, AD_DATUM_GEBURT, AD_USER_OK_HF, AD_AUTOR ", 
		"AD_MAIN", $c_query_condition);
	$z = 0;			
} else {
	// normales skip der vorhrigen ergebnisse
	$c_limit = "FIRST 25 SKIP ".$find_limit_skip;		
	$db_result = db_query_list_items_limit_1(
		"AD_ID, AD_NR, AD_NAME, AD_VORNAME, AD_FIRMA, AD_ORT, AD_EMAIL, AD_TEL_1, 
		AD_TEL_2, AD_DATUM_GEBURT, AD_USER_OK_HF, AD_AUTOR ", 
		"AD_MAIN", $c_query_condition, $c_limit);
	$z = $find_limit_skip;
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Adresse</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_3.css");   </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>	
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>	
</head>
<body>
 
<div class="main">
<?php 
include "../parts/site_elements/header_srb_2.inc";
include "../parts/menu/menu_srb_root_1_eb_1.inc" ;
	
echo "<div class='column_left'>";
include "parts/ad_menu.inc";	
user_display();
echo "</div> <!--class=column_left-->";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n";
echo "</div>";
include "parts/ad_toolbar.inc";
echo "<div class='content'>";	
		 
if ( $action_ok == false ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 	 
	if ( $db_result	) {	
		foreach ( $db_result as $item ) {			
			$z +=1;
			if ( $z % 2 != 0 ) { 
				echo "<div class='content_row_a_1'>";	
			} else { 
				echo "<div class='content_row_b_1'>";
			}
			echo "<div class='content_column_6_a'>";
			if ( $display_firma == true ) {
				echo "<a href='adress_detail.php?action=display&amp;ad_id=".$item['AD_ID']."' class='c_box_4'>".$item['AD_FIRMA']. ", ". $item['AD_NAME']. ", " . $item['AD_VORNAME']. ", " . $item['AD_ORT']. "</a>";
			} else {
					
				switch ( $display_option ) {
				case "geburt": 
					echo "<a href='adress_detail.php?action=display&amp;ad_id=".$item['AD_ID']."' class='c_box_4'>".get_date_format_deutsch($item['AD_DATUM_GEBURT'])." - ".$item['AD_NAME']. ", " . $item['AD_VORNAME']. ", " . $item['AD_ORT']. "</a>";
					break;
				case "normal": 
					echo "<a href='adress_detail.php?action=display&amp;ad_id=".$item['AD_ID']."' class='c_box_4'>".$item['AD_NAME']. ", " . $item['AD_VORNAME']. ", " . $item['AD_ORT']. "</a>";
					break;
				case "adress": 
					echo "<a href='adress_detail.php?action=display&amp;ad_id=".$item['AD_ID']."' class='c_box_4'>".$item['AD_NAME']. ", " . $item['AD_VORNAME']. ", " . $item['AD_STRASSE']. ", " . $item['AD_PLZ']. ", " . $item['AD_ORT']."</a>";
					break;						
				case "email": 
					echo "<a href='adress_detail.php?action=display&amp;ad_id=".$item['AD_ID']."' class='c_box_4'>".$item['AD_NAME']. ", " . $item['AD_VORNAME']. ", " . $item['AD_EMAIL']. "</a>";
					break;						
				case "telefon": 
					echo "<a href='adress_detail.php?action=display&amp;ad_id=".$item['AD_ID']."' class='c_box_4'>".$item['AD_NAME']. ", " . $item['AD_VORNAME']. ", " . $item['AD_TEL_1']. "/ ".$item['AD_TEL_2'] ."</a>";
					break;							
				//endswitch;
				}		
			} 
			echo "</div>";
			echo "<div class='content_column_tool_img_2'>";
					
			// Module direktlinks	
			if ( rtrim($item['AD_AUTOR']) == "T" ) {		
				echo "<a href='../admin_srb_sendung_hf/sg_hf_edit.php?action=new&amp;ad_id=".$item['AD_ID']."' class='c_box'><img src='../admin_srb_sendung_hf/parts/rectangle_green.png' width='16px' height='16px' title='Sendung HF anmelden' alt='Sendung HF anmelden'></a> ";
				echo "<a href='../admin_srb_sendung_hf/sg_hf_find_list.php?action=list&amp;find_option=broadcaster&amp;broadcaster_id=".$item['AD_ID']."'><img src='../parts/pict/folder_green_mydocuments.png' width='16px' height='16px' title='Sendungen dieses Autoren auflisten' alt='Sendungen dieses Autoren listen'></a> ";
			} elseif ( rtrim($item['AD_USER_OK_HF']) == "T" ) {		
				echo "<a href='../admin_srb_sendung_hf/sg_hf_edit.php?action=new&amp;ad_id=".$item['AD_ID']."' class='c_box'><img src='../admin_srb_sendung_hf/parts/rectangle_green.png' width='16px' height='16px' title='Sendung HF anmelden' alt='Sendung HF anmelden'></a> ";
				echo "<a href='../admin_srb_sendung_hf/sg_hf_find_list.php?action=list&amp;find_option=broadcaster&amp;broadcaster_id=".$item['AD_ID']."'><img src='../parts/pict/folder_green_mydocuments.png' width='16px' height='16px' title='Sendungen dieses Autoren auflisten' alt='Sendungen dieses Autoren listen'></a> ";
				echo "<a href='../admin_srb_sendung_hf/sg_hf_find_list.php?action=list&amp;find_option=editor&amp;editor_id=".$item['AD_ID']."'><img src='../parts/pict/folder_beige_mydocuments.png' width='16px' height='16px' title='Sendungen redaktionell verantwortet auflisten' alt='Sendungen redaktionell verantwortet listen'></a> ";
				echo "<a href='../admin_srb_sendung_tv/sg_tv_find_list.php?action=list&amp;find_option=broadcaster&amp;broadcaster_id=".$item['AD_NR']."'><img src='../parts/pict/folder_blue_mydocuments.png' width='16px' height='16px' title='Sendungen TV auflisten' alt='Sendungungen TV listen'></a> ";
				echo "<a href='../admin_srb_verleih/vl_edit.php?action=new&amp;ad_id=".$item['AD_ID']."&amp;vl_id=0' class='c_box'><img src='../parts/pict/1281368175_limited-edition.png' width='16px' height='15px' title='Geräte ausleihen' alt='Geräte ausleihen'></a> ";
				echo "<a href='../admin_srb_verleih/vl_find_list.php?action=list&amp;find_option=vl_adress&amp;vl_ad_id=".$item['AD_ID']."'><img src='../parts/pict/1279185345_folder_yellow_mydocuments.png' width='16px' height='16px' title='Ausleihen auflisten' alt='Verleih listen'></a> ";			
			}

			echo "</div>\n</div>\n";
		}//foreach
	}

	if ( $z == 0 ) { 
		echo "Keine Übereinstimmung gefunden...";
	} else {
		$zz = $z+1;	
		$x = $z / 25;
		echo "<div class='content_footer'>Gefunden: ".$z. " ::: "; 	
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			switch ( $action ) {
			case "find": 				
				echo " >> <a href='adress_find_list.php?action=".$action."&amp;find_option=".$find_option."&amp;field_desc=".$c_field_desc."&amp;field_value=".$c_field_value."&amp;find_limit_skip=".$z."'>Weitere Anzeige ab Datensatz ".$zz."</a>";
				break;
			case "list": 
				echo " >> <a href='adress_find_list.php?action=".$action."&amp;find_option=".$find_option."&amp;find_limit_skip=".$z."'>Weitere Anzeige ab Datensatz ".$zz."</a>";
				break;							
			//endswitch;
			}					
		}							
		echo "</div>";
		echo "<div class='space_line'></div>";	
		echo "<br><div class='menu_bottom'>";
		echo "<ul class='menu_bottom_list'><li>";
				
		// fuer anzeige der listen limit nicht beruecksichtigen
		// fuer export limit nicht beruecksichtigen
		//$zz = $z-25;
		
		if ( $_SESSION["log_rights"] <= "B" ) {
			if ( $action != "list_adress" ) {
				echo "<a href='adress_find_list.php?action=list_adress&amp;find_option=adress&amp;find_limit_skip=no&amp;condition=".rawurlencode($c_query_condition)."'> Adress (Export)-Liste anzeigen</a> ";			
			} else {
				echo "<a href='adress_export.php?action=export_adress&amp;find_option=adress&amp;find_limit_skip=no&amp;condition=".rawurlencode($c_query_condition)."'> Adress-Liste exportieren</a> ";	
			}

			if ( $action != "list_email" ) {
				echo "<a href='adress_find_list.php?action=list_email&amp;find_option=email&amp;find_limit_skip=no&amp;condition=".rawurlencode($c_query_condition)."'> eMail-Liste anzeigen</a> ";			
			} else {
				echo "<a href='adress_export.php?action=export_email&amp;find_option=email&amp;find_limit_skip=no&amp;condition=".rawurlencode($c_query_condition)."'> eMail-Liste exportieren</a> ";	
			}

			if ( $action != "list_telefon" ) {				
				echo "<a href='adress_find_list.php?action=list_telefon&amp;find_option=telefon&amp;&amp;find_limit_skip=no&amp;condition=".rawurlencode($c_query_condition)." '> Telefon-Liste anzeigen</a> ";
			} else {
				echo "<a href='adress_export.php?action=export_telefon&amp;find_option=telefon&amp;find_limit_skip=no&amp;condition=".rawurlencode($c_query_condition)."'> Telefon-Liste exportieren</a>";	
			}		
			
		} else {
			echo "<a title='Keine Berechtigung'>Adress (Export)-Liste anzeigen </a>";
			echo "<a title='Keine Berechtigung'>eMail-Liste anzeigen </a>";
			echo "<a title='Keine Berechtigung'>Telefon-Liste anzeigen </a>";
		}	
		
		echo "</ul>";		
		echo "</div>";
	}
} // user_rights

echo "</div><!--content-->";
echo "</div><!--column_right-->";
echo "</div><!--main-->";
?>
</body>
</html>