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
	
$user_rights = user_rights_1($_SERVER['PHP_SELF'], rawurlencode($_SERVER['QUERY_STRING']), "B");
if ( $user_rights != "yes" ) {
	return;	
} // user_rights
	
require '../parts/fpdf/fpdf.php';
$message = "";
$action_ok = "no";
$find_option_ok = "no";
$display_text = "no";
$c_query_condition ="";
	
// action pruefen	
if ( isset( $_GET['action'] ) ) {
	$action = $_GET['action'];	
	$action_ok = "yes";
}
if ( isset( $_POST['action'] ) ) { 
	$action = $_POST['action']; 
	$action_ok = "yes";
}
		
if ( $action_ok != "yes" ) { 
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
		
// Bedingung pruefen		
if ( isset( $_GET['find_option'] ) ) { 
	$find_option = $_GET['find_option'];
	$find_option_ok = "yes";
}
if ( isset( $_POST['find_option'] ) ) { 
	$find_option = $_POST['find_option']; 
	$find_option_ok = "yes";
}		
	
if ( $find_option_ok = "yes" ) {
	if ( $action == "find") {
		if ( isset( $_POST['form_iv_eigentuemer'] ) ) {
			// form_iv_eigentuemer gibts praktisch immer
			if ( $_POST['form_iv_eigentuemer'] != "Keine Zuordnung") {
				$id_eigentuemer = db_query_load_id_by_value("IV_EIGENTUEMER", "IV_EIG_DESC", $_POST['form_iv_eigentuemer']);
				$c_query_condition = "upper( IV_OBJEKT ) >= 'A' AND IV_EIGENTUEMER_ID = '".$id_eigentuemer."' AND IV_AUSGEMUSTERT = 'F' ORDER BY IV_OBJEKT";
				$message_find_string = "Inventar Liste der Objekte des Eigentümers: ".$_POST['form_iv_eigentuemer'];
			} else {
				if ( isset( $_POST['form_iv_sponsor'] ) ) {
					$c_query_condition = "upper( IV_OBJEKT ) >= 'A' AND IV_SPONSOR = '".$_POST['form_iv_sponsor']."' AND IV_AUSGEMUSTERT = 'F' ORDER BY IV_OBJEKT";
					$message_find_string = "Inventar Liste der Objekte des Sponsors: ".$_POST['form_iv_sponsor'];
				}
			}
		}
			
	} else {
			
		switch ( $find_option ) {
				
		case "gesamt":
			//$c_query_condition = "upper( IV_OBJEKT ) >= '".utf8_decode("A")."' AND IV_AUSGEMUSTERT = '".utf8_decode("F")."' ORDER BY IV_OBJEKT";
			$c_query_condition = "upper( IV_OBJEKT ) >= 'A' AND IV_AUSGEMUSTERT = 'F' ORDER BY IV_OBJEKT";
			$message_find_string = "Inventar Gesamtliste ohne Ausgemusterte, alphabetisch";
			break;
			
		case "gesamt_eigentuemer_verein":
			$c_query_condition = "upper( IV_OBJEKT ) >= 'A' AND IV_EIGENTUEMER_ID = '01' AND IV_AUSGEMUSTERT = 'F' ORDER BY IV_OBJEKT";
			$message_find_string = "Inventar Gesamtliste ohne Ausgemusterte nur Eigentümer Verein, ohne Texte, alphabetisch";
			break;

		case "gesamt_sponsor_verein":
			$c_query_condition = "upper( IV_OBJEKT ) >= 'A' AND IV_SPONSOR = 'OK' AND IV_AUSGEMUSTERT = 'F' ORDER BY IV_OBJEKT";
			$message_find_string = "Inventar Gesamtliste ohne Ausgemusterte nur Eigentümer Verein, ohne Texte, alphabetisch";
			break;
											
		case "gesamt_ohne_buecherei":
			$c_query_condition = "upper( IV_OBJEKT ) >= 'A' AND IV_ORT_ID <> '03' AND IV_AUSGEMUSTERT = 'F' ORDER BY IV_OBJEKT";
			$message_find_string = "Inventar Gesamtliste ohne Bücherei, ohne Ausgemusterte, ohne Texte, alphabetisch";
			break;
		
		case "gesamt_ohne_buecherei_mit_text":
			$display_text = "yes";
			$c_query_condition = "upper( IV_OBJEKT ) >= 'A' AND IV_ORT_ID <> '03' AND IV_AUSGEMUSTERT = 'F' ORDER BY IV_OBJEKT";
			$message_find_string = "Inventar Gesamtliste ohne Bücherei, ohne Ausgemusterte, mit Texten, alphabetisch";
			break;
		//endswitch; // $find_option
		}
	}
}

if ( $action_ok == "yes" and $find_option_ok = "yes" and $c_query_condition !="") {
	if ( $display_text == "no" ) {		
		// text nicht erst mit laden
		$db_result = db_query_list_items_1("IV_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_DATUM_ANSCHAFFUNG, IV_WERT, IV_VERLIEHEN", "IV_MAIN", $c_query_condition);
	} else {
		$db_result = db_query_list_items_1("IV_ID, IV_OBJEKT, IV_HERSTELLER, IV_TYP, IV_SPONSOR, IV_DATUM_ANSCHAFFUNG, IV_WERT, IV_VERLIEHEN, IV_TEXT", "IV_MAIN", $c_query_condition);
	}
} else {
	return;
} 


/**
*
* @category Class
* @package  FPDF
* @author   Oliver
* @link     http://www.fpdf.de/
*/

class PDF extends FPDF
{

	public $my_page_title;

	/**
	*
	* Page header
	* @return
	*/
	function Header() 
	{
		//Logo
		$this->Image('../parts/pict/Logo_SRB_101_klein.jpg', 10, 8, 33);
		//Arial bold 15
		$this->SetFont('Arial', 'B', 14);
		//Move to the right
		$this->Cell(50);
		//Title
		$this->Cell(0, 10, $this->my_page_title, 0, 0);
		//Line break
		$this->Ln(20);
		// Spalten   
		$this->SetFont('Arial', '', 10); 
    	$this->Cell(18, 5, 'Nummer', 1, 0);
	 	$this->Cell(80, 5, 'Objekt', 1, 0);	
	 	$this->Cell(110, 5, 'Hersteller, Typ ', 1, 0);
	 	$this->Cell(25, 5, 'Sponsor', 1, 0);
	 	$this->Cell(22, 5, 'Anschaffung', 1, 0);
	 	$this->Cell(20, 5, 'Wert', 1, 1, 'R');
	 	$this->Ln(10);
	}

	/**
	*
	* Page footer
	* @return
	*/
	function Footer()
	{
		//Position at 1.5 cm from bottom
		$this->SetY(-15);
		//Arial italic 8
		$this->SetFont('Arial', 'I', 8);
		//Page number
		$this->Cell(0, 10, 'Seite '.$this->PageNo().'/{nb}', 0, 0, 'C');
	}
}	

//Instanciation of inherited class
$pdf=new PDF();
$pdf->my_page_title = $message_find_string;
$pdf->AliasNbPages();
$pdf->AddPage('L');
$pdf->SetFont('Arial', '', 8);

$z =0;
$summe = 0;
if ( $db_result ) { 
	foreach ( $db_result as $item ) {	
		$z +=1;
		$pdf->Cell(18, 5, $item['IV_ID'], 0, 0);
		$pdf->Cell(80, 5, utf8_decode($item['IV_OBJEKT']), 0, 0);
		if ( $item['IV_HERSTELLER'] != "" ) {		
				$pdf->Cell(110, 5, substr(utf8_decode($item['IV_HERSTELLER']), 0, 20).', '.substr(utf8_decode($item['IV_TYP']), 0, 80), 0, 0);
		} else { 
			$pdf->Cell(110, 5, substr(utf8_decode($item['IV_TYP']), 0, 100), 0, 0); 
		}
		$pdf->Cell(25, 5, utf8_decode($item['IV_SPONSOR']), 0, 0);
		$pdf->Cell(22, 5, get_date_format_deutsch($item['IV_DATUM_ANSCHAFFUNG']), 0, 0);
		$pdf->Cell(20, 5, $item['IV_WERT'], 0, 1, 'R');
		if ( $display_text == "yes" ) {
			if ( $item['IV_TEXT'] != "" ) {
				$n_cell_heigth = 5;
				if ( strlen(utf8_decode($item['IV_TEXT'])) > 50 ) { 
					$n_cell_heigth = strlen(utf8_decode($item['IV_TEXT'])) / 10; 
				}
				$pdf->Cell(18);
				// doppelte Zeilenumbruche entfernen
				$new_string = str_replace("\n", "", utf8_decode($item['IV_TEXT']));
				// cr durch zeilenumbruch ersetzen
				$new_string = str_replace("\r", "\n", $new_string);
				$pdf->MultiCell(120, 4, $new_string, 0, 1);
			}
		}
		$summe += $item['IV_WERT'];
	} // foreach
} // if

$pdf->Ln(5);
$pdf->Cell(275, 5, "Gesamtsumme: ".$summe, 1, 1, 'R');
	
$pdf->Output();

?>