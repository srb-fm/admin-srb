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
* this file may be edited thru /install/config_firebird.sh
* @return db-connect
*
*/	
function db_connect()
{
	$db_host_db = "";// FB-SQL-Host like localhost:mydb
	$db_user = "";// FB-User
	$db_pwd  = "";// Passwort

	$db_connect = ibase_connect($db_host_db, $db_user, $db_pwd, "UTF8");
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
	$db_log_host_db = "";// FB-SQL-Host
	$db_log_user = "";// FB-User
	$db_log_pwd  = "";// Passwort

	$db_connect = ibase_connect($db_log_host_db, $db_log_user, $db_log_pwd, "UTF8");
	if ( ! $db_connect ) {
		echo "Keine Verbindung zu Firebird-SQL";
		exit;
	}
	return $db_connect;
}

?>
