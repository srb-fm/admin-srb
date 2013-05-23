$(document).ready(function() {
	// dialogbox
	// abhaengig von jquery und:
	// /parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css
	// /parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js 
	$( "#dialog:ui-dialog" ).dialog( "destroy" );
	$( "#dialog-form" ).dialog({
			height: 200,
			width: 450,
			modal: true,
			buttons: {
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			},
			close: function() {
				allFields.val( "" ).removeClass( "ui-state-error" );
			}
		});
		
	// datepicker
	// abhaengig von jquery und:
	// /parts/jquery/jquery_ui_1_8_16/css/jquery-ui-1.8.16.custom.css
	// /parts/jquery/jquery_ui_1_8_16/jquery-ui-1.8.16.custom.min.js 
	$('#datepicker').datepicker({
				showButtonPanel: true,
				dateFormat: 'dd.mm.yy',
				firstDay: 1
			});
			
			
});