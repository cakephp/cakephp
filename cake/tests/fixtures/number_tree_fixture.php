<?php
/* SVN FILE: $Id$ */

/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
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
 * @subpackage		cake.tests.fixtures
 * @since			CakePHP v 1.2.0.4487
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Number Tree Test Fixture
 *
 * Generates a tree of data for use testing the tree behavior
 *
 * @package		cake
 * @subpackage	cake.tests.fixtures
 */
class NumberTreeFixture extends CakeTestFixture {
	var $name = 'NumberTree';
	var $fields = array ('id' => array (
				'type' => 'integer','key' => 'primary'),
				'name' => array ('type' => 'string','null' => false),
				'parent_id' => 'integer',
					'lft' => array ('type' => 'integer','null' => false),
					'rght' => array ('type' => 'integer','null' => false));
}
?>
