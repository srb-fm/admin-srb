<?php
/** 
* Sendung Manuskript bearebiten 
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
$action_ok = "no";
	
// action pruefen	
if ( isset( $_GET['action'] ) ) {	
	$action = $_GET['action'];	
	$action_ok = "yes";
}
if ( isset( $_GET['sg_titel'] ) ) { 
	$sg_titel = $_GET['sg_titel'];
} else { 
	$sg_titel = "Kein Titel uebernommen";
}
	
if ( $action_ok == "yes" ) {	
	if ( isset( $_GET['sg_id'] ) ) { 
		$sg_id = $_GET['sg_id'];
	}
	if ( isset( $_GET['sg_mk_id'] ) ) { 
		$sg_mk_id = $_GET['sg_mk_id'];
	}
	if ( $sg_id !="" )	{ 
		switch ( $action ) {
		case "new":
			// erstmal pruefen ob nicht doch schon ein Manuskript vorhanden ist
			// wenn sg_deteil.php nicht aktualisiert wurde...
			$tbl_row = db_query_display_item_1("SG_MANUSKRIPT", "SG_MK_SG_CONT_ID = ".$sg_id);
			if ( isset($tbl_row->SG_MK_ID )) { 
				//zur Sendung ist bereits ein Manuskript vorhanden
				$message =   "Manuskript bearbeiten";
				$form_input_type = "update"; //form action einstellen
			} else {
				// Datensatz anlegen
				$message =  "Manuskript eingetragen";
				$main_id = db_generator_main_id_load_value();    					
    			$a_values = array($main_id, trim($sg_id), trim($sg_titel));    					
    			$db_result = db_query_add_item_b("SG_MANUSKRIPT", "SG_MK_ID, SG_MK_SG_CONT_ID, SG_MK_TEXT", "?,?,?", $a_values);    											
				$tbl_row = db_query_display_item_1("SG_MANUSKRIPT", "SG_MK_ID = ".$main_id);
				$form_input_type = "update"; //form action einstellen
			}
			break;

		case "edit":
			$message =   "Manuskript bearbeiten";
			$form_input_type = "update"; //form action einstellen
			$tbl_row = db_query_display_item_1("SG_MANUSKRIPT", "SG_MK_ID = ".$sg_mk_id);
			break;

			//endswitch;
		}
	} else {
		$message = "Keine ID. Nichts zu tun..... "; 
	}
} else {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>Admin-SRB-Manuskript</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >
	<meta http-equiv="expires" content="0">
	<style type="text/css"> @import url("../parts/style/style_srb_2.css"); </style>
	<style type="text/css">	@import url("../parts/style/style_srb_jq_2.css");</style>
	<style type="text/css"> @import url("../parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css");    </style>
	<script type="text/javascript" src="../parts/jquery/jquery_1_7_1/jquery.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js"></script>
	<script type="text/javascript" src="../parts/jquery/jquery_tools/jq_tools.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#ajax_message').hide();
			$("#submit_button").click(function() {
				$('#ajax_message').hide();
				var action = $("#action").val();
	 			var ctxt = CKEDITOR.instances['editor1'].getData();
	 			var mk_id = $("#sg_mk_id").val();
	 			var sg_id = $("#sg_id").val();
	 			//var dataString = 'action='+ action + '&mk_id=' + mk_id + '&sg_id=' + sg_id + '&ctxt=' + ctxt;
	 			var dataString = 'action='+ action + '&mk_id=' + mk_id + '&sg_id=' + sg_id + '&ctxt=' + encodeURIComponent(ctxt);
	 			//alert(dataString);
	 			$.ajax({
      			type: "POST",
      			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      			url: "sg_hf_manuskript_ajax.php",
      			data: dataString,
      			success: function(response) {
      				$('#ajax_message').fadeIn(1500, function() {  
      					$('#ajax_message').html(response); 
    				}      			
      			);
      	}
     		});
    		return false;
			});
			
			$("#delete_button").click(function() {
				$('#ajax_message').hide();
				$('#ajax_message').html('');
				$('<div></div>').appendTo('body')
  					.html('<div>Manuskript wirklich löschen?</div>')
  					.dialog({
      				modal: true, title: 'message', zIndex: 10000, autoOpen: true,
      				width: 'auto', resizable: false,
      				buttons: {
          				Ja: function () {
              				//doFunctionForYes();
              
              				var action2 = "delete";
	 							var id = $("#sg_mk_id").val();
	 							var dataString = 'action='+ action2 + '&id=' + id;
	 							//alert(dataString);

	 							$.ajax({
      							type: "POST",
      							url: "sg_hf_manuskript_ajax.php",
      							data: dataString,
      							success: function(response) {
      								$('#ajax_message').fadeIn(1500, function() {  
      								$('#ajax_message').html(response);
      								$('#button_line').fadeOut(1500);
      								$('.content_row_a_9').fadeOut(1500);

    									} ); 
      							}
     							}); // ende ajax  
                            
              				$(this).dialog("close");
          				},
          				Nein: function () {
              			//doFunctionForNo();
              			$(this).dialog("close");
          				}
      				},
      				close: function (event, ui) {
          				$(this).remove();
      				}
					});
    		return false;
			});
		});
	</script>

	<script type="text/javascript" src="../parts/ckeditor_4/ckeditor.js"></script>
	
</head>
<body>

<div class="main">
<?php 
require "../parts/site_elements/header_srb_2.inc";
require "../parts/menu/menu_srb_root_1_eb_1.inc";
echo "<div class='head_item_right'>";
echo $message.": ".$sg_titel; 
echo "</div>";
echo "<div class='content'>";
if ( $action_ok == "no" ) { 
	return;
}

if ( ! isset($tbl_row->SG_MK_SG_CONT_ID)) { 
	echo "Fehler bei Abfrage!"; 
	return;
}
		
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights == "yes" ) { 
	echo "<form name=\"form1\" action=\"\" method=\"POST\" >\n";
	echo "<input type=\"hidden\" id='action' name=\"action\" value=\"".$form_input_type."\">";
	echo "<input type=\"hidden\" id='sg_mk_id' name='sg_mk_id' value=\"".$tbl_row->SG_MK_ID."\">";
	echo "<input type=\"hidden\" id='sg_id' name='sg_id' value=\"".$sg_id."\">";
			
	echo "<div class='content_row_a_1'>\n";
	echo "<div id='ajax_message' class='content_column_9'>"."</div>";
	echo "</div>";
			
	echo "<div class='content_row_a_9'>";
	echo "<textarea id='editor1' class='ckeditor textarea_3' name='form_mk_text' >".htmlspecialchars($tbl_row->SG_MK_TEXT)."</textarea>";
	echo "<script type='text/javascript'>";
	//<![CDATA[
	echo "CKEDITOR.replace( 'editor1',{";
	echo "toolbar :";
	echo "[	['Font','FontSize','Bold','Italic','Styles','Format', '-', 'NumberedList', 'BulletedList', 'Link', '-',  'Source', 'RemoveFormat', '-', 'Maximize', '-', 'Print' ]	]";
	echo "});";
	echo "CKEDITOR.config.entities = false;";
	//echo "CKEDITOR.config.entities_latin = false;";
	echo "CKEDITOR.config.forcePasteAsPlainText = true ;";
	echo "CKEDITOR.config.autoParagraph = false;";
	echo "CKEDITOR.config.basicEntities = false;";
	echo "CKEDITOR.config.height='450px';";

	//]]>
	echo "</script>";
	echo "</div>";
			
	echo "<br>";
	echo "<div class='line'> </div>";			
	echo "<div id='button_line'><input id='submit_button' type='submit' value='Speichern'> <input id='delete_button' type='submit' value='Manuskript löschen'></div>";
	echo "</form>";
} // user_rights	
echo "</div>";
?>
</div>
</body>
