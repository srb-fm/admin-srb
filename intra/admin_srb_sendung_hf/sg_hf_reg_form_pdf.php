<?php

/** 
* Sednung HF pdf Sendeanmeldung 
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
$action_ok = false;
	
// action pruefen	
if ( isset($_GET['action']) ) {	
	$action = $_GET['action'];	
	$action_ok = true;
}	
//if ( isset( $_POST['action'] ) ) { 
//	$action = $_POST['action']; $action_ok = true;
//}
if ( $action != "pdf" ) { 
	$action_ok = false;
}
if ( $action_ok == false ) {
	$message = "Keine Anweisung. Nichts zu tun..... "; 
}
		
if ( $action_ok == true ) {
	if ( isset($_GET['sg_id']) ) {	
		$id = $_GET['sg_id'];
	} else {
		$action_ok = false;
	}
	if ( isset($_GET['sg_file']) ) {	
		$sg_filename = $_GET['sg_file'];
	} else {
		$action_ok = false;
	}

	// check id
	if ( ! filter_var($id, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000))) ) {
		$id = "";
		$action_ok = false;
		$message ="sdfds";
	}
	if ( ! filter_var(substr($sg_filename,0, 7), FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000))) ) {
		$sg_filename = "";
		$action_ok = false;
	}
}

if ( $action_ok == true ) {
	// Paths player-audios from Settings
	$tbl_row_config = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'INTRA_Sendung_HF'");
	$tbl_row_config_serv = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings'");
	// are we on server-line A or B?
	if ( $tbl_row_config_serv->USER_SP_PARAM_3 == $_SERVER['SERVER_NAME'] ) {
		$tbl_row_config_A = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_a_A'");
		$tbl_row_config_B = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_b_A'");
	}
	if ( $tbl_row_config_serv->USER_SP_PARAM_4 == $_SERVER['SERVER_NAME'] ) {
		$tbl_row_config_A = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_a_B'");
		$tbl_row_config_B = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_b_B'");
	}
	
	$file_name = new SplFileInfo($sg_filename);
	$file_name_base = basename($file_name, "mp3");
	//$php_filename = $tbl_row_config_B->USER_SP_PARAM_12.$file_name_base."pdf";
	$php_filename = "/mnt/Data_Server_03/Play_Out_Server/Sendeanmeldungen/".$file_name_base."pdf";
	$sg_filename = $file_name_base."pdf";
	if ( file_exists($php_filename) ) {
		header("Location: http://".$_SERVER['SERVER_NAME'].$tbl_row_config_B->USER_SP_PARAM_12.$file_name_base."pdf");
		//header('Content-type: application/pdf');
		//header('Content-Disposition: inline;"'.$sg_filename.'"');
		//readfile($php_filename);
		exit;
	}

	$tbl_row_sg = db_query_sg_display_item_1($_GET['sg_id']);
	if ( !$tbl_row_sg ) { 
		$message .= "Fehler bei Abfrage Sendung!"; 
		$action_ok = false;
	} else {
		$tbl_row_ad = db_query_display_item_1("AD_MAIN", "AD_ID = " .$tbl_row_sg->SG_HF_CONT_AD_ID);
		if ( !$tbl_row_ad ) { 
			$message .= "Fehler bei Abfrage Adresse!"; 
			$action_ok = false;
		}
	// Userdata
	$tbl_row_1 = db_query_display_item_1("USER_DATA", "none");
	}
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
  		$this->Image('../parts/pict/logo_user.jpg', 10, 8, 33);
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
  		$this->Cell(18, 5, "", 1 , 0);
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
$pdf->my_page_title = "Sendeanmeldung".$php_filename;
$pdf->my_page_adress = utf8_decode($tbl_row_1->USER_AD_NAME)."\n".utf8_decode($tbl_row_1->USER_AD_STR)."\n".$tbl_row_1->USER_AD_PLZ." ".utf8_decode($tbl_row_1->USER_AD_ORT);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 8);
	
$pdf->Cell(18, 5, $tbl_row_sg->SG_ID, 0, 0);
$pdf->Cell(100, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_TITEL), 0, 0);
$pdf->Cell(25, 5, utf8_decode($tbl_row_sg->IV_SPONSOR), 0, 0);
$pdf->Cell(22, 5, get_german_day_name_a(substr($tbl_row_sg->SG_HF_TIME, 0, 10)), 0, 0);
$pdf->Cell(20, 5, get_date_format_deutsch(substr($tbl_row_sg->SG_HF_TIME, 0, 10)), 0, 1, R);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(18);
$pdf->Cell(80, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_UNTERTITEL), 0, 0);
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(80, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_STICHWORTE), 0, 20);
$pdf->Cell(25, 5, $tbl_row_sg->SG_HF_CONT_ID, 0, 0);
$pdf->Ln(5);
//$pdf->Cell(18);
//if ( $tbl_row->IV_TEXT != "" ) {
	//$n_cell_heigth = 5;
	//if ( strlen(utf8_decode($tbl_row->IV_TEXT)) > 50 ) { 
		//$n_cell_heigth = strlen(utf8_decode($tbl_row->IV_TEXT)) / 10; 
	//}
	// doppelte Zeilenumbruche entfernen
	//$new_string = str_replace("\n", "", utf8_decode($tbl_row->IV_TEXT));
	// cr durch zeilenumbruch ersetzen
	//$new_string = str_replace("\r", "\n", $new_string);
	//$pdf->MultiCell(120, 4, $new_string, 0, 1);
//}
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(160, 5, "Web: ".utf8_decode($tbl_row_sg->SG_HF_CONT_WEB), 0, 0);
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(22, 5, "File: ".$tbl_row_sg->SG_HF_CONT_FILENAME, 0, 0);
$pdf->Ln(5);
$pdf->Cell(18);
$pdf->Cell(160, 5, "genre: ".db_query_load_value_by_id("SG_GENRE", "SG_GENRE_ID", $tbl_row_sg->SG_HF_CONT_GENRE_ID), 0, 0);
$pdf->Ln(10);
$pdf->Line(18, 250, 200, 250);
$pdf->SetY(255);
$pdf->Cell(18);
$pdf->Cell(160, 5, "Unterschrift Studioleitung, ".date("d.m.Y"), 0, 0);

//$pdf->Output();
$pdf->Output("../admin_srb_export/xy.pdf");
//$pdf->Output($php_filename);
//header("Location: ../admin_srb_export/xy.pdf");
header('Content-type: application/pdf');
//header('Content-Disposition: attachment; filename="July Report.pdf"');
header('Content-Disposition: inline; filename="'.$sg_filename.'"');
readfile('../admin_srb_export/xy.pdf');
//readfile($php_filename);
exit;

?>