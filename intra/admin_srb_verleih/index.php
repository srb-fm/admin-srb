<?php
/** 
* Index Verleih
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
<head>
	<title>Admin-SRB-Verleih</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_3.css");    </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");    </style>
	<style type="text/css"> @import url("../parts/colorbox/colorbox.css");  </style>
	
	<script src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script src="../parts/colorbox/jquery.colorbox.js"></script>	
	<script src="../parts/jquery/jquery_my_tools/jq_my_tools_2.js"></script>	
	
</head>
<body>

<div class="main">

<?php
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='column_left'>";
require "parts/vl_menu.inc";
user_display();		
echo "</div>";
echo "<div class='column_right'>";
echo "<div class='head_item_right'>";
echo "Verleih anzeigen und bearbeiten";
echo "</div>";
require "parts/vl_toolbar.inc";
?>	
		<div class="content">
			<div>
				<b>Hier können Geräte für Reportagen u.a. ausgeliehen werden.</b><br>
				<ul><li>Für eine neue Ausleihe muß zunächst das "Verleihprojekt" angelegt werden [Neuer Verleih]. 
				<li>Anschließend werden diesem Projekt die Geräte (Objekte) aus dem Inventar hinzugefügt, die ausgeliehen werden sollen.
				<p>
				Es können einzelne Geräte (Objekte) oder in Gruppen zusammengefasst, mehrere Geräte mit einem Mal zugebucht werden. 
				<li> Einzelne Objekte werden mit dem Meüpunkt [Objekt ausleihen] 				
				<li> Gruppen werden mit dem Menüpunkt [Kategorie ausleihen] zugebucht.
				</p>  
				<p>Kategorien sind nach Sachgebieten geordnet, z.B. Audio, EDV, Video... </p>
				</ul>
			</div>
		</div>	
	</div>		
</div>
<script type="text/JavaScript" src="../parts/js/js_my_tools/js_my_toolbar_focus.js"></script>
</body>
</html>
