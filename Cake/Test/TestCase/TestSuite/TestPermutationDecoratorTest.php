<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\TestSuite\TestPermutationDecorator;
use \PHPUnit_Extensions_TestDecorator;
use \PHPUnit_Framework_Test;
use \PHPUnit_Framework_TestResult;

/**
 * TestPermutationTest
 *
 */
class TestPermutationTest extends \Cake\TestSuite\TestCase {

/**
 * Tests that decorating a test will return the amount of cases times the amount
 * of permutations
 *
 * @return void
 */
	public function testCount() {
		$permutations = [1, 2, 3];
		$test = $this->getMockForAbstractClass('\PHPUnit_Framework_Test', ['count']);
		$test->expects($this->once())->method('count')->will($this->returnValue(5));
		$decorated = new TestPermutationDecorator($test, $permutations);
		$this->assertEquals(15, $decorated->count());
	}

/**
 * Tests that the decorated test is run once per permutation
 *
 * @return void
 */
	public function testRunNoCallbacks() {
		$permutations = [1, 2, 3];
		$test = $this->getMockForAbstractClass('\PHPUnit_Framework_Test', ['run', 'count']);
		$test->expects($this->exactly(3))->method('run');
		$decorated = new TestPermutationDecorator($test, $permutations);
		$decorated->run();
	}

/**
 * Tests that the decorated test is run once per permutation and the supplied
 * callback is executed
 *
 * @return void
 */
	public function testRunWithCallback() {
		$callback1 = $this->getMock('stdClass', ['__invoke']);
		$callback2 = $this->getMock('stdClass', ['__invoke']);
		$permutations = [$callback1, $callback2];
		$test = $this->getMockForAbstractClass('\PHPUnit_Framework_Test', ['run', 'count']);
		$test->expects($this->exactly(2))->method('run');
		$callback1->expects($this->once())->method('__invoke')->with($test);
		$callback2->expects($this->once())->method('__invoke')->with($test);
		$decorated = new TestPermutationDecorator($test, $permutations);
		$decorated->run();
	}

}
