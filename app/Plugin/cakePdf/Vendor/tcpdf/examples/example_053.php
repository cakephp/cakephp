<?php
//============================================================+
// File name   : example_053.php
// Begin       : 2009-09-02
// Last Update : 2010-08-08
//
// Description : Example 053 for TCPDF class
//               Javascript example.
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
 * @abstract TCPDF - Example: Javascript example.
 * @author Nicola Asuni
 * @since 2009-09-02
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 053');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 053', PDF_HEADER_STRING);

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
$pdf->SetFont('times', '', 14);

// add a page
$pdf->AddPage();

// print a some of text
$text = 'This is an example of <strong>JavaScript</strong> usage on PDF documents.<br /><br />For more information check the source code of this example, the source code documentation for the <i>IncludeJS()</i> method and the <i>JavaScript for Acrobat API Reference</i> guide.<br /><br /><a href="http://www.tcpdf.org">www.tcpdf.org</a>';
$pdf->writeHTML($text, true, 0, true, 0);

// write some JavaScript code
$js = <<<EOD
app.alert('JavaScript Popup Example', 3, 0, 'Welcome');
var cResponse = app.response({
	cQuestion: 'How are you today?',
	cTitle: 'Your Health Status',
	cDefault: 'Fine',
	cLabel: 'Response:'
});
if (cResponse == null) {
	app.alert('Thanks for trying anyway.', 3, 0, 'Result');
} else {
	app.alert('You responded, "'+cResponse+'", to the health question.', 3, 0, 'Result');
}
EOD;

// force print dialog
$js .= 'print(true);';

// set javascript
$pdf->IncludeJS($js);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_053.pdf', 'I');

//============================================================+
// END OF FILE                                                
//============================================================+
