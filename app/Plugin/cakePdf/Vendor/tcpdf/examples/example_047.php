<?php
//============================================================+
// File name   : example_047.php
// Begin       : 2009-03-19
// Last Update : 2010-08-08
//
// Description : Example 047 for TCPDF class
//               Transactions
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
 * @abstract TCPDF - Example: Transactions
 * @author Nicola Asuni
 * @since 2009-03-19
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 047');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 047', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', '', 16);

// add a page
$pdf->AddPage();

$txt = 'Example of Transactions.
TCPDF allows you to undo some operations using the Transactions.
Check the source code for further information.';
$pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

$pdf->Ln(5);

$pdf->SetFont('times', '', 12);

// start transaction
$pdf->startTransaction();

$pdf->Write(0, "LINE 1\n");
$pdf->Write(0, "LINE 2\n");

// restarts transaction
$pdf->startTransaction();

$pdf->Write(0, "LINE 3\n");
$pdf->Write(0, "LINE 4\n");

// rolls back to the last (re)start
$pdf = $pdf->rollbackTransaction();

$pdf->Write(0, "LINE 5\n");
$pdf->Write(0, "LINE 6\n");

// start transaction
$pdf->startTransaction();

$pdf->Write(0, "LINE 7\n");

// commit transaction (actually just frees memory)
$pdf->commitTransaction(); 

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_047.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
