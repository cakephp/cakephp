<?php
//============================================================+
// File name   : example_025.php
// Begin       : 2008-03-04
// Last Update : 2010-08-08
//
// Description : Example 025 for TCPDF class
//               Object Transparency
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
 * @abstract TCPDF - Example: Object Transparency
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
$pdf->SetTitle('TCPDF Example 025');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 025', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', '', 12);

// add a page
$pdf->AddPage();

$txt = 'You can set the transparency of PDF objects using the setAlpha() method.';
$pdf->Write(0, $txt, '', 0, '', true, 0, false, false, 0);

/*
 * setAlpha() gives transparency support. You can set the
 * alpha channel from 0 (fully transparent) to 1 (fully
 * opaque). It applies to all elements (text, drawings,
 * images).
 */

$pdf->SetLineWidth(2);

// draw opaque red square
$pdf->SetFillColor(255, 0, 0);
$pdf->SetDrawColor(127, 0, 0);
$pdf->Rect(30, 40, 60, 60, 'DF');

// set alpha to semi-transparency
$pdf->SetAlpha(0.5);

// draw green square
$pdf->SetFillColor(0, 255, 0);
$pdf->SetDrawColor(0, 127, 0);
$pdf->Rect(50, 60, 60, 60, 'DF');

// draw blue square
$pdf->SetFillColor(0, 0, 255);
$pdf->SetDrawColor(0, 0, 127);
$pdf->Rect(70, 80, 60, 60, 'DF');

// draw jpeg image
$pdf->Image('../images/image_demo.jpg', 90, 100, 60, 60, '', 'http://www.tcpdf.org', '', true, 72);

// restore full opacity
$pdf->SetAlpha(1);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_025.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
