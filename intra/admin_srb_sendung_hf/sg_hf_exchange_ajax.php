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
		case "ftp":
		echo ftp_download( $_POST['ftp_server'], $_POST['ftp_user_name'], $_POST['ftp_user_pass'], $_POST['server_file'], $_POST['local_file'] );
			//echo $_POST['ftp_server'].$_POST['ftp_user_name'].$_POST['ftp_user_pass'].$_POST['server_file'].$_POST['local_file'];
		break;
		case "rename":
		echo "rename".$_POST['ren_file'];
		rename($_POST['local_file'], $_POST['ren_file']);
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