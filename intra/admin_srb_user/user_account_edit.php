<?php
/** 
* User Details bearbeiten 
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
	if ( isset( $_GET['account_id'] ) ) { 
		$id = $_GET['account_id'];
	}
	if ( isset( $_POST['account_id'] ) ) { 
		$id = $_POST['account_id'];
	}
	if ( $id !="" ) { 
		switch( $action ) {
		case "new":
			$message =   "Benutzer eintragen";
			$form_input_type = "add"; //form action einstellen
			$tbl_row = db_query_display_item_1("USER_ACCOUNT", "USER_ID = " .$id);
			break;

		case "add":
			// fields
			$tbl_fields = "USER_ID, USER_NAME, USER_NAME_SHORT, USER_PW, USER_RIGHTS";
			// check or load values
			$main_id = db_generator_main_id_load_value();
			$pw = md5(trim($_POST['form_ac_user_pw']));
  			$a_values = array( $main_id, trim($_POST['form_ac_user_name']), trim($_POST['form_ac_user_name_short']),
			$pw, trim($_POST['form_ac_user_rights']));
  			$insert_ok = db_query_add_item_b("USER_ACCOUNT", $tbl_fields, "?,?,?,?,?", $a_values);
    		header("Location: user_account_detail.php?action=display&account_id=".$main_id);
			break;
				
		case "edit":
			$message =   "Benutzer-Details bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row = db_query_display_item_1("USER_ACCOUNT", "USER_ID = " .$id);
			break;
			
		case "update":
			$fields_params = "USER_NAME=?, USER_NAME_SHORT=?, USER_PW=?, USER_RIGHTS=?";
			$pw = md5(trim($_POST['form_ac_user_pw']));
    		$a_values = array(trim($_POST['form_ac_user_name']), trim($_POST['form_ac_user_name_short']),
    		$pw, trim($_POST['form_ac_user_rights']));
    		db_query_update_item_b("USER_ACCOUNT", $fields_params, "USER_ID =".$id, $a_values);
 			header("Location: user_account_detail.php?action=display&account_id=".$id);
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
	<title>Admin-SRB-User-Benutzer</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_3.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>

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
if ( ! isset($tbl_row->USER_ID)) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "A");
if ( $user_rights == "yes" ) { 
	echo "<form name=\"form1\" action=\"user_account_edit.php\" method=\"POST\" enctype=\"application/x-www-form-urlencoded\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"".$form_input_type."\">";
	echo "<input type=\"hidden\" name=\"account_id\" value=\"".$tbl_row->USER_ID."\">";
			
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Benutzer-Name </div>";
	echo "<input type=\"text\" name='form_ac_user_name' class='text_1' maxlength='40' value='".htmlspecialchars($tbl_row->USER_NAME)."' >";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Benutzer-Name kurz</div>";
	echo "<input type=\"text\" name='form_ac_user_name_short' class='text_1' maxlength='12' value=\"".htmlspecialchars(rtrim($tbl_row->USER_NAME_SHORT))."\">";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Benutzer-Passwort</div>";
	echo "<input type=\"password\" name='form_ac_user_pw' class='text_1' maxlength='50' value=\"".htmlspecialchars($tbl_row->USER_PW)."\" >";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Benutzer-Rechte</div>";
	echo "<input type=\"text\" name='form_ac_user_rights' class='text_1' maxlength=1 value=\"".htmlspecialchars(rtrim($tbl_row->USER_RIGHTS))."\">";
	echo "</div>";
	echo "<br>";
	echo "<div class='line'> </div>";			
	echo "<input type=\"submit\" value=\"Speichern\">";
			
	echo "</form>";
} // user_rights	
?>
</div>
</div>
</body>
</html>