<?php
/** 
* User blocken 
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
	
$error_ok = "no";
// error pruefen	
if ( isset($_GET['error']) ) { 
	$log_error = $_GET['error'];	
	$error_ok = "yes";
}
?>	
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<title>Admin-SRB-Administration</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");   </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>

</head>
<body>

<div class="main">
<?php 
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
echo "<div class='head_item_left'>";
echo "Administration";	
echo "</div>";
user_display();
echo "</div>";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo "Administration, Kontrolle, Einstellungen";
echo "</div>";
		
if ( $log_error == "Name_oder_Passwort_falsch") { 
	display_message("Prüfung der Berechtigung", "Benutzername oder Passwort ist nicht korrekt!");
}		
	
?>
		
		<div class="content">
			Zugangskontrolle
		</div>
	</div>		
</div>

</body>
</html>
