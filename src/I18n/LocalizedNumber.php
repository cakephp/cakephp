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
namespace Cake\I18n;

use Cake\I18n\Number;

/**
 * Number helper library.
 *
 * Method to handle translation of Number.
 *
 * @link http://book.cakephp.org/3.0/en/
 */
class LocalizedNumber extends Number {

/**
 * Returns a formatted-for-humans file size.
 *
 * @param int $size Size in bytes
 * @return string Human readable size
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
 */
	public static function toReadableSize($size) {
		switch (true) {
			case $size < 1024:
				return __dn('cake', '{0,number,integer} Byte', '{0,number,integer} Bytes', $size, $size);
			case round($size / 1024) < 1024:
				return __d('cake', '{0,number,#,###.##} KB', $size / 1024);
			case round($size / 1024 / 1024, 2) < 1024:
				return __d('cake', '{0,number,#,###.##} MB', $size / 1024 / 1024);
			case round($size / 1024 / 1024 / 1024, 2) < 1024:
				return __d('cake', '{0,number,#,###.##} GB', $size / 1024 / 1024 / 1024);
			default:
				return __d('cake', '{0,number,#,###.##} TB', $size / 1024 / 1024 / 1024 / 1024);
		}
	}

}
