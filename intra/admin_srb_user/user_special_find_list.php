<?php
/** 
* Einstellungen listen
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

// check action
$action_ok = false;

if ( isset($_GET['action']) ) {	
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action']) ) { 
	$action = $_POST['action'];	
	$action_ok = true;
}

if ( $action_ok == true ) {	
	switch ( $action ) {
	case "find": 
		$message .= "Gefundene Spezial-Einstellungen anzeigen. "; 
		break;

	case "list": 
		$message .= "Spezial-Einstellungen auflisten. "; 
		break;
	//endswitch;
	}

	// Felder pruefen, in einem Feld muss was sein, sonst kann find-form nicht abgeschickt werden, 
	// also hier nur pruefen in welchem feld was ist
	if ( $action == "find" ) {
		if ( isset($_POST['sp_desc']) ) {
			if ( $_POST['sp_desc'] !="") { 
				$c_field_message ="Bezeichnung";
				$c_field_desc = "USER_SP_DESC";
				$c_field_value = strtoupper($_POST['sp_desc']);
			}
		}

		if ( isset($_POST['sp_special']) ) {
			if ( $_POST['sp_special'] !="") { 
				$c_field_message ="Bezeichnung intern";
				$c_field_desc = "USER_SP_SPECIAL";
				$c_field_value = strtoupper($_POST['sp_special']);
			}
		}
	}// Ende if action== find

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
				$c_query_condition = "upper(".$c_field_desc.") LIKE '".$c_field_value."%' ";	
				$message_find_string = $c_field_message. " beginnt mit " .$c_field_value ;
			} elseif ( $find_option == "in" ) {
				$c_query_condition = "upper(".$c_field_desc.") LIKE '%".$c_field_value."%' ";
				$message_find_string = $c_field_message. " enthält " .$c_field_value ;
			} elseif ( $find_option == "exact" ) {
				$c_query_condition = "upper(".$c_field_desc.") = '".$c_field_value."' ";
				$message_find_string = $c_field_message. " ist exakt " .$c_field_value ;
			}
			break;
			
		case "list": 
		    $c_query_condition = "upper( USER_SP_DESC ) > 'A' ORDER BY USER_SP_DESC";
			$message_find_string = "USER_SP_DESC ist größer als A";
			break;
			//endswitch;
		}
	} else {
		$message = "Keine Suchbedingung! Kann nichts tun... "; 
	}

	$db_result = db_query_list_items_1("USER_SP_ID, USER_SP_DESC, USER_SP_SPECIAL ", "USER_SPECIALS", $c_query_condition);
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Spezial</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css"); </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
</head>
<body>
 
<div class="main">
<?php 
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
echo "<div class='head_item_left'>";
echo "Einstellungen";
echo "</div>";
require "parts/admin_menu.inc";
user_display();
echo "</div>";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo $message; 
echo "</div>";
echo "<div class='content'>";
if ( $action_ok == false ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "A");
if ( $user_rights == "yes" ) { 	
	echo "<p>Einstellungen der Bedingung: " .$message_find_string. "</p>";
	$z = 0;
	if ( $db_result ) {
		foreach ($db_result as $item ) {	
	  		$z +=1;
			if ( $z % 2 != 0 ) { 
				echo "<div class='content_row_a_4'>";
			} else { 
				echo "<div class='content_row_b_4'>";
			}
			echo "<a href='user_special_detail.php?action=display&amp;special_id=".$item['USER_SP_ID']."'>".$item['USER_SP_DESC']. "</a>";
			echo "</div><br>";
		}
	}
	if ( $z == 0 ) { 
		echo "Keine Übereinstimmung gefunden...";
	} else {
		echo "<div class='content_footer'> Gefunden: ".$z. " ::: </div>"; ;
	}
}// user_rights
?>
</div>
</div>
</div>
</body>
</html>