<?php
//============================================================+
// File name   : example_032.php
// Begin       : 2008-06-09
// Last Update : 2010-08-08
//
// Description : Example 032 for TCPDF class
//               EPS/AI image
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
 * @abstract TCPDF - Example: EPS/AI image
 * @author Nicola Asuni
 * @since 2008-06-09
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 032');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 032', PDF_HEADER_STRING);

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

$pdf->AddPage();

$pdf->Write(0, 'Example of ImageEPS() method for AI and EPS images');

$pdf->ImageEps($file='../images/tiger.ai', $x=10, $y=50, $w=190, $h=190, $link='', $useBoundingBox=true, $align='', $palign='', $border=0, $fitonpage=false);

$pdf->AddPage();

$pdf->ImageEps('../images/bug.eps', 0, 25, 0, 240, "http://www.tcpdf.org", true, 'T', 'C');

$pdf->AddPage();

$pdf->ImageEps('../images/pelican.ai', 15, 70, 180);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_032.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
