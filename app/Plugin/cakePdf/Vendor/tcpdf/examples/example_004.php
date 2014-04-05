<?php
//============================================================+
// File name   : example_004.php
// Begin       : 2008-03-04
// Last Update : 2010-10-08
//
// Description : Example 004 for TCPDF class
//               Cell stretching
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
 * @abstract TCPDF - Example: Cell stretching
 * @author Nicola Asuni
 * @since 2008-03-04
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 004');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 004', PDF_HEADER_STRING);

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

// ---------------------------------------------------------

// set font
$pdf->SetFont('times', '', 11);

// add a page
$pdf->AddPage();

//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')

// test Cell stretching
$pdf->Cell(0, 0, 'TEST CELL STRETCH: no stretch', 1, 1, 'C', 0, '', 0);
$pdf->Cell(0, 0, 'TEST CELL STRETCH: scaling', 1, 1, 'C', 0, '', 1);
$pdf->Cell(0, 0, 'TEST CELL STRETCH: force scaling', 1, 1, 'C', 0, '', 2);
$pdf->Cell(0, 0, 'TEST CELL STRETCH: spacing', 1, 1, 'C', 0, '', 3);
$pdf->Cell(0, 0, 'TEST CELL STRETCH: force spacing', 1, 1, 'C', 0, '', 4);

$pdf->Ln(5);

$pdf->Cell(45, 0, 'TEST CELL STRETCH: scaling', 1, 1, 'C', 0, '', 1);
$pdf->Cell(45, 0, 'TEST CELL STRETCH: force scaling', 1, 1, 'C', 0, '', 2);
$pdf->Cell(45, 0, 'TEST CELL STRETCH: spacing', 1, 1, 'C', 0, '', 3);
$pdf->Cell(45, 0, 'TEST CELL STRETCH: force spacing', 1, 1, 'C', 0, '', 4);

$pdf->AddPage();

// example using general stretching and spacing

for ($stretching = 90; $stretching <= 110; $stretching += 10) {
	for ($spacing = -0.254; $spacing <= 0.254; $spacing += 0.254) {

		// set general stretching (scaling) value
		$pdf->setFontStretching($stretching);

		// set general spacing value
		$pdf->setFontSpacing($spacing);

		$pdf->Cell(0, 0, 'Stretching '.$stretching.'%, Spacing '.sprintf('%+.3F', $spacing).'mm, no stretch', 1, 1, 'C', 0, '', 0);
		$pdf->Cell(0, 0, 'Stretching '.$stretching.'%, Spacing '.sprintf('%+.3F', $spacing).'mm, scaling', 1, 1, 'C', 0, '', 1);
		$pdf->Cell(0, 0, 'Stretching '.$stretching.'%, Spacing '.sprintf('%+.3F', $spacing).'mm, force scaling', 1, 1, 'C', 0, '', 2);
		$pdf->Cell(0, 0, 'Stretching '.$stretching.'%, Spacing '.sprintf('%+.3F', $spacing).'mm, spacing', 1, 1, 'C', 0, '', 3);
		$pdf->Cell(0, 0, 'Stretching '.$stretching.'%, Spacing '.sprintf('%+.3F', $spacing).'mm, force spacing', 1, 1, 'C', 0, '', 4);

		$pdf->Ln(2);
	}
}

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_004.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
