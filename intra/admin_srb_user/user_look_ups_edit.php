<?php

/** 
* LookUps bearbeiten 
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
$error_message = "";
$action_ok = "no";
$look_up_item = "no";
$look_up_desc = "";
	
if ( isset( $_GET['error_message'] ) ) { 
	$error_message = $_GET['error_message'];
}
if ( isset( $_POST['error_message'] ) ) { 
	$error_message = $_POST['error_message'];
}
		
if ( isset( $_GET['lu_item'] ) ) { 
	$look_up_item = $_GET['lu_item'];
}
if ( isset( $_POST['lu_item'] ) ) { 
	$look_up_item = $_POST['lu_item'];
}

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
	if ( isset( $_GET['id'] ) ) { 	
		$id = $_GET['id'];
	}
	if ( isset( $_POST['id'] ) ) {	
		$id = $_POST['id'];
	}
		
	if ( $id !="" ) { 
		switch ( $action ) {
		case "new":
			switch( $look_up_item ) {
			case "iv_kategorie":
				$message .=  "Inventar Kategorie eintragen";
				break;
			case "iv_eigentuemer":
				$message .=  "Inventar Eigentümer eintragen";
				break;
			case "ad_titel":
				$message .=  "Adresse Titel eintragen";
				break;			
			case "sg_genre":
				$message .=   "Sendung Genre eintragen";
				break;			
			case "sg_sprache":
				$message .=   "Sendung Sprache eintragen";
				break;			
			//endswitch;
			}
					
			$form_input_type = "add"; //form action einstellen
			$tbl_row = db_query_display_item_1("USER_LOOK_UPS_TEMPLATE", "LOOK_UP_ID = '99'");
			$result_field_id = '0';
						
			if ( isset( $_GET['id_double'] ) ) {
				// kommt von add wegen schon vorhandener id, desc weiterreichen
				$result_field_desc = 	$_GET['new_desc'];	
			} else {															
				$result_field_desc = $tbl_row->LOOK_UP_DESC;
			}
			break;

		case "add":
			$call_for_new_id = "no";
			switch( $look_up_item ) {
			case "ad_titel":
				$tbl = "AD_TITEL";
				$tbl_fields = "AD_TITEL_ID, AD_TITEL_DESC";
				$_POST['form_look_up_desc'] = substr($_POST['form_look_up_desc'], 0, 20);
				// letzte ID holen und eins drauf
				$result_field_id = db_call_last_id($tbl, "MAX(AD_TITEL_ID)", "AD_TITEL_ID>0"); 
				break;
						
			case "iv_eigentuemer":
				$tbl = "IV_EIGENTUEMER";
				$tbl_fields = "IV_EIG_ID, IV_EIG_DESC";
				// letzte ID holen und eins drauf
				$result_field_id = db_call_last_id($tbl, "MAX(IV_EIG_ID)", "IV_EIG_ID>0"); 
				break;
							
			case "iv_kategorie":
				$tbl = "IV_KATEGORIE";
				$tbl_fields = "IV_KAT_ID, IV_KAT_DESC";
				// letzte ID holen und eins drauf
				$result_field_id = db_call_last_id("IV_KATEGORIE", "MAX(IV_KAT_ID)", "IV_KAT_ID>0");
				break;
							
			case "sg_genre":
				$tbl = "SG_GENRE";
				$tbl_fields = "SG_GENRE_ID, SG_GENRE_DESC";
				$_POST['form_look_up_desc'] = substr($_POST['form_look_up_desc'], 0, 30); 
				// letzte ID holen und eins drauf
				$result_field_id = db_call_last_id($tbl, "MAX(SG_GENRE_ID)", "SG_GENRE_ID>0"); 
				break;
						
			case "sg_sprache":
				$tbl = "SG_SPEECH";
				$tbl_fields = "SG_SPEECH_ID, SG_SPEECH_DESC";
				$_POST['form_look_up_desc'] = substr($_POST['form_look_up_desc'], 0, 20); 
				// letzte ID holen und eins drauf
				$result_field_id = db_call_last_id($tbl, "MAX(SG_SPEECH_ID)", "SG_SPEECH_ID>0"); 
				break;														
				//endswitch;
			}		
			$a_values = array($result_field_id, trim($_POST['form_look_up_desc']));
	    	$insert_ok = db_query_add_item_b($tbl, $tbl_fields, "?,?", $a_values);
			header("Location: user_look_ups_detail.php?action=display&id=".$result_field_id."&lu_item=".$look_up_item);
			break;
				
		case "edit":
			switch( $look_up_item ) {
			case "ad_titel":
				$message .=   "Adresse Titel - Details bearbeiten";
				$form_input_type = "update"; //form action einstellen
				$tbl_row = db_query_display_item_1("AD_TITEL", "AD_TITEL_ID = " .$id);
				$result_field_desc = $tbl_row->AD_TITEL_DESC;
				break;
											
			case "iv_eigentuemer":
				$message .=   "Inventar Eigentuemer - Details bearbeiten";
				$form_input_type = "update"; //form action einstellen
				$tbl_row = db_query_display_item_1("IV_EIGENTUEMER", "IV_EIG_ID = " .$id);
				$result_field_desc = $tbl_row->IV_EIG_DESC;
				break;
					
			case "iv_kategorie":
				$message .=   "Inventar Kategorie - Details bearbeiten";
				$form_input_type = "update"; //form action einstellen
				$tbl_row = db_query_display_item_1("IV_KATEGORIE", "IV_KAT_ID = " .$id);
				$result_field_desc = $tbl_row->IV_KAT_DESC;
				break;
												
			case "sg_genre":
				$message .=   "Sendung Genre - Details bearbeiten";
				$form_input_type = "update"; //form action einstellen
				$tbl_row = db_query_display_item_1("SG_GENRE", "SG_GENRE_ID = " .$id);
				$result_field_desc = $tbl_row->SG_GENRE_DESC;
				break;

			case "sg_sprache":
				$message .=   "Sendung Sprache - Details bearbeiten";
				$form_input_type = "update"; //form action einstellen
				$tbl_row = db_query_display_item_1("SG_SPEECH", "SG_SPEECH_ID = " .$id);
				$result_field_desc = $tbl_row->SG_SPEECH_DESC;
				break;
				
			//endswitch;
			}				
			break;
								
		case "update":
			switch( $look_up_item ) {
			case "ad_titel":
				$tbl_fields = "AD_TITEL_DESC=?"; 
				$tbl = "AD_TITEL";
				$tbl_field_value = trim(substr($_POST['form_look_up_desc'], 0, 20));
				$tbl_condition =  "AD_TITEL_ID =".$id;
		  		break;
		  				
			case "iv_eigentuemer":
				$tbl_fields = "IV_EIG_DESC=?"; 
				$tbl = "IV_EIGENTUEMER";
				$tbl_field_value = trim($_POST['form_look_up_desc']);
				$tbl_condition =  "IV_EIG_ID =".$id;
		  		break;
	
			case "iv_kategorie":
				$tbl_fields = "IV_KAT_DESC=?"; 
				$tbl = "IV_KATEGORIE";
				$tbl_field_value = trim($_POST['form_look_up_desc']);
				$tbl_condition =  "IV_KAT_ID =".$id;
		  		break;
		  				
			case "sg_genre":
				$tbl_fields = "SG_GENRE_DESC=?"; 
				$tbl = "SG_GENRE";
				$tbl_field_value = trim($_POST['form_look_up_desc']);
				$tbl_condition =  "SG_GENRE_ID =".$id;
		  		break;		  				

			case "sg_sprache":
				$tbl_fields = "SG_SPEECH_DESC=?"; 
				$tbl = "SG_SPEECH";
				$tbl_field_value = trim($_POST['form_look_up_desc']);
				$tbl_condition =  "SG_SPEECH_ID =".$id;
		  		break;		  				

			//	endswitch;			
			}
			// wenn umgebaut hier der neue updatecode		    	
			$a_values = array($tbl_field_value);
			$db_result = db_query_update_item_b($tbl, $tbl_fields, $tbl_condition, $a_values);
			header("Location: user_look_ups_detail.php?action=display&id=".$id."&lu_item=".$look_up_item);
			break;
			//	endswitch; // action
		}
	} else {
		$message = "Keine ID enthalten. Nichts zu tun..... "; 
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-User</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_form_validator/css/validationEngine.jquery.css");    </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine-ge.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine.js"></script>
	
	<script type="text/javascript">
		$(document).ready(function() {
			$("#user_look_ups_edit_form").validationEngine() 
		})
		
	</script>
</head>
<body>
 
<div class="column_large">
<div class="column_right">
<?php
echo "<div class='head_item_right'>";
echo $message;
echo "</div>";
echo "<div class='content'>";
if ( $action_ok == "no" ) { 
	return;
}
if ( ! isset($result_field_desc )) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "A");
if ( $user_rights == "yes" ) { 
		
	echo "<form name='form1' id='user_look_ups_edit_form' action='user_look_ups_edit.php' method='POST' enctype='application/x-www-form-urlencoded'>";
	echo "<input type='hidden' name='action' value='".$form_input_type."'>";
	echo "<input type='hidden' name='id' value='".$id."'>";
	echo "<input type='hidden' name='lu_item' value='".$look_up_item."'>";

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Bezeichnung</div>";
	echo "<input type='text' id='look_up_desc' name='form_look_up_desc' class='validate[required,length[3,100]] text_1' maxlength='80' value='".$result_field_desc."'>"; 
	echo "</div>";

	echo "<div class='line_a'> </div>";
	echo "<br><span class='error_message'><?php print $error_message?></span><br>";	
	echo "<input type='submit' value='Speichern'>";
	echo "</form>";
} // user_rights	
?>
</div>
</div>
</body>
</html>
