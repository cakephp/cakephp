<?php
/**
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
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Core;

/**
 * ClassLoader
 *
 */
class ClassLoader {

/**
 * File extension
 *
 * @var string
 */
	protected $_fileExtension;

/**
 * The path a given namespace maps to.
 *
 * @var string
 */
	protected $_path;

/**
 * Registered namespace
 *
 * @var string
 */
	protected $_namespace;

/**
 * Store the namespace length for performance
 *
 * @var integer
 */
	protected $_namespaceLength;

/**
 * Constructor
 *
 * @param string $ns The _namespace to use.
 */
	public function __construct($ns = null, $path = null, $fileExtension = '.php') {
		$this->_namespace = rtrim($ns, '\\') . '\\';
		$this->_namespaceLength = strlen($this->_namespace);
		$this->_path = $path;
		$this->_fileExtension = '.php';
	}

/**
 * Gets the base include path for all class files in the _namespace of this class loader.
 *
 * @return string
 */
	public function getIncludePath() {
		return $this->_includePath;
	}

/**
 * Gets the file extension of class files in the _namespace of this class loader.
 *
 * @return string
 */
	public function getFileExtension() {
		return $this->_fileExtension;
	}

/**
 * Installs this class loader on the SPL autoload stack.
 *
 * @return void
 */
	public function register() {
		spl_autoload_register([$this, 'loadClass']);
	}

/**
 * Uninstalls this class loader from the SPL autoloader stack.
 *
 * @return void
 */
	public function unregister() {
		spl_autoload_unregister([$this, 'loadClass']);
	}

/**
 * Loads the given class or interface.
 *
 * @param string $className The name of the class to load.
 * @return boolean
 */
	public function loadClass($className) {
		if (substr($className, 0, $this->_namespaceLength) !== $this->_namespace) {
			return false;
		}
		$path = $this->_path . DS . str_replace('\\', DS, $className) . $this->_fileExtension;
		if (!file_exists($path)) {
			return false;
		}
		return require $path;
	}

}
