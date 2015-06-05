<?php
/** 
* Sendung Manuskript Processing 
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

// check action	
$action_ok = false;
if ( isset($_POST['action']) ) { 
	$action = $_POST['action'];	
	$action_ok = true;
}
		
if ( $action_ok == true ) {
	switch ( $action ) {
	case "update":
		// save changes
		//write_log_file( utf8_decode($_POST['ctxt'])) ;
		//write_log_file( $_POST['ctxt']) ;
		$fields_params = "SG_MK_TEXT=?";
		//$a_values = array( utf8_encode($_POST['ctxt']) );
		$a_values = array($_POST['ctxt']);
		$db_result = db_query_update_item_b("SG_MANUSKRIPT", $fields_params, "SG_MK_ID =".$_POST['mk_id'], $a_values);
			
		if ($db_result == false) {
			echo 'Fehler beim Speichern des Manuskriptes...';
		} else {
			echo 'Manuskript gespeichert...';
		}
		break;
    		
	case "delete":	
		$db_result = db_query_delete_item("SG_MANUSKRIPT", "SG_MK_ID", $_POST['id']);
		if ($db_result) {
			echo 'Manuskript geloescht...';
		} else {
			echo 'Fehler beim Löschen des Manuskriptes...';
		}
		break;
		//endswitch;
	}

} else {
	echo "Keine Anweisung. Nichts zu tun..... "; 
}

/**
* Logfile fuer Fehleranalyse schreiben  
*
* @param wert $wert Werte 
*
* @return none
*
*/
function write_log_file( $wert ) 
{
	// logfile schreiben
	$myFile = "../admin_srb_export/sg_mk.log";
	$fh = fopen($myFile, 'w') or die("can't open file");
	$stringData = $wert."\n";
	fwrite($fh, $stringData);
	fclose($fh);
}		

?>