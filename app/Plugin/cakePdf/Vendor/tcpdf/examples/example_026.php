<?php
//============================================================+
// File name   : example_026.php
// Begin       : 2008-03-04
// Last Update : 2010-08-08
//
// Description : Example 026 for TCPDF class
//               Text Rendering Modes and Text Clipping
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
 * @abstract TCPDF - Example: Text Rendering Modes and Text Clipping
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
$pdf->SetTitle('TCPDF Example 026');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 026', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', '', 22);

// add a page
$pdf->AddPage();

// set color for text stroke
$pdf->SetDrawColor(255,0,0);


$pdf->setTextRenderingMode($stroke=0, $fill=true, $clip=false);
$pdf->Write(0, 'Fill text', '', 0, '', true, 0, false, false, 0);

$pdf->setTextRenderingMode($stroke=0.2, $fill=false, $clip=false);
$pdf->Write(0, 'Stroke text', '', 0, '', true, 0, false, false, 0);

$pdf->setTextRenderingMode($stroke=0.2, $fill=true, $clip=false);
$pdf->Write(0, 'Fill, then stroke text', '', 0, '', true, 0, false, false, 0);

$pdf->setTextRenderingMode($stroke=0, $fill=false, $clip=false);
$pdf->Write(0, 'Neither fill nor stroke text (invisible)', '', 0, '', true, 0, false, false, 0);


// * * * CLIPPING MODES  * * * * * * * * * * * * * * * * * *

$pdf->StartTransform();
$pdf->setTextRenderingMode($stroke=0, $fill=true, $clip=true);
$pdf->Write(0, 'Fill text and add to path for clipping', '', 0, '', true, 0, false, false, 0);
$pdf->Image('../images/image_demo.jpg', 15, 65, 170, 10, '', '', '', true, 72);
$pdf->StopTransform();

$pdf->StartTransform();
$pdf->setTextRenderingMode($stroke=0.3, $fill=false, $clip=true);
$pdf->Write(0, 'Stroke text and add to path for clipping', '', 0, '', true, 0, false, false, 0);
$pdf->Image('../images/image_demo.jpg', 15, 75, 170, 10, '', '', '', true, 72);
$pdf->StopTransform();

$pdf->StartTransform();
$pdf->setTextRenderingMode($stroke=0.3, $fill=true, $clip=true);
$pdf->Write(0, 'Fill, then stroke text and add to path for clipping', '', 0, '', true, 0, false, false, 0);
$pdf->Image('../images/image_demo.jpg', 15, 85, 170, 10, '', '', '', true, 72);
$pdf->StopTransform();

$pdf->StartTransform();
$pdf->setTextRenderingMode($stroke=0, $fill=false, $clip=true);
$pdf->Write(0, 'Add text to path for clipping', '', 0, '', true, 0, false, false, 0);
$pdf->Image('../images/image_demo.jpg', 15, 95, 170, 10, '', '', '', true, 72);
$pdf->StopTransform();

// reset text rendering mode
$pdf->setTextRenderingMode($stroke=0, $fill=true, $clip=false);

// * * * HTML MODE * * * * * * * * * * * * * * * * * * * * *

// The following attributes were added to HTML:
// stroke : stroke width
// strokecolor : stroke color
// fill : true (default) to fill the font, false otherwise


// create some HTML content with text rendering modes
$html  = '<span stroke="0" fill="true">HTML Fill text</span><br />';
$html .= '<span stroke="0.2" fill="false">HTML Stroke text</span><br />';
$html .= '<span stroke="0.2" fill="true" strokecolor="#FF0000" color="#FFFF00">HTML Fill, then stroke text</span><br />';
$html .= '<span stroke="0" fill="false">HTML Neither fill nor stroke text (invisible)</span><br />';

// output the HTML content
$pdf->writeHTML($html, true, 0, true, 0);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_026.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
