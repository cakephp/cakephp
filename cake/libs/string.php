<?php
/* SVN FILE: $Id$ */
/**
 * String handling methods.
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *			1785 E. Sahara Avenue, Suite 490-204
 *			Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * String handling methods.
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class String extends Object {
/**
 * Gets a reference to the String object instance
 *
 * @return object String instance
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new String();
		}
		return $instance[0];
	}
/**
 * Generate a random UUID
 *
 * @see http://www.ietf.org/rfc/rfc4122.txt
 * @return RFC 4122 UUID
 * @static
 */
	function uuid() {
		$node = env('SERVER_ADDR');

		if(empty($node)) {
			$host = env('HOSTNAME');

			if (empty($host)) {
				$host = env('HOST');
			}

			if (empty($host)) {
				$node = ip2long('127.0.0.1');
			} else {
				$ip = gethostbyname($host);
				if ($ip === $host) {
					$node = crc32($host);
				} else {
					$node = ip2long($ip);
				}
			}
		} else {
			$node = ip2long($node);
		}

		if (function_exists('zend_thread_id')) {
			$pid = zend_thread_id();
		} else {
			$pid = getmypid();
		}

		list($timeMid, $timeLow) = explode(' ', microtime());
		$uuid = sprintf("%08x-%04x-%04x-%02x%02x-%04x%08x", (int)$timeLow, (int)substr($timeMid, 2) & 0xffff,
					mt_rand(0, 0xfff) | 0x4000, mt_rand(0, 0x3f) | 0x80, mt_rand(0, 0xff), $pid, $node);
		return $uuid;
	}
}
?>