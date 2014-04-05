<?php
//============================================================+
// File name   : example_019.php
// Begin       : 2008-03-07
// Last Update : 2010-08-08
//
// Description : Example 019 for TCPDF class
//               Non unicode with alternative config file
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
 * @abstract TCPDF - Example: Non unicode with alternative config file
 * @author Nicola Asuni
 * @since 2008-03-04
 */

require_once('../config/lang/eng.php');

// load alternative config file
require_once('../config/tcpdf_config_alt.php');
define("K_TCPDF_EXTERNAL_CONFIG", true);

require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

// Set document information dictionary in unicode mode
$pdf->SetDocInfoUnicode(true);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni [€]');
$pdf->SetTitle('TCPDF Example 019');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 019', PDF_HEADER_STRING);

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

// set some language dependent data:
$lg = Array();
$lg['a_meta_charset'] = 'ISO-8859-1';
$lg['a_meta_dir'] = 'ltr';
$lg['a_meta_language'] = 'en';
$lg['w_page'] = 'page';

//set some language-dependent strings
$pdf->setLanguageArray($lg);

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 12);

// add a page
$pdf->AddPage();

// set color for background
$pdf->SetFillColor(200, 255, 200);

$txt = 'An alternative configuration file is used on this example.
Check the definition of the K_TCPDF_EXTERNAL_CONFIG constant on the source code.';

// print some text
$pdf->MultiCell(0, 0, $txt."\n", 1, 'J', 1, 1, '', '', true, 0, false, true, 0);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_019.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
