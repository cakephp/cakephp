<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Debugger', 'Utility');

/**
 * Contains methods for Profiling and creating timers.
 *
 */
class DebugTimer {

/**
 * Internal timers array
 *
 * @var array
 */
	protected static $_timers = array();

/**
 * Start an benchmarking timer.
 *
 * @param string $name The name of the timer to start.
 * @param string $message A message for your timer
 * @return boolean Always true
 */
	public static function start($name = null, $message = null) {
		$start = microtime(true);

		if (!$name) {
			$named = false;
			$calledFrom = debug_backtrace();
			$_name = $name = Debugger::trimpath($calledFrom[0]['file']) . ' line ' . $calledFrom[0]['line'];
		} else {
			$named = true;
		}

		if (!$message) {
			$message = $name;
		}

		$_name = $name;
		$i = 1;
		while (isset(self::$_timers[$name])) {
			$i++;
			$name = $_name . ' #' . $i;
		}

		if ($i > 1) {
			$message .= ' #' . $i;
		}

		self::$_timers[$name] = array(
			'start' => $start,
			'message' => $message,
			'named' => $named
		);
		return true;
	}

/**
 * Stop a benchmarking timer.
 *
 * $name should be the same as the $name used in startTimer().
 *
 * @param string $name The name of the timer to end.
 * @return boolean true if timer was ended, false if timer was not started.
 */
	public static function stop($name = null) {
		$end = microtime(true);
		if (!$name) {
			$names = array_reverse(array_keys(self::$_timers));
			foreach ($names as $name) {
				if (!empty(self::$_timers[$name]['end'])) {
					continue;
				}
				if (empty(self::$_timers[$name]['named'])) {
					break;
				}
			}
		} else {
			$i = 1;
			$_name = $name;
			while (isset(self::$_timers[$name])) {
				if (empty(self::$_timers[$name]['end'])) {
					break;
				}
				$i++;
				$name = $_name . ' #' . $i;
			}
		}
		if (!isset(self::$_timers[$name])) {
			return false;
		}
		self::$_timers[$name]['end'] = $end;
		return true;
	}

/**
 * Get all timers that have been started and stopped.
 * Calculates elapsed time for each timer. If clear is true, will delete existing timers
 *
 * @param boolean $clear false
 * @return array
 */
	public static function getAll($clear = false) {
		$start = self::requestStartTime();
		$now = microtime(true);

		$times = array();
		if (!empty(self::$_timers)) {
			$firstTimer = reset(self::$_timers);
			$_end = $firstTimer['start'];
		} else {
			$_end = $now;
		}
		$times['Core Processing (Derived from $_SERVER["REQUEST_TIME"])'] = array(
			'message' => __d('debug_kit', 'Core Processing (Derived from $_SERVER["REQUEST_TIME"])'),
			'start' => 0,
			'end' => $_end - $start,
			'time' => round($_end - $start, 6),
			'named' => null
		);
		foreach (self::$_timers as $name => $timer) {
			if (!isset($timer['end'])) {
				$timer['end'] = $now;
			}
			$times[$name] = array_merge($timer, array(
				'start' => $timer['start'] - $start,
				'end' => $timer['end'] - $start,
				'time' => self::elapsedTime($name)
			));
		}
		if ($clear) {
			self::$_timers = array();
		}
		return $times;
	}

/**
 * Clear all existing timers
 *
 * @return boolean true
 */
	public static function clear() {
		self::$_timers = array();
		return true;
	}

/**
 * Get the difference in time between the timer start and timer end.
 *
 * @param $name string the name of the timer you want elapsed time for.
 * @param $precision int the number of decimal places to return, defaults to 5.
 * @return float number of seconds elapsed for timer name, 0 on missing key
 */
	public static function elapsedTime($name = 'default', $precision = 5) {
		if (!isset(self::$_timers[$name]['start']) || !isset(self::$_timers[$name]['end'])) {
			return 0;
		}
		return round(self::$_timers[$name]['end'] - self::$_timers[$name]['start'], $precision);
	}

/**
 * Get the total execution time until this point
 *
 * @return float elapsed time in seconds since script start.
 */
	public static function requestTime() {
		$start = self::requestStartTime();
		$now = microtime(true);
		return ($now - $start);
	}

/**
 * get the time the current request started.
 *
 * @return float time of request start
 */
	public static function requestStartTime() {
		if (defined('TIME_START')) {
			$startTime = TIME_START;
		} elseif (isset($GLOBALS['TIME_START'])) {
			$startTime = $GLOBALS['TIME_START'];
		} else {
			$startTime = env('REQUEST_TIME');
		}
		return $startTime;
	}

}
