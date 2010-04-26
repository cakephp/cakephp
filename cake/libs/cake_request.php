<?php
/**
 * A class that helps wrap Request information and particulars about a single request.
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
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class CakeRequest {
/**
 * Array of parameters parsed from the url.
 *
 * @var array
 */
	public $params = array();

/**
 * Array of POST data
 *
 * @var array
 */
	public $data = array();

/**
 * Array of querystring arguments
 *
 * @var array
 */
	public $url = array();

/**
 * Constructor 
 *
 * @return void
 */
	public function __construct() {
		if (isset($_POST)) {
			$this->_processPost();
		}
		if (isset($_GET)) {
			$this->_processGet();
		}
		$this->_processFiles();
	}

/**
 * process the post data and set what is there into the object.
 *
 * @return void
 */
	protected function _processPost() {
		$this->params['form'] = $_POST;
		if (ini_get('magic_quotes_gpc') === '1') {
			$this->params['form'] = stripslashes_deep($this->params['form']);
		}
		if (env('HTTP_X_HTTP_METHOD_OVERRIDE')) {
			$this->params['form']['_method'] = env('HTTP_X_HTTP_METHOD_OVERRIDE');
		}
		if (isset($this->params['form']['_method'])) {
			if (!empty($_SERVER)) {
				$_SERVER['REQUEST_METHOD'] = $this->params['form']['_method'];
			} else {
				$_ENV['REQUEST_METHOD'] = $this->params['form']['_method'];
			}
			unset($this->params['form']['_method']);
		}
		if (isset($this->params['form']['data'])) {
			$this->data = $this->params['form']['data'];
			unset($this->params['form']['data']);
		}
	}

/**
 * Process the GET parameters and move things into the object.
 *
 * @return void
 */
	protected function _processGet() {
		if (ini_get('magic_quotes_gpc') === '1') {
			$url = stripslashes_deep($_GET);
		} else {
			$url = $_GET;
		}
		if (isset($this->params['url'])) {
			$this->url = array_merge($this->url, $url);
		} else {
			$this->url = $url;
		}
	}

/**
 * Process $_FILES and move things into the object.
 *
 * @return void
 */
	protected function _processFiles() {
		if (isset($_FILES) && is_array($_FILES)) {
			foreach ($_FILES as $name => $data) {
				if ($name != 'data') {
					$this->params['form'][$name] = $data;
				}
			}
		}

		if (isset($_FILES['data'])) {
			foreach ($_FILES['data'] as $key => $data) {
				foreach ($data as $model => $fields) {
					if (is_array($fields)) {
						foreach ($fields as $field => $value) {
							if (is_array($value)) {
								foreach ($value as $k => $v) {
									$this->params['data'][$model][$field][$k][$key] = $v;
								}
							} else {
								$this->params['data'][$model][$field][$key] = $value;
							}
						}
					} else {
						$this->params['data'][$model][$key] = $fields;
					}
				}
			}
		}
	}

/**
 * Get the IP the client is using, or says they are using.
 *
 * @param boolean $safe Use safe = false when you think the user might manipulate their HTTP_CLIENT_IP
 *   header.  Setting $safe = false will will also look at HTTP_X_FORWARDED_FOR
 * @return void
 */
	public function getClientIp($safe = true) {
		if (!$safe && env('HTTP_X_FORWARDED_FOR') != null) {
			$ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
		} else {
			if (env('HTTP_CLIENT_IP') != null) {
				$ipaddr = env('HTTP_CLIENT_IP');
			} else {
				$ipaddr = env('REMOTE_ADDR');
			}
		}

		if (env('HTTP_CLIENTADDRESS') != null) {
			$tmpipaddr = env('HTTP_CLIENTADDRESS');

			if (!empty($tmpipaddr)) {
				$ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
			}
		}
		return trim($ipaddr);
	}
}