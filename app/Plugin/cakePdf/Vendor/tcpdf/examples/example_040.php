<?php
//============================================================+
// File name   : example_040.php
// Begin       : 2008-10-28
// Last Update : 2010-08-31
//
// Description : Example 040 for TCPDF class
//               Booklet mode (double-sided pages)
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
 * @abstract TCPDF - Example: Booklet mode (double-sided pages)
 * @author Nicola Asuni
 * @since 2008-10-28
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 040');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 040', PDF_HEADER_STRING);

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

// set display mode
$pdf->SetDisplayMode($zoom='fullpage', $layout='TwoColumnRight', $mode='UseNone');

// set pdf viewer preferences
$pdf->setViewerPreferences(array('Duplex' => 'DuplexFlipLongEdge'));

// set booklet mode
$pdf->SetBooklet(true, 10, 30);

// set core font
$pdf->SetFont('helvetica', '', 18);

// add a page (left page)
$pdf->AddPage();

$pdf->Write(0, 'Example of booklet mode', '', 0, 'L', true, 0, false, false, 0);

// print a line using Cell()
$pdf->Cell(0, 0, 'PAGE 1', 1, 1, 'C');


// add a page (right page)
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 0, 'PAGE 2', 1, 1, 'C');


// add a page (left page)
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 0, 'PAGE 3', 1, 1, 'C');

// add a page (right page)
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 0, 'PAGE 4', 1, 1, 'C');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_040.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
