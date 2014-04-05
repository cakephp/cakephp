<?php
/**
 * DebugTimer Test Case
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         debug_kit 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugTimer', 'DebugKit.Lib');

/**
 * Class DebugTimerTest
 *
 * @since         debug_kit 2.0
 */
class DebugTimerTest extends CakeTestCase {

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		DebugTimer::clear();
	}

/**
 * Start Timer test
 *
 * @return void
 */
	public function testTimers() {
		$this->assertTrue(DebugTimer::start('test1', 'this is my first test'));
		usleep(5000);
		$this->assertTrue(DebugTimer::stop('test1'));
		$elapsed = DebugTimer::elapsedTime('test1');
		$this->assertTrue($elapsed > 0.0050);

		$this->assertTrue(DebugTimer::start('test2', 'this is my second test'));
		sleep(1);
		$this->assertTrue(DebugTimer::stop('test2'));
		$elapsed = DebugTimer::elapsedTime('test2');
		$expected = stripos(PHP_OS, 'win') === false ? 0.999: 0.95; // Windows timer's precision is bad
		$this->assertTrue($elapsed >= $expected);

		DebugTimer::start('test3');
		$this->assertIdentical(DebugTimer::elapsedTime('test3'), 0);
		$this->assertFalse(DebugTimer::stop('wrong'));
	}

/**
 * test timers with no names.
 *
 * @return void
 */
	public function testAnonymousTimers() {
		$this->assertTrue(DebugTimer::start());
		usleep(2000);
		$this->assertTrue(DebugTimer::stop());
		$timers = DebugTimer::getAll();

		$this->assertEquals(2, count($timers));
		end($timers);
		$key = key($timers);
		$lineNo = __LINE__ - 8;

		$file = Debugger::trimPath(__FILE__);
		$expected = $file . ' line ' . $lineNo;
		$this->assertEquals($expected, $key);

		$timer = $timers[$expected];
		$this->assertTrue($timer['time'] > 0.0020);
		$this->assertEquals($expected, $timers[$expected]['message']);
	}

/**
 * Assert that nested anonymous timers don't get mixed up.
 *
 * @return void
 */
	public function testNestedAnonymousTimers() {
		$this->assertTrue(DebugTimer::start());
		usleep(100);
		$this->assertTrue(DebugTimer::start());
		usleep(100);
		$this->assertTrue(DebugTimer::stop());
		$this->assertTrue(DebugTimer::stop());

		$timers = DebugTimer::getAll();
		$this->assertEquals(3, count($timers), 'incorrect number of timers %s');
		$firstTimerLine = __LINE__ - 9;
		$secondTimerLine = __LINE__ - 8;
		$file = Debugger::trimPath(__FILE__);

		$this->assertTrue(isset($timers[$file . ' line ' . $firstTimerLine]), 'first timer is not set %s');
		$this->assertTrue(isset($timers[$file . ' line ' . $secondTimerLine]), 'second timer is not set %s');

		$firstTimer = $timers[$file . ' line ' . $firstTimerLine];
		$secondTimer = $timers[$file . ' line ' . $secondTimerLine];
		$this->assertTrue($firstTimer['time'] > $secondTimer['time']);
	}

/**
 * test that calling start with the same name does not overwrite previous timers
 * and instead adds new ones.
 *
 * @return void
 */
	public function testRepeatTimers() {
		DebugTimer::start('my timer', 'This is the first call');
		usleep(100);
		DebugTimer::start('my timer', 'This is the second call');
		usleep(100);

		DebugTimer::stop('my timer');
		DebugTimer::stop('my timer');

		$timers = DebugTimer::getAll();
		$this->assertEquals(3, count($timers), 'wrong timer count %s');

		$this->assertTrue(isset($timers['my timer']));
		$this->assertTrue(isset($timers['my timer #2']));

		$this->assertTrue($timers['my timer']['time'] > $timers['my timer #2']['time'], 'timer 2 is longer? %s');
		$this->assertEquals('This is the first call', $timers['my timer']['message']);
		$this->assertEquals('This is the second call #2', $timers['my timer #2']['message']);
	}

/**
 * testRequestTime
 *
 * @return void
 */
	public function testRequestTime() {
		$result1 = DebugTimer::requestTime();
		usleep(50);
		$result2 = DebugTimer::requestTime();
		$this->assertTrue($result1 < $result2);
	}

/**
 * test getting all the set timers.
 *
 * @return void
 */
	public function testGetTimers() {
		DebugTimer::start('test1', 'this is my first test');
		DebugTimer::stop('test1');
		usleep(50);
		DebugTimer::start('test2');
		DebugTimer::stop('test2');
		$timers = DebugTimer::getAll();

		$this->assertEquals(3, count($timers));
		$this->assertTrue(is_float($timers['test1']['time']));
		$this->assertTrue(isset($timers['test1']['message']));
		$this->assertTrue(isset($timers['test2']['message']));
	}
}
