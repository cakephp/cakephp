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
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Input;

use Cake\View\Input\InputInterface;
use Cake\View\StringTemplate;
use Cake\Utility\Time;

/**
 * Input widget class for generating a date time input widget.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class DateTime implements InputInterface {

/**
 * Select box widget.
 *
 * @var Cake\View\Input\Select;
 */
	protected $_select;

/**
 * List of inputs that can be rendered
 *
 * @var array
 */
	protected $_selects = [
		'year',
		'month',
		'day',
		'hour',
		'minute',
		'second',
	];

/**
 * Template instance.
 *
 * @var \Cake\View\StringTemplate
 */
	protected $_templates;

/**
 * Constructor
 *
 * @param Cake\View\StringTemplate $templates
 * @param Cake\View\Input\SelectBox $selectBox
 */
	public function __construct($templates, $selectBox) {
		$this->_select = $selectBox;
		$this->_templates = $templates;
	}

/**
 * Renders a date time widget
 *
 * - `name` - Set the input name.
 * - `disabled` - Either true or an array of options to disable.
 * - `val` - A date time string, integer or DateTime object
 * - `empty` - Set to true to add an empty option at the top of the
 *   option elements. Set to a string to define the display value of the
 *   empty option.
 * - `year` - Array of options for the year select box.
 * - `month` - Array of options for the month select box.
 * - `day` - Array of options for the day select box.
 * - `hour` - Array of options for the hour select box.
 * - `minute` - Array of options for the minute select box.
 * - `second` - Array of options for the second select box.
 *
 * @param array $data Data to render with.
 * @return string A generated select box.
 * @throws \RuntimeException when the name attribute is empty.
 */
	public function render(array $data) {
		$data += [
			'name' => 'data',
			'empty' => false,
			'disabled' => null,
			'val' => null,
			'year' => [],
			'month' => [
				'names' => false,
			],
			'day' => [
				'names' => false,
			],
			'hour' => [],
			'minute' => [],
			'second' => [],
		];

		$selected = $this->_deconstuctDate($data['val']);

		$templateOptions = [];
		foreach ($this->_selects as $select) {
			if ($data[$select] !== false) {
				$method = $select . 'Select';
				$data[$select]['name'] = $data['name'] . "[" . $select . "]";
				$data[$select]['val'] = $selected[$select];

				if (is_bool($data['empty'])) {
					$data[$select]['empty'] = $data['empty'];
				}
				if (isset($data['empty'][$select])) {
					$data[$select]['empty'] = $data['empty'][$select];
				}
				$data[$select]['disabled'] = $data['disabled'];
				$data[$select] += $data[$select];
				$templateOptions[$select] = $this->{$method}($data[$select]);
			}
			unset($data[$select]);
		}
		unset($data['name'], $data['empty'], $data['disabled'], $data['val']);
		$templateOptions['attrs'] = $this->_templates->formatAttributes($data);
		return $this->_templates->format('dateWidget', $templateOptions);
	}

/**
 * Deconstructs the passed date value into all time units
 *
 * @param string|integer|array|DateTime $value
 * @return array
 */
	protected function _deconstuctDate($value) {
		if (empty($value)) {
			return [
				'year' => '', 'month' => '', 'day' => '',
				'hour' => '', 'minute' => '', 'second' => ''
			];
		}
		if (is_string($value)) {
			$date = new \DateTime($value);
		} elseif (is_int($value)) {
			$date = new \DateTime('@' . $value);
		} elseif (is_array($value)) {
			$date = new \DateTime();
			if (isset($value['year'], $value['month'], $value['day'])) {
				$date->setDate($value['year'], $value['month'], $value['day']);
			}
			if (isset($value['hour'], $value['minute'], $value['second'])) {
				$date->setTime($value['hour'], $value['minute'], $value['second']);
			}
		} else {
			$date = $value;
		}

		return [
			'year' => $date->format('Y'),
			'month' => $date->format('m'),
			'day' => $date->format('d'),
			'hour' => $date->format('H'),
			'minute' => $date->format('i'),
			'second' => $date->format('s')
		];
	}

/**
 * Generates a year select
 *
 * @param array $options
 * @return string
 */
	public function yearSelect($options = []) {
		$options += [
			'name' => 'data[year]',
			'val' => null,
			'start' => date('Y', strtotime('-5 years')),
			'end' => date('Y', strtotime('+5 years')),
			'options' => []
		];

		if (empty($options['options'])) {
			$options['options'] = $this->_generateNumbers($options['start'], $options['end']);
		}

		return $this->_select->render($options);
	}

/**
 * Generates a month select
 *
 * @param array $options
 * @return string
 */
	public function monthSelect($options = []) {
		$options += [
			'name' => 'data[month]',
			'names' => false,
			'val' => null,
			'leadingZeroKey' => true,
			'leadingZeroValue' => true
		];

		if (empty($options['options'])) {
			if ($options['names'] === true) {
				$options['options'] = $this->_getMonthNames($options['leadingZeroKey']);
			} else {
				$options['options'] = $this->_generateNumbers(1, 12, $options);
			}
		}

		unset($options['leadingZeroKey'], $options['leadingZeroValue'], $options['names']);
		return $this->_select->render($options);
	}

/**
 * Generates a day select
 *
 * @param array $options
 * @return string
 */
	public function daySelect($options = []) {
		$options += [
			'name' => 'data[day]',
			'val' => null,
			'leadingZeroKey' => true,
			'leadingZeroValue' => true,
		];
		$options['options'] = $this->_generateNumbers(1, 31, $options);

		unset($options['names'], $options['leadingZeroKey'], $options['leadingZeroValue']);
		return $this->_select->render($options);
	}

/**
 * Generates a hour select
 *
 * @param array $options
 * @return string
 */
	public function hourSelect($options = []) {
		$options += [
			'name' => 'data[hour]',
			'val' => null,
			'leadingZeroKey' => true,
			'leadingZeroValue' => true,
			'options' => $this->_generateNumbers(1, 24)
		];

		unset($options['leadingZeroKey'], $options['leadingZeroValue']);
		return $this->_select->render($options);
	}

/**
 * Generates a minute select
 *
 * @param array $options
 * @return string
 */
	public function minuteSelect($options = []) {
		$options += [
			'name' => 'data[minute]',
			'val' => null,
			'leadingZeroKey' => true,
			'leadingZeroValue' => true,
			'options' => $this->_generateNumbers(1, 60)
		];

		unset($options['leadingZeroKey'], $options['leadingZeroValue']);
		return $this->_select->render($options);
	}

/**
 * Generates a second select
 *
 * @param array $options
 * @return string
 */
	public function secondSelect($options = []) {
		$options += [
			'name' => 'data[second]',
			'val' => null,
			'leadingZeroKey' => true,
			'leadingZeroValue' => true,
			'options' => $this->_generateNumbers(1, 60)
		];

		unset($options['leadingZeroKey'], $options['leadingZeroValue']);
		return $this->_select->render($options);
	}

/**
 * Returns a translated list of month names
 *
 * @param boolean $leadingZero
 * @return array
 */
	protected function _getMonthNames($leadingZero = false) {
		$months = [
			'01' => __d('cake', 'January'),
			'02' => __d('cake', 'February'),
			'03' => __d('cake', 'March'),
			'04' => __d('cake', 'April'),
			'05' => __d('cake', 'May'),
			'06' => __d('cake', 'June'),
			'07' => __d('cake', 'July'),
			'08' => __d('cake', 'August'),
			'09' => __d('cake', 'September'),
			'10' => __d('cake', 'October'),
			'11' => __d('cake', 'November'),
			'12' => __d('cake', 'December'),
		];

		if ($leadingZero === false) {
			$i = 1;
			foreach ($months as $key => $name) {
				$months[$i++] = $name;
				unset($months[$key]);
			}
		}

		return $months;
	}

/**
 * Generates a range of numbers
 *
 * @param integer $start Start of the range of numbers to generate
 * @param integer $end End of the range of numbers to generate
 * @param array $options
 * @return array
 */
	protected function _generateNumbers($start = 1, $end = 31, $options = []) {
		$options += [
			'leadingZeroKey' => true,
			'leadingZeroValue' => true,
		];

		$numbers = [];
		for ($i = $start; $i <= $end; $i++) {
			$key = $i;
			$value = $i;
			if ($i < 10) {
				if ($options['leadingZeroKey'] === true) {
					$key = '0' . $key;
				}
				if ($options['leadingZeroValue'] === true) {
					$value = '0' . $value;
				}
			}
			$numbers[(string)$key] = (string)$value;
		}
		return $numbers;
	}

}
