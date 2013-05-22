<?php
/** 
* Verleih bearbeiten
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
	if ( isset( $_GET['ad_id'] ) ) {	
		$id = $_GET['ad_id'];
	}
	if ( isset( $_POST['ad_id'] ) ) {	
		$id = $_POST['ad_id'];
	}
	if ( isset( $_GET['vl_id'] ) ) {	
		$vl_id = $_GET['vl_id'];
	}
	if ( isset( $_POST['vl_id'] ) ) { 	
		$vl_id = $_POST['vl_id'];
	}
			
	if ( $id !="" and  $vl_id !="" ) { 
		switch ( $action ) {
		case "new":
			$message = "Verleih buchen";
			$form_input_type = "add"; //form action einstellen
			$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id);			
			$tbl_row_vl = db_query_display_item_1("VL_MAIN", "VL_ID = ".$vl_id);
			$tbl_row_vl->VL_DATUM_START = date("Y-m-d");
			$tbl_row_vl->VL_DATUM_END = date("Y-m-d");
			break;

		case "add":
			// fields
			$tbl_fields = "VL_ID, VL_AD_ID, VL_DATUM_START, VL_DATUM_END, VL_PROJEKT, VL_TEXT ";
			// check or load values, lookups
			$main_id = db_generator_main_id_load_value();
			$value_dat_start = get_date_format_sql($_POST['form_vl_date_start']);
			$value_dat_end = get_date_format_sql($_POST['form_vl_date_end']);
			$a_values = array($main_id, $_POST['ad_id'], $value_dat_start, $value_dat_end, $_POST['form_vl_projekt'], $_POST['form_vl_text']);
			$insert_ok = db_query_add_item_b("VL_MAIN", $tbl_fields, "?,?,?,?,?,?", $a_values);
			header("Location: vl_detail.php?action=display&vl_id=".$main_id);
			break;
				
		case "edit":
			$message =   "Verleih-Details bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row_vl = db_query_display_item_1("VL_MAIN", "VL_ID = " .$vl_id);
			$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$id);
			break;
				
		case "update":
			$fields_params = "VL_DATUM_START=?, VL_DATUM_END=?, VL_PROJEKT=?, VL_TEXT=? ";
			$value_dat_start = get_date_format_sql($_POST['form_vl_date_start']);
			$value_dat_end = get_date_format_sql($_POST['form_vl_date_end']);
			$a_values = array($value_dat_start, $value_dat_end, $_POST['form_vl_projekt'], $_POST['form_vl_text']);	
			db_query_update_item_b("VL_MAIN", $fields_params, "VL_ID =".$vl_id, $a_values);
			header("Location: vl_detail.php?action=display&vl_id=".$vl_id);
			break;
				//endswitch;
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
	<title>Admin-SRB-Verleih</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta http-equiv="expires" content="0">
	<style type="text/css">@import url("../parts/style/style_srb_2.css");  </style>
	<style type="text/css">@import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css">@import url("../parts/jquery/jquery_form_validator/css/validationEngine.jquery.css");    </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine-ge.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_form_validator/jquery.validationEngine.js"></script>	
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>	
	<script type="text/javascript">
		$(document).ready(function() {
			$("#vl_edit_form").validationEngine() 
		})
		
	</script>

</head>
<body>
 
<div class="column_right">
<?php
echo "<div class='head_item_right'>";
echo $message;
echo "</div>";	
echo "<div class='content'>";	
if ( $action_ok == "no" ) { 
	return;
}
if ( ! isset($tbl_row_vl->VL_ID )) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 	
	echo "<form name='form1' id='vl_edit_form' action='vl_edit.php' method='POST' enctype='application/x-www-form-urlencoded'>";
	echo "<input type='hidden' name='action' value='".$form_input_type."'>";
	echo "<input type='hidden' name='ad_id' value='".$tbl_row_ad->AD_ID."'>";
	echo "<input type='hidden' name='vl_id' value='".$tbl_row_vl->VL_ID."'>";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Verleih an</div>";
	echo "<input type='text' name='form_ad_name' class='text_1' value='".$tbl_row_ad->AD_VORNAME." " .$tbl_row_ad->AD_NAME.", ".$tbl_row_ad->AD_ORT."' >";
	echo "</div>";

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Verleih von/ bis</div>";			
	echo "<input type='text' name='form_vl_date_start' id='vl_date_start' class='validate[required,custom[date_ge]] text_2' value='".get_date_format_deutsch($tbl_row_vl->VL_DATUM_START)."'><input type='text' name='form_vl_date_end' id='datepicker' class='validate[required,custom[date_ge]] text_2' value='".get_date_format_deutsch($tbl_row_vl->VL_DATUM_END)."'>";
	echo "</div>";

	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Projekt</div>";
	echo "<input type='text' name='form_vl_projekt' id ='vl_projekt' class='validate[required,length[3,100]] text_1' maxlength=100 value='".$tbl_row_vl->VL_PROJEKT."' >";
	echo "</div>";
			
	echo "<div class='content_row_b_3'>";
	echo "<div class='content_column_1'>Bemerkungen</div>";
	echo "<textarea class='textarea_1_e' name='form_vl_text'>".$tbl_row_vl->VL_TEXT."</textarea>";
	echo "</div>";
	echo "<div class='line_a'> </div>";
	echo "<input type='submit' value='Speichern'>";
	echo "</form>";
} // user_rights
echo "</div>";

echo "<script type='text/javascript'>";
echo "document.form1.vl_projekt.focus();";
echo "</script>";
?>
</body>
</html>
