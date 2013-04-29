<?php

/** 
* Inventar von Verleih suchen 
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

$display_kat = "no";
if ( isset( $_GET['vl_id'] ) ) {	
	$vl_id = $_GET['vl_id'];
}
if ( isset( $_POST['vl_id'] ) ) { 
	$vl_id = $_POST['vl_id'];
}
if ( isset( $_GET['vl_kat'] ) ) { 
	$display_kat = "yes"; 
}
?>	
	
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Admin-SRB-Inventar finden</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");   </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript">
	
	function chk_formular () {
		if( '<?php echo "$display_kat";?>' == "no" ){	
			cEingabe = document.form1.iv_objekt.value + document.form1.iv_typ.value + document.form1.iv_hersteller.value;
		}else{
			cEingabe = document.form1.iv_kat.value;
			} 

  		if( cEingabe == "") {		  
	    	alert("Es wurden keine Suchbegriffe eingegeben!");
	    	if( '<?php echo "$display_kat";?>' == "no" ){
    			document.form1.iv_objekt.focus();
    		}else{	
    			document.form1.iv_kat.focus();
    		}
			return false;	}
		}
</script>

</head>
<body>
<?php
echo "<div class='column_large'>";
echo "<div class='head_item_right'>";
echo "Inventar finden";
echo "</div>";
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) {
	echo "<div class='content'>";
	echo "<form name='form1' action='iv_vl_find_list.php' method='POST' onsubmit='return chk_formular()' enctype='application/x-www-form-urlencoded'>";
	echo "<div>";
	echo "<input type='hidden' name='action' value='find'>";
	echo "<input type='hidden' name='vl_id' value='".$vl_id."'>";
	echo "</div>";
	echo "<table>";
		
	if ( $display_kat == "no" ) {
		echo "<tr><td>Objekt</td><td><input type='TEXT' name='iv_objekt' value='' size='60' maxlength='100'></td></tr>";
		echo "<tr><td>Typ</td><td><input type='TEXT' name='iv_typ' value='' size='60' maxlength='100'></td></tr>";
		echo "<tr><td>Hersteller</td><td><input type='TEXT' name='iv_hersteller' value='' size='60' maxlength='100'></td></tr>";
	} else {
		echo "<tr><td>Kategorie</td><td><input type='TEXT' name='iv_kat' value='' size='60' maxlength='100'></td></tr>";
	}

	echo "<tr><td> </td></tr>";
	echo "<tr><td>Optionen für Suche</td><td><input type='radio' name='find_option' value='begin' checked='checked' > Anfang <input type='radio' name='find_option' value='in'> Innerhalb <input type='radio' name='find_option' value='exact'> Exakte Übereinstimmung</td></tr>";
	echo "<tr><td><br>&nbsp;<br></td><td><br>&nbsp;<br><input type='SUBMIT' name='submit' value='Finden'> <input type='RESET' name='reset' value='Zurücksetzen'></td></tr>";
	echo "</table>";
	echo "</form>";
	echo "</div>";
} // user_rights
echo "</div>";
?>
<script type="text/javascript">
	<!-- focus zuordnen, anfuerungsstriche bei uebernahem php-variable beachten! -->
	if( '<?php echo "$display_kat";?>' == "no" ){	
		document.form1.iv_objekt.focus();
	}else{
		document.form1.iv_kat.focus();};	
</script>
</body>
</html>
