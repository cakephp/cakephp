<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * Author(s): Larry E. Masters aka PhpNut <phpnut@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @author       Larry E. Masters aka PhpNut <phpnut@gmail.com>
 * @copyright    Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * @link         http://www.phpnut.com/projects/
 * @package      test_suite
 * @subpackage   test_suite.cases.app
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	require_once LIBS.'inflector.php';
/**
 * Short description for class.
 *
 * @package    test_suite
 * @subpackage test_suite.cases.libs
 * @since      CakePHP Test Suite v 1.0.0.0
 */
class InflectorTest extends UnitTestCase {

	function setUp() {
		$this->inflector = new Inflector();
	}

	function testInflectingSingulars() {
		$result = $this->inflector->singularize('menus');
		$expected = 'menu';
		$this->assertEqual($result, $expected, "Badness! Expected '{$expected}', got '{$result}'");

		$result = $this->inflector->singularize('quizzes');
		$expected = 'quiz';
		$this->assertEqual($result, $expected, "Badness! Expected '{$expected}', got '{$result}'");
	}

	function testInflectingPlurals() {
		$result = $this->inflector->pluralize('Bus');
		$expected = 'Buses';
		$this->assertEqual($result, $expected, "Badness! Expected '{$expected}', got '{$result}'");

		$result = $this->inflector->pluralize('bus');
		$expected = 'buses';
		$this->assertEqual($this->inflector->pluralize('bus'), 'buses', "Badness! Expected '{$expected}', got '{$result}'");

		$result = $this->inflector->pluralize('menu');
		$expected = 'menus';
		$this->assertEqual($result, $expected, "Badness! Expected '{$expected}', got '{$result}'");

		$result = $this->inflector->pluralize('quiz');
		$expected = 'quizzes';
		$this->assertEqual($result, $expected, "Badness! Expected '{$expected}', got '{$result}'");
	}

	function tearDown() {
		unset($this->inflector);
	}
}

?>