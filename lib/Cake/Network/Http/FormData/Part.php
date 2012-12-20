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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Network\Http\FormData;

/**
 * Contains the data and behavior for a single
 * part in a Multipart FormData request body.
 *
 * Added to Cake\Network\Http\FormData when sending
 * data to a remote server.
 */
class Part {

/**
 * Name of the value.
 *
 * @var string
 */
	protected $_name;

/**
 * Value to send.
 *
 * @var string
 */
	protected $_value;

/**
 * Content type to use
 *
 * @var string
 */
	protected $_type;

/**
 * Disposition to send
 *
 * @var string
 */
	protected $_disposition;

/**
 * Filename to send if using files.
 *
 * @var string
 */
	protected $_filename;

/**
 * Constructor
 *
 * @param string $name The name of the data.
 * @param string $value The value of the data.
 * @param string $disposition The type of disposition to use, defaults to form-data.
 * @return void
 */
	public function __construct($name, $value, $disposition = 'form-data') {
		$this->_name = $name;
		$this->_value = $value;
		$this->_disposition = $disposition;
	}

/**
 * Get/set the filename.
 *
 * @param null|string $filename Use null to get/string to set.
 * @return mixed
 */
	public function filename($filename = null) {
		if ($filename === null) {
			return $this->_filename;
		}
		$this->_filename = $filename;
	}

/**
 * Get/set the content type.
 *
 * @param null|string $type Use null to get/string to set.
 * @return mixed
 */
	public function type($type) {
		if ($type === null) {
			return $this->_type;
		}
		$this->_type = $type;
	}

/**
 * Convert the part into a string.
 *
 * Creates a string suitable for use in HTTP requests.
 *
 * @return string
 */
	public function __toString() {
		$out = '';
		$out .= sprintf('Content-Disposition: %s; name="%s"', $this->_disposition, $this->_name);
		if ($this->_filename) {
			$out .= '; filename="' . $this->_filename . '"';
		}
		$out .= "\r\n";
		if ($this->_type) {
			$out .= 'Content-Type: ' . $this->_type . "\r\n";
		}
		$out .= "\r\n";
		$out .= (string)$this->_value;
		return $out;
	}

}
