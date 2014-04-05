<?php
//============================================================+
// File name   : example_049.php
// Begin       : 2009-04-03
// Last Update : 2011-05-12
//
// Description : Example 049 for TCPDF class
//               WriteHTML with TCPDF callback functions
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
 * @abstract TCPDF - Example: WriteHTML with TCPDF callback functions
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
$pdf->SetTitle('TCPDF Example 049');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 049', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

IMPORTANT:
If you are printing user-generated content, tcpdf tag can be unsafe.
You can disable this tag by setting to false the K_TCPDF_CALLS_IN_HTML
constant on TCPDF configuration file.

For security reasons, the parameters for the 'params' attribute of TCPDF 
tag must be prepared as an array and encoded with the 
serializeTCPDFtagParameters() method (see the example below).

 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


$html = '<h1>Test TCPDF Methods in HTML</h1>
<h2 style="color:red;">IMPORTANT:</h2>
<span style="color:red;">If you are printing user-generated content, tcpdf tag can be unsafe.<br />
You can disable this tag by setting to false the <b>K_TCPDF_CALLS_IN_HTML</b> constant on TCPDF configuration file.</span>
<h2>write1DBarcode method in HTML</h2>';

$params = $pdf->serializeTCPDFtagParameters(array('CODE 39', 'C39', '', '', 80, 30, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
$html .= '<tcpdf method="write1DBarcode" params="'.$params.'" />';

$params = $pdf->serializeTCPDFtagParameters(array('CODE 128', 'C128', '', '', 80, 30, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
$html .= '<tcpdf method="write1DBarcode" params="'.$params.'" />';

$html .= '<tcpdf method="AddPage" /><h2>Graphic Functions</h2>';

$params = $pdf->serializeTCPDFtagParameters(array(0));
$html .= '<tcpdf method="SetDrawColor" params="'.$params.'" />';

$params = $pdf->serializeTCPDFtagParameters(array(50, 50, 40, 10, 'DF', array(), array(0,128,255)));
$html .= '<tcpdf method="Rect" params="'.$params.'" />';


// output the HTML content
$pdf->writeHTML($html, true, 0, true, 0);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_049.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
