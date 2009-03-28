<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.6879//Correct version number as needed**
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.6879//Correct version number as needed**
 */
class DependencyFixture extends CakeTestFixture {
/**
 * name property
 *
 * @var string 'Dependency'
 * @access public
 */
	var $name = 'Dependency';
/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'child_id' => 'integer',
		'parent_id' => 'integer'
	);
/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('child_id' => 1, 'parent_id' => 2),
	);
}

?>
