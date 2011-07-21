<?php 
/** 
 * 
 * @author Clément Hallet 
 * @link http://twitter.com/challet
 * @license MIT license 
 * 
 */ 

if (!class_exists('FileEngine')) {
	require LIBS . 'cache/file.php';
}

class HashFileEngine extends FileEngine { 
 
	const HASH_DIGITS_NUMBER = 3;

	function _setKey($key) { 
		
		parent::_setKey($key);
		
		$intermediate_key = substr(md5($this->_File->name), 0, self::HASH_DIGITS_NUMBER);
		
		$this->_File->Folder->create(
			$this->_File->Folder->addPathElement(
				$this->_File->Folder->pwd(),
				$intermediate_key
			)
		);
		$this->_File->Folder->cd($intermediate_key);
		$this->_File->path = null;
		$this->_File->pwd();
		
	} 

}