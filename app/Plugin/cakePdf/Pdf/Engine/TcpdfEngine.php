<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');
App::uses('Multibyte', 'I18n');

class TcpdfEngine extends AbstractPdfEngine {

/**
 * Constructor
 *
 * @param $Pdf CakePdf instance
 */
	public function __construct(CakePdf $Pdf) {
		parent::__construct($Pdf);
		App::import('Vendor', 'CakePdf.TCPDF', array('file' => 'tcpdf' . DS . 'tcpdf.php'));
	}

/**
 * Generates Pdf from html
 *
 * @return string raw pdf data
 */
	public function output() {
		//TCPDF often produces a whole bunch of errors, although there is a pdf created when debug = 0
		//Configure::write('debug', 0);
		$TCPDF = new TCPDF($this->_Pdf->orientation(), 'mm', $this->_Pdf->pageSize());
		$TCPDF->AddPage();
		$TCPDF->writeHTML($this->_Pdf->html());
		return $TCPDF->Output('', 'S');
	}
}