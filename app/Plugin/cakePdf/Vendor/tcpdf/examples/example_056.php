<?php
//============================================================+
// File name   : example_056.php
// Begin       : 2010-03-26
// Last Update : 2011-12-10
//
// Description : Example 056 for TCPDF class
//               Crop marks and color registration bars
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
 * @abstract TCPDF - Example: Crop marks and color registration bars
 * @author Nicola Asuni
 * @since 2010-03-26
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 056');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 056', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', '', 20);

// add a page
$pdf->AddPage();

$pdf->Write(0, 'Example of Crop Marks and Color Registration Bars', '', 0, 'L', true, 0, false, false, 0);

$pdf->Ln(5);

// color registration bars

$pdf->colorRegistrationBar(50, 70, 40, 40, true, false, 'A,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar(90, 70, 40, 40, true, true, 'A,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar(50, 115, 80, 5, false, true, 'A,W,R,G,B,C,M,Y,K');
$pdf->colorRegistrationBar(135, 70, 5, 50, false, false, 'A,W,R,G,B,C,M,Y,K');

// corner crop marks

$pdf->cropMark(50, 70, 10, 10, 'TL', array(0,0,0));
$pdf->cropMark(140, 70, 10, 10, 'TR', array(0,0,0));
$pdf->cropMark(50, 120, 10, 10, 'BL', array(0,0,0));
$pdf->cropMark(140, 120, 10, 10, 'BR', array(0,0,0));

// various crop marks

$pdf->cropMark(95, 65, 5, 5, 'LEFT,TOP,RIGHT', array(255,0,0));
$pdf->cropMark(95, 125, 5, 5, 'LEFT,BOTTOM,RIGHT', array(255,0,0));

$pdf->cropMark(45, 95, 5, 5, 'TL,BL', array(0,255,0));
$pdf->cropMark(145, 95, 5, 5, 'TR,BR', array(0,255,0));

$pdf->cropMark(95, 140, 5, 5, 'A,D', array(0,0,255));

// registration marks

$pdf->registrationMark(40, 60, 5, false, array(0,0,0), array(255,255,255));
$pdf->registrationMark(150, 60, 5, true, array(0,0,0), array(255,255,0));
$pdf->registrationMark(40, 130, 5, true, array(0,0,0), array(255,255,0));
$pdf->registrationMark(150, 130, 5, false, array(0,0,0), array(255,255,255));

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_056.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
