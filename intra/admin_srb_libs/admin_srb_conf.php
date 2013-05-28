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

/**
* db_connect
* data-db
*
* @return db-connect
*
*/	
function db_connect()
{
	$db_host = "";// FB-SQL-Host angeben
	$db_user = "";// FB-User angeben
	$db_pwd  = "";// Passwort angeben
	$db_name = "";// Gewuenschte Datenbank angeben
   
	$db_connect = ibase_connect($db_host, $db_user, $db_pwd, "UTF8");
	if ( ! $db_connect ) { 
		echo "Keine Verbindung zu Firebird-SQL";
		exit; 
	} 
	return $db_connect;
}

/**
* db_log_connect
* log-db
*
* @return db-connect
*
*/	
function db_log_connect()
{
	$db_host = "";// FB-SQL-Host angeben
	$db_user = "";// FB-User angeben
	$db_pwd  = "";// Passwort angeben
	$db_name = "";// Gewuenschte Datenbank angeben
   
	$db_connect = ibase_connect($db_host, $db_user, $db_pwd, "UTF8");
	if ( ! $db_connect ) { 
		echo "Keine Verbindung zu Firebird-SQL";
		exit; 
	} 
	return $db_connect;
}

?>
