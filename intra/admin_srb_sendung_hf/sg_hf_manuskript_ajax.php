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
		// Thanx to Wayne Weibel on http://stackoverflow.com/questions/1176904/php-how-to-remove-all-non-printable-characters-in-a-string
		
		$txt = iconv("UTF-8", "UTF-8//IGNORE", $_POST['ctxt']); // drop all non utf-8 characters
		// this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
		$txt = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $txt);
		//write_log_file(strlen($txt));
		if ( strlen($txt) > 8000 ) {
			echo 'Fehler beim Speichern des Manuskriptes...zu viele Zeichen';
			return;
		}
		
		//$a_values = array($_POST['ctxt']);
		$a_values = array($txt);
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
	$myFile = "/tmp/sg_mk.log";
	$fh = fopen($myFile, 'w') or die("can't open file");
	$stringData = $wert."\n";
	fwrite($fh, $stringData);
	fclose($fh);
}		

?>