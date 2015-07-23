<?php

/** 
* Sendung Plan Processing 
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

$message = "";
$option = "";

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
	case "infotime_update":
		$c_it_sg_ids = $_POST['table_sort'];
		$n_it_anzahl = $_POST['sendungen_anzahl']; 
		$n_it_start_hour =  $_POST['it_start_hour'];
												
		$n_it_interval_sekunden = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_8", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
		$c_it_date = $_POST['sendungen_date'];
		$timestamp = mktime($n_it_start_hour, 0, 0, substr($c_it_date, 5, 2), substr($c_it_date, 8, 2), substr($c_it_date, 0, 4));

		for ( $i='0';$i<$n_it_anzahl;$i++ ) {
			// Bei null anfangen
			if ( $i>'0' ) { 
				$timestamp = $timestamp + $n_it_interval_sekunden;	
			}
			$c_time_new =  date("H:i:s", $timestamp);

			//ids aus uebergebenen serialized array raussholen
			parse_str($c_it_sg_ids);
			$tbl_fields_values_sg = "SG_HF_TIME= '".$c_it_date." ".$c_time_new."' ";
			//db_query_update_item( "SG_HF_MAIN", "SG_HF_ID", substr( $table_sg_plan[$i],6), $tbl_fields_values_sg );
			db_query_update_item_a("SG_HF_MAIN", $tbl_fields_values_sg, "SG_HF_ID =".substr($table_sg_plan[$i], 6));	
		}

		header("Location: sg_hf_plan.php?action=list&find_option=".$_POST['find_option']);
		exit;
		break;	

	case "magazin_update": 
		$c_mag_sg_ids = $_POST['table_sort'];
		$n_mag_anzahl = $_POST['sendungen_anzahl']; 
		//$n_mags_start_hour = 6;
		$n_mag_start_hour = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_1", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);
		//$n_mags_interval = 15;
		$n_mag_interval = substr(db_query_load_fieldvalue_by_condition("USER_SPECIALS", "USER_SP_PARAM_6", "USER_SP_SPECIAL = 'PO_Time_Config_1'"), 0, 2);				
		$c_mag_date = $_POST['sendungen_date'];
		$n_mag_minute = 0;

		for ( $i='0';$i<$n_mag_anzahl;$i++ ) {
			$n_mag_minute = $n_mag_minute + $n_mag_interval;
			if ( $n_mag_minute == 60 ) {
				$n_mag_start_hour += 1;
				$n_mag_minute = 15;
			}
			if ( strlen($n_mag_start_hour) == 1 ) { 
				$n_mag_start_hour = "0".$n_mag_start_hour;
			}	
			$c_time = $n_mag_start_hour.":".$n_mag_minute;
			//ids aus übergebenen serialized array raussholen
			parse_str($c_mag_sg_ids);
			//echo substr( $table_sg_plan[$i],6);
			$tbl_fields_values_sg = "SG_HF_TIME= '".$c_mag_date." ".$c_time.":00' ";
			db_query_update_item_a("SG_HF_MAIN", $tbl_fields_values_sg, "SG_HF_ID =".substr($table_sg_plan[$i], 6));	
		}

		#echo "Location: sg_hf_plan.php?action=list&find_option=".$_POST['find_option']."&form_k_datum=".$_POST['sendungen_date'];
		header("Location: sg_hf_plan.php?action=list&find_option=".$_POST['find_option']."&form_k_datum=".$_POST['sendungen_date']);
		exit;
		break;	
		//endswitch;
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
?>