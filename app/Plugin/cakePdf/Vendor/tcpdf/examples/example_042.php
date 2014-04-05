<?php
//============================================================+
// File name   : example_042.php
// Begin       : 2008-12-23
// Last Update : 2010-08-08
//
// Description : Example 042 for TCPDF class
//               Test Image with alpha channel
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
 * @abstract TCPDF - Example: Test Image with alpha channel
 * @author Nicola Asuni
 * @since 2008-12-23
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 042');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 042', PDF_HEADER_STRING);

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

// set JPEG quality
//$pdf->setJPEGQuality(75);

$pdf->SetFont('helvetica', '', 18);

// add a page
$pdf->AddPage();

// create background text
$background_text = str_repeat('TCPDF test PNG Alpha Channel ', 50);
$pdf->MultiCell(0, 5, $background_text, 0, 'J', 0, 2, '', '', true, 0, false);

// --- Method (A) ------------------------------------------
// the Image() method recognizes the alpha channel embedded on the image:

$pdf->Image('../images/image_with_alpha.png', 50, 50, 100, '', '', 'http://www.tcpdf.org', '', false, 300);

// --- Method (B) ------------------------------------------
// provide image + separate 8-bit mask

// first embed mask image (w, h, x and y will be ignored, the image will be scaled to the target image's size)
$mask = $pdf->Image('../images/alpha.png', 50, 140, 100, '', '', '', '', false, 300, '', true);

// embed image, masked with previously embedded mask
$pdf->Image('../images/img.png', 50, 140, 100, '', '', 'http://www.tcpdf.org', '', false, 300, '', false, $mask);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_042.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
