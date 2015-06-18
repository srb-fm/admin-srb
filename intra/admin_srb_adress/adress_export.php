<?php
/** 
* adress-details anzeigen 
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
require_once 'HTTP/Download.php';
require "../../cgi-bin/admin_srb_libs/lib_sess.php";
	
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) {

	$message = "";
	$action_ok = false;

	// check action	
	if ( isset($_GET['action']) ) {
		$action = $_GET['action'];
		$action_ok = true;
	}

	if ( $action_ok == false ) { 
		$message = "Keine Anweisung. Nichts zu tun..... "; 
	}
	
	$c_query_condition = rawurldecode($_GET['condition']);
	$db_result = db_query_list_items_1("AD_ID, AD_ANREDE_ID, AD_NAME, AD_VORNAME, AD_FIRMA, AD_STRASSE, AD_PLZ, AD_ORT, AD_EMAIL, AD_TEL_1, AD_TEL_2, AD_DATUM_GEBURT, AD_USER_OK_HF ", "AD_MAIN", $c_query_condition);
	
	switch ( $action ) {
	case "export_adress":
		$pfad_datei = "../admin_srb_export/adress_export_anschrift.csv";
		$dateiname = "adress_export_anschrift.csv";
		$handler = fOpen($pfad_datei, "w");
		fWrite($handler, "Firma,Anrede,Vorname,Name,Strasse,PLZ,ORT\n");
		foreach ( $db_result as $item ) {	
				$anrede = db_query_load_value_by_id("AD_ANREDE", "AD_ANREDE_ID", $item['AD_ANREDE_ID']);
				fWrite($handler, $item['AD_FIRMA']. "," .$anrede. "," .$item['AD_VORNAME']. "," .$item['AD_NAME']. "," .$item['AD_STRASSE']. ",".$item['AD_PLZ']. ",".$item['AD_ORT'] ."\n");
		}
		break;
		
	case "export_telefon":
		$pfad_datei = "../admin_srb_export/adress_export_telefon.csv";
		$dateiname = "adress_export_telefon.csv";
		$handler = fOpen($pfad_datei, "w");		
		fWrite($handler, "Name,Vorname,Telefon_1,Telefon_2\n");
		foreach ($db_result as $item ) {
			if ( $item['AD_TEL_1'] != "" ) {
				fWrite($handler, $item['AD_NAME']. "," . $item['AD_VORNAME']. "," . $item['AD_TEL_1']. ",".$item['AD_TEL_2'] ."\n");
			}
		}

		break;
	
	case "export_email":
		$pfad_datei = "../admin_srb_export/adress_export_email.csv";
		$dateiname = "adress_export_email.csv";
		$handler = fOpen($pfad_datei, "w");
		fWrite($handler, "Name,Vorname,eMail\n");
		foreach ($db_result as $item ) {
			if ( $item['AD_EMAIL'] != "" ) {
				fWrite($handler, $item['AD_NAME']. "," . $item['AD_VORNAME']. "," . $item['AD_EMAIL']."\n");
			}
		}

		break;
	//endswitch;		
	}
	
	fClose($handler); // close file
	
	$params = array(
	  'file'                => $pfad_datei,
	  'contenttype'         => 'text/comma-separated-values',
	  'contentdisposition'  => array(HTTP_DOWNLOAD_ATTACHMENT, $dateiname),
	 );

	$error = HTTP_Download::staticSend($params, false);
	echo $error;
	echo "fertsch";
} // user_rights
?>