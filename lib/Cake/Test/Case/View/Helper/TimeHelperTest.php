<?php
/**
 * TimeHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('TimeHelper', 'View/Helper');
App::uses('View', 'View');
App::uses('CakeTime', 'Utility');

/**
 * TimeHelperTestObject class
 */
class TimeHelperTestObject extends TimeHelper {

	public function attach(CakeTime $cakeTime) {
		$this->_CakeTime = $cakeTime;
	}

}

/**
 * TimeHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TimeHelperTest extends CakeTestCase {

	public $Time = null;

	public $CakeTime = null;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$View = new View(null);
		$this->CakeTime = $this->getMock('CakeTime');
		$this->Time = new TimeHelperTestObject($View);
		$this->Time->attach($this->CakeTime);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Time);
		unset($this->CakeTime);
	}

/**
 * test CakeTime class methods are called correctly
 */
	public function testTimeHelperProxyMethodCalls() {
		$methods = array(
			'convertSpecifiers', 'convert', 'serverOffset', 'fromString',
			'nice', 'niceShort', 'daysAsSql', 'dayAsSql',
			'isToday', 'isThisMonth', 'isThisYear', 'wasYesterday',
			'isTomorrow', 'toQuarter', 'toUnix', 'toAtom', 'toRSS',
			'timeAgoInWords', 'wasWithinLast', 'gmt', 'format', 'i18nFormat',
			);
		foreach ($methods as $method) {
			$this->CakeTime->expects($this->at(0))->method($method);
			$this->Time->{$method}('who', 'what', 'when', 'where', 'how');
		}
	}

}
