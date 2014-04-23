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
namespace Cake\Database\Type;

class DateType extends \Cake\Database\Type\DateTimeType {

/**
 * Date format for DateTime object
 *
 * @var string
 */
	protected $_format = 'Y-m-d';

/**
 * Convert request data into a datetime object.
 *
 * @param mixed $value Request data
 * @return \DateTime
 */
	public function marshal($value) {
		$date = parent::marshal($value);
		if ($date instanceof \DateTime) {
			$date->setTime(0, 0, 0);
		}
		return $date;
	}

}
