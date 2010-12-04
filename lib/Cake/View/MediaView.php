<?php
/**
 * Methods to display or download any type of file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view
 * @since         CakePHP(tm) v 1.2.0.5714
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('View', 'View');
App::uses('CakeRequest', 'Core');

class MediaView extends View {
/**
 * Indicates whether response gzip compression was enabled for this class
 *
 * @var boolean
 */
	protected  $_compressionEnabled = false;

/**
 * Reference to the Response object responsible for sending the headers
 *
 * @var CakeResponse
 */
	public $response = null;


/**
 * Constructor
 *
 * @param object $controller
 */
	function __construct($controller = null) {
		parent::__construct($controller);
		if (is_object($controller) && isset($controller->response)) {
			$this->response = $controller->response;
		} else {
			$this->response = new CakeResponse;
		}
	}

/**
 * Display or download the given file
 *
 * @return unknown
 */
	function render() {
		$name = $download = $extension = $id = $modified = $path = $size = $cache = $mimeType = $compress = null;
		extract($this->viewVars, EXTR_OVERWRITE);

		if ($size) {
			$id = $id . '_' . $size;
		}

		if (is_dir($path)) {
			$path = $path . $id;
		} else {
			$path = APP . $path . $id;
		}

		if (!is_file($path)) {
			if (Configure::read('debug')) {
				throw new NotFoundException(sprintf('The requested file %s was not found', $path));
			}
			throw new NotFoundException('The requested file was not found');
		}

		if (is_null($name)) {
			$name = $id;
		}

		if (is_array($mimeType)) {
			$this->response->type($mimeType);
		}

		if (isset($extension) && $this->response->type($extension) && $this->_isActive()) {
			$chunkSize = 8192;
			$buffer = '';
			$fileSize = @filesize($path);
			$handle = fopen($path, 'rb');

			if ($handle === false) {
				return false;
			}
			if (!empty($modified) && !is_numeric($modified)) {
				$modified = strtotime($modified, time());
			} else {
				$modified = time();
			}

			if ($cache) {
				$this->response->cache($modified, $cache);
			} else {
				$this->response->header(array(
					'Date' => gmdate('D, d M Y H:i:s', time()) . ' GMT',
					'Expires' => '0',
					'Cache-Control' => 'private, must-revalidate, post-check=0, pre-check=0',
					'Pragma' => 'no-cache'
				));
			}

			if ($download) {
				$agent = env('HTTP_USER_AGENT');

				if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent)) {
					$contentType = 'application/octetstream';
				} else if (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
					$contentType = 'application/force-download';
				}

				if (!empty($contentType)) {
					$this->response->type($contentType);
				}
				$this->response->download($name . '.' . $extension);
				$this->response->header(array('Accept-Ranges' => 'bytes'));

				$httpRange = env('HTTP_RANGE');
				if (isset($httpRange)) {
					list($toss, $range) = explode('=', $httpRange);

					$size = $fileSize - 1;
					$length = $fileSize - $range;

					$this->response->header(array(
						'Content-Length' => $length,
						'Content-Range' => 'bytes ' . $range . $size . '/' . $fileSize
					));

					$this->response->statusCode(206);
					fseek($handle, $range);
				} else {
					$this->response->header('Content-Length', $fileSize);
				}
			} else {
				$this->response->type($extension);
				$this->response->header(array(
					'Content-Length' => $fileSize
				));
			}
			$this->_clearBuffer();
			if ($compress) {
				$this->_compressionEnabled = $this->response->compress();
			}

			$this->response->send();
			return $this->_sendFile($handle);
		}

		return false;
	}

	protected function _sendFile($handle) {
		$chunkSize = 8192;
		$buffer = '';
		while (!feof($handle)) {
			if (!$this->_isActive()) {
				fclose($handle);
				return false;
			}
			set_time_limit(0);
			$buffer = fread($handle, $chunkSize);
			echo $buffer;
			if (!$this->_compressionEnabled) {
				$this->_flushBuffer();
			}
		}
		fclose($handle);
	}

/**
 * Returns true if connection is still active
 * @return boolean
 */
	protected function _isActive() {
		return connection_status() == 0 && !connection_aborted();
	}

/**
 * Clears the contents of the topmost output buffer and discards them
 * @return boolean
 */
	protected function _clearBuffer() {
		return @ob_end_clean();
	}

/**
 * Flushes the contents of the output buffer
 */
	protected function _flushBuffer() {
		@flush();
		@ob_flush();
	}
}
