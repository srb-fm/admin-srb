<?php
/** 
* Verleih finden
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
?>	
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<title>Admin SRB Verleih finden</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	
	<script src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<!--muss zum schluss, sonst geht slidemenu nicht-->
	<script src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>

	<script>
	
	function chk_formular () {
		cEingabe = document.form1.vl_projekt.value + document.form1.vl_text.value + document.form1.vl_datum.value + document.form1.vl_id.value

  		if ( cEingabe == "") {		  
	    	alert("Es wurden keine Suchbegriffe eingegeben!");
    		document.form1.vl_projekt.focus();
			return false;		
		}
		if ( document.form1.vl_datum.value != "") {		  
	    	<!--find_option auf datum schalten-->
    		document.form1.find_option[3].checked;
			return true;		
		}}
	</script>

</head>
<body>
<?php
echo "<div class='main'>";
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
require "parts/vl_menu.inc";
user_display();			
echo "</div> <!--class=column_left-->";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo "Verleih finden";
echo "</div>";
require "parts/vl_toolbar.inc";
echo "<div class='content'>";

$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 	
	echo "<form name='form1' action='vl_find_list.php' method='POST' onsubmit='return chk_formular()' enctype='application/x-www-form-urlencoded'>";
	echo "<div><input type='hidden' name='action' value='find'></div>";
		
	echo "<table>";		
	echo "<tr><td>Projekt</td><td><input type='TEXT' name='vl_projekt' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Bemerkung</td><td><input type='TEXT' name='vl_text' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Datum</td><td><input type='TEXT'  id='datepicker' name='vl_datum' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Nummer</td><td><input type='TEXT' name='vl_id' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>&nbsp;</td><td>&nbsp; </td></tr>";
	echo "<tr><td>Optionen für Suche</td><td><input type='radio' name='find_option' value='begin' checked='checked' > Anfang <input type='radio' name='find_option' value='in'> Innerhalb <input type='radio' name='find_option' value='exact'> Exakte Übereinstimmung <input type='radio' name='find_option' value='datum'> Datum</td></tr>";
	echo "<tr><td><br>&nbsp;<br></td><td><br>&nbsp;<br><input type='SUBMIT' name='submit' value='Finden'> <input type='RESET' name='reset' value='Zurücksetzen'></td></tr>";
	echo "</table>";

	echo "</form>";
} // user_rights
echo "</div>";
echo "</div>";
echo "</div>";
echo "<script type='text/javascript'>";
echo "document.form1.vl_projekt.focus();";
echo "</script>";
?>
</body>
</html>
