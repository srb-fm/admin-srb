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
$filename_ok = false;

// check action	
if ( isset($_GET['action']) ) {	
	$action = $_GET['action'];	
	$action_ok = true;
}	

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
		$filename_reg_form = $_GET['sg_file'];
	} else {
		$action_ok = false;
	}

	// check id
	if ( ! filter_var($id, FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000))) ) {
		$id = "";
		$action_ok = false;
		$message ="sdfds";
	}
	// check if filename starts with id
	if ( ! filter_var(substr($filename_reg_form,0, 7), FILTER_VALIDATE_INT, array("options"=>array("min_range"=>1000000))) ) {
		$filename_reg_form = "";
	} else {
		$filename_ok = true;
	}
}

if ( $action_ok == true ) {
	// Paths from Settings
	$tbl_row_config_serv = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings'");
	// are we on server-line A or B?
	if ( $tbl_row_config_serv->USER_SP_PARAM_3 == $_SERVER['SERVER_NAME'] ) {
		$tbl_row_config_C = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_c_A'");
	}
	if ( $tbl_row_config_serv->USER_SP_PARAM_4 == $_SERVER['SERVER_NAME'] ) {
		$tbl_row_config_C = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'server_settings_paths_c_B'");
	}
	
	// it seems like a valid filename, display reg-form if exists
	// this is only for non http in filename
	if ( $filename_ok ) {
		$file_name = new SplFileInfo($filename_reg_form);
		$file_name_base = basename($file_name, "mp3");
		$php_filename = $tbl_row_config_C->USER_SP_PARAM_2.$file_name_base."pdf";
		$filename_reg_form = $file_name_base."pdf";
		if ( file_exists($php_filename) ) {
			header("Location: http://".$_SERVER['SERVER_NAME'].$tbl_row_config_C->USER_SP_PARAM_1.$file_name_base."pdf");
			exit;
		}
	}
	
	// no reg-form found, 
	// build filename also for http-streams and search again
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
		
		if ( !$filename_ok ) {
			// filename couldt be a url, then we must buildt it
			// it's possible that keyword has additional keywords after an whitspace
			// so cut it 
			//$pos = strpos($tbl_row_sg->SG_HF_CONT_STICHWORTE, " ");
			//if ( ! $pos ) {
			//	$keyword = replace_umlaute_sonderzeichen($tbl_row_sg->SG_HF_CONT_STICHWORTE);
			//} else {
			//	$keyword = substr(replace_umlaute_sonderzeichen($tbl_row_sg->SG_HF_CONT_STICHWORTE),0, $pos);	
			//}
			//$filename_reg_form = $tbl_row_sg->SG_HF_CONT_ID."_"
			//			.replace_umlaute_sonderzeichen($tbl_row_ad->AD_NAME)
			//			."_"
			//			.$keyword
			//			.".pdf";
			//$php_filename = $tbl_row_config_C->USER_SP_PARAM_2.$filename_reg_form;
			
			list($filename_reg_form, $filename_reg_form_php) = sg_build_filename_for_reg_form( 
				$tbl_row_sg->SG_HF_CONT_FILENAME, $tbl_row_sg->SG_HF_CONT_STICHWORTE, 
				$tbl_row_sg->SG_HF_CONT_ID, $tbl_row_ad->AD_NAME );
		
			// now we have an valid filename also for http-streams
			// we will try to find a existing reg-form for this 
			if ( file_exists($filename_reg_form_php) ) {
				header("Location: http://".$_SERVER['SERVER_NAME'].$tbl_row_config_C->USER_SP_PARAM_1.$filename_reg_form);
				exit;
			}
		}
	
	// no reg-fom found, build it
	// Userdata
	$tbl_row_1 = db_query_display_item_1("USER_DATA", "none");
	$tbl_row_a = db_query_display_item_1("USER_SPECIALS", "USER_SP_SPECIAL = 'Sendung_Anmeldung'");	
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
		$this->MultiCell(0, 5, $this->my_page_adress, 0, 'R');
  		$this->SetFont('Arial', 'B', 12);
  		$this->Ln(20);
  		//Title
  		$this->Cell(0, 10, $this->my_page_title, 0, 0);
  		//Line break
  		$this->Ln(5);
  		$this->SetFont('Arial', '', 10);
  		//Line break
  		$this->Ln(20);
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
$pdf->my_page_title = "Sendeanmeldung";
$pdf->my_page_adress = utf8_decode($tbl_row_1->USER_AD_NAME)."\n".utf8_decode($tbl_row_1->USER_AD_STR)."\n".$tbl_row_1->USER_AD_PLZ." ".utf8_decode($tbl_row_1->USER_AD_ORT);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Datum/ Zeit/ Länge"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 5, get_german_day_name_a(substr($tbl_row_sg->SG_HF_TIME, 0, 10))
	.", ".get_date_format_deutsch(substr($tbl_row_sg->SG_HF_TIME, 0, 10))
	."/ ".substr($tbl_row_sg->SG_HF_TIME, 11, 8)."/ ".$tbl_row_sg->SG_HF_DURATION , 0, 0);
//$pdf->Cell(30, 5, substr($tbl_row_sg->SG_HF_TIME, 11, 8)."/ ".$tbl_row_sg->SG_HF_DURATION, 0, 0);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Titel"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_TITEL), 0, 0);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Untertitel"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_UNTERTITEL), 0, 0);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Sendeverantwortlich"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, utf8_decode($tbl_row_ad->AD_VORNAME)." ".utf8_decode($tbl_row_ad->AD_NAME).", ".utf8_decode($tbl_row_ad->AD_ORT), 0, 0);
$pdf->Ln(5);
$pdf->Line(11,90,110,90);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Stichworte"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_STICHWORTE), 0, 0);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Internet"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_WEB), 0, 0);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Gengre/ Sprache"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, db_query_load_value_by_id("SG_GENRE", "SG_GENRE_ID", $tbl_row_sg->SG_HF_CONT_GENRE_ID)."/ ".db_query_load_value_by_id("SG_SPEECH", "SG_SPEECH_ID", $tbl_row_sg->SG_HF_CONT_SPEECH_ID), 0, 0);
$pdf->Ln(5);
$pdf->Line(11,110,110,110);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Einstellungen/Quelle"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
if ( rtrim($tbl_row_sg->SG_HF_INFOTIME) == "T" ) {
	$pdf->Cell(0, 5,"InfoTime - Sendung/ ".db_query_load_value_by_id("SG_HF_SOURCE", "SG_HF_SOURCE_ID", rtrim($tbl_row_sg->SG_HF_SOURCE_ID)), 0, 0);
}
if ( rtrim($tbl_row_sg->SG_HF_MAGAZINE) == "T" ) {
	$pdf->Cell(0, 5,"Magazin - Sendung/ ".db_query_load_value_by_id("SG_HF_SOURCE", "SG_HF_SOURCE_ID", rtrim($tbl_row_sg->SG_HF_SOURCE_ID)), 0, 0);
}
if ( rtrim($tbl_row_sg->SG_HF_INFOTIME) != "T" and rtrim($tbl_row_sg->SG_HF_MAGAZINE) != "T" ) {
	$pdf->Cell(0, 5,"Normal - Sendung/ ".db_query_load_value_by_id("SG_HF_SOURCE", "SG_HF_SOURCE_ID", rtrim($tbl_row_sg->SG_HF_SOURCE_ID)), 0, 0);
}
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Content/ Sendung-Nr"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, $tbl_row_sg->SG_HF_CONT_ID."/ ".$tbl_row_sg->SG_HF_ID, 0, 0);
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 5, utf8_decode("Dateiname"), 0, 0);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, utf8_decode($tbl_row_sg->SG_HF_CONT_FILENAME), 0, 0);

$pdf->Line(11, 132, 200, 132);
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30);
$n_cell_heigth = 5;
if ( strlen(utf8_decode($tbl_row_a->USER_SP_TEXT)) > 50 ) { 
	$n_cell_heigth = strlen(utf8_decode($tbl_row_a->USER_SP_TEXT)) / 10; 
}
// doppelte Zeilenumbrueche entfernen
$new_string = str_replace("\n", "", utf8_decode($tbl_row_a->USER_SP_TEXT));
//cr durch zeilenumbruch ersetzen
$new_string = str_replace("\r", "\n", $new_string);
$pdf->MultiCell(120, 4, $new_string, 0, 1);

$pdf->Ln(5);
$pdf->Line(18, 262, 200, 262);
$pdf->SetY(265);
$pdf->Cell(18);
$pdf->Cell(0, 5, "Unterschrift Nutzer/ SRB, ".date("d.m.Y"), 0, 0);

$pdf->Output("../admin_srb_export/xy.pdf");
header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="'.$filename_reg_form.'"');
readfile('../admin_srb_export/xy.pdf');
exit;

?>