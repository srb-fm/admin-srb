<?php

/** 
* Sendung - Details for Exchange 
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
	
$message = "";
$action_ok = false;
	
// check action	
if ( isset($_GET['action']) ) { 
	$action = $_GET['action'];	
	$action_ok = true;
}
if ( isset($_POST['action']) ) { 
	$action = $_POST['action']; 
	$action_ok = true;
}
			
if ( $action_ok == true ) {
	if ( isset($_GET['sg_file']) ) {	
		$id = $_GET['sg_file'];
	}
	if ( isset($_POST['sg_file']) ) { 
		$id = $_POST['sg_file'];
	}
		
	// switch action
	if ( $id !="" ) {
		switch ( $action ) {
		case "display":		
			$message = "Übernahme Sendung: Meta anzeigen. ";
			$local_file = '../admin_srb_export/local.txt';
			$server_file = '/Pool/Beitraege/'.$id;
			$ftp_server = ;
			$ftp_user_name = ;
			$ftp_user_pass =;
			// Verbindung aufbauen
			$conn_id = ftp_connect($ftp_server);

			// Login mit Benutzername und Passwort
			$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

// Versuche $server_file herunterzuladen und in $local_file zu speichern
if (ftp_get($conn_id, $local_file, $server_file, FTP_BINARY)) {
    $message = "$local_file wurde erfolgreich geschrieben\n";
    $ftxt = file_get_contents($local_file);
    #$ftxt = fopen($local_file, "r");
} else {
    $message = "Ein Fehler ist aufgetreten\n";
}

// Verbindung schließen
ftp_close($conn_id);
			break;

		case "play":		
			$message = "Übernahme Sendung: Abspielen! ";
			
			break;

			//endswitch;
		}
	}

} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

// ok, retrieve data
if ( $action_ok == true ) {
	#$tbl_row = db_query_display_item_1("SG_HF_ROBOT", $query_condition);
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung-Übernahme</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>

</head>
<body>
 
<div class="column_large">
	<div class="column_right">
<?php
echo "<div class='head_item_right'>";
echo $message; 
echo "</div>";
echo "<div class='content'>";
if ( $action_ok == false ) { 
	return;
}

$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 		
	echo "<div class='content_row_a_1'>";
	echo "<div class='content_column_1'>Dateiname</div>";
	echo "<div class='content_column_2'>" .$id. "</div>";
	echo "</div>\n";
	echo "<div> <textarea class='textarea_1' name='meta' width=97% height=80px>" .$ftxt.  "</textarea></div>";	
	echo "<div class='line_a'> </div>\n";
		
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";		
	echo "<li><a href='sg_hf_robot_edit.php?action=edit&amp;sg_robot_id="."'>xxxBearbeiten</a> ";
	echo "</ul>\n</div><!--menu_bottom-->"; 
} // user_rights	
echo "</div>\n";
?>
</div>
</div>
</body>
</html>