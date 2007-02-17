<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.i18n
 * @since			CakePHP(tm) v 1.2.0.3995
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package	 	cake
 * @subpackage cake.cake.libs.model.i18n
 * @since		CakePHP v 1.2.0.3995
 *
 */
class Translate extends AppModel {
	var $locale = null;

	function read ($id = null, $fields = null) {
		$result = parent::read($id, $fields);
		return $result;
	}

	function field ($name, $conditions = null, $order = null) {
		$result = parent::field ($name, $conditions, $order);
		return $result;
	}

	function saveField($name, $value, $validate = false) {
		$result = parent::saveField($name, $value, $validate );
		return $result;
	}

	function save ($data=null, $validate=true) {
		$result = parent::save ($data, $validate);
		return $result;
	}

	function del ($id = null) {
		$result = parent::del($id);
		return $result;
	}

	function find ($conditions = null, $fields = null, $order = null, $recursive = 1) {
		$result = parent::find($conditions , $fields , $order , $recursive);
		return $result;
	 }

	 function findAll ($conditions = null, $fields = null, $order = null, $limit = 50, $page = 1, $recursive = 1) {
	 	$result = parent::findAll($conditions , $fields , $order , $limit , $page , $recursive);
	 	return $result;
	 }
}
?>