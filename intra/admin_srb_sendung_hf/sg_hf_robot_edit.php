<?php

/** 
* Sendung - Bearbeitung automatisierter Sendung 
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
	if ( isset( $_GET['sg_robot_id'] ) ) { 
		$id = $_GET['sg_robot_id'];
	}
	if ( isset( $_POST['sg_robot_id'] ) ) { 
		$id = $_POST['sg_robot_id'];
	}
	if ( $id !="" ) { 
		switch ( $action ) {
		case "new":
			$message = "Automatisierte Sendung eintragen";
			$form_input_type = "add"; //form action einstellen
			$tbl_row = db_query_display_item_1("SG_HF_ROBOT", "SG_HF_ROB_ID = " .$id);
			break;

		case "add":
			// fields
			$tbl_fields = "SG_HF_ROB_ID, SG_HF_ROB_TITEL, SG_HF_ROB_STICHWORTE, SG_HF_ROB_FILENAME, SG_HF_ROB_VP, SG_HF_ROB_DUB_ID";
			$main_id = db_generator_main_id_load_value();
			// checkboxen
			if ( isset( $_POST['form_sg_rob_vp'] ) ) { 
				$tbl_value_vp = $_POST['form_sg_rob_vp']; 
			} else { 
				$tbl_value_vp = "F" ;
			}
			// lookups
			$tbl_value_dub = db_query_load_id_by_value("SG_HF_ROB_DUB", "SG_HF_ROB_DUB_DESC", $_POST['form_sg_rob_dub']);
				
    		$a_values = array($main_id, trim($_POST['form_sg_rob_titel']), trim($_POST['form_sg_rob_stichworte']),
    		trim($_POST['form_sg_rob_filename']), $tbl_value_vp, $tbl_value_dub);
  			$insert_ok = db_query_add_item_b("SG_HF_ROBOT", $tbl_fields, "?,?,?,?,?,?", $a_values);
			header("Location: sg_hf_robot_detail.php?action=display&sg_robot_id=".$main_id);
			break;
				
		case "edit":
			$message = "Automatisierte Sendung: Details bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row = db_query_display_item_1("SG_HF_ROBOT", "SG_HF_ROB_ID = " .$id);
			break;
			
		case "update":
			$fields_params = "SG_HF_ROB_TITEL=?, SG_HF_ROB_STICHWORTE=?, SG_HF_ROB_FILENAME=?, SG_HF_ROB_VP=?, SG_HF_ROB_DUB_ID=?";
			// checkboxen
			if ( isset( $_POST['form_sg_rob_vp'] ) ) { 
				$tbl_value_vp = $_POST['form_sg_rob_vp']; 
			} else { 
				$tbl_value_vp = "F" ;
			}

			// lookups
			$tbl_value_dub = db_query_load_id_by_value("SG_HF_ROB_DUB", "SG_HF_ROB_DUB_DESC", $_POST['form_sg_rob_dub']);
			
    		$a_values = array( trim($_POST['form_sg_rob_titel']), trim($_POST['form_sg_rob_stichworte']),
    		trim($_POST['form_sg_rob_filename']), $tbl_value_vp, $tbl_value_dub );   					
    		$update_ok = db_query_update_item_b("SG_HF_ROBOT", $fields_params, "SG_HF_ROB_ID =".$id, $a_values);
			header("Location: sg_hf_robot_detail.php?action=display&sg_robot_id=".$id);
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
	<title>Admin-SRB-Sendung-Automatisierte Sendung</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
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
if ( ! isset($tbl_row->SG_HF_ROB_ID ) ) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "A");
if ( $user_rights == "yes" ) { 		
	echo "<form name=\"form1\" action=\"sg_hf_robot_edit.php\" method=\"POST\" enctype=\"application/x-www-form-urlencoded\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"".$form_input_type."\">";
	echo "<input type=\"hidden\" name=\"sg_robot_id\" value=\"".$tbl_row->SG_HF_ROB_ID."\">";
			
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Titel </div>";
	echo "<input type=\"text\" name='form_sg_rob_titel' class='text_1' maxlength='100' value='".trim(htmlspecialchars($tbl_row->SG_HF_ROB_TITEL))."' >";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Stichworte</div>";
	echo "<input type=\"text\" name='form_sg_rob_stichworte' class='text_1' maxlength='40' value=\"".trim(htmlspecialchars($tbl_row->SG_HF_ROB_STICHWORTE))."\">";
	echo "</div>";
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Pfad/Dateiname</div>";
	echo "<input type=\"text\" name='form_sg_rob_filename' class='text_1' maxlength='100' value=\"".trim(htmlspecialchars($tbl_row->SG_HF_ROB_FILENAME))."\" >";
	echo "</div>";
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Einstellungen</div>";			
	echo "<div class='content_column_2'>" ;
	if ( rtrim($tbl_row->SG_HF_ROB_VP) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_vp' value='T' checked='checked' title='Wird übernommen'> VP-Übernahme ";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_vp' value='T' title='Wird nicht übernommen'> VP-Übernahme ";
	}				
	echo "/ Wiederholung: ".html_dropdown_from_table_1("SG_HF_ROB_DUB", "SG_HF_ROB_DUB_DESC", "form_sg_rob_dub", "text_2", rtrim($tbl_row->SG_HF_ROB_DUB_ID));
	echo "</div></div>\n";
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