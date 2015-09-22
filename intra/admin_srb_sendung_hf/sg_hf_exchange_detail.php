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

		$tbl_row_ftp_set = db_query_display_item_1(
						"USER_SPECIALS", "USER_SP_SPECIAL = 'Exchange_Finder'");
	
		switch ( $action ) {
			case "display":		
			$local_file = '../admin_srb_export/local.txt';
			break;

			case "play":
			$local_file = '../admin_srb_export/'.$id;
			break;
			//endswitch;
		}
		// ftp
		$server_file = trim($tbl_row_ftp_set->USER_SP_PARAM_5)."/".$id;
		$ftp_server = trim($tbl_row_ftp_set->USER_SP_PARAM_6);
		$ftp_user_name = trim($tbl_row_ftp_set->USER_SP_PARAM_7);
		$ftp_user_pass = trim($tbl_row_ftp_set->USER_SP_PARAM_8);
		// connect
		$conn_id = ftp_connect($ftp_server);
		// Login
		$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

		// download
		if (ftp_get($conn_id, $local_file, $server_file, FTP_BINARY)) {
			$message = "$local_file wurde erfolgreich geschrieben\n";
		} else {
			$message = "Ein Fehler ist aufgetreten\n";
		}
		// close connect
		ftp_close($conn_id);	
	
		switch ( $action ) {
			case "display":		
			$message = "Übernahme Sendung: Meta anzeigen. ";
			$ftxt = file_get_contents($local_file);
			break;

			case "play":		
			$message = "Übernahme Sendung: Abspielen! ";
			$remotefilename = "http://".$_SERVER['SERVER_NAME']."/admin_srb_export/".$id;
			break;
			//endswitch;
		}
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Sendung Übernahme Detailansicht</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<style type="text/css">	@import url("../parts/style/style_srb_2.css");    </style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<link href="../parts/jPlayer-2.9.2/dist/skin/blue.monday/css/jplayer.blue.monday.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	
	<script type="text/javascript" src="../parts/jPlayer-2.9.2/dist/jplayer/jquery.jplayer.min.js"></script>
	<script type="text/javascript" src="../parts/jPlayer-2.9.2/dist/add-on/jquery.jplayer.inspector.min.js"></script> 

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
	echo "<div class='content_row_a_8'>";

	switch ( $action ) {
		case "display":	
		echo "<div class='content_column_1'>Meta</div>";
		echo "<div > <textarea class='textarea_2' width='95%' name='meta'>".$ftxt."</textarea></div>";
		echo "</div>\n";
		break;
		
		case "play":		
		echo '<script type="text/javascript">';
		//echo '//<![CDATA[';
		echo '$(document).ready(function(){';
		echo '	$("#jquery_jplayer_1").jPlayer({';
		echo 'ready: function () {';
		echo '			$(this).jPlayer("setMedia", {';
		echo 'mp3: "'.$remotefilename.'"';
		echo '		});';
		echo '		},';
		echo '		swfPath: "../../dist/jplayer",';
		echo '		supplied: "mp3",';
		echo '		wmode: "window",';
		echo '		useStateClassSkin: true,';
		echo '		autoBlur: false,';
		echo '		smoothPlayBar: true,';
		echo '		keyEnabled: true,';
		echo '		remainingDuration: true,';
		echo '		toggleDuration: true';
		echo '});';
		echo '	$("#jplayer_inspector").jPlayerInspector({jPlayer:$("#jquery_jplayer_1")});';
		echo '});	';

		echo '</script>';

		echo '<div id="jquery_jplayer_1" class="jp-jplayer"></div>';
		echo '<div id="jp_container_1" class="jp-audio" role="application" aria-label="media player">';
			echo '<div class="jp-type-single">';
				echo '<div class="jp-gui jp-interface">';
					echo '<div class="jp-controls">';
						echo '<button class="jp-play" role="button" tabindex="0">play</button>';
						echo '<button class="jp-stop" role="button" tabindex="0">stop</button>';
					echo '</div>';
					echo '<div class="jp-progress">';
						echo '<div class="jp-seek-bar">';
							echo '<div class="jp-play-bar"></div>';
						echo '</div>';
					echo '</div>';
					echo '<div class="jp-time-holder">';
						echo '<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>';
						echo '<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>';
					echo '</div>';
				echo '</div>';
				echo '<div class="jp-no-solution">';
					echo '<span>Update Required</span>';
					echo 'To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.';
				echo '</div>';
			echo '</div>';
		echo '</div>';	
#echo '<div id="jplayer_inspector"></div>';
	
			break;
			//endswitch;
		}	
	echo "<div class='line_a'> </div>\n";
		
	echo "<div class='menu_bottom'>";
	echo "<ul class='menu_bottom_list'>";		
	//echo "<li><a href='sg_hf_robot_edit.php?action=edit&amp;sg_robot_id="."'>xxxBearbeiten</a> ";
	echo "</ul>\n</div><!--menu_bottom-->"; 
} // user_rights	
echo "</div>\n";
?>
</div>
</div>
</body>
</html>