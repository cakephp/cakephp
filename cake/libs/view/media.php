<?php
/**
 * Methods to display or download any type of file
 *
 * PHP versions 4 and 5
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
App::import('View', 'View', false);

class MediaView extends View {

/**
 * Constructor
 *
 * @param object $controller
 */
	function __construct(&$controller) {
		parent::__construct($controller);
		if (is_object($controller) && isset($controller->response)) {
			$this->response = $controller->response;
		} else {
			App::import('Core', 'CakeRequest');
			$this->response = new CakeResponse;
		}
	}

/**
 * Display or download the given file
 *
 * @return unknown
 */
	function render() {
		$name = $download = $extension = $id = $modified = $path = $size = $cache = $mimeType = null;
		extract($this->viewVars, EXTR_OVERWRITE);

		if ($size) {
			$id = $id . '_' . $size;
		}

		if (is_dir($path)) {
			$path = $path . $id;
		} else {
			$path = APP . $path . $id;
		}

		if (!file_exists($path)) {
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
			if (!empty($modified)) {
				$modified = gmdate('D, d M Y H:i:s', strtotime($modified, time())) . ' GMT';
			} else {
				$modified = gmdate('D, d M Y H:i:s') . ' GMT';
			}

			if ($download) {
				$contentTypes = array('application/octet-stream');
				$agent = env('HTTP_USER_AGENT');

				if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent)) {
					$contentTypes[0] = 'application/octetstream';
				} else if (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
					$contentTypes[0] = 'application/force-download';
					array_merge($contentTypes, array(
						'application/octet-stream',
						'application/download'
					));
				}
				foreach($contentTypes as $contentType) {
					$this->_header('Content-Type: ' . $contentType);
				}
				$this->_header(array(
					'Content-Disposition: attachment; filename="' . $name . '.' . $extension . '";',
					'Expires: 0',
					'Accept-Ranges: bytes',
					'Cache-Control: private' => false,
					'Pragma: private'));

				$httpRange = env('HTTP_RANGE');
				if (isset($httpRange)) {
					list($toss, $range) = explode('=', $httpRange);

					$size = $fileSize - 1;
					$length = $fileSize - $range;

					$this->_header(array(
						'HTTP/1.1 206 Partial Content',
						'Content-Length: ' . $length,
						'Content-Range: bytes ' . $range . $size . '/' . $fileSize));

					fseek($handle, $range);
				} else {
					$this->_header('Content-Length: ' . $fileSize);
				}
			} else {

				if ($cache) {
					$this->response->cache(time(), $cache);
				} else {
					$this->response->header(array(
						'Date' => gmdate('D, d M Y H:i:s', time()) . ' GMT',
						'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
						'Pragma' => 'no-cache'
					));
				}

				$this->response->type($extension);
				$this->respose->header(array(
					'Last-Modified' => $modified,
					'Content-Length' => $fileSize
				));
			}
			$this->response->send();
			$this->_clearBuffer();
			$this->_sendFile($handle);

			return;
		}
		return false;
	}

	protected function _sendFile($handle) {
		while (!feof($handle)) {
			if (!$this->_isActive()) {
				fclose($handle);
				return false;
			}
			set_time_limit(0);
			$buffer = fread($handle, $chunkSize);
			echo $buffer;
			$this->_flushBuffer();
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
