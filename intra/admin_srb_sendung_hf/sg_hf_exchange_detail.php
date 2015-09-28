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
		// are we on server-line A or B?
		$tbl_row_config_serv = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings'");		
		if ( $tbl_row_config_serv->USER_SP_PARAM_3 == $_SERVER['SERVER_NAME'] ) {
			$tbl_row_config_A = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_a_A'");
			$tbl_row_config_B = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_b_A'");
		}
		if ( $tbl_row_config_serv->USER_SP_PARAM_4 == $_SERVER['SERVER_NAME'] ) {
			$tbl_row_config_A = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_a_B'");
			$tbl_row_config_B = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_b_B'");
		}


		$tbl_row_ftp_set = db_query_display_item_1(
						"USER_SPECIALS", "USER_SP_SPECIAL = 'Exchange_Finder'");
		// ftp
		$server_file = trim($tbl_row_ftp_set->USER_SP_PARAM_5)."/".$id;
		$ftp_server = trim($tbl_row_ftp_set->USER_SP_PARAM_6);
		$ftp_user_name = trim($tbl_row_ftp_set->USER_SP_PARAM_7);
		$ftp_user_pass = trim($tbl_row_ftp_set->USER_SP_PARAM_8);					
	
		switch ( $action ) {
			case "display":		
			$local_file = '../admin_srb_export/local.txt';
			$message = "Übernahme Sendung: Meta anzeigen. ";
			ftp_download( $ftp_server, $ftp_user_name, $ftp_user_pass, $server_file, $local_file );
			$ftxt = file_get_contents($local_file);
			break;

			case "play":
			$local_file = $tbl_row_config_B->USER_SP_PARAM_10."tmp.mp3";
			$remotefilename = "http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_11."tmp.mp3";
			$play_out_filename = $tbl_row_config_B->USER_SP_PARAM_10.$id;
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

  <script>
		$(document).ready(function() {
			var action = "<?php echo $action ?>";
			if (action = "play" ) {
				$( "#opener" ).hide();
				$( "#dialog" ).dialog({
   			   autoOpen: true,
      			show: {
        				effect: "blind",
        				duration: 800
      			},
      			hide: {
        				effect: "explode",
        				duration: 800
      			}
    			});
			
			var ftp_server = "<?php echo $ftp_server ?>";
			var ftp_user_name = "<?php echo $ftp_user_name ?>";
			var ftp_user_pass = "<?php echo $ftp_user_pass ?>";
			var server_file = "<?php echo $server_file ?>";
			var local_file = "<?php echo $local_file ?>";
			var dataString = ('action=ftp' 
							+ '&ftp_server=' + ftp_server
							+ '&ftp_user_name=' + ftp_user_name
							+ '&ftp_user_pass=' + ftp_user_pass
							+ '&server_file=' + server_file
							+ '&local_file=' + local_file
				);
			// ajax
			$.ajax({
  				type: "POST",
  				url: "sg_hf_exchange_ajax.php",
  				data: dataString,
  				success: function(msg){
    				//alert( "Data Saved: " + msg );
 					$( "#dialog" ).dialog( "close" );
 					$( "#opener" ).show();
  				}
			});
 
    	$( "#opener" ).click(function() {
    		
    		$( "#dialog" ).html( "Speichern" );
      	
      	var dataString = ('action=rename' 
							+ '&local_file=' + "<?php echo $local_file ?>"
							+ '&ren_file=' + "<?php echo $play_out_filename ?>"
				);
     		// ajax
			$.ajax({
  				type: "POST",
  				url: "sg_hf_exchange_ajax.php",
  				data: dataString,
  				success: function(msg){
    				//alert( "Data Saved: " + msg );
 					$( "#dialog" ).dialog( "open" );
  				}
			});
    	});
	}	
 });
</script>
  
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
//echo '<div id="jplayer_inspector"></div>';
	
			break;
			//endswitch;
		}	
	echo "<div class='line_a'> </div>\n";

	$filename = new SplFileInfo($id);
	$fileext = $filename->getExtension();
	if ($fileext == "mp3") {
		echo "<button id='opener'>Audio-Datei in Play_Out_Uebernahmen speichern</button>";
				
		echo "<div id='dialog' title='Download und Speichern'>";
		echo "<img src='../parts/pict/wait30trans.gif' width='30' height='30' border='0' alt='gleich gehts weiter'> ";
		echo "Datei wird heruntergeladen...";
		echo "</div>";
	}

} // user_rights	
echo "</div>\n";
?>

</div>
</div>
</body>
</html>