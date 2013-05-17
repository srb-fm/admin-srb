<?php

/** 
* prefen ob gewuenschter sendeplatz frei ist 
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
	
/* RECEIVE VALUE */
$validateValue=$_POST['validateValue'];
$validateId=$_POST['validateId'];
$validateError=$_POST['validateError'];

// pruefen ob satz mit gleicher zeit da und ungleicher id
$c_date_time = get_date_format_sql(substr($validateValue, 0, 10))." ".substr($validateValue, 11, 8);
// bei neu ist id = 0
if ( substr($validateValue, 20, 1) == "0") {
	$c_id = substr($validateValue, 20, 1);
	$c_duration = substr($validateValue, 21, 8);	
	$c_it = substr($validateValue, 31, 1);
	$c_mag = substr($validateValue, 33, 1);
} else {
	$c_id = substr($validateValue, 20, 7);
	$c_duration = substr($validateValue, 28, 8);	
	$c_it = substr($validateValue, 37, 1);
	$c_mag = substr($validateValue, 39, 1);
}
	
// Endzeit der Sendung durch Duration berechnen
// 1. Timestamp der Startzeit, 2. Timestamp der Duration, 3. Addieren (Sekunden), 4. Umwandeln in Date_Time-Endezeit
$t_timestamp = mktime(substr($c_date_time, 11, 2), substr($c_date_time, 14, 2), substr($c_date_time, 17, 2), substr($c_date_time, 5, 2), substr($c_date_time, 8, 2), substr($c_date_time, 0, 4));
$c_time_new =  date("H:i:s", $t_timestamp);
// Eine sekunde abziehen, da Endzeit auch Startzeit ist z.B. 17_05 Uhr + 55 Minuten = 18:00 Uhr, 18 Uhr koennte gehgt aber die naechste losgehen.. 
$t_timestamp_duration_seconds = strtotime('1970-01-01 ' . $c_duration . ' GMT') -1;
$c_time_new =  date("H:i:s", $t_timestamp + $t_timestamp_duration_seconds);
$c_date_time_new = get_date_format_sql(substr($validateValue, 0, 10))." ".$c_time_new;
//write_log_file( $c_date_time );
$db_result = db_query_list_items_1("SG_HF_ID", "SG_HF_MAIN", "SG_HF_ON_AIR = 'T' AND SG_HF_TIME >='".$c_date_time."' AND SG_HF_TIME <='".$c_date_time_new."' AND SG_HF_MAGAZINE = '".$c_mag."' AND SG_HF_INFOTIME = '".$c_it."' AND SG_HF_ID <> ".$c_id);
$count = ($db_result);

/* RETURN VALUE */
$arrayToJs = array();
$arrayToJs[0] = $validateId;
$arrayToJs[1] = $validateError;

if ($count ==0) {		// validate??
	$arrayToJs[2] = "true";			// RETURN TRUE
	echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';			// RETURN ARRAY WITH success
} else {
	for ($x=0;$x<1000000;$x++) {
		if ($x == 990000) {
			$arrayToJs[2] = "false";
			echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';		// RETURN ARRAY WITH ERROR
		}
	}
	
}


/**
* Daten zum Testen in Datei schreiben   
*
* @param string $wert Daten zurm Schreiben in Logfile
*
* @return none
 
*/
function write_log_file( $wert ) 
{
	// logfile schreiben
	$myFile = "../admin_srb_export/validate_sg_time.log";
	$fh = fopen($myFile, 'w') or die("can't open file");
	$stringData = $wert."\n";
	fwrite($fh, $stringData);
	fclose($fh);
}
?>