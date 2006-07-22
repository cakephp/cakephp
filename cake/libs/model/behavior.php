<?php
/* SVN FILE: $Id$ */

/**
 * Model behaviors base class.
 *
 * Adds methods and automagic functionality to Cake Models.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.model
 * @since			CakePHP v 1.2.0.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class ModelBehavior extends Object {

	var $mapMethods = array();
	
	function setup(&$model, $config = array()) { }

	function beforeFind(&$model, &$query) { }

	function afterFind(&$model, &$results) { }

	function beforeDelete(&$model) { }

	function afterDelete(&$model) { }

	function onError(&$model, &$error) { }
}

?>