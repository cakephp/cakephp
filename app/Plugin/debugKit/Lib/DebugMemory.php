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
 * @since         DebugKit 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Debugger', 'Utility');

/**
 * Contains methods for Profiling memory usage.
 *
 */
class DebugMemory {

/**
 * An array of recorded memory use points.
 *
 * @var array
 */
	protected static $_points = array();

/**
 * Get current memory usage
 *
 * @return integer number of bytes ram currently in use. 0 if memory_get_usage() is not available.
 */
	public static function getCurrent() {
		return memory_get_usage();
	}

/**
 * Get peak memory use
 *
 * @return integer peak memory use (in bytes). Returns 0 if memory_get_peak_usage() is not available
 */
	public static function getPeak() {
		return memory_get_peak_usage();
	}

/**
 * Stores a memory point in the internal tracker.
 * Takes a optional message name which can be used to identify the memory point.
 * If no message is supplied a debug_backtrace will be done to identify the memory point.
 *
 * @param string $message Message to identify this memory point.
 * @return boolean
 */
	public static function record($message = null) {
		$memoryUse = self::getCurrent();
		if (!$message) {
			$named = false;
			$trace = debug_backtrace();
			$message = Debugger::trimpath($trace[0]['file']) . ' line ' . $trace[0]['line'];
		}
		if (isset(self::$_points[$message])) {
			$originalMessage = $message;
			$i = 1;
			while (isset(self::$_points[$message])) {
				$i++;
				$message = $originalMessage . ' #' . $i;
			}
		}
		self::$_points[$message] = $memoryUse;
		return true;
	}

/**
 * Get all the stored memory points
 *
 * @param boolean $clear Whether you want to clear the memory points as well. Defaults to false.
 * @return array Array of memory marks stored so far.
 */
	public static function getAll($clear = false) {
		$marks = self::$_points;
		if ($clear) {
			self::$_points = array();
		}
		return $marks;
	}

/**
 * Clear out any existing memory points
 *
 * @return void
 */
	public static function clear() {
		self::$_points = array();
	}

}
