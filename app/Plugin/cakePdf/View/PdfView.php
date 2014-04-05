<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakePdf', 'CakePdf.Pdf');
App::uses('View', 'View');

/**
 * @package       Cake.View
 */
class PdfView extends View {

/**
 * The subdirectory.  PDF views are always in pdf.
 *
 * @var string
 */
	public $subDir = 'pdf';

/**
 * CakePdf Instance
 *
 * @var object
 */
	protected $_renderer = null;

/**
 * List of pdf configs collected from the associated controller.
 *
 * @var array
 */
	public $pdfConfig = array();

/**
 * Constructor
 *
 * @param Controller $controller
 * @return void
 */
	public function __construct(Controller $Controller = null) {
		$this->_passedVars[] = 'pdfConfig';
		parent::__construct($Controller);
		$this->pdfConfig = array_merge(
			(array)Configure::read('Pdf'),//BC line, remove later @todo
			(array)Configure::read('CakePdf'),
			(array)$this->pdfConfig
		);

		$this->response->type('pdf');
		if ($Controller instanceof CakeErrorController) {
			$this->subDir = null;
			return $this->response->type('html');
		}
		if (!$this->pdfConfig) {
			throw new CakeException(__d('cakepdf', 'Controller attribute $pdfConfig is not correct or missing'));
		}
		$this->renderer($this->pdfConfig);
	}

/**
 * Return CakePdf instance, optionally set engine to be used
 * @param array $config Array of pdf configs. When empty CakePdf instance will be returned.
 * @return CakePdf
 */
	public function renderer($config = null) {
		if ($config !== null) {
			$this->_renderer = new CakePdf($config);
		}
		return $this->_renderer;
	}

/**
 * Render a Pdf view.
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		$content = parent::render($view, $layout);
		if ($this->response->type() == 'text/html') {
			return $content;
		}

		if (isset($this->pdfConfig['download']) && $this->pdfConfig['download'] === true) {
			$this->response->download($this->getFilename());
		}

		$this->Blocks->set('content', $this->renderer()->output($content));
		return $this->Blocks->get('content');
	}

/**
 * Get or build a filename for forced download
 * @return string The filename
 */
	public function getFilename() {
		if (isset($this->pdfConfig['filename'])) {
			return $this->pdfConfig['filename'];
		}
		$id = current($this->request->params['pass']);
		return strtolower($this->viewPath) . $id . '.pdf';
	}
}
