<?php
/**
 * Methods to display or download any type of file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View
 * @since         CakePHP(tm) v 1.2.0.5714
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('CakeRequest', 'Network');

/**
 * Media View provides a custom view implementation for sending files to visitors.  Its great
 * for making the response of a controller action be a file that is saved somewhere on the filesystem.
 *
 * An example use comes from the CakePHP internals. MediaView is used to serve plugin and theme assets,
 * as they are not normally accessible from an application's webroot.  Unlike other views, MediaView
 * uses several viewVars that have special meaning:
 *
 * - `id` The filename on the server's filesystem, including extension.
 * - `name` The filename that will be sent to the user, specified without the extension.
 * - `download` Set to true to set a `Content-Disposition` header.  This is ideal for file downloads.
 * - `extension` The extension of the file being served. This is used to set the mimetype.
 * 	If not provided its extracted from filename provided as `id`.
 * - `path` The absolute path, including the trailing / on the server's filesystem to `id`.
 * - `mimeType` The mime type of the file if CakeResponse doesn't know about it.
 * 	Must be an associative array with extension as key and mime type as value eg. array('ini' => 'text/plain')
 *
 * ### Usage
 *
 * {{{
 * class ExampleController extends AppController {
 *		public function download () {
 *			$this->viewClass = 'Media';
 *			$params = array(
 *				'id' => 'example.zip',
 *				'name' => 'example',
 *				'download' => true,
 *				'extension' => 'zip',
 *				'path' => APP . 'files' . DS
 *			);
 *			$this->set($params);
 *		}
 * }
 * }}}
 *
 * @package       Cake.View
 */
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
 * @param Controller $controller The controller with viewVars
 */
	public function __construct($controller = null) {
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
 * @param string $view Not used
 * @param string $layout Not used
 * @return mixed
 * @throws NotFoundException
 */
	public function render($view = null, $layout = null) {
		$name = $download = $extension = $id = $modified = $path = $cache = $mimeType = $compress = null;
		extract($this->viewVars, EXTR_OVERWRITE);

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

		if (is_array($mimeType)) {
			$this->response->type($mimeType);
		}

		if (!isset($extension)) {
			$extension = pathinfo($id, PATHINFO_EXTENSION);
		}

		if ($this->_isActive()) {
			$extension = strtolower($extension);
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
			if (!$extension || $this->response->type($extension) === false) {
				$download = true;
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
				if (is_null($name)) {
					$name = $id;
				} elseif ($extension) {
					$name .= '.' . $extension;
				}
				$this->response->download($name);
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

/**
 * Reads out a file handle, and echos the content to the client.
 *
 * @param resource $handle A file handle or stream
 * @return void
 */
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
 *
 * @return boolean
 */
	protected function _isActive() {
		return connection_status() == 0 && !connection_aborted();
	}

/**
 * Clears the contents of the topmost output buffer and discards them
 *
 * @return boolean
 */
	protected function _clearBuffer() {
		return @ob_end_clean();
	}

/**
 * Flushes the contents of the output buffer
 *
 * @return void
 */
	protected function _flushBuffer() {
		@flush();
		@ob_flush();
	}
}
