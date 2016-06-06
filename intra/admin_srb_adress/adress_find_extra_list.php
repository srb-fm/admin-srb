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
$display_option = "normal";
$find_limit_skip = "no";
$sg_id = "no";
$only_user = false;
$sg_author = "";	
$sg_editor = "";
	
// info ausgabebegrenzung auf 25 datensaetze:
// der abfrage_condition wird das limit von 25 datensaetzen zugefuegt: ausgabebegrenzung 1
// fuer den link zu den naechsten saetzen wird die skip-anzahl in der url zugrechnet: 
// (ausgabebegrenzung 2) und dann in die abfrage uebernommen (// ausgabebegrenzung 1)
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
	$message .= "Keine Anweisung. Nichts zu tun..... "; 
}
if ( isset($_GET['find_limit_skip'])) {
	$find_limit_skip = $_GET['find_limit_skip'];
}

if ( isset($_POST['sg_id']) ) {
	$sg_id = $_POST['sg_id'];
}

if ( isset($_POST['sg_cont_id']) ) {
		$sg_cont_id = $_POST['sg_cont_id'];
}
	
// change author
if ( isset($_POST['sg_author']) ) {
	$only_user = true;
	$sg_author = $_POST['sg_author'];
}
// change editor
if ( isset($_POST['sg_editor']) ) {
	$only_user = true;
	$sg_editor = $_POST['sg_editor'];
}

// check id
if ( ! filter_var( $sg_id, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000 )) ) ) {
	$sg_id = "no";
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
	if ( $_POST['ad_vorname'] != "") { 
		$c_field_message ="Vorname";
		$c_field_desc = "AD_VORNAME";
		$c_field_value = $_POST['ad_vorname']; 
	}
}

if ( isset($_POST['ad_firma']) ) {
	if ( $_POST['ad_firma'] !="") { 
		$c_field_message ="Firma";
		$c_field_desc = "AD_FIRMA";
		$display_firma = "yes";
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

if ( $find_option_ok == true ) {
	switch ( $action ) {
	case "find": 
		if ( $find_option == "begin" ) {
			if ( $only_user ) {
				$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '".utf8_decode($c_field_value)."%' collate de_de) AND (AD_USER_OK_HF = 'T' OR AD_AUTOR = 'T') ORDER BY ".$c_field_desc;
				$message_find_string = $c_field_message. " beginnt mit " .$c_field_value. "/ Nur Macher oder Autor";
			} else {
				$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '".utf8_decode($c_field_value)."%' collate de_de) ORDER BY ".$c_field_desc;
				$message_find_string = $c_field_message. " beginnt mit " .$c_field_value ;
			}
		} elseif ( $find_option == "in" ) {
			if ( $only_user ) {
				$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".utf8_decode($c_field_value)."%' collate de_de) AND (AD_USER_OK_HF = 'T' OR AD_AUTOR = 'T') ORDER BY AD_NAME";
				$message_find_string = $c_field_message. " enthält " .$c_field_value. "/ Nur Macher oder Autor";
			} else {
				$c_query_condition = "upper(".$c_field_desc.") LIKE UPPER(_iso8859_1 '%".utf8_decode($c_field_value)."%' collate de_de) ORDER BY AD_NAME";
				$message_find_string = $c_field_message. " enthält " .$c_field_value ;
			}
		} elseif ( $find_option == "exact" ) {
			if ( $only_user ) {
				//$c_query_condition = $c_field_desc." = '".$c_field_value."' AND AD_USER_OK_HF = 'T'";
				$c_query_condition = $c_field_desc." = '".$c_field_value."' AND (AD_USER_OK_HF = 'T' OR AD_AUTOR = 'T')";
				$message_find_string = $c_field_message. " ist exakt " .$c_field_value. "/ Nur Macher oder Autor";
			} else {
				$c_query_condition = $c_field_desc." = '".$c_field_value."' ";
				$message_find_string = $c_field_message. " ist exakt " .$c_field_value ;
			}
		}
		break;
		//endswitch;						
	}
} else { 
	//$find_option_ok == true
	$message .= "Keine Suchbedingung! Kann nichts tun... "; 
}

// ausgabebegrenzung 1
if ( 	$find_limit_skip == "no" ) {		
	$db_result = db_query_list_items_limit_1(
		"AD_ID, AD_NAME, AD_VORNAME, AD_FIRMA, AD_ORT, AD_EMAIL, AD_TEL_1, AD_TEL_2, 
		AD_DATUM_GEBURT, AD_USER_OK_HF ", "AD_MAIN", 
		$c_query_condition, "FIRST 25");
	$z = 0;
} else {
	$c_limit = "FIRST 25 SKIP ".$find_limit_skip;			
	$db_result = db_query_list_items_limit_1("AD_ID, AD_NAME, AD_VORNAME, 
		AD_FIRMA, AD_ORT, AD_EMAIL, AD_TEL_1, AD_TEL_2, AD_DATUM_GEBURT, 
		AD_USER_OK_HF ", "AD_MAIN", $c_query_condition, $c_limit);
	$z = $find_limit_skip;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Adresse</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_3.css");   </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
</head>
<body>
 
<div class="main">
<?php	
echo "<div class='column_right_1'>";
echo "<div class='head_item_right'>";
echo $message_find_string."\n";
echo "</div>";
echo "<div class='content'>";	
 
if ( $action_ok == false ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	 
	if ( $db_result ) {
		foreach ( $db_result as $item ) {	
			$z +=1;
			if ( $z % 2 != 0 ) { 
				echo "<div class='content_row_a_1'>";	
			} else { 
				echo "<div class='content_row_b_1'>";
			}
			echo "<div class='content_column_8_a'>";		
			echo $item['AD_NAME']. ", " . $item['AD_VORNAME']. ", " . $item['AD_ORT'];
			echo "</div>";
			echo "<div class='content_column_tool_img_1'>";
					
			// directlinks for shows
			// set focus on toolbar-pict of first item
			if ( $z == 1 ) {				// change author 
				if ( $sg_author == "new" ) {
					echo "<a href='../admin_srb_sendung_hf/sg_hf_edit.php?action=edit&amp;sg_id=".$sg_id."&amp;ad_id=".$item['AD_ID']."' ><img id='first_element_of_toolbar' src='../parts/pict/1279544355_user-red.png' width='16px' height='16px' title='Sendeverantwortlichen ändern' alt='Sendeverantwortlichen ändern'></a>";					
				}
				// change editor
				if ( $sg_editor == "new" ) {
					echo "<a href='../admin_srb_sendung_hf/sg_hf_edit.php?action=editor_new&amp;sg_id=".$sg_id."&amp;ad_id=".$item['AD_ID']."&amp;sg_cont_id=".$sg_cont_id."&amp;sg_editor=new'><img id='first_element_of_toolbar' src='../parts/pict/1279544355_user-red.png' width='16px' height='16px' title='Redaktuer ändern' alt='Redakteur ändern'></a>";
				}
			} else {
				// change author 
				if ( $sg_author == "new" ) {
					echo "<a href='../admin_srb_sendung_hf/sg_hf_edit.php?action=edit&amp;sg_id=".$sg_id."&amp;ad_id=".$item['AD_ID']."' ><img src='../parts/pict/1279544355_user-red.png' width='16px' height='16px' title='Sendeverantwortlichen ändern' alt='Sendeverantwortlichen ändern'></a>";					
				}
				// change editor
				if ( $sg_editor == "new" ) {
					echo "<a href='../admin_srb_sendung_hf/sg_hf_edit.php?action=editor_new&amp;sg_id=".$sg_id."&amp;ad_id=".$item['AD_ID']."&amp;sg_cont_id=".$sg_cont_id."&amp;sg_editor=new'><img src='../parts/pict/1279544355_user-red.png' width='16px' height='16px' title='Redaktuer ändern' alt='Redakteur ändern'></a>";
				}
			}
			echo "</div>\n</div>\n";
		} //for
	}		
	if ( $z == 0 ) { 
		echo "Keine Übereinstimmung gefunden...";
	} else {
    	$zz = $z+1;	
    	$x = $z / 25;
		echo "<div class='content_footer'>Gefunden: ".$z. " ::: "; 	
		if ( $sg_id != "no" ) {
			if ( $sg_author == "new" ) {
				echo "Sendeverantwortlichen durch Klick auf Symbol ändern.";
			}
			if ( $sg_editor == "new" ) {
				echo "Redakteur durch Klick auf Symbol ändern.";
			}
			
		}
		// ausgabebegrenzung 2				
		if ( is_integer($x)) {
			// weiter-link nur anzeigen wenn noch weitere datensaetze da sind, 
			// bei ganze zahl ja, bei kommazahl wird nur der rest angezeigt	also nein
			switch ( $action ) {
			case "find": 				
				echo " >> <a href='adress_find_extra_list.php?action=".$action."&amp;find_option=".$find_option."&amp;field_desc=".$c_field_desc."&amp;field_value=".$c_field_value."&amp;find_limit_skip=".$z."'>Weitere Anzeige ab Datensatz ".$zz."</a>";
				break;
							
				//endswitch;
			}
		}	
	}						
			
	echo "</div>";
	echo "<div class='space_line'></div>";	
} // user_rights
echo "</div>";
echo "</div>";
?>
</div>
<script type="text/JavaScript" src="../parts/js/js_my_tools/js_my_toolbar_focus.js"></script>
</body>
</html>