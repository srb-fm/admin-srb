<?php

/** 
* User einloggen oder auch nicht 
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
require "../../cgi-bin/admin_srb_libs/lib_sess.php";

$action_ok = "no";
	
// action pruefen	
if ( isset($_GET['action']) ) { 
	$action = $_GET['action'];	
	$action_ok = "yes";
}
if ( isset( $_GET['form_log_name'] ) ) { 
	$form_log_name = $_GET['form_log_name'];
} else {
	$form_log_name = false;
}
if ( isset( $_GET['form_log_pass'] ) ) { 
	$form_log_pass = $_GET['form_log_pass'];
} else {
	$form_log_pass = false;
}			
	
if ( isset( $action ) && $action=="LogIn" ) {
	if ( empty($form_log_name) || empty($form_log_pass) ) {
		header("Location: ".rawurldecode($_GET['form_log_webseite']));
	} else {
		$string_ok = search_forbidden_strings($form_log_name);
		$string_ok_1 = search_forbidden_strings($form_log_pass);
		if ( $string_ok == "no" || $string_ok_1 == "no" ) { 
			header("Location: ../index.shtml?error=Login_mit_unerlaubten_Zeichen");
			return;	
		}
		
		$db_log = user_login($form_log_name, $form_log_pass);
		if (! $db_log ) {
  			header("Location: ../admin_srb_user/user_block.php?error=Name_oder_Passwort_falsch");
  		} else {
			// alles ok
			//Session starten, Benutzer uebergeben
			initSession($db_log["USER_ID"], $db_log["USER_NAME"], rtrim($db_log["USER_NAME_SHORT"]), rtrim($db_log["USER_RIGHTS"]));
			header("Location: ".rawurldecode($_GET['form_log_webseite']));
		}
	} 
		
} elseif ( isset($action) && $action=="LogOut" ) {
	// $_SESSION leeren 
	$_SESSION = array(); 
	// Session zerstoeren 
	session_destroy();
	//nach log-out zur index.php des modules gehen
	header("Location: ".rawurldecode($_GET['log_dir'])."/index.php");	
}
?>