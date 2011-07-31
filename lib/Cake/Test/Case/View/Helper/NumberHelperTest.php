<?php
/**
 * NumberHelperTest file
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

App::uses('View', 'View');
App::uses('NumberHelper', 'View/Helper');

/**
 * NumberHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class NumberHelperTest extends CakeTestCase {

/**
 * helper property
 *
 * @var mixed null
 */
	public $helper = null;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$view = $this->getMock('View', array(), array(), '', false);
		$this->Number = new NumberHelper($view);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Number);
	}

/**
 * testFormatAndCurrency method
 *
 * @return void
 */
	public function testFormat() {
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
	}

/**
 * Test currency method.
 *
 * @return void
 */
	public function testCurrency() {
		$value = '100100100';

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

		$result = $this->Number->currency($value, '', array('thousands' =>' ', 'wholeSymbol' => '€', 'wholePosition' => 'after', 'decimals' => ',', 'zero' => 'Gratuit'));
		$expected = '100 100 100,00€';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(1000.45, NULL, array('after'=>'øre','before'=>'Kr. ','decimals'=>',','thousands'=>'.'));
		$expected = 'Kr. 1.000,45';
		$this->assertEqual($expected,$result);

		$result = $this->Number->currency(0.5, 'USD');
		$expected = '50c';
		$this->assertEqual($expected,$result);

		$result = $this->Number->currency(0.5, NULL, array('after'=>'øre'));
		$expected = '50øre';
		$this->assertEqual($expected,$result);

		$result = $this->Number->currency(1, null, array('wholeSymbol' => '$ '));
		$expected = '$ 1.00';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(1, null, array('wholeSymbol' => ' $', 'wholePosition' => 'after'));
		$expected = '1.00 $';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(.2, null, array('wholeSymbol' => ' $', 'wholePosition' => 'after', 'fractionSymbol' => 'cents'));
		$expected = '20cents';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(.2, null, array('wholeSymbol' => ' $', 'wholePosition' => 'after', 'fractionSymbol' => 'cents', 'fractionPosition' => 'before'));
		$expected = 'cents20';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(311, 'USD', array('wholePosition' => 'after'));
		$expected = '311.00$';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(.2, 'EUR');
		$expected = '&#8364;0,20';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(12, null, array('wholeSymbol' => ' dollars', 'wholePosition' => 'after', 'fractionSymbol' => ' cents', 'fractionPosition' => 'after'));
		$expected = '12.00 dollars';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(.12, null, array('wholeSymbol' => ' dollars', 'wholePosition' => 'after', 'fractionSymbol' => ' cents', 'fractionPosition' => 'after'));
		$expected = '12 cents';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency(.5, null, array('fractionSymbol' => false, 'fractionPosition' => 'before', 'wholeSymbol' => '$'));
		$expected = '$0.50';
		$this->assertEqual($expected, $result);
	}

/**
 * Test adding currency format options to the number helper
 *
 * @return void
 */
	public function testCurrencyAddFormat() {
		$this->Number->addFormat('NOK', array('before' => 'Kr. '));
		$result = $this->Number->currency(1000, 'NOK');
		$expected = 'Kr. 1,000.00';
		$this->assertEqual($expected,$result);

		$this->Number->addFormat('Other', array('before' => '$$ ', 'after' => 'c!'));
		$result = $this->Number->currency(0.22, 'Other');
		$expected = '22c!';
		$this->assertEqual($expected,$result);

		$result = $this->Number->currency(-10, 'Other');
		$expected = '($$ 10.00)';
		$this->assertEqual($expected,$result);
	}

/**
 * testCurrencyPositive method
 *
 * @return void
 */
	public function testCurrencyPositive() {
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
 * @return void
 */
	public function testCurrencyNegative() {
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
 * @return void
 */
	public function testCurrencyCentsPositive() {
		$value = '0.99';

		$result = $this->Number->currency($value, 'USD');
		$expected = '99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;0,99';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '99p';
		$this->assertEqual($expected, $result);
	}

/**
 * testCurrencyCentsNegative method
 *
 * @return void
 */
	public function testCurrencyCentsNegative() {
		$value = '-0.99';

		$result = $this->Number->currency($value, 'USD');
		$expected = '(99c)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '(&#8364;0,99)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '(99p)';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('negative'=>'-'));
		$expected = '-99c';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'EUR', array('negative'=>'-'));
		$expected = '-&#8364;0,99';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('negative'=>'-'));
		$expected = '-99p';
		$this->assertEqual($expected, $result);
	}

/**
 * testCurrencyZero method
 *
 * @return void
 */
	public function testCurrencyZero() {
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

		$result = $this->Number->currency($value, 'GBP', array('zero' => 'FREE!'));
		$expected = 'FREE!';
		$this->assertEqual($expected, $result);
	}

/**
 * testCurrencyOptions method
 *
 * @return void
 */
	public function testCurrencyOptions() {
		$value = '1234567.89';

		$result = $this->Number->currency($value, null, array('before' => 'GBP'));
		$expected = 'GBP1,234,567.89';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('places' => 0));
		$expected = '&#163;1,234,568';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency('1234567.8912345', null, array('before' => 'GBP', 'places' => 3));
		$expected = 'GBP1,234,567.891';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency('650.120001', null, array('before' => 'GBP', 'places' => 4));
		$expected = 'GBP650.1200';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('escape' => true));
		$expected = '&amp;#163;1,234,567.89';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency('0.35', 'USD', array('after' => false));
		$expected = '$0.35';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency('0.35', 'GBP', array('after' => false));
		$expected = '&#163;0.35';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency('0.35', 'GBP');
		$expected = '35p';
		$this->assertEqual($expected, $result);

		$result = $this->Number->currency('0.35', 'EUR');
		$expected = '&#8364;0,35';
		$this->assertEqual($expected, $result);
	}

/**
 * testToReadableSize method
 *
 * @return void
 */
	public function testToReadableSize() {
		$result = $this->Number->toReadableSize(0);
		$expected = '0 Bytes';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1);
		$expected = '1 Byte';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(45);
		$expected = '45 Bytes';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1023);
		$expected = '1023 Bytes';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024);
		$expected = '1 KB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*512);
		$expected = '512 KB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024-1);
		$expected = '1.00 MB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*512);
		$expected = '512.00 MB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024-1);
		$expected = '1.00 GB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*512);
		$expected = '512.00 GB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024-1);
		$expected = '1.00 TB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024*512);
		$expected = '512.00 TB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024*1024-1);
		$expected = '1024.00 TB';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toReadableSize(1024*1024*1024*1024*1024*1024);
		$expected = (1024 * 1024) . '.00 TB';
		$this->assertEqual($expected, $result);
	}

/**
 * testToPercentage method
 *
 * @return void
 */
	public function testToPercentage() {
		$result = $this->Number->toPercentage(45, 0);
		$expected = '45%';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toPercentage(45, 2);
		$expected = '45.00%';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toPercentage(0, 0);
		$expected = '0%';
		$this->assertEqual($expected, $result);

		$result = $this->Number->toPercentage(0, 4);
		$expected = '0.0000%';
		$this->assertEqual($expected, $result);
	}
}
