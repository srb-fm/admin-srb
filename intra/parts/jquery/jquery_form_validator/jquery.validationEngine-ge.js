
(function($) {
	$.fn.validationEngineLanguage = function() {};
	$.validationEngineLanguage = {
		newLang: function() {
			$.validationEngineLanguage.allRules = 	{"required":{    			// Add your regex rules here, you can take telephone as an example
						"regex":"none",
						"alertText":"* Eingabe nötig",
						"alertTextCheckboxMultiple":"* Please select an option",
						"alertTextCheckboxe":"* This checkbox is required"},
					"length":{
						"regex":"none",
						"alertText":"* Zwischen ",
						"alertText2":" und ",
						"alertText3": " Zeichen sind erlaubt"},
					"maxCheckbox":{
						"regex":"none",
						"alertText":"* Checks allowed Exceeded"},	
					"minCheckbox":{
						"regex":"none",
						"alertText":"* Please select ",
						"alertText2":" options"},	
					"confirm":{
						"regex":"none",
						"alertText":"* Your field is not matching"},		
					"telephone":{
						"regex":"/^[0-9\-\(\)\ ]+$/",
						"alertText":"* Invalid phone number"},	
					"email":{
						"regex":"/^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/",
						"alertText":"* Keine gültige eMail-Addresse"},	
					"date":{
                         "regex":"/^[0-9]{4}\-\[0-9]{1,2}\-\[0-9]{1,2}$/",
                         "alertText":"* Invalid date, must be in YYYY-MM-DD format"},
					"date_ge":{
                         "regex":"/^[0-3]{1}[0-9]{1}\.\[0-1]{1}[0-9]{1}\.\[0-9]{4}$/",
                         "alertText":"* Datum nicht korrekt, muss TT.MM.JJJJ sein"},
					"onlyNumber":{
						"regex":"/^[0-9\ ]+$/",
						"alertText":"* Nur Zahlen erlaubt"},	
					"c_time_day":{
						"regex":"/^[0-2]{1}[0-9]{1}\:\[0-5]{1}[0-9]{1}\:\[0-5]{1}[0-9]{1}$/",
						"alertText":"* Zeitformat nur in hh:mm:ss, max 23:59:59"},							
					"c_time_duration":{
						"regex":"/^[0]{1}[0-1]{1}\:\[0-5]{1}[0-9]{1}\:\[0-5]{1}[0-9]{1}$/",
						"alertText":"* Zeitformat nur in hh:mm:ss"},							
					"noSpecialCaracters":{
						"regex":"/^[0-9a-zA-Z]+$/",
						"alertText":"* No special caracters allowed"},	
					"ajax_sg_time":{
						"file":"sg_hf_check_free_time.php",
						"extraData":"name=eric",
						"alertTextOk":"* Sendezeit verfügbar",	
						"alertTextLoad":"* Sendezeit prüfen....",
						"alertText":"* Diese Sendezeit ist bereits belegt"},			
					"ajaxName":{
						"file":"validateUser.php",
						"alertText":"* This name is already taken",
						"alertTextOk":"* This name is available",	
						"alertTextLoad":"* Loading, please wait"},		
					"onlyLetter":{
						"regex":"/^[a-zA-Z\ \']+$/",
						"alertText":"* Nur Buchstaben erlaubt"}
					}	
		}
	}
})(jQuery);

$(document).ready(function() {	
	$.validationEngineLanguage.newLang()
});