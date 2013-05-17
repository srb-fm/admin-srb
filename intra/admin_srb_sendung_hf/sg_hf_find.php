<?php
/** 
* sendungen suchen 
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
	<title>Admin SRB Sendung finden</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css"> @import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/style/style_srb_jq_2.css");    </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
		
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript"src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>	
	
	<!--muss zum schluss, sonst geht slidemenu nicht-->
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
		
	<script type="text/javascript">
	
	function chk_formular () {
		cEingabe = document.form1.sg_titel.value + document.form1.sg_untertitel.value + document.form1.sg_stichwort.value;
		cEingabe += document.form1.sg_regieanweisung.value + document.form1.sg_dateiname.value + document.form1.sg_genre.value;
		cEingabe += document.form1.sg_quelle.value + document.form1.sg_datum.value + document.form1.sg_cont_id.value;
		cEingabe += document.form1.sg_magazin.value + document.form1.sg_live.value

  		if ( cEingabe == "") {		  
	    	alert("Es wurden keine Suchbegriffe eingegeben!");
    		document.form1.sg_titel.focus();
			return false;}
		if ( document.form1.sg_datum.value != "") {		  
	    	<!--find_option auf datum schalten-->
    		document.form1.find_option[3].checked;
			return true;}
		}

	</script>

</head>
<body>
<?php
echo "<div class='main'>";
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
require "parts/sg_hf_menu.inc";
user_display();			
echo "</div> <!--class=column_left-->";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo "Sendung finden";
echo "</div>";
require "parts/sg_hf_toolbar.inc";
		
echo "<div class='content'>";
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "C");
if ( $user_rights == "yes" ) { 	
	echo "<form name='form1' action='sg_hf_find_list.php' method='POST' onsubmit='return chk_formular()' enctype='application/x-www-form-urlencoded'>";
	echo "<div><input type='hidden' name='action' value='find'></div>";
		
	echo "<table>";		
	echo "<tr><td>Titel</td><td><input type='TEXT' name='sg_titel' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Untertitel</td><td><input type='TEXT' name='sg_untertitel' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Stichwort</td><td><input type='TEXT' name='sg_stichwort' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Regieanweisung </td><td><input type='TEXT' name='sg_regieanweisung' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Dateiname </td><td><input type='TEXT' name='sg_dateiname' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Genre</td><td>".html_dropdown_from_table_1("SG_GENRE", "SG_GENRE_DESC", "sg_genre", "text_a_1", "00")."</td></tr>";
	echo "<tr><td>Quelle</td><td><select name='sg_quelle' size='01' >";
	echo "<option selected='selected'> </option>";
	echo "<option>Studio 1</option>";
	echo "<option>Studio 2</option>";
	echo "<option>Extern Internetstream</option>";
	echo "<option>Extern ISDN Codec</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr><td>Magazin-Beiträge</td><td><input type='checkbox' name='sg_magazin' value='T' title='Magazin-Beträge'> </td></tr>";
	echo "<tr><td>Livesendungen</td><td><input type='checkbox' name='sg_live' value='T' title='Livesendungen'> </td></tr>";
	echo "<tr><td>Datum</td><td><input type='TEXT'  id='datepicker' name='sg_datum' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Content-Nr. </td><td><input type='TEXT' name='sg_cont_id' value='' size='60' maxlength='100'></td></tr>";
	echo "<tr><td>Optionen für Suche</td><td><input type='radio' name='find_option' value='begin' checked='checked' > Anfang <input type='radio' name='find_option' value='in'> Innerhalb <input type='radio' name='find_option' value='exact'> Exakte Übereinstimmung <input type='radio' name='find_option' value='datum'> Datum<br>";		
	echo "<br>(Es kann immer nur nach dem Inhalt EINES Formularfeldes gesucht werden. <br>Bei mehrern Einträgen zählt die Reihenfolge von oben.)</td></tr>";
	echo "<tr><td><br>&nbsp;<br></td><td><br>&nbsp;<br><input type='SUBMIT' name='submit' value='Finden'> <input type='RESET' name='reset' value='Zurücksetzen'></td></tr>";
	echo "</table>";
	echo "</form>";
} // user_rights
echo "</div>";
echo "</div>";
echo "</div>";
echo "<script type='text/javascript'>";
echo "document.form1.sg_titel.focus();";
echo "</script>";
?>
</body>
</html>
