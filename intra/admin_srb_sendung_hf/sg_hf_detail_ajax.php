<?php
/** 
* Sendung Exchange play and download
*
* PHP version 5
*
* @category Intranetsite
* @package  Admin-SRB
* @author   Joerg Sorge <joergsorge@googel.com>
* @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link     http://srb.fm
*/

require "../../cgi-bin/admin_srb_libs/lib.php";

// check action	
$action_ok = false;
if ( isset($_POST['action']) ) { 
	$action = $_POST['action'];	
	$action_ok = true;
}
		
if ( $action_ok == true ) {
	switch ( $action ) {

		case "copy":
		write_log_file( $_POST['file_archive'] );
			echo "copy".$_POST['file_archive'];
			$success = "no";
			if (!copy($_POST['file_archive'], $_POST['file_play_out'])) {
    			echo "copy $file schlug fehl...\n";
			} else {
				$success = "yes";
			}
//			write_log_file( $_POST['file_archive'] ); 
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
	$myFile = "../admin_srb_export/sg_detail.log";
	$fh = fopen($myFile, 'w') or die("can't open file");
	//$stringData = $wert."\n";
	//fwrite($fh, $stringData);
	//foreach ($wert as $value) {
    //fwrite($fh, $value[0].$value[1]."\n");
   // fwrite($fh, print_r($wert));
    //}
	fwrite($fh, $wert);
	fclose($fh);
}		

?>