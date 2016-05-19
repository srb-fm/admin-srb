<?php
/** 
* Einstellung bearbeiten
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
	if ( isset( $_GET['special_id'] ) ) { 
		$id = $_GET['special_id'];
	}
	if ( isset( $_POST['special_id'] ) ) { 
		$id = $_POST['special_id'];
	}
	if ( $id !="" ) { 
		switch ( $action ) {
		case "new":
			$message .=   "Einstellung eintragen";
			$form_input_type = "add"; //form action einstellen
			$tbl_row = db_query_display_item_1("USER_SPECIALS", "USER_SP_ID = " .$id);
			break;

		case "add":
			// fields
			$tbl_fields = "USER_SP_ID, USER_SP_DESC, USER_SP_SPECIAL, ";
			$tbl_fields .= "USER_SP_PARAM_1, USER_SP_PARAM_2, USER_SP_PARAM_3, USER_SP_PARAM_4, USER_SP_PARAM_5, ";
			$tbl_fields .= "USER_SP_PARAM_6, USER_SP_PARAM_7, USER_SP_PARAM_8,  ";
			$tbl_fields .= "USER_SP_PARAM_9, USER_SP_PARAM_10, USER_SP_PARAM_11, USER_SP_PARAM_12, USER_SP_TEXT";			
			// check or load values
			$main_id = db_generator_main_id_load_value();
  			$a_values = array( $main_id, trim($_POST['form_sp_desc']), trim($_POST['form_sp_special']),
  					trim($_POST['form_sp_param_1']), trim($_POST['form_sp_param_2']), 
  					trim($_POST['form_sp_param_3']), trim($_POST['form_sp_param_4']),
  					trim($_POST['form_sp_param_5']), trim($_POST['form_sp_param_6']),
  					trim($_POST['form_sp_param_7']), trim($_POST['form_sp_param_8']),
  					trim($_POST['form_sp_param_9']), trim($_POST['form_sp_param_10']),
  					trim($_POST['form_sp_param_11']), trim($_POST['form_sp_param_12']),
  					trim($_POST['form_sp_text']) );

    		$insert_ok = db_query_add_item_b("USER_SPECIALS", $tbl_fields, "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?", $a_values);	
			header("Location: user_special_detail.php?action=display&special_id=".$main_id);
			break;
				
		case "edit":
			$message .=   "Einstellung-Details bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row = db_query_display_item_1("USER_SPECIALS", "USER_SP_ID = " .$id);
			break;
			
		case "update":
			$fields_params = "USER_SP_DESC=?, USER_SP_SPECIAL=?, ";
			$fields_params .= "USER_SP_PARAM_1=?, USER_SP_PARAM_2=?, USER_SP_PARAM_3=?, USER_SP_PARAM_4=?, USER_SP_PARAM_5=?, ";
			$fields_params .= "USER_SP_PARAM_6=?, USER_SP_PARAM_7=?, USER_SP_PARAM_8=?, ";
			$fields_params .= "USER_SP_PARAM_9=?, USER_SP_PARAM_10=?, USER_SP_PARAM_11=?, USER_SP_PARAM_12=?, USER_SP_TEXT=? ";
  			$a_values = array( trim($_POST['form_sp_desc']), trim($_POST['form_sp_special']),
  					trim($_POST['form_sp_param_1']), trim($_POST['form_sp_param_2']), 
  					trim($_POST['form_sp_param_3']), trim($_POST['form_sp_param_4']),
  					trim($_POST['form_sp_param_5']), trim($_POST['form_sp_param_6']),
  					trim($_POST['form_sp_param_7']), trim($_POST['form_sp_param_8']),
  					trim($_POST['form_sp_param_9']), trim($_POST['form_sp_param_10']),
  					trim($_POST['form_sp_param_11']), trim($_POST['form_sp_param_12']),
  					trim($_POST['form_sp_text']) );
  			db_query_update_item_b("USER_SPECIALS", $fields_params, "USER_SP_ID =".$id, $a_values);
			header("Location: user_special_detail.php?action=display&special_id=".$id);
			break;
			//endswitch;
		}
	} else {
		$message = "Keine ID. Nichts zu tun..... "; 
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-User-Specials</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_3.css"); </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
	<script type="text/javascript" src="../parts/ckeditor/ckeditor.js"></script>
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
if ( $action_ok == "no" ) { 
	return;
}
if ( ! isset($tbl_row->USER_SP_ID )) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
		
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "A");
if ( $user_rights == "yes" ) { 	
		
	echo "<form name=\"form1\" action=\"user_special_edit.php\" method=\"POST\" enctype=\"application/x-www-form-urlencoded\">\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"".$form_input_type."\">";
	echo "<input type=\"hidden\" name='special_id' value=\"".$tbl_row->USER_SP_ID."\">";
			
	echo "<div class='content_row_a_1'>\n";
	echo "<div class='content_column_1'>Bezeichnung </div>";
	echo "<input type='text' name='form_sp_desc' class='text_1' maxlength='60' value='".htmlspecialchars($tbl_row->USER_SP_DESC)."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Bezeichnung intern</div>";
	echo "<input type='text' name='form_sp_special' class='text_1' maxlength=60 value='".htmlspecialchars($tbl_row->USER_SP_SPECIAL)."'>";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 1</div>";
	echo "<input type='text' name='form_sp_param_1' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_1)."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 2</div>";
	echo "<input type='text' name='form_sp_param_2' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_2)."'>";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 3</div>";
	echo "<input type='text' name='form_sp_param_3' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_3)."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 4</div>";
	echo "<input type='text' name='form_sp_param_4' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_4)."'>";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 5</div>";
	echo "<input type='text' name='form_sp_param_5' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_5)."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 6</div>";
	echo "<input type='text' name='form_sp_param_6' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_6)."'>";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 7</div>";
	echo "<input type='text' name='form_sp_param_7' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_7)."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 8</div>";
	echo "<input type='text' name='form_sp_param_8' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_8)."'>";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 9</div>";
	echo "<input type='text' name='form_sp_param_9' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_9)."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 10</div>";
	echo "<input type='text' name='form_sp_param_10' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_10)."'>";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Parameter 11</div>";
	echo "<input type='text' name='form_sp_param_11' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_11)."'>";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Parameter 12</div>";
	echo "<input type='text' name='form_sp_param_12' class='text_1' maxlength=120 value='".htmlspecialchars($tbl_row->USER_SP_PARAM_12)."'>";
	echo "</div>";
	
	echo "<div class='content_row_a_8'>";
	echo "<div class='content_column_1'>Text</div>";
	echo "<textarea id='editor1' class='ckeditor textarea_2' name='form_sp_text' >".htmlspecialchars($tbl_row->USER_SP_TEXT)."</textarea>";			
	echo "<script type='text/javascript'>";
	//<![CDATA[
	echo "CKEDITOR.replace( 'editor1',{";
	echo "toolbar :";
	echo "[	['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', 'Link', '-', 'Source', 'Styles', 'Format', 'RemoveFormat']	]";
	echo "});";
	echo "CKEDITOR.config.entities = false;";
	echo "CKEDITOR.config.basicEntities = false;";
	//]]>
	echo "</script>";
	echo "</div>";
		
	echo "<br>";
	echo "<div class='line'> </div>";			
	echo "<input type='submit' value='Speichern'>";
	echo "</form>";
} // user_rights	
echo "</div>";
?>
</div>
</body>
