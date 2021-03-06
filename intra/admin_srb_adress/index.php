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
require "../../cgi-bin/admin_srb_libs/lib_sess.php";
?>	
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<title>Admin-SRB-Adresse</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_3.css");   </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>

	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/colorbox/jquery.colorbox.js"></script>	
	<script type="text/javascript" src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>
	
</head>
<body>

<div class="main">

<?php
include "../parts/site_elements/header_srb_2.inc";
include "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
include "parts/ad_menu.inc";
user_display();		
echo "</div>";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo "Adressen anzeigen und bearbeiten";
echo "</div>";
include "parts/ad_toolbar.inc";
?>	
	
	<div class="content">
		<br>
		Die Adressverwaltung ist praktisch die "Zentrale" für weitere Bereiche der Verwaltung, z.B. der Sendeverwaltung und des Verleihs. 
		Deshalb ist die Adressverwaltung häufig Ausgangspunkt für die Suche nach Radiomachern für Sendeanmeldungen oder die Buchung von Leihgeräten.  
		</div>
	</div>		
</div>
<script type="text/JavaScript" src="../parts/js/js_my_tools/js_my_toolbar_focus.js"></script>
</body>
</html>
