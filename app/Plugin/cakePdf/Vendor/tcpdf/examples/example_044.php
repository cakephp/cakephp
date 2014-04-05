<?php
//============================================================+
// File name   : example_044.php
// Begin       : 2009-01-02
// Last Update : 2010-08-08
//
// Description : Example 044 for TCPDF class
//               Move, copy and delete pages
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
 * @abstract TCPDF - Example: Move, copy and delete pages
 * @author Nicola Asuni
 * @since 2009-01-02
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 044');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 044', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', 'B', 40);

// print a line using Cell()
$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: A', 0, 1, 'L');

// add some vertical space
$pdf->Ln(10);

// print some text
$pdf->SetFont('times', 'I', 16);
$txt = 'TCPDF allows you to Copy, Move and Delete pages.';
$pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

$pdf->SetFont('helvetica', 'B', 40);

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: B', 0, 1, 'L');

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: D', 0, 1, 'L');

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: E', 0, 1, 'L');

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: E-2', 0, 1, 'L');

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: F', 0, 1, 'L');

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: C', 0, 1, 'L');

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: G', 0, 1, 'L');

// Move page 7 to page 3
$pdf->movePage(7, 3);

// Delete page 6
$pdf->deletePage(6);

$pdf->AddPage();
$pdf->Cell(0, 10, 'PAGE: H', 0, 1, 'L');

// copy the second page
$pdf->copyPage(2);

// NOTE: to insert a page to a previous position, you can add a new page to the end of document and then move it using movePage().

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_044.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
