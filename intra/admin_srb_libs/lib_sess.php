<?php
/** 
* library for session-functions 
*
* PHP version 5
*
* @category Intranetsite
* @package  Admin-SRB
* @author   Joerg Sorge <joergsorge@googel.com>
* @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
* @link     http://srb.fm
*/

session_start();

/**
* initSession
*
* @param id             $id             eben das
* @param c_log_name     $c_log_name     eben das
* @param c_log_usr_name $c_log_usr_name eben das
* @param c_log_rights   $c_log_rights   eben das
* 
* @return Session_Vars
*
*/	
function initSession( $id, $c_log_name, $c_log_usr_name, $c_log_rights )
{
	$_SESSION["id"] = $id;
	$_SESSION["log_name"] = $c_log_name;
	$_SESSION["log_usr_name"] = $c_log_usr_name;
	$_SESSION["log_rights"]  = $c_log_rights;
}

/**
* user_rights_1
*
* @param website $website URL
* @param query   $query   Query
* @param right   $right   user-right
* 
* @return yes or no
*
*/	
function user_rights_1( $website, $query, $right ) 
{
	if ( ! isset($_SESSION["log_usr_name"]) ) {
		user_login_form($website, $query);
	} else {	
		if ( $_SESSION["log_rights"] <= $right ) {
			return "yes";
		} else {
			display_message("Prüfung der Berechtigung", "Sie haben leider keine Berechtigung zur Anzeige dieser Seite!");
			return "no";
		}
	}
	return;		
}

/**
* user_display
* 
* @return html
*
*/	
function user_display() 
{
	if ( isset($_SESSION["log_usr_name"])) {		
		echo "<div class='content_1'>Angemeldet als: ".$_SESSION["log_name"]." - <a href='../admin_srb_user/user_login_1.php?action=LogOut&amp;log_dir=".dirname($_SERVER['PHP_SELF'])."'>Abmelden</a></div>";
	} else {
		echo "<div class='content_1'>Nicht angemeldet.. </div>";
	}
}
?>
