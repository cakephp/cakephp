<?php
//============================================================+
// File name   : example_024.php
// Begin       : 2008-03-04
// Last Update : 2010-08-08
//
// Description : Example 024 for TCPDF class
//               Object Visibility and Layers
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
 * @abstract TCPDF - Example: Object Visibility and Layers
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
$pdf->SetTitle('TCPDF Example 024');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 024', PDF_HEADER_STRING);

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
$pdf->SetFont('times', '', 18);

// add a page
$pdf->AddPage();

/*
 * setVisibility() allows to restrict the rendering of some
 * elements to screen or printout. This can be useful, for
 * instance, to put a background image or color that will
 * show on screen but won't print.
 */

$txt = 'You can limit the visibility of PDF objects to screen or printer by using the setVisibility() method.
Check the print preview of this document to display the alternative text.';

$pdf->Write(0, $txt, '', 0, '', true, 0, false, false, 0);

// change font size
$pdf->SetFontSize(40);

// change text color
$pdf->SetTextColor(0,63,127);

// set visibility only for screen
$pdf->setVisibility('screen');

// write something only for screen
$pdf->Write(0, '[This line is for display]', '', 0, 'C', true, 0, false, false, 0);

// set visibility only for print
$pdf->setVisibility('print');

// change text color
$pdf->SetTextColor(127,0,0);

// write something only for print
$pdf->Write(0, '[This line is for printout]', '', 0, 'C', true, 0, false, false, 0);

// restore visibility
$pdf->setVisibility('all');

// ---------------------------------------------------------

// LAYERS

// start a new layer
$pdf->startLayer('layer1', true, true);

// change font size
$pdf->SetFontSize(18);

// change text color
$pdf->SetTextColor(0,127,0);

$txt = 'Using the startLayer() method you can group PDF objects into layers.
This text is on "layer1".';

// write something
$pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

// close the current layer
$pdf->endLayer();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_024.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
