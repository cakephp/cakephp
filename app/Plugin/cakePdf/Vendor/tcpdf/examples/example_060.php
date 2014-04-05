<?php
//============================================================+
// File name   : example_060.php
// Begin       : 2010-05-17
// Last Update : 2010-08-08
//
// Description : Example 060 for TCPDF class
//               Advanced page settings.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               Manor Coach House, Church Hill
//               Aldershot, Hants, GU12 4RQ
//               UK
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Advanced page settings.
 * @author Nicola Asuni
 * @since 2010-05-17
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 060');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 060', PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// set font
$pdf->SetFont('helvetica', '', 20);

// ---------------------------------------------------------

// set page format (read source code documentation for further information)
$page_format = array(
	'MediaBox' => array ('llx' => 0, 'lly' => 0, 'urx' => 210, 'ury' => 297),
	'CropBox' => array ('llx' => 0, 'lly' => 0, 'urx' => 210, 'ury' => 297),
	'BleedBox' => array ('llx' => 5, 'lly' => 5, 'urx' => 205, 'ury' => 292),
	'TrimBox' => array ('llx' => 10, 'lly' => 10, 'urx' => 200, 'ury' => 287),
	'ArtBox' => array ('llx' => 15, 'lly' => 15, 'urx' => 195, 'ury' => 282),
	'Dur' => 3,
	'trans' => array(
		'D' => 1.5,
		'S' => 'Split',
		'Dm' => 'V',
		'M' => 'O'
	),
	'Rotate' => 90,
	'PZ' => 1,
);

// Check the example n. 29 for viewer preferences

// add first page ---
$pdf->AddPage('P', $page_format, false, false);
$pdf->Cell(0, 12, 'First Page', 1, 1, 'C');

// add second page ---
$page_format['Rotate'] = 270;
$pdf->AddPage('P', $page_format, false, false);
$pdf->Cell(0, 12, 'Second Page', 1, 1, 'C');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_060.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
