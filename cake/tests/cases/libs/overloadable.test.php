<?php
/**
 * OverloadableTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Overloadable');

/**
 * OverloadableTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class OverloadableTest extends CakeTestCase {

/**
 * skip method
 *
 * @access public
 * @return void
 */
	function skip() {
		$this->skipIf(true, ' %s OverloadableTest not implemented');
	}
}
