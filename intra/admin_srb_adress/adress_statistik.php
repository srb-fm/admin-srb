<?php
/** 
* adress-details anzeigen 
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
require "../../cgi-bin/admin_srb_libs/lib_statistik.php";
require "../../cgi-bin/admin_srb_libs/lib_sess.php";

$message = "";

// check action	
$action_ok = false;
if ( isset($_GET['action']) ) {	
	$action = $_GET['action'];
	$action_ok = true;
}
		
if ( $action_ok == true ) {	
	$message = "Statistik Adresse"; 	
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

$a_statistik_ad = statistik_ad();	

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
	<title>Admin-SRB-Adresse</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
   <style type="text/css">	@import url("../parts/style/style_srb_3.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");    </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
</head>
<body>
 
<?php	
echo "<div class='column_right'>\n";
echo "<div class='head_item_right'>";
echo "Statistik Adresse";
echo "</div>\n";
	
if ( $action_ok == false ) { 
	return;
}
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 		
	echo "<div class='content'>Gesamtübersicht Macher</div>\n";	
	echo "<div class='content' id='jq_slide_by_click'>\n";
	// Counter fuer Zeilenformatierung		
	$z = 0;
	foreach ( $a_statistik_ad[0] as $item => $item_count ) {
		$z += 1;
		// Listcolors					
		if ( $z % 2 != 0 ) { 
			echo "<div class='content_row_a_4'>\n"; 
		} else {	
			echo "<div class='content_row_b_4'>\n";
		}
		echo "<div class='content_column_1'>".$item."</div><div class='content_column_6'>".$item_count."</div>\n";
		echo "</div>\n"; 			
	} 			//foreach			
	echo "</div>\n";

	echo "<div class='content'>Jahresübersicht neue Macher</div>\n";	
	echo "<div class='content' id='jq_slide_by_click'>\n";
	foreach ( $a_statistik_ad[4] as $item_year_macher_new => $item_count_year_macher_new ) {
		$z += 1;
		// Listcolors					
		if ( $z % 2 != 0 ) { 
			echo "<div class='content_row_a_4'>"; 
		} else {	
			echo "<div class='content_row_b_4'>";
		}
		echo "<div class='content_column_1'>".$item_year_macher_new."</div><div class='content_column_6'>".$item_count_year_macher_new."</div>\n";
		echo "</div>\n"; 	
		if ( $item_year_macher_new == "2009") {
			echo "<div class='content_row_toggle_head_3'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>\n";
			echo "<div class='content_row_toggle_body_3'>";
			echo "<div class='content_column_1'>Hinweis </div> <div class='content_column_6'>Ehemalige TV-Nutzer wurden vor 2009 aufgenommen \n (Anzahl: Differenz aller Macher zur Summe der Neuen ab 2009)</div>\n";
			echo "</div>\n";
		}	
	} 			//foreach			
	echo "</div>\n";
		
	echo "<div class='content'>Jahresübersicht aktive Macher</div>\n";	
	echo "<div class='content' id='jq_slide_by_click'>\n";
		
	foreach ( $a_statistik_ad[3] as $item_year => $item_value ) {
		$z += 1;
		// Listcolors					
		if ( $z % 2 != 0 ) { 
			echo "<div class='content_row_a_4'>"; 
		} else {	
			echo "<div class='content_row_b_4'>";
		}
		echo "<div class='content_column_1'>Im Jahr ".$item_year. "</div> <div class='content_column_6'>".$item_value."</div>\n";
		echo "</div>\n";
		echo "<div class='content_row_toggle_head_3'><img src='../parts/pict/form.gif' title='Mehr anzeigen' alt='Zusatz'></div>\n";
		echo "<div class='content_row_toggle_body_3'>";
	
		foreach ( $a_statistik_ad[2] as $item_quartal => $item_value ) {
			// Listcolors					
			if ( substr($item_quartal, 0, 4) == 	$item_year ) {			
				echo "<div class='content_column_1'>Quartal ".$item_quartal."</div> <div class='content_column_6'>".$item_value."</div>\n";
			}
		}
		echo "</div>\n";
	}

	//echo print_r($a_statistik_ad[4]);    
	//echo print_r($a_statistik_ad[5]);
	echo "<br><div class='space_line'> </div>\n ";		
} // user_rights
echo "</div> <!--class=column_right-->\n";
echo "</div> <!--class=content-->\n";
?>
</body>
</html>