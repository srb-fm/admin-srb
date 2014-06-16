<?php
/** 
* library for statistic-functions 
*
* PHP version 5
*
* @category Intranetsite
* @package  Admin-SRB
* @author   Joerg Sorge <joergsorge@googel.com>
* @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link     http://srb.fm
*/


/**
* statistik_ad
* Adresse
* 
* @return array
*
*/
function statistik_ad ()
{
	// Gesamtzahlen
	$a_ad_gesamt = array("Gesamt" => db_query_count_rows("AD_MAIN", "AD_USER_OK_HF='T'"));
	$a_ad_gesamt["Frauen"] = db_query_count_rows("AD_MAIN", "AD_USER_OK_HF='T' AND AD_ANREDE_ID='01'"); 
	$a_ad_gesamt["Männer"] = db_query_count_rows("AD_MAIN", "AD_USER_OK_HF='T' AND AD_ANREDE_ID='02'");
	$j = date('Y');
	$m = date('m');
	$d = date('d');
	$d_achzehn_jahr_zurueck = date('Y-m-d', mktime(0, 0, 0, $m, $d, $j-18));
	$c_condition = "AD_USER_OK_HF='T' AND AD_DATUM_GEBURT>='".$d_achzehn_jahr_zurueck."'";
	$a_ad_gesamt["Kinder/ Jugendliche"] = db_query_count_rows("AD_MAIN", $c_condition);			
	$a_ad_gesamt["Nutzer Fernsehen"] = db_query_count_rows("AD_MAIN", "AD_USER_OK_TV='T'");
	$a_ad_gesamt["Praktikanten"] = db_query_count_rows("AD_MAIN", "AD_USER_OK_PRAKTIKANT='T'");
	// Macher pro Jahr
	// Datensatz der vorlage holen 
	$db_result_ad_year = db_query_display_item_1("AD_MAIN", "AD_ID = 0");
	$year_macher = substr($db_result_ad_year->AD_TIME_AUFNAHME, 0, 4);
	$year_current = date("Y");
	//$a_test = $db_result_ad_year->AD_TIME_AUFNAHME;
	$a_ad_macher_year = array();
	//$a_ad_macher_year[$year_macher] = db_query_count_rows( "AD_MAIN", "AD_USER_OK_HF='T' AND SUBSTRING(AD_TIME_AUFNAHME FROM 1 FOR 4 ) = ".$year_macher );
	//$a_ad_macher_year[$year_macher] = $year_macher;
	//$year_macher+=1;
	//$a_ad_macher_year[$year_macher] = db_query_count_rows( "AD_MAIN", "AD_USER_OK_HF='T' AND SUBSTRING(AD_TIME_AUFNAHME FROM 1 FOR 4 ) = ".$year_macher );
	for ( $year_macher; $year_macher <= $year_current; $year_macher+=1) {
		$a_ad_macher_year[$year_macher] = $year_macher;
		//$year_macher+=1;
		$a_ad_macher_year[$year_macher] = db_query_count_rows("AD_MAIN", "AD_USER_OK_HF='T' AND SUBSTRING(AD_TIME_AUFNAHME FROM 1 FOR 4 ) = ".$year_macher);
	}
		
	// Aktive pro Quartal aktuell
	$a_ad_gesamt["Aktive akt. Quartal"] = db_query_count_rows("AD_MAIN", "AD_USER_OK_AKTIV='T'");
	// Aktive pro Quartal Vergangenheit
	// erstmal alle holen
	$c_query_condition = "SUBSTRING( ST_USER_OK_ACTIVE_TIME FROM 1 FOR 4 ) > '2009' ORDER BY ST_USER_OK_ACTIVE_TIME" ;//'".$c_month.";
	$a_ad_active = db_query_list_items_1("ST_USER_OK_ACTIVE_TIME, ST_USER_OK_ACTIVE_NUMBER ", "ST_USER_OK_ACTIVE", $c_query_condition);
	$a_ad_active_quartal = array();
	$a_ad_active_year = array();
	//Anfangsjahr
	$c_year_a = "1900";
	$n_active_count = 0;
	$n_z_all = 0;
	// Quartalszahlen in Array uebernehmen
	foreach ( $a_ad_active as $item_time => $item_user_counter ) {
		$c_year = substr($item_user_counter['ST_USER_OK_ACTIVE_TIME'], 0, 4);
		$c_month = substr($item_user_counter['ST_USER_OK_ACTIVE_TIME'], 5, 2);
		
		// Quartale ermitteln, Quartalszahlen sind immer am ersten Tag des naechsten Quartals gespeichert
		if ( $c_month =="01" ) {
			$year = $c_year -1;
			$c_quartal = $year." IV";
			$c_year = (string)$year;
		} elseif ( $c_month == "04" ) {
			$c_quartal = $c_year." I";
		} elseif (	$c_month == "07" ) {
			$c_quartal = $c_year." II";
		} elseif (	$c_month == "10") {
			$c_quartal = $c_year." III";
		}  		

		// Erstes Jahr	in Vari	
		if ( $n_z_all==0 ) { 
			$c_year_a = $c_year;	
		}

		// Anzahl zum Quartal registrieren
		$a_ad_active_quartal[$c_quartal] = $item_user_counter['ST_USER_OK_ACTIVE_NUMBER'];
		
		if ( $c_year_a != $c_year ) {
			// Jahressumme registrieren
			$a_ad_active_year[$c_year_a] = $n_active_count;
			$c_year_a = $c_year;
			$n_active_count = $item_user_counter['ST_USER_OK_ACTIVE_NUMBER'];
		} else {
			// Anzahl in summieren
			$n_active_count = $n_active_count + $item_user_counter['ST_USER_OK_ACTIVE_NUMBER'];
		}
			
		$n_z_all +=1;
	}	
	// Jahressumme aus letztem Durchlauf noch registrieren	
	$a_ad_active_year[$c_year_a] = $n_active_count;
	return array($a_ad_gesamt, $a_ad_active, $a_ad_active_quartal, $a_ad_active_year, $a_ad_macher_year);   
}

/**
* statistik_ad
* Sendung
* 
* @return array
*
*/
function statistik_sg ()
{
	$a_sg_gesamt = array("Gesamt" => db_query_count_rows("SG_HF_MAIN", "SG_HF_TIME >'1900-01-01'"));
	$a_sg_gesamt["Erstsendungen"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_FIRST_SG='T'"); 
	$a_sg_gesamt["Wiederholungen"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_FIRST_SG='F'");
	$a_sg_gesamt["Livesendungen"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_SOURCE_ID<>'03'");
	$a_sg_gesamt["Live aus Studio 1"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_SOURCE_ID='01'");
	$a_sg_gesamt["Live aus Studio 2"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_SOURCE_ID='02'");
	$a_sg_gesamt["Infotime-Sendungen (ES)"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_FIRST_SG='T' AND SG_HF_INFOTIME='T'");
	$a_sg_gesamt["Magazin-Sendungen (ES)"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_FIRST_SG='T' AND SG_HF_MAGAZINE='T'");
	$a_sg_gesamt["Normale-Sendungen (ES)"] = db_query_count_rows("SG_HF_MAIN", "SG_HF_FIRST_SG='T' AND SG_HF_INFOTIME='F' AND SG_HF_MAGAZINE='F'");
	// Folgesendungen ermitteln (wenn Sendung laenger als eine Stunde, und deshalb mehrere gebucht sind) 
	$c_query_condition_sg_only_first_hour = "B.SG_HF_CONT_SG_ID = A.SG_HF_ID AND A.SG_HF_FIRST_SG ='T' order by A.SG_HF_TIME";
	$db_result_sg_only_first_hour = db_query_list_items_1("A.SG_HF_TIME, B.SG_HF_CONT_TITEL ", "SG_HF_MAIN A, SG_HF_CONTENT B", $c_query_condition_sg_only_first_hour);
	$z_rows = 0;
	$z_rows_1 = 0;
	$c_time = "";
	$c_time_1 = "";
	$c_title = "";
	foreach ( $db_result_sg_only_first_hour as $item ) {
		if ( intval($c_time)+1 == intval(substr($item['SG_HF_TIME'], 11, 2)) and substr($c_title, 0, 6) == substr($item['SG_HF_CONT_TITEL'], 0, 6) ) {
			$z_rows_1 +=1;
		}
		$c_time = substr($item['SG_HF_TIME'], 11, 2);
		$c_title = $item['SG_HF_CONT_TITEL'];
		$z_rows +=1;
	}
	$a_sg_gesamt["TEst"] = $z_rows_1;
   
	// Webstream-Hoerer
	// Durchschnitt der max gleichzeitigen ermitteln	
	$date_today = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
	$c_query_condition = "SUBSTRING( ST_WEB_STREAM_LIS_DAY FROM 1 FOR 10 ) <= '".$date_today."' ORDER BY ST_WEB_STREAM_LIS_DAY";
	$db_result = db_query_list_items_1("ST_WEB_STREAM_LIS_DAY, ST_WEB_STREAM_LIS_NUMBER", "ST_WEB_STREAM_LISTENERS", $c_query_condition);
	$z_rows = 0;
	$z_listeners = 0;
	$max_listeners = 0;
	$year_current = date('Y');
	$year_stream = "x";
	foreach ( $db_result as $item ) {
		$z_rows +=1;
		// erstes Jahr fuer max Listener in jedem Jahr
		if ( $z_rows == 1 ) { 
			$year_stream = substr($item['ST_WEB_STREAM_LIS_DAY'], 0, 4); 
		}			
		$z_listeners += $item['ST_WEB_STREAM_LIS_NUMBER'];
		// max-wert ermitteln
		if ( $item['ST_WEB_STREAM_LIS_NUMBER'] > $max_listeners ) {
			$max_listeners = $item['ST_WEB_STREAM_LIS_NUMBER'];
		}
	}
	$a_sg_gesamt["Webstreamhörer/ Tag"] = $z_listeners/ $z_rows;
	$a_sg_gesamt["Webstreamhörer/ Tag max"] = $max_listeners;
	//$a_sg_gesamt["Webstreamhörer/fYear"] = $c_year;
	
	// Webstream nach Jahren
	$a_sg_stream = array();
	$max_listeners = 0;
	//Jahre durchgehen, jeweils max-Wert und dazugehoerigen Tag in array
	for ( $year_stream; $year_stream <= $year_current; $year_stream+=1) {
		$c_query_condition = "SUBSTRING( ST_WEB_STREAM_LIS_DAY FROM 1 FOR 4 ) = '".$year_stream."' ORDER BY ST_WEB_STREAM_LIS_DAY";
		$db_result = db_query_list_items_1("ST_WEB_STREAM_LIS_DAY, ST_WEB_STREAM_LIS_NUMBER", "ST_WEB_STREAM_LISTENERS", $c_query_condition);
		foreach ( $db_result as $item ) {
			$z_listeners += $item['ST_WEB_STREAM_LIS_NUMBER'];
			if ( $item['ST_WEB_STREAM_LIS_NUMBER'] > $max_listeners ) {
				$max_listeners = $item['ST_WEB_STREAM_LIS_NUMBER'];
				$max_listeners_date = substr($item['ST_WEB_STREAM_LIS_DAY'], 0, 10);            
			}
		}
		$a_sg_stream[$year_stream] = $max_listeners." (".get_date_format_deutsch($max_listeners_date).")" ;
		$max_listeners = 0;
		//$year_macher+=1;
		//$a_sg_stream[$year_stream] = "x";
	}	
	
	// Sendungen pro Quartal
	$tbl_rows_sg = db_query_list_items_1("SG_HF_TIME, SG_HF_FIRST_SG", "SG_HF_MAIN", "SG_HF_ID>1 ORDER BY SG_HF_TIME");
	$c_year = "x";
	$c_quartal = "x";
	$n_z_all = 0;	
	//$n_z_all_first_sg = 0;	
	$n_z_year = 0;
	$n_z_year_first_sg = 0;
	$n_z_quartal = 0;
	$n_z_quartal_first_sg = 0;
	foreach ( $tbl_rows_sg as $item ) {
		if ( $n_z_all==0 ) {
			// zu Beginn erstes Jahr und Quartal holen				
			$c_year = substr($item['SG_HF_TIME'], 0, 4);
			$c_month = substr($item['SG_HF_TIME'], 5, 2);
 			if ($c_month >="01" and $c_month <= "03" ) {
				$c_quartal = $c_year." I";
			} elseif ( $c_month >= "04" and $c_month <= "06") {
				$c_quartal = $c_year." II";
			} elseif ( $c_month >="07" and $c_month <= "09") {
				$c_quartal = $c_year." III";
			} elseif ( $c_month >="10" and $c_month <= "12") {
				$c_quartal = $c_year." IV";
			}  						
			//$n_z_year +=1; // zahelt sonst im ersten jahr eins zuviel, keene ahnung warum...
			$n_z_quartal +=1;
			$n_z_all +=1;
			if ( rtrim($item['SG_HF_FIRST_SG']) == "T" ) { 
				$n_z_quartal_first_sg += 1;
			}
			$a_sg_year = array($c_year=>$n_z_year." (ES: ".$n_z_year_first_sg);
			$a_sg_quartal = array($c_quartal=>$n_z_quartal." (ES: ");
		} else {
			// neues Jahr zaehlen
			$n_z_year +=1;
			if ( rtrim($item['SG_HF_FIRST_SG']) == "T" ) {
				$n_z_year_first_sg += 1;
			}
			$c_year_a = substr($item['SG_HF_TIME'], 0, 4);
			if ( $c_year_a != $c_year) {
				// durchgegangenes Jahr mit Anzahl in array registrieren 
				$a_sg_year[$c_year] = $n_z_year." (ES: ".$n_z_year_first_sg.")";
				$c_year = $c_year_a; 
				$n_z_year = 0;	
				$n_z_year_first_sg = 0;
			}

			// Quartal zaehlen
			$c_month = substr($item['SG_HF_TIME'], 5, 2);
			if ( $c_month >="01" and $c_month <= "03" ) {
				$c_quartal_a = $c_year." I";
			} elseif ( $c_month >= "04" and $c_month <= "06") {
				$c_quartal_a = $c_year." II";
			} elseif (	$c_month >="07" and $c_month <= "09") {
				$c_quartal_a = $c_year." III";
			} elseif (	$c_month >="10" and $c_month <= "12") {
				$c_quartal_a = $c_year." IV";
			} 				
			// neues Quartal, Zaehler zuruecksetzen
			if ( $c_quartal_a != $c_quartal) {               
				$a_sg_quartal[$c_quartal] = $n_z_quartal." (ES: ".$n_z_quartal_first_sg.")";
				$c_quartal = $c_quartal_a ;
				$n_z_quartal =0;
				$n_z_quartal_first_sg =0;	
			}
					
			$n_z_quartal +=1;
			$n_z_all +=1;
			if ( rtrim($item['SG_HF_FIRST_SG']) == "T" ) { 
				$n_z_quartal_first_sg += 1;
			}	 
		}
	}
	// "angefangenes" Jahr noch mit Anzahl registrieren
	$a_sg_year[$c_year_a] = $n_z_year." (ES: ".$n_z_year_first_sg.")";
	//$n_z_all = $n_z_all-1; // eins zuviel vom anfang wieder weg	

	return array($a_sg_gesamt, $a_sg_year, $a_sg_quartal, $a_sg_stream);   
}

/**
* statistik_ad
* Verleih
* 
* @return array
*
*/
function statistik_vl ()
{
	$tbl_rows_vl = db_query_list_items_1("VL_DATUM_START", "VL_MAIN", "VL_ID>1 ORDER BY VL_DATUM_START");
	$c_year = "x";
	$c_quartal = "x";
	$n_z_all = 0;	
	$n_z_year = 0;
	$n_z_quartal = 0;
	foreach ( $tbl_rows_vl as $item ) {
		if ( $n_z_all==0 ) {
			// zu Beginn erstes Jahr und Quartal holen				
			$c_year = substr($item['VL_DATUM_START'], 0, 4);
			$c_month = substr($item['VL_DATUM_START'], 5, 2);
			if ( $c_month >="01" and $c_month <= "03" ) {
				$c_quartal = $c_year." I";
			} elseif ( $c_month >= "04" and $c_month <= "06") {
				$c_quartal = $c_year." II";
			} elseif ( $c_month >="07" and $c_month <= "09") {
				$c_quartal = $c_year." III";
			} elseif ( $c_month >="10" and $c_month <= "12") {
				$c_quartal = $c_year." IV";
			}
					 		
			//$n_z_year +=1; // zahelt sonst im ersten jahr eins zuviel, keene ahnung warum...
			$n_z_quartal +=1;
			$n_z_all +=1;
			$a_year = array($c_year=>$n_z_year);
			$a_quartal = array($c_quartal=>$n_z_quartal);
		} else {
			// neues Jahr zaehlen
			$n_z_year +=1;
			$c_year_a = substr($item['VL_DATUM_START'], 0, 4);
			if ( $c_year_a != $c_year) {
				// durchgegangenes Jahr mit Anzahl in array registrieren 
				$a_year[$c_year] = $n_z_year;
				$c_year = $c_year_a; 
				$n_z_year = 0;	
			}

			// Quartal zaehlen
			$c_month = substr($item['VL_DATUM_START'], 5, 2);
			if ( $c_month >="01" and $c_month <= "03" ) {
				$c_quartal_a = $c_year." I";
			} elseif ( $c_month >= "04" and $c_month <= "06") {
				$c_quartal_a = $c_year." II";
			} elseif (	$c_month >="07" and $c_month <= "09") {
				$c_quartal_a = $c_year." III";
			} elseif ( $c_month >="10" and $c_month <= "12") {
				$c_quartal_a = $c_year." IV";
			}				
			// neues Quartal, Zaehler zuruecksetzen
			if ( $c_quartal_a != $c_quartal) {
				$a_quartal[$c_quartal] = $n_z_quartal;
				$c_quartal = $c_quartal_a ;
				$n_z_quartal =0;	
			}
					
			$n_z_quartal +=1;
			$n_z_all +=1;	 
		}
	}
		
	// "angefangenes" Jahr noch mit Anzahl registrieren
	$a_year[$c_year_a] = $n_z_year;
	$n_z_all = $n_z_all-1; // eins zuviel vom anfang wieder weg
			
	return array($a_year, $a_quartal, $n_z_all);
}

?>
