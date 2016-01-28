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
			// delete old files
			$dir_files = scandir($_POST['local_dir'], 1);
			// one day back
			$time_delete = time() - 86400;
			//$x = date( "Y-m-d H:m:s ",$time_delete)."\n";
			foreach ($dir_files as $dir_file) {
				//$x .= $dir_file." ".date( "Y-m-d H:m:s ",filectime($_POST['local_dir'].$dir_file))."\n";				
				if ( filectime($_POST['local_dir'].$dir_file) < $time_delete ) {
					if ( ($dir_file != ".." ) AND ($dir_file != ".") ) {
						//$x .= $dir_file." yes ".date( "Y-m-d H:m:s ",filectime($_POST['local_dir'].$dir_file))."\n";
						unlink($_POST['local_dir'].$dir_file);
					}
				}
		   }
			
			//write_log_file( $x);
			echo ftp_download( $_POST['ftp_server'], $_POST['ftp_user_name'], $_POST['ftp_user_pass'], $_POST['server_file'], $_POST['local_file'] );
		break;
		
		case "rename":
			echo "rename".$_POST['ren_file'];
			$file_name = new SplFileInfo($_POST['ren_file']);
			$file_name_base = basename($file_name);
			$file_ext = $file_name->getExtension();
			if ( $file_ext == "MP3" ) {
				$file_name_base = basename($file_name, "MP3");
				$file_path_parts = pathinfo($_POST['ren_file']);
				$file_path_parts['dirname'];
				$file_new_name = $file_path_parts['dirname']."/".$file_name_base."mp3";
				rename($_POST['local_file'], $file_new_name);
			} else{
				rename($_POST['local_file'], $_POST['ren_file']);
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
	$myFile = "../admin_srb_export/sg_exchange.log";
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