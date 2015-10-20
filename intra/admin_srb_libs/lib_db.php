<?php

/** 
* library for db-functions 
*
* PHP version 5
*
* @category Intranetsite
* @package  Admin-SRB
* @author   Joerg Sorge <joergsorge@googel.com>
* @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link     http://srb.fm
*/

require "admin_srb_conf.php";

/**
* user_login
*
* Zugangsbeschraenkung
* LogIn-Name und Passwort ueberpruefen, und aus Tabelle zurueckgeben
*
* @param c_log_name $c_log_name Name
* @param c_log_pass $c_log_pass PW
* 
* @return row with userdata
*
*/	
function user_login( $c_log_name, $c_log_pass ) 
{
	$db_connect = db_connect();
	$sql = ibase_prepare($db_connect, "SELECT * FROM USER_ACCOUNT WHERE USER_NAME_SHORT=? AND USER_PW = ?");
 	$db_result = ibase_execute($sql, $c_log_name, md5($c_log_pass));
 	ibase_close($db_connect);
 	if ( !$db_result ) { 
 		echo "<br>Error executing query: user_login!";  
 		exit;
 	}
	$tbl_row = ibase_fetch_assoc($db_result); 
	return $tbl_row;	
}

/**
* db_fetch_in_array
*
* Hilfsfunktion
* 
* @param db_result $db_result Resultset
* 
* @return Array 
*
*/	
function db_fetch_in_array( $db_result ) 
{
	while ( $tbl_row = ibase_fetch_assoc($db_result)) {
		$a[]=	$tbl_row;
	}
	if (! isset($a)) { 
		$a = false;
	}
	return $a;
}

/**
* db_call_last_id
*
* Letzte ID und eins drauf  
* 
* @param c_table     $c_table     db-Tabelle
* @param c_field     $c_field     Feld
* @param c_condition $c_condition Bedingung
* 
* @return ID 
*
*/	
function db_call_last_id( $c_table, $c_field, $c_condition ) 
{
	$result_id = db_query_load_fieldvalue_by_condition($c_table, $c_field, $c_condition);
	$result_field_id = $result_id +1;
	return $result_field_id;
}

/**
* db_query_count_rows
*
* Datensaetze zaehlen   
* 
* @param c_table     $c_table     db-Tabelle
* @param c_condition $c_condition Bedingung
* 
* @return number 
*
*/			
function db_query_count_rows( $c_table, $c_condition ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT COUNT (*) FROM " .$c_table;
	$db_query 	.= " WHERE " .$c_condition;
	$db_result = ibase_query($db_connect, $db_query);
	if ( !$db_result ) {
		echo "Error executing: db_query_count_rows!";
		ibase_close($db_connect);
		exit;
	}
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	return $tbl_row[0];	
}

/**
* db_query_load_value_by_id
*
* Inhalt eines einzelnen Feldes   
* 
* @param c_table $c_table db-Tabelle
* @param c_field $c_field Feld
* @param c_id    $c_id    Bedingung
* 
* @return value 
*
*/
function db_query_load_value_by_id( $c_table, $c_field, $c_id ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT * FROM " .$c_table; 
	$db_query 	.= " WHERE " .$c_field. " = '" .$c_id. "'";
	$db_result = ibase_query($db_connect, $db_query);
	if ( !$db_result ) {
		echo "Error executing:db_query_load_value_by_id!";
		ibase_close($db_connect);
		exit;
	}
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	return $tbl_row[1];
}

/**
* db_query_load_value_n_by_id
*
* Liefert Inhalt eines bestimmten Feldes  
* 
*
* @param c_table    $c_table    db-Tabelle
* @param c_field    $c_field    Felder
* @param c_id       $c_id       Bedingung
* @param n_filed_nr $n_filed_nr Feld-Nr
* 
* @return value 
*
*/
function db_query_load_value_n_by_id( $c_table, $c_field, $c_id, $n_filed_nr ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT * FROM " .$c_table;
	$db_query 	.= " WHERE " .$c_field. " = '" .$c_id."'";
	$db_result = ibase_query($db_connect, $db_query);
	if ( !$db_result ) {
		echo "Error executing: db_query_load_value_n_by_id!";
		ibase_close($db_connect);
		exit;
	}
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	return $tbl_row[$n_filed_nr]; 
}

/**
* db_query_load_id_by_value
*
* Load ID  
* fuer LookUps
*
* @param c_table $c_table db-Tabelle
* @param c_field $c_field Felder
* @param c_value $c_value Bedingung
* 
* @return ID 
*
*/
function db_query_load_id_by_value( $c_table, $c_field, $c_value ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT * FROM " .$c_table; 
	$db_query .= " WHERE " .$c_field. " = '" .rtrim($c_value). "'";
	$db_result = ibase_query($db_connect, $db_query);
	if ( !$db_result ) {
		echo "Error executing: db_query_load_id_by_value!";
		ibase_close($db_connect);
		exit;
	}
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	// erstes feld ist id
	return rtrim($tbl_row[0]);
}

/**
* db_query_load_item
*
* Load item  
* nur für Tabellen mit einer row
*
* @param c_table    $c_table    db-Tabelle
* @param n_field_nr $n_field_nr Feld-(Nr) die benoetigt wird
* 
* @return value des x.feldes
*
*/
function db_query_load_item( $c_table, $n_field_nr ) 
{
	// 
	$db_connect = db_connect();
	$db_query 	= "SELECT * FROM " .$c_table;
	$db_result = ibase_query($db_connect, $db_query);
	if ( !$db_result ) {
		echo "Error executing: db_query_load_item!";
		ibase_close($db_connect);
		exit;
	}
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	// erstes feld!
	return $tbl_row[$n_field_nr];
}

/**
* db_query_load_items_sort_by_desc
*
* Loading sorted rowset  
*
* @param c_table $c_table db-Tabelle
* @param c_field $c_field Feld nach dem sortiert wird
* 
* @return db-result
*
*/
function db_query_load_items_sort_by_desc( $c_table, $c_field ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT * FROM " .$c_table ;
	$db_query 	.= " ORDER BY " .$c_field;
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_load_items_sort_by_desc!";
		exit;
	}
	return $db_result ;
}

/**
* db_query_display_item_1
*
* Display item 
*
* @param c_table     $c_table     db-Tabelle
* @param c_condition $c_condition Value
* 
* @return result of row
*
*/
function db_query_display_item_1( $c_table, $c_condition ) 
{
	$db_connect = db_connect();
	// wenn condition uebergeben mit, sonst ohne
	if ($c_condition != "none" ) {
		$db_query = "SELECT * FROM ".$c_table." WHERE ".$c_condition;
	} else { 
		$db_query 	= "SELECT * FROM ".$c_table;
	}	
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_display_item_1!" ;
		exit;
	}
	$tbl_row = ibase_fetch_object($db_result);
	return $tbl_row;
}

/**
* db_query_load_fieldvalue_by_condition
*
* Load one Filedvalue of a Row 
*
* @param c_table     $c_table     db-Tabelle
* @param c_field     $c_field     Tabelle Feld
* @param c_condition $c_condition Value
* 
* @return Value
*
*/
function db_query_load_fieldvalue_by_condition( $c_table, $c_field, $c_condition ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT ".$c_field." FROM " .$c_table;
	$db_query 	.= " WHERE " .$c_condition;
	$db_result = ibase_query($db_connect, $db_query);
	if ( !$db_result ) {
		echo "Error executing: db_query_load_fieldvalue_by_condition!";
		ibase_close($db_connect);
		exit;
	}
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	// erstes feld ist $c_field
	return $tbl_row[0];
}

/**
* db_query_display_item_from_one_row_table_1
*
* Display item
*
* @param c_table $c_table db-Tabelle
* 
* @return Object with row
*
*/
function db_query_display_item_from_one_row_table_1( $c_table ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT * FROM " .$c_table;
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_display_item_from_one_row_table_1!";
		exit;
	}
	$tbl_row = ibase_fetch_object($db_result);
	return $tbl_row;
}

/**
* db_query_list_items_1
*
* items listen
*
* @param c_fields    $c_fields    Tabelle Felder
* @param c_table     $c_table     db-Tabelle
* @param c_condition $c_condition Values
* 
* @return Array with result
*
*/
function db_query_list_items_1( $c_fields, $c_table, $c_condition ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT ".$c_fields." FROM ".$c_table;
	$db_query 	.= " WHERE " .$c_condition;
	$db_result  = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_list_items_1!";
		exit;
	}
	while ( $tbl_row = ibase_fetch_assoc($db_result)) {
		$a[]=	$tbl_row;
	}
	if (! isset($a)) { 
		$a = false;
	}
	return $a;
}

/**
* db_log_query_list_items_1
*
* items listen
*
* @param c_fields    $c_fields    Tabelle Felder
* @param c_table     $c_table     db-Tabelle
* @param c_condition $c_condition Values
* 
* @return Array with result
*
*/
function db_log_query_list_items_1( $c_fields, $c_table, $c_condition ) 
{
	$db_connect = db_log_connect();
	$db_query 	= "SELECT ".$c_fields." FROM ".$c_table;
	$db_query 	.= " WHERE " .$c_condition;
	$db_result  = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_log_query_list_items_1!";
		exit;
	}
	while ( $tbl_row = ibase_fetch_assoc($db_result)) {
		$a[]=	$tbl_row;
	}
	if (! isset($a)) { 
		$a = false;
	}
	return $a;
}

/**
* db_query_list_items_limit_1
*
* items listen
*
* @param c_fields    $c_fields    Tabelle Felder
* @param c_table     $c_table     db-Tabelle
* @param c_condition $c_condition Values
* @param c_limit     $c_limit     Limit
* 
* @return Array with result
*
*/
function db_query_list_items_limit_1( $c_fields, $c_table, $c_condition, $c_limit ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT ".$c_limit." ".$c_fields." FROM ".$c_table;
	$db_query 	.= " WHERE " .$c_condition;
	$db_res  = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_res ) {
		echo "Error executing: db_query_list_items_limit_1!";
		exit;
	}
	$db_result = db_fetch_in_array($db_res);
	return $db_result;   
}

/**
* db_query_add_item
*
* item zufuegen
* ohne prepare: keine sonderzeichen in values erlaubt
*
* @param c_table  $c_table  db-Tabelle
* @param c_fields $c_fields Tabelle Felder
* @param c_values $c_values Values
* 
* @return true or false
*
*/
function db_query_add_item( $c_table, $c_fields, $c_values ) 
{
	$db_connect = db_connect();
	$db_query 	= "INSERT INTO ".$c_table." ( ".$c_fields." ) VALUES ( ".$c_values." )";
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_add_item!";
		exit;
	}
	return $db_result;
}

/**
* db_query_add_item_b
*
* item zufuegen
*
* @param c_table  $c_table  db-Tabelle
* @param c_fields $c_fields Tabelle Felder
* @param params   $params   Parameter
* @param a_values $a_values Array mit values
* 
* @return true or false or result-set
*
*/
function db_query_add_item_b( $c_table, $c_fields, $params, $a_values ) 
{
	$db_connect = db_connect();
	$db_query = "INSERT INTO ".$c_table." ( ".$c_fields." ) VALUES ( ".$params." )";
	$db_stmt = ibase_prepare($db_connect, $db_query);
	if ( !is_array($a_values)) {
		$db_result = ibase_execute($db_stmt, $a_values);
	} else {
		array_unshift($a_values, $db_stmt);
		$db_result = call_user_func_array('ibase_execute', $a_values);			
	}
	ibase_close($db_connect);		
	if ( !$db_result ) {
		echo "Error executing: db_query_add_item_b ";
		exit;
	}
	return $db_result;
}

/**
* db_query_update_item
*
* item loeschen
* ohne prepare: keine sonderzeichen in values erlaubt
*
* @param c_table         $c_table         db-Tabelle
* @param c_field_id      $c_field_id      ID-Feldbezeichnung
* @param c_id            $c_id            ID
* @param c_fields_values $c_fields_values Tabelle Felder/Werte
* 
* @return true or false or result-set
*
*/
function db_query_update_item( $c_table, $c_field_id, $c_id, $c_fields_values ) 
{
	$db_connect = db_connect();
	$db_query 	= "UPDATE ".$c_table." SET ".$c_fields_values." WHERE ".$c_field_id." = ".$c_id;
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_update_item!";
		exit;
	}
	return $db_result;
}

/**
* db_query_update_item_a
*
* item loeschen
* ohne prepare: keine sonderzeichen in values erlaubt
*
* @param c_table         $c_table         db-Tabelle
* @param c_fields_values $c_fields_values Tabelle Felder/Werte
* @param c_condition     $c_condition     sql-query
* 
* @return true or false or result-set
*
*/
function db_query_update_item_a( $c_table, $c_fields_values, $c_condition ) 
{
	$db_connect = db_connect();
	$db_query 	= "UPDATE ".$c_table." SET ".$c_fields_values." WHERE ".$c_condition;
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_update_item_a!";
		exit;
	}
	return $db_result;
}

/**
* db_query_update_item_b
*
* item loeschen
*
* @param c_table         $c_table         db-Tabelle
* @param c_fields_params $c_fields_params Tabelle Felder/Parameter
* @param c_condition     $c_condition     sql-query
* @param $a_values       $a_values        Array Tabelle Feldwerte
*
* @return true or false or result-set
*
*/
function db_query_update_item_b( $c_table, $c_fields_params, $c_condition, $a_values ) 
{
	$db_connect = db_connect();
	$db_query = "UPDATE ".$c_table." SET ".$c_fields_params." WHERE ".$c_condition ;
	$db_stmt = ibase_prepare($db_connect, $db_query);
	if ( !is_array($a_values)) {
		$db_result = ibase_execute($db_stmt, $a_values);
	} else {
		array_unshift($a_values, $db_stmt);
		$db_result = call_user_func_array('ibase_execute', $a_values);			
	}		
	ibase_close($db_connect);
	if ( !$db_result ) {
  		echo 'Error executing: db_query_update_item_b';
  		exit;
  	} 
	return $db_result;
}

/**
* db_query_delete_item
*
* item loeschen
*
* @param c_table    $c_table    db-Tabelle
* @param c_field_id $c_field_id Tabelle ID-Feld
* @param n_id       $n_id       Item-ID
*
* @return "true" if success
*
*/
function db_query_delete_item( $c_table, $c_field_id, $n_id ) 
{
	$db_connect = db_connect();
	$db_query 	= "DELETE FROM ".$c_table." WHERE ".$c_field_id." = ".$n_id;
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_delete_item!";
		exit;
	}
	return "true";
}

/**
* html_dropdown_from_table_1
*
* Dropdowns
* Nachschlagetabelle enthaelt Felder in Reihenfolge id, desc
* Zugriff auf id hier ueber Feld $tbl_row[0]
* alles html wird in variable "gesammelt"
* weil direkte echo-Ausgabe zu falscher Platzierung in aufrufender Seite fuehrt
* keine Ahnung warum
*
* @param c_table_lookup         $c_table_lookup         db-Tabelle
* @param c_lookup_field_desc    $c_lookup_field_desc    Tabelle-Feld
* @param c_select_name          $c_select_name          html-dropdown Name
* @param c_select_class         $c_select_class         html-dropdown Klasse
* @param db_table_main_field_id $db_table_main_field_id id fuer preselected item 
*
* @return html mit Adresse
*
*/
function html_dropdown_from_table_1( $c_table_lookup, $c_lookup_field_desc, $c_select_name, $c_select_class, $db_table_main_field_id ) 
{
	$db_connect = db_connect();
	$db_result = db_query_load_items_sort_by_desc($c_table_lookup, $c_lookup_field_desc);

	if ( !$db_result )	{
		echo "Error executing: html_dropdown_from_table_1!";
		ibase_close($db_connect);
		exit;
	}
	$drop_down = "<select name='".$c_select_name."' class='".$c_select_class."' size='1'>";
	while ( $tbl_row = ibase_fetch_row($db_result)) {
		if (trim($tbl_row[0]) == $db_table_main_field_id) { 
	     	$drop_down = $drop_down."<option selected='selected'>".$tbl_row[1]."</option>";
		} else {
			$drop_down = $drop_down."<option>".$tbl_row[1]."</option>";	
		}
	}
	$drop_down = $drop_down."</select>";
	ibase_close($db_connect);			
	return $drop_down;
}

/**
* html_dropdown_from_table_1_a
*
* Dropdowns
* Nachschlagetabelle enthaelt Felder in Reihenfolge id, desc
* Zugriff auf id hier ueber Feld $tbl_row[0]
* alles html wird in variable "gesammelt"
* weil direkte echo-Ausgabe zu falscher Platzierung in aufrufender Seite fuehrt
* keine Ahnung warum
*
* @param c_table_lookup         $c_table_lookup         db-Tabelle
* @param c_lookup_field_desc    $c_lookup_field_desc    Tabelle-Feld
* @param c_select_name          $c_select_name          html-dropdown Name
* @param c_select_class         $c_select_class         html-dropdown Klasse
*
* @return html mit Adresse
*
*/
function html_dropdown_from_table_1_a( $c_table_lookup, $c_lookup_field_desc, $c_select_name, $c_select_class ) 
{
	$db_connect = db_connect();
	$db_result = db_query_load_items_sort_by_desc($c_table_lookup, $c_lookup_field_desc);

	if ( !$db_result )	{
		echo "Error executing: html_dropdown_from_table_1_a!";
		ibase_close($db_connect);
		exit;
	}
	$drop_down = "<select name='".$c_select_name."' class='".$c_select_class."' size='1'>";
	$drop_down = $drop_down."<option> </option>";	
	while ( $tbl_row = ibase_fetch_row($db_result)) {
		$drop_down = $drop_down."<option>".$tbl_row[1]."</option>";	
	}
	$drop_down = $drop_down."</select>";
	ibase_close($db_connect);			
	return $drop_down;
}

/**
* html_header_srb_print_a
*
* Adresskopf fuer Ausdruck
*
* @param c_head_text $c_head_text Ueberschrift 
*
* @return html mit Adresse
*
*/
function html_header_srb_print_a( $c_head_text ) 
{
	echo "<div class='head_item_a_1'>";
	echo "<img src='../../parts/pict/logo_user.jpg' width='150' height='60' alt='Logo'></div>";

	$db_query 	= "SELECT * FROM USER_DATA ";
	$db_connect = db_connect();
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: html_header_srb_print_a!";
		exit;
	}

	$tbl_row = ibase_fetch_object($db_result);	
	echo "<div class='head_item_a_4'>";
	echo $tbl_row->USER_AD_NAME. " <br>";
	echo $tbl_row->USER_AD_STR. " <br>";
	echo $tbl_row->USER_AD_PLZ. " " .$tbl_row->USER_AD_ORT. "<br>";
	echo "<br> <br></div>";
	echo "<div class='head_item_a_3'>".$c_head_text."<br>&nbsp;<br></div>";
	return;
}

/**
* html_header_srb_print_b
*
* Adresskopf fuer Ausdruck
*
* @return html mit Adresse
*
*/
function html_header_srb_print_b() 
{
	$db_query 	= "SELECT * FROM USER_DATA";
	$db_connect = db_connect();
	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: html_header_srb_print_b!";
		exit;
	}

	$tbl_row = ibase_fetch_object($db_result);	
	echo "<div class='head_adress'>";
	echo $tbl_row->USER_AD_NAME. " <br>";
	echo $tbl_row->USER_AD_STR. " <br>";
	echo $tbl_row->USER_AD_PLZ. " " .$tbl_row->USER_AD_ORT. "<br>";
	echo "</div>";
	return;
}

/**
* db_generator_main_id_load_value
*
* Main-ID hoechste ID ermitteln
*
* @return id
*
*/
function db_generator_main_id_load_value() 
{
	$db_connect = db_connect();
	$db_result = ibase_query($db_connect, 'SELECT GEN_ID(GENERATOR_MAIN_ID, 1) FROM "RDB$DATABASE"');
	if ( !$db_result ) {
		echo "Error executing: db_generator_main_id_load_value!";
		ibase_close($db_connect);
		exit;
	} 
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	return $tbl_row[0];
}

/**
* db_query_sg_load_year_by_id
*
* Sendung Jahr ermitteln, z.B. fuer Archiv-Pfade
*
* @param c_id $c_id sql-query-condition 
*
* @return year
*
*/
function db_query_sg_load_year_by_id( $c_id ) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT SG_HF_ID, SG_HF_TIME FROM SG_HF_MAIN";
	$db_query 	.= " WHERE SG_HF_ID = '" .$c_id. "'";
	$db_result = ibase_query($db_connect, $db_query);
	if ( !$db_result ) {
		echo "Error executing: db_query_sg_load_year_by_id!";
		ibase_close($db_connect);
		exit;
	}
	$tbl_row = ibase_fetch_row($db_result);
	ibase_close($db_connect);
	return substr($tbl_row[1], 0, 4); 
}

/**
* db_query_sg_display_item_1
*
* Sendung Item 
*
* @param n_id $n_id sql-query-condition 
*
* @return object with tablerow
*
*/
function db_query_sg_display_item_1( $n_id ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, "
		."A.SG_HF_DURATION, A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_PODCAST, "
	 	."A.SG_HF_VP_OUT, A.SG_HF_SOURCE_ID, A.SG_HF_FIRST_SG, A.SG_HF_ON_AIR, "
	 	."A.SG_HF_REPEAT_PROTO, A.SG_HF_LIVE, "
    	."B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, "
    	."B.SG_HF_CONT_GENRE_ID, B.SG_HF_CONT_SPEECH_ID, B.SG_HF_CONT_TITEL, " 
    	."B.SG_HF_CONT_UNTERTITEL, B.SG_HF_CONT_STICHWORTE, "
    	."B.SG_HF_CONT_REGIEANWEISUNG, B.SG_HF_CONT_FILENAME, "
    	."B.SG_HF_CONT_TEAMPRODUCTION, B.SG_HF_CONT_WEB, B.SG_HF_CONT_EDITOR_AD_ID "
    	. "FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
	 	."ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
	 	."WHERE A.SG_HF_ID = ".$n_id;

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_sg_display_item_1!";
		exit;
	}
	$tbl_row = ibase_fetch_object($db_result);
	return $tbl_row;
}

/**
* db_query_sg_tv_display_item
*
* Sendung TV Item 
*
* @param n_id $n_id sql-query-condition 
*
* @return object with tablerow
*
*/
function db_query_sg_tv_display_item( $n_id ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT A.SG_TV_NR, A.SG_TV_CONT_NR, A.SG_TV_TIME, A.SG_TV_DURATION, A.SG_TV_INFOTIME, A.SG_TV_FIRST_SG, A.SG_TV_ON_AIR, "
	."B.SG_TV_CONT_ID, B.SG_TV_CONT_NR, B.SG_TV_CONT_AD_NR, B.SG_TV_CONT_GENRE_ID, B.SG_TV_CONT_SPEECH, B.SG_TV_CONT_TITEL, B.SG_TV_CONT_UNTERTITEL, "
	."B.SG_TV_CONT_STICHWORTE, B.SG_TV_CONT_REGIEANWEISUNG, B.SG_TV_CONT_FILENAME, B.SG_TV_CONT_TEAMPRODUCTION, B.SG_TV_CONT_WEB, "
	."B.SG_TV_CONT_CARRIER_NR, B.SG_TV_CONT_AR_TC_BEGIN, B.SG_TV_CONT_AR_TC_END, "
	."C.AD_NR, C.AD_VORNAME, C.AD_NAME, C.AD_ORT "
	."FROM SG_TV_MAIN A LEFT JOIN SG_TV_CONTENT B "
	."ON A.SG_TV_CONT_NR = B.SG_TV_CONT_NR "
	."LEFT JOIN AD_MAIN C "
	."ON B.SG_TV_CONT_AD_NR = C.AD_NR " 
	."WHERE A.SG_TV_NR = '".$n_id."'";

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_sg_tv_display_item!";
		exit;
	}
	$tbl_row_sg = ibase_fetch_object($db_result);
	return $tbl_row_sg;
}

/**
* db_query_sg_tv_display_item_a
*
* Sendung TV Liste 
*
* @param condition $condition sql-query-condition 
*
* @return object with tablerow
*
*/
function db_query_sg_tv_display_item_a( $condition ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT A.SG_TV_NR, A.SG_TV_CONT_NR, A.SG_TV_TIME, A.SG_TV_DURATION, A.SG_TV_INFOTIME, A.SG_TV_FIRST_SG, A.SG_TV_ON_AIR, "
	."B.SG_TV_CONT_NR, B.SG_TV_CONT_AD_NR, "
	."C.AD_NR, C.AD_VORNAME, C.AD_NAME, C.AD_ORT "
	."FROM SG_TV_MAIN A LEFT JOIN SG_TV_CONTENT B "
	."ON A.SG_TV_CONT_NR = B.SG_TV_CONT_NR "
	."LEFT JOIN AD_MAIN C "
	."ON B.SG_TV_CONT_AD_NR = C.AD_NR " 
	."WHERE ".$condition;

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_sg_tv_display_item_a!";
		exit;
	}
	
	$tbl_row_sg = ibase_fetch_object($db_result);
	return $tbl_row_sg;
}

/**
* db_query_sg_tv_display_item_b
*
* Sendung TV Liste 
*
* @param condition $condition sql-query-condition 
*
* @return object with tablerow
*
*/
function db_query_sg_tv_display_item_b( $condition ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT A.SG_TV_CONT_NR, A.SG_TV_CONT_AD_NR, A.SG_TV_CONT_GENRE_ID, A.SG_TV_CONT_SPEECH, A.SG_TV_CONT_TITEL, A.SG_TV_CONT_UNTERTITEL, "
	."A.SG_TV_CONT_STICHWORTE, A.SG_TV_CONT_REGIEANWEISUNG, A.SG_TV_CONT_FILENAME, A.SG_TV_CONT_TEAMPRODUCTION, A.SG_TV_CONT_WEB, "
	."A.SG_TV_CONT_CARRIER_NR, A.SG_TV_CONT_AR_TC_BEGIN, A.SG_TV_CONT_AR_TC_END, "
	."B.AD_NR, B.AD_VORNAME, B.AD_NAME, B.AD_ORT "
	."FROM SG_TV_CONTENT A "
	."LEFT JOIN AD_MAIN B "
	."ON A.SG_TV_CONT_AD_NR = B.AD_NR " 
	."WHERE ".$condition;

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_sg_tv_display_item_b!";
		exit;
	}	
	$tbl_row_sg = ibase_fetch_object($db_result);
	return $tbl_row_sg;	
}

/**
* db_query_sg_ad_list_items_1
*
* Sendung Liste mit Adressdaten  
*
* @param c_condition $c_condition sql-query-condition 
*
* @return array with tablerows
*
*/
function db_query_sg_ad_list_items_1( $c_condition ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, A.SG_HF_DURATION, A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_SOURCE_ID, A.SG_HF_FIRST_SG, A.SG_HF_ON_AIR, "
    	."B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, B.SG_HF_CONT_GENRE_ID, B.SG_HF_CONT_SPEECH_ID, B.SG_HF_CONT_TITEL, B.SG_HF_CONT_UNTERTITEL, B.SG_HF_CONT_STICHWORTE, B.SG_HF_CONT_REGIEANWEISUNG, B.SG_HF_CONT_FILENAME, B.SG_HF_CONT_TEAMPRODUCTION, B.SG_HF_CONT_WEB, "
		."C.AD_ID, C.AD_VORNAME, C.AD_NAME "
    	."FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
		."ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
		."LEFT JOIN AD_MAIN C "
		."ON B.SG_HF_CONT_AD_ID = C.AD_ID "
		."WHERE ".$c_condition;

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_sg_ad_list_items_limit_1!";
		exit;
	}

	while ( $tbl_row = ibase_fetch_assoc($db_result)) {
		$a[]=	$tbl_row;
	}
	if (! isset($a)) { 
		$a = false;
	}
	return $a;
}

/**
* db_query_sg_ad_list_items_limit_1
*
* Sendung Liste mit Adressdaten  
*
* @param c_condition $c_condition sql-query-condition 
* @param c_limit     $c_limit     e.g. "FIRST 25"
*
* @return array with tablerows
*
*/
function db_query_sg_ad_list_items_limit_1( $c_condition, $c_limit ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT ".$c_limit." A.SG_HF_ID, A.SG_HF_CONTENT_ID, A.SG_HF_TIME, A.SG_HF_DURATION, A.SG_HF_INFOTIME, A.SG_HF_MAGAZINE, A.SG_HF_SOURCE_ID, A.SG_HF_FIRST_SG, A.SG_HF_ON_AIR, "
    	."B.SG_HF_CONT_ID, B.SG_HF_CONT_SG_ID, B.SG_HF_CONT_AD_ID, B.SG_HF_CONT_GENRE_ID, B.SG_HF_CONT_SPEECH_ID, B.SG_HF_CONT_TITEL, B.SG_HF_CONT_UNTERTITEL, B.SG_HF_CONT_STICHWORTE, B.SG_HF_CONT_REGIEANWEISUNG, B.SG_HF_CONT_FILENAME, B.SG_HF_CONT_TEAMPRODUCTION, B.SG_HF_CONT_WEB, "
		."C.AD_ID, C.AD_VORNAME, C.AD_NAME "
    	."FROM SG_HF_MAIN A LEFT JOIN SG_HF_CONTENT B "
		."ON A.SG_HF_CONTENT_ID = B.SG_HF_CONT_ID "
		."LEFT JOIN AD_MAIN C "
		."ON B.SG_HF_CONT_AD_ID = C.AD_ID "
		."WHERE ".$c_condition;

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_sg_ad_list_items_limit_1!";
		exit;
	}

	while ( $tbl_row = ibase_fetch_assoc($db_result)) {
		$a[]=	$tbl_row;
	}
	if (! isset($a)) { 
		$a = false;
	}
	return $a;
}

/**
* db_query_vl_ad_list_items_limit
*
* Verleih Liste mit Adresse  
*
* @param c_condition $c_condition sql-query-condition 
* @param c_limit     $c_limit     Limit Datensaetze
*
* @return result with tablerows
*
*/
function db_query_vl_ad_list_items_limit($c_condition, $c_limit) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT "
		.$c_limit
		." A.VL_ID, A.VL_AD_ID, A.VL_DATUM_START, A.VL_DATUM_END, A.VL_PROJEKT, A.VL_TEXT, "
		."B.AD_ID, B.AD_VORNAME, B.AD_NAME "
		."FROM VL_MAIN A "
		."LEFT JOIN AD_MAIN B "
		."ON A.VL_AD_ID = B.AD_ID "
		. " WHERE " .$c_condition;
	$db_res  = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_res ) {
		echo "Error executing: db_query_vl_list_items_ad_limit!";
		exit;
	}
	$db_result = db_fetch_in_array($db_res);
	return $db_result;   
}

/**
* db_query_vl_ad_list_items_limit
*
* Verleih Liste mit Adresse  
*
* @param c_condition $c_condition sql-query-condition 
* @param c_limit     $c_limit     Limit Datensaetze
*
* @return result with tablerows
*
*/
function db_query_vl_item_ad_iv_list_items_limit($c_condition, $c_limit) 
{
	$db_connect = db_connect();
	$db_query 	= "SELECT "
		.$c_limit
		." A.VL_ITEM_ID, A.VL_MAIN_ID, A.VL_ITEM_IV_ID, A.VL_ITEM_IV_BACK, "
		."B.VL_ID, B.VL_AD_ID, B.VL_DATUM_START, B.VL_DATUM_END, B.VL_PROJEKT, B.VL_TEXT, "
		."C.AD_ID, C.AD_VORNAME, C.AD_NAME, "
		. "D.IV_OBJEKT "
		."FROM VL_ITEMS A "
		."LEFT JOIN VL_MAIN B "
		."ON A.VL_MAIN_ID = B.VL_ID "
		."LEFT JOIN AD_MAIN C "
		."ON B.VL_AD_ID = C.AD_ID "
		."LEFT JOIN IV_MAIN D "
		."ON A.VL_ITEM_IV_ID = D.IV_ID "
		. " WHERE " .$c_condition;
	$db_res  = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_res ) {
		echo "Error executing: db_query_vl_item_ad_iv_list_items_limit!";
		exit;
	}
	$db_result = db_fetch_in_array($db_res);
	return $db_result;   
}

/**
* db_query_vl_iv_list_item_items
*
* Verleih Inventar Objektliste  
*
* @param c_condition $c_condition sql-query-condition 
*
* @return array with tablerows
*
*/
function db_query_vl_iv_list_item_items( $c_condition ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT "
    ."A.VL_ITEM_ID, A.VL_MAIN_ID, A.VL_ITEM_IV_ID, A.VL_ITEM_DATUM_START, A.VL_ITEM_IV_KAT_ID, VL_ITEM_IV_BACK, "
    ."B.IV_ID, B.IV_OBJEKT, B.IV_TYP, B.IV_VERLIEHEN "
    ."FROM VL_ITEMS A "
    ."LEFT JOIN IV_MAIN B "
	 ."ON A.VL_ITEM_IV_ID = B.IV_ID "
	 ."WHERE ".$c_condition;

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_vl_iv_list_item_items!";
		exit;
	}
	
	while ( $tbl_row = ibase_fetch_assoc($db_result)) {
		$a[]=	$tbl_row;
	}
	if (! isset($a)) { 
		$a = false;
	}
	return $a;
}

/**
* db_query_vl_iv_list_kat_items
*
* Verleih Inventar Kategorieliste mit zug. Objekten   
*
* @param c_condition $c_condition sql-query-condition 
*
* @return array with tablerows
*
*/
function db_query_vl_iv_list_kat_items( $c_condition ) 
{
	$db_connect = db_connect();
	$db_query = "SELECT "
    ."A.VL_ITEM_ID, A.VL_MAIN_ID, A.VL_ITEM_IV_ID, A.VL_ITEM_DATUM_START, A.VL_ITEM_IV_KAT_ID, A.VL_ITEM_IV_BACK, "
    ."B.IV_ID, B.IV_OBJEKT, B.IV_TYP, B.IV_VERLIEHEN, "
    ."C.IV_KAT_ID, C.IV_KAT_DESC "
    ."FROM VL_ITEMS A "
    ."LEFT JOIN IV_MAIN B "
	 ."ON A.VL_ITEM_IV_ID = B.IV_ID "
	 ."LEFT JOIN IV_KATEGORIE C "
	 ."ON A.VL_ITEM_IV_KAT_ID = C.IV_KAT_ID "
	 ."WHERE ".$c_condition;

	$db_result = ibase_query($db_connect, $db_query);
	ibase_close($db_connect);
	if ( !$db_result ) {
		echo "Error executing: db_query_vl_iv_list_kat_items!";
		exit;
	}
	while ( $tbl_row = ibase_fetch_assoc($db_result)) {
		$a[]=	$tbl_row;
	}

	if (! isset($a)) { 
		$a = false;
	}
	return $a;
}

?>
