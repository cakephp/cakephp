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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Datasource\Database\Log;

use Cake\Log\Log;
use Cake\Model\Datasource\Database\Log\LoggedQuery;
use Cake\Model\Datasource\Database\Log\QueryLogger;

/**
 * Tests QueryLogger class
 *
 **/
class QueryLoggerTest extends \Cake\TestSuite\TestCase {

	protected $_disabledEngines = [];

	public function setUp() {
		foreach (Log::configured() as $e) {
			if (Log::enabled($e)) {
				Log::disable($e);
				$this->_disabledEngines[] = $e;
			}
		}
	}

	public function tearDown() {
		Log::drop('queryLoggerTest');
		foreach ($this->_disabledEngines as $e) {
			Log::enable($e);
		}
	}

	public function testStingInterpolation() {
		$logger = $this->getMock('\Cake\Model\Datasource\Database\Log\QueryLogger', ['_log']);
		$query = new LoggedQuery;
		$query->query = 'SELECT a FROM b where a = :p1 AND b = :p2 AND c = :p3';
		$query->params = ['p1' =>  'string', 'p2' => 3, 'p3' => null];

		$logger->expects($this->once())->method('_log')->with($query);
		$logger->write($query);
		$expected = "SELECT a FROM b where a = 'string' AND b = 3 AND c = NULL";
		$this->assertEquals($expected, (string)$query);
	}

	public function testStingInterpolation2() {
		$logger = $this->getMock('\Cake\Model\Datasource\Database\Log\QueryLogger', ['_log']);
		$query = new LoggedQuery;
		$query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ?';
		$query->params = ['string', '3',  null];

		$logger->expects($this->once())->method('_log')->with($query);
		$logger->write($query);
		$expected = "SELECT a FROM b where a = 'string' AND b = '3' AND c = NULL";
		$this->assertEquals($expected, (string)$query);
	}

	public function testLogFunction() {
		$logger = new QueryLogger;
		$query = new LoggedQuery;
		$query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ?';
		$query->params = ['string', '3',  null];
		$engine = $this->getMock('\Cake\Log\Engine\BaseLog', ['write'], ['scopes' => ['queriesLog']]);
		Log::engine('queryLoggerTest', $engine);
		$engine->expects($this->once())->method('write')->with('debug', $query);
		$logger->write($query);
	}


}
