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
$action_ok = false;

// check action	
if ( isset($_GET['action']) ) {
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action']) ) { 
	$action = $_POST['action']; 
	$action_ok = true;
}

if ( $action_ok == true ) {
	if ( isset($_GET['sg_robot_id']) ) { 
		$id = $_GET['sg_robot_id'];
	}
	if ( isset($_POST['sg_robot_id']) ) { 
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
			$tbl_fields = "SG_HF_ROB_ID, SG_HF_ROB_TITEL, SG_HF_ROB_STICHWORTE, 
								SG_HF_ROB_VP_IN, 
								SG_HF_ROB_IN_DROPB, SG_HF_ROB_FILE_IN_DB, 
								SG_HF_ROB_IN_FTP, SG_HF_ROB_FILE_IN_FTP, 
								SG_HF_ROB_DUB_ID, SG_HF_ROB_SHIFT,
								SG_HF_ROB_VP_OUT, 
								SG_HF_ROB_OUT_DROPB, SG_HF_ROB_FILE_OUT_DB, 
								SG_HF_ROB_OUT_FTP, SG_HF_ROB_FILE_OUT_FTP";
			$main_id = db_generator_main_id_load_value();
			// checkboxes
			if ( isset($_POST['form_sg_rob_vp']) ) { 
				$tbl_value_vp_in = $_POST['form_sg_rob_vp']; 
			} else { 
				$tbl_value_vp_in = "F" ;
			}
			if ( isset($_POST['form_sg_rob_in_dropb']) ) { 
				$tbl_value_vp_in_db = $_POST['form_sg_rob_in_dropb']; 
			} else { 
				$tbl_value_vp_in_db = "F" ;
			}
			if ( isset($_POST['form_sg_rob_in_ftp']) ) { 
				$tbl_value_vp_in_ftp = $_POST['form_sg_rob_in_ftp']; 
			} else { 
				$tbl_value_vp_in_ftp = "F" ;
			}
			if ( isset($_POST['form_sg_rob_vp_out']) ) { 
				$tbl_value_vp_out = $_POST['form_sg_rob_vp_out']; 
			} else { 
				$tbl_value_vp_out = "F" ;
			}
			if ( isset($_POST['form_sg_rob_out_dropb']) ) { 
				$tbl_value_vp_out_db = $_POST['form_sg_rob_out_dropb']; 
			} else { 
				$tbl_value_vp_out_db = "F" ;
			}
			if ( isset($_POST['form_sg_rob_out_ftp']) ) { 
				$tbl_value_vp_out_ftp = $_POST['form_sg_rob_out_ftp']; 
			} else { 
				$tbl_value_vp_out_ftp = "F" ;
			}
			// lookups
			$tbl_value_dub = db_query_load_id_by_value(
				"SG_HF_ROB_DUB", "SG_HF_ROB_DUB_DESC", $_POST['form_sg_rob_dub']);

    		$a_values = array($main_id, 
    						trim($_POST['form_sg_rob_titel']), 
    						trim($_POST['form_sg_rob_stichworte']), 
    						$tbl_value_vp_in, 
							$tbl_value_vp_in_db, 
							trim($_POST['form_sg_rob_file_in_db']),
							$tbl_value_vp_in_ftp, 
							trim($_POST['form_sg_rob_file_in_ftp']), 
							$tbl_value_dub, 
							$_POST['form_sg_rob_shift'],
							$tbl_value_vp_out, 
							$tbl_value_vp_out_db, 
							trim($_POST['form_sg_rob_file_out_db']),
							$tbl_value_vp_out_ftp, 
							trim($_POST['form_sg_rob_file_out_ftp']));
  			$insert_ok = db_query_add_item_b(
  			"SG_HF_ROBOT", $tbl_fields, "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?", $a_values);
			header("Location: sg_hf_robot_detail.php?action=display&sg_robot_id=".$main_id);
			exit;
			break;
			
		case "edit":
			$message = "Automatisierte Sendung: Details bearbeiten";
			$form_input_type = "update"; // set form action
			$tbl_row = db_query_display_item_1("SG_HF_ROBOT", "SG_HF_ROB_ID = " .$id);
			break;

		case "update":
			$fields_params = "SG_HF_ROB_TITEL=?, SG_HF_ROB_STICHWORTE=?, 
									SG_HF_ROB_VP_IN=?,
									SG_HF_ROB_IN_DROPB=?, SG_HF_ROB_FILE_IN_DB=?, 
									SG_HF_ROB_IN_FTP=?, SG_HF_ROB_FILE_IN_FTP=?, 
									SG_HF_ROB_DUB_ID=?, SG_HF_ROB_SHIFT=?,
									SG_HF_ROB_VP_OUT=?,
									SG_HF_ROB_OUT_DROPB=?, SG_HF_ROB_FILE_OUT_DB=?, 
									SG_HF_ROB_OUT_FTP=?, SG_HF_ROB_FILE_OUT_FTP=?";
			// checkboxes
			if ( isset($_POST['form_sg_rob_vp']) ) { 
				$tbl_value_vp_in = $_POST['form_sg_rob_vp']; 
			} else { 
				$tbl_value_vp_in = "F" ;
			}
			if ( isset($_POST['form_sg_rob_in_dropb']) ) { 
				$tbl_value_vp_in_db = $_POST['form_sg_rob_in_dropb']; 
			} else { 
				$tbl_value_vp_in_db = "F" ;
			}
			if ( isset($_POST['form_sg_rob_in_ftp']) ) { 
				$tbl_value_vp_in_ftp = $_POST['form_sg_rob_in_ftp']; 
			} else { 
				$tbl_value_vp_in_ftp = "F" ;
			}
			if ( isset($_POST['form_sg_rob_vp_out']) ) { 
				$tbl_value_vp_out = $_POST['form_sg_rob_vp_out']; 
			} else { 
				$tbl_value_vp_out = "F" ;
			}
			if ( isset($_POST['form_sg_rob_out_dropb']) ) { 
				$tbl_value_vp_out_db = $_POST['form_sg_rob_out_dropb']; 
			} else { 
				$tbl_value_vp_out_db = "F" ;
			}
			if ( isset($_POST['form_sg_rob_out_ftp']) ) { 
				$tbl_value_vp_out_ftp = $_POST['form_sg_rob_out_ftp']; 
			} else { 
				$tbl_value_vp_out_ftp = "F" ;
			}
			// lookups
			$tbl_value_dub = db_query_load_id_by_value(
				"SG_HF_ROB_DUB", "SG_HF_ROB_DUB_DESC", $_POST['form_sg_rob_dub']);

    		$a_values = array(trim($_POST['form_sg_rob_titel']), 
    								trim($_POST['form_sg_rob_stichworte']),
    								$tbl_value_vp_in, 
    								$tbl_value_vp_in_db, 
    								trim($_POST['form_sg_rob_file_in_db']), 
    								$tbl_value_vp_in_ftp, 
    								trim($_POST['form_sg_rob_file_in_ftp']), 
    								$tbl_value_dub, 
    								$_POST['form_sg_rob_shift'], 
    								$tbl_value_vp_out, 
									$tbl_value_vp_out_db, 
    								trim($_POST['form_sg_rob_file_out_db']), 
    								$tbl_value_vp_out_ftp, 
									trim($_POST['form_sg_rob_file_out_ftp']));   					
    		db_query_update_item_b(
    				"SG_HF_ROBOT", $fields_params, "SG_HF_ROB_ID =".$id, $a_values);
			header("Location: sg_hf_robot_detail.php?action=display&sg_robot_id=".$id);
			exit;
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
if ( $action_ok == false ) {
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
	echo "<div class='content_column_1'>Duplizierung </div>";
	echo html_dropdown_from_table_1("SG_HF_ROB_DUB", "SG_HF_ROB_DUB_DESC", "form_sg_rob_dub", "text_2", rtrim($tbl_row->SG_HF_ROB_DUB_ID));
	echo " Verschiebung zw. Erstsendung Lieferant und SRB: ";
	echo "<select name='form_sg_rob_shift' class='text_2' size='1'>";
	$i = 0;
	while ($i <= 7) {
		if ( $i != rtrim($tbl_row->SG_HF_ROB_SHIFT) ) {
			echo "<option>".$i."</option>";
		} else {
			echo "<option selected='selected'>".$i."</option>";
		}
		$i++;
	}
	echo "</select>" ;
	echo "</div>";
	
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>VP-Übernahme</div>";			
	echo "<div class='content_column_2'><label for='check1'>" ;
	if ( rtrim($tbl_row->SG_HF_ROB_VP_IN) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_vp' value='T' checked='checked' title='Wird übernommen' id='check1'> VP von extern ";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_vp' value='T' title='Wird nicht übernommen' id='check1'> VP von extern ";
	}
	echo "</label> <label for='check2'>";
	if ( rtrim($tbl_row->SG_HF_ROB_IN_DROPB) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_in_dropb' value='T' checked='checked' title='Via Dropbox' id='check2'> Dropbox";
	} else {
		echo "<input type='checkbox' name='form_sg_rob_in_dropb' value='T' title='Nicht via Dropbox' id='check2'> Dropbox";
	}
	echo "</label> <label for='check3'>";
	if ( rtrim($tbl_row->SG_HF_ROB_IN_FTP) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_in_ftp' value='T' checked='checked' title='Via ftp' id='check3'> FTP";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_in_ftp' value='T' title='Nicht via ftp' id='check3'> FTP";
	}
	echo "</label> </div></div>\n";
	
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Ordner/Datei <b>von</b> Dropbox</div>";
	echo "<input type=\"text\" name='form_sg_rob_file_in_db' class='text_1' maxlength='100' value=\"".trim(htmlspecialchars($tbl_row->SG_HF_ROB_FILE_IN_DB))."\" >";
	echo "</div>";

	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Ordner/Datei <b>von</b> FTP</div>";
	echo "<input type=\"text\" name='form_sg_rob_file_in_ftp' class='text_1' maxlength='100' value=\"".trim(htmlspecialchars($tbl_row->SG_HF_ROB_FILE_IN_FTP))."\" >";
	echo "</div>";
	
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>VP <b>nach</b> extern</div>";			
	echo "<div class='content_column_2'><label for='check4'>" ;
	if ( rtrim($tbl_row->SG_HF_ROB_VP_OUT) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_vp_out' value='T' checked='checked' title='Wird zur Verfügung gestellt' id='check4'> VP nach extern ";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_vp_out' value='T' title='Wird nicht zur Verfügung gestellt' id='check4'> VP nach extern ";
	}				
	
		echo "</label> <label for='check5'>";
	if ( rtrim($tbl_row->SG_HF_ROB_OUT_DROPB) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_out_dropb' value='T' checked='checked' title='Via Dropbox' id='check5'> Dropbox";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_out_dropb' value='T' title='Nicht via Dropbox' id='check5'> Dropbox";
	}
	echo "</label> <label for='check6'>";
	if ( rtrim($tbl_row->SG_HF_ROB_OUT_FTP) == "T") {
		echo "<input type='checkbox' name='form_sg_rob_out_ftp' value='T' checked='checked' title='Via ftp' id='check6'> FTP";
	} else { 
		echo "<input type='checkbox' name='form_sg_rob_out_ftp' value='T' title='Nicht via ftp' id='check6'> FTP";
	}
	echo "</label> </div></div>\n";
	
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Ordner Dropbox nach extern</div>";
	echo "<input type=\"text\" name='form_sg_rob_file_out_db' class='text_1' maxlength='100' value=\"".trim(htmlspecialchars($tbl_row->SG_HF_ROB_FILE_OUT_DB))."\" >";
	echo "</div>";
	
	echo "<div class='content_row_b_1'>";
	echo "<div class='content_column_1'>Ordner FTP nach extern</div>";
	echo "<input type=\"text\" name='form_sg_rob_file_out_ftp' class='text_1' maxlength='100' value=\"".trim(htmlspecialchars($tbl_row->SG_HF_ROB_FILE_OUT_FTP))."\" >";
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