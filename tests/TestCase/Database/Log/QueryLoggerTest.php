<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\QueryLogger;
use Cake\Log\Log;

/**
 * Tests QueryLogger class
 *
 */
class QueryLoggerTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Log::reset();
	}

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Log::reset();
	}

/**
 * Tests that query placeholders are replaced when logged
 *
 * @return void
 */
	public function testStingInterpolation() {
		$logger = $this->getMock('\Cake\Database\Log\QueryLogger', ['_log']);
		$query = new LoggedQuery;
		$query->query = 'SELECT a FROM b where a = :p1 AND b = :p2 AND c = :p3';
		$query->params = ['p1' => 'string', 'p2' => 3, 'p3' => null];

		$logger->expects($this->once())->method('_log')->with($query);
		$logger->log($query);
		$expected = "SELECT a FROM b where a = 'string' AND b = 3 AND c = NULL";
		$this->assertEquals($expected, (string)$query);
	}

/**
 * Tests that positional placeholders are replaced when logging a query
 *
 * @return void
 */
	public function testStingInterpolation2() {
		$logger = $this->getMock('\Cake\Database\Log\QueryLogger', ['_log']);
		$query = new LoggedQuery;
		$query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ?';
		$query->params = ['string', '3', null];

		$logger->expects($this->once())->method('_log')->with($query);
		$logger->log($query);
		$expected = "SELECT a FROM b where a = 'string' AND b = '3' AND c = NULL";
		$this->assertEquals($expected, (string)$query);
	}

/**
 * Tests that the logged query object is passed to the built-in logger using
 * the correct scope
 *
 * @return void
 */
	public function testLogFunction() {
		$logger = new QueryLogger;
		$query = new LoggedQuery;
		$query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ?';
		$query->params = ['string', '3', null];

		$engine = $this->getMock('\Cake\Log\Engine\BaseLog', ['write'], ['scopes' => ['queriesLog']]);
		Log::engine('queryLoggerTest', $engine);

		$engine2 = $this->getMock('\Cake\Log\Engine\BaseLog', ['write'], ['scopes' => ['foo']]);
		Log::engine('queryLoggerTest2', $engine2);

		$engine2->expects($this->never())->method('write');
		$logger->log($query);
	}

}
