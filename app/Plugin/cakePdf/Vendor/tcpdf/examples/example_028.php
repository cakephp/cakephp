<?php
//============================================================+
// File name   : example_028.php
// Begin       : 2008-03-04
// Last Update : 2010-08-08
//
// Description : Example 028 for TCPDF class
//               Changing page formats
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
 * @abstract TCPDF - Example: changing page formats
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
$pdf->SetTitle('TCPDF Example 028');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(10, PDF_MARGIN_TOP, 10);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

$pdf->SetDisplayMode('fullpage', 'SinglePage', 'UseNone');

// set font
$pdf->SetFont('times', 'B', 20);

$pdf->AddPage('P', 'A4');
$pdf->Cell(0, 0, 'A4 PORTRAIT', 1, 1, 'C');

$pdf->AddPage('L', 'A4');
$pdf->Cell(0, 0, 'A4 LANDSCAPE', 1, 1, 'C');

$pdf->AddPage('P', 'A5');
$pdf->Cell(0, 0, 'A5 PORTRAIT', 1, 1, 'C');

$pdf->AddPage('L', 'A5');
$pdf->Cell(0, 0, 'A5 LANDSCAPE', 1, 1, 'C');

$pdf->AddPage('P', 'A6');
$pdf->Cell(0, 0, 'A6 PORTRAIT', 1, 1, 'C');

$pdf->AddPage('L', 'A6');
$pdf->Cell(0, 0, 'A6 LANDSCAPE', 1, 1, 'C');

$pdf->AddPage('P', 'A7');
$pdf->Cell(0, 0, 'A7 PORTRAIT', 1, 1, 'C');

$pdf->AddPage('L', 'A7');
$pdf->Cell(0, 0, 'A7 LANDSCAPE', 1, 1, 'C');


// --- test backward editing ---


$pdf->setPage(1, true);
$pdf->SetY(50);
$pdf->Cell(0, 0, 'A4 test', 1, 1, 'C');

$pdf->setPage(2, true);
$pdf->SetY(50);
$pdf->Cell(0, 0, 'A4 test', 1, 1, 'C');

$pdf->setPage(3, true);
$pdf->SetY(50);
$pdf->Cell(0, 0, 'A5 test', 1, 1, 'C');

$pdf->setPage(4, true);
$pdf->SetY(50);
$pdf->Cell(0, 0, 'A5 test', 1, 1, 'C');

$pdf->setPage(5, true);
$pdf->SetY(50);
$pdf->Cell(0, 0, 'A6 test', 1, 1, 'C');

$pdf->setPage(6, true);
$pdf->SetY(50);
$pdf->Cell(0, 0, 'A6 test', 1, 1, 'C');

$pdf->setPage(7, true);
$pdf->SetY(40);
$pdf->Cell(0, 0, 'A7 test', 1, 1, 'C');

$pdf->setPage(8, true);
$pdf->SetY(40);
$pdf->Cell(0, 0, 'A7 test', 1, 1, 'C');

$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_028.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
