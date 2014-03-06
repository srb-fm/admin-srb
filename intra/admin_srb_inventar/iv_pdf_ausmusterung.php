<?php

/** 
* Inventar pdf Ausmusterungsformular 
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
	
// action pruefen	
if ( isset( $_GET['action'] ) ) {	
	$action = $_GET['action'];	$action_ok = "yes";
}	
if ( isset( $_POST['action'] ) ) { 
	$action = $_POST['action']; $action_ok = "yes";
}
if ( $action != "pdf_ausmusterung" ) { 
	$action_ok = "no";
}
if ( $action_ok != "yes" ) {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
		
if ( $action_ok == "yes" ) {
	if ( isset($_GET['iv_id']) ) {	
		$id = $_GET['iv_id'];
	}
	if ( isset($_POST['iv_id']) ) {	
		$id = $_POST['iv_id'];
	}

	// check id
	if ( ! filter_var($id, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000 ))) ) {
		$id = "";
		$action_ok = "no";
	}
}

if ( $action_ok != "yes" ) {
	$message = "Fehler bei Uebergabe..... ";
	return; 
} else {
	// IV-Daten
	$tbl_row = db_query_display_item_1("IV_MAIN", "IV_ID = ".$id);	
	// Userdaten
	$tbl_row_1 = db_query_display_item_1("USER_DATA", "none");
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
	public $my_page_adress;

	/**
	*
	* Page header
	* @return
	*/
	function Header() 
	{
  		//Logo
  		$this->Image('../parts/pict/Logo_SRB_101_klein.jpg', 10, 8, 33);
		$this->SetFont('Arial', 'B', 10);
		$this->SetX(140);
		$this->MultiCell(0, 5, $this->my_page_adress, 0, R);
  		$this->SetFont('Arial', 'B', 12);
  		$this->Ln(20);
  		//Title
  		$this->Cell(0, 10, $this->my_page_title, 0, 0);
  		//Line break
  		$this->Ln(5);
  		$this->SetFont('Arial', '', 10);
  		//Line break
  		$this->Ln(20);
		// Spalten   
  		$this->Cell(18, 5, 'Nummer', 1 , 0);
		$this->Cell(100, 5, 'Objekt, Typ, Hersteller, Ser.-Nr.', 1, 0);	
		$this->Cell(25, 5, 'Sponsor', 1, 0);
		$this->Cell(22, 5, 'Anschaffung', 1, 0);
		$this->Cell(20, 5, 'Wert', 1, 1, R);
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
$pdf->my_page_title = "INVENTAR - Ausmusterungsprotokoll";
$pdf->my_page_adress = utf8_decode($tbl_row_1->USER_AD_NAME)."\n".utf8_decode($tbl_row_1->USER_AD_STR)."\n".$tbl_row_1->USER_AD_PLZ." ".utf8_decode($tbl_row_1->USER_AD_ORT);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 8);
	
$pdf->Cell(18, 5, $tbl_row->IV_ID, 0, 0);
$pdf->Cell(100, 5, utf8_decode($tbl_row->IV_OBJEKT), 0, 0);
$pdf->Cell(25, 5, utf8_decode($tbl_row->IV_SPONSOR), 0, 0);
$pdf->Cell(22, 5, get_date_format_deutsch($tbl_row->IV_DATUM_ANSCHAFFUNG), 0, 0);
$pdf->Cell(20, 5, $tbl_row->IV_WERT, 0, 1, R);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(18);
$pdf->Cell(80, 5, utf8_decode($tbl_row->IV_TYP), 0, 0);
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(80, 5, utf8_decode($tbl_row->IV_HERSTELLER), 0, 20);
$pdf->Cell(25, 5, $tbl_row->IV_SERIEN_NR, 0, 0);
$pdf->Ln(5);
$pdf->Cell(18);
if ( $tbl_row->IV_TEXT != "" ) {
	$n_cell_heigth = 5;
	if ( strlen(utf8_decode($tbl_row->IV_TEXT)) > 50 ) { 
		$n_cell_heigth = strlen(utf8_decode($tbl_row->IV_TEXT)) / 10; 
	}
	// doppelte Zeilenumbruche entfernen
	$new_string = str_replace("\n", "", utf8_decode($tbl_row->IV_TEXT));
	// cr durch zeilenumbruch ersetzen
	$new_string = str_replace("\r", "\n", $new_string);
	$pdf->MultiCell(120, 4, $new_string, 0, 1);
}
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(160, 5, "Ausmunsterungsgrund: ".utf8_decode($tbl_row->IV_AUS_GRUND), 0, 0);
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(22, 5, "Ausgemustert am: ".get_date_format_deutsch($tbl_row->IV_AUS_DATUM), 0, 0);
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(160, 5, "Ausmusterungsziel: ".utf8_decode($tbl_row->IV_AUS_ZIEL), 0, 0);
$pdf->Ln(10);
$pdf->Line(18, 250, 200, 250);
$pdf->SetY(255);
$pdf->Cell(18);
$pdf->Cell(160, 5, "Unterschrift Studioleitung, ".date("d.m.Y"), 0, 0);

$pdf->Output();

?>