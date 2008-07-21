<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link			https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Helper', 'Number');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class NumberTest extends CakeTestCase {
/**
 * helper property
 * 
 * @var mixed null
 * @access public
 */
	var $helper = null;

/**
 * setUp method
 * 
 * @access public
 * @return void
 */
	function setUp() {
		$this->Number =& new NumberHelper();
	}
/**
 * testFormatAndCurrency method
 * 
 * @access public
 * @return void
 */
	function testFormatAndCurrency() {
		$value = '100100100';

		$result = $this->Number->format($value, '#');
		$expected = '#100,100,100';
		$this->assertEqual($expected, $result);

		$result = $this->Number->format($value, 3);
		$expected = '100,100,100.000';
		$this->assertEqual($expected, $result);

		$result = $this->Number->format($value);
		$expected = '100,100,100';
		$this->assertEqual($expected, $result);

		$result = $this->Number->format($value, '-');
		$expected = '100-100-100';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value);
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, '#');
		$expected = '#100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, false);
		$expected = '100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD');
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;100.100.100,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;100,100,100.00';
		$this->assertEqual($expected, $result);
	}
/**
 * testCurrencyPositive method
 * 
 * @access public
 * @return void
 */
	function testCurrencyPositive() {
		$value = '100100100';

		$result = $this->Number->currency($value);
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('before'=> '#'));
		$expected = '#100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, false);
		$expected = '100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD');
		$expected = '$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;100.100.100,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;100,100,100.00';
		$this->assertEqual($expected, $result);
	}

/**
 * testCurrencyNegative method
 * 
 * @access public
 * @return void
 */
	function testCurrencyNegative() {
		$value = '-100100100';

		$result = $this->Number->currency($value);
		$expected = '($100,100,100.00)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '(&#8364;100.100.100,00)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '(&#163;100,100,100.00)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('negative'=>'-'));
		$expected = '-$100,100,100.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR', array('negative'=>'-'));
		$expected = '-&#8364;100.100.100,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('negative'=>'-'));
		$expected = '-&#163;100,100,100.00';
		$this->assertEqual($expected, $result);

	}
/**
 * testCurrencyCentsPositive method
 * 
 * @access public
 * @return void
 */
	function testCurrencyCentsPositive() {
		$value = '0.99';

		$result = $this->Number->currency($value, 'USD');
		$expected = '99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '99p';
		$this->assertEqual($expected, $result);

	}
/**
 * testCurrencyCentsNegative method
 * 
 * @access public
 * @return void
 */
	function testCurrencyCentsNegative() {
		$value = '-0.99';

		$result = $this->Number->currency($value, 'USD');
		$expected = '(99c)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '(99c)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '(99p)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('negative'=>'-'));
		$expected = '-99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR', array('negative'=>'-'));
		$expected = '-99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('negative'=>'-'));
		$expected = '-99p';
		$this->assertEqual($expected, $result);

	}
/**
 * testCurrencyZero method
 * 
 * @access public
 * @return void
 */
	function testCurrencyZero() {
		$value = '0';

		$result = $this->Number->currency($value, 'USD');
		$expected = '$0.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;0,00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;0.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('zero'=> 'FREE!'));
		$expected = 'FREE!';
		$this->assertEqual($expected, $result);

	}
/**
 * testCurrencyOptions method
 * 
 * @access public
 * @return void
 */
	function testCurrencyOptions() {
		$value = '1234567.89';

		$result = $this->Number->currency($value, null, array('before'=>'GBP'));
		$expected = 'GBP1,234,567.89';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('places'=>0));
		$expected = '&#163;1,234,568';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('escape'=>true));
		$expected = '&amp;#163;1,234,567.89';
		$this->assertEqual($expected, $result);

	}
/**
 * testToReadableSize method
 * 
 * @access public
 * @return void
 */
	function testToReadableSize() {
		$result = $this->Number->toReadableSize(0);
		$expected = '0 Bytes';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1);
		$expected = '1 Byte';
		$this->assertEqual($expected, $result);

		$under1KB = 45;
		$result = $this->Number->toReadableSize($under1KB);
		$expected = $under1KB.' Bytes';
		$this->assertEqual($expected, $result);

		$under1MB = 1024*1024-1;
		$result = $this->Number->toReadableSize($under1MB);
		$expected = sprintf("%01.0f", $under1MB/1024).' KB';
		$this->assertEqual($expected, $result);

		$under1GB = (float) 1024*1024*1024-1;
		$result = $this->Number->toReadableSize($under1GB);
		$expected = sprintf("%01.2f", $under1GB/1024/1024).' MB';
		$this->assertEqual($expected, $result);

		$float = (float) 1024*1024*1024*1023-1;
		$result = $this->Number->toReadableSize($float);
		$expected = sprintf("%01.2f", $float/1024/1024/1024).' GB';
		$this->assertEqual($expected, $result);

		$float = (float) 1024*1024*1024*1024*1023-1;
		$result = $this->Number->toReadableSize($float);
		$expected = sprintf("%01.2f", $float/1024/1024/1024/1024).' TB';
		$this->assertEqual($expected, $result);
	}
/**
 * testToPercentage method
 * 
 * @access public
 * @return void
 */
	function testToPercentage() {
		$result = $this->Number->toPercentage(45, 0);
		$expected = '45%';
		$this->assertEqual($result, $expected);

		$result = $this->Number->toPercentage(45, 2);
		$expected = '45.00%';
		$this->assertEqual($result, $expected);

		$result = $this->Number->toPercentage(0, 0);
		$expected = '0%';
		$this->assertEqual($result, $expected);

		$result = $this->Number->toPercentage(0, 4);
		$expected = '0.0000%';
		$this->assertEqual($result, $expected);



	}
/**
 * tearDown method
 * 
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Number);
	}
}

?>