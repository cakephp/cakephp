<?php
/**
 * CakeNumberTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('CakeNumber', 'Utility');

/**
 * CakeNumberTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class CakeNumberTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Number = new CakeNumber();
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->format($value, 3);
		$expected = '100,100,100.000';
		$this->assertEquals($expected, $result);

		$result = $this->Number->format($value);
		$expected = '100,100,100';
		$this->assertEquals($expected, $result);

		$result = $this->Number->format($value, '-');
		$expected = '100-100-100';
		$this->assertEquals($expected, $result);
	}

/**
 * testMultibyteFormat
 *
 * @return void
 */
	public function testMultibyteFormat() {
		$value = '5199100.0006';
		$result = $this->Number->format($value, array(
			'thousands'	=> '&nbsp;',
			'decimals'	=> '&amp;',
			'places'	=> 3,
			'escape'	=> false,
			'before'	=> '',
		));
		$expected = '5&nbsp;199&nbsp;100&amp;001';
		$this->assertEquals($expected, $result);

		$value = 1000.45;
		$result = $this->Number->format($value, array(
			'thousands'	=> ',,',
			'decimals'	=> '.a',
			'escape'	=> false,
		));
		$expected = '$1,,000.a45';
		$this->assertEquals($expected, $result);

		$value = 519919827593784.00;
		$this->Number->addFormat('RUR', array(
			'thousands'		=> 'ø€ƒ‡™',
			'decimals'		=> '(§.§)',
			'escape'		=> false,
			'wholeSymbol'	=> '€',
			'wholePosition'	=> 'after',
		));
		$result = $this->Number->currency($value, 'RUR');
		$expected = '519ø€ƒ‡™919ø€ƒ‡™827ø€ƒ‡™593ø€ƒ‡™784(§.§)00€';
		$this->assertEquals($expected, $result);

		$value = '13371337.1337';
		$result = CakeNumber::format($value, array(
			'thousands'	=> '- |-| /-\ >< () |2 -',
			'decimals'	=> '- £€€† -',
			'before'	=> ''
		));
		$expected = '13- |-| /-\ &gt;&lt; () |2 -371- |-| /-\ &gt;&lt; () |2 -337- £€€† -13';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, '#');
		$expected = '#100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, false);
		$expected = '100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'USD');
		$expected = '$100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;100.100.100,00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, '', array('thousands' => ' ', 'wholeSymbol' => '€', 'wholePosition' => 'after', 'decimals' => ',', 'zero' => 'Gratuit'));
		$expected = '100 100 100,00€';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(1000.45, null, array('after' => 'øre', 'before' => 'Kr. ', 'decimals' => ',', 'thousands' => '.'));
		$expected = 'Kr. 1.000,45';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(0.5, 'USD');
		$expected = '50c';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(0.5, null, array('after' => 'øre'));
		$expected = '50øre';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(1, null, array('wholeSymbol' => '$ '));
		$expected = '$ 1.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(1, null, array('wholeSymbol' => ' $', 'wholePosition' => 'after'));
		$expected = '1.00 $';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(0.2, null, array('wholeSymbol' => ' $', 'wholePosition' => 'after', 'fractionSymbol' => 'cents'));
		$expected = '20cents';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(0.2, null, array('wholeSymbol' => ' $', 'wholePosition' => 'after', 'fractionSymbol' => 'cents', 'fractionPosition' => 'before'));
		$expected = 'cents20';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(311, 'USD', array('wholePosition' => 'after'));
		$expected = '311.00$';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(0.2, 'EUR');
		$expected = '&#8364;0,20';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(12, null, array('wholeSymbol' => ' dollars', 'wholePosition' => 'after', 'fractionSymbol' => ' cents', 'fractionPosition' => 'after'));
		$expected = '12.00 dollars';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(0.12, null, array('wholeSymbol' => ' dollars', 'wholePosition' => 'after', 'fractionSymbol' => ' cents', 'fractionPosition' => 'after'));
		$expected = '12 cents';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(0.5, null, array('fractionSymbol' => false, 'fractionPosition' => 'before', 'wholeSymbol' => '$'));
		$expected = '$0.50';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$this->Number->addFormat('Other', array('before' => '$$ ', 'after' => 'c!'));
		$result = $this->Number->currency(0.22, 'Other');
		$expected = '22c!';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency(-10, 'Other');
		$expected = '($$ 10.00)';
		$this->assertEquals($expected, $result);

		$this->Number->addFormat('Other2', array('before' => '$ ', 'after' => false));
		$result = $this->Number->currency(0.22, 'Other2');
		$expected = '$ 0.22';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('before' => '#'));
		$expected = '#100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, false);
		$expected = '100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'USD');
		$expected = '$100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;100.100.100,00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;100,100,100.00';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '(&#8364;100.100.100,00)';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '(&#163;100,100,100.00)';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('negative' => '-'));
		$expected = '-$100,100,100.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR', array('negative' => '-'));
		$expected = '-&#8364;100.100.100,00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('negative' => '-'));
		$expected = '-&#163;100,100,100.00';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;0,99';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '99p';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '(&#8364;0,99)';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '(99p)';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'USD', array('negative' => '-'));
		$expected = '-99c';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR', array('negative' => '-'));
		$expected = '-&#8364;0,99';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('negative' => '-'));
		$expected = '-99p';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'EUR');
		$expected = '&#8364;0,00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP');
		$expected = '&#163;0.00';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('zero' => 'FREE!'));
		$expected = 'FREE!';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('places' => 0));
		$expected = '&#163;1,234,568';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency('1234567.8912345', null, array('before' => 'GBP', 'places' => 3));
		$expected = 'GBP1,234,567.891';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency('650.120001', null, array('before' => 'GBP', 'places' => 4));
		$expected = 'GBP650.1200';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency($value, 'GBP', array('escape' => true));
		$expected = '&amp;#163;1,234,567.89';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency('0.35', 'USD', array('after' => false));
		$expected = '$0.35';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency('0.35', 'GBP', array('after' => false));
		$expected = '&#163;0.35';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency('0.35', 'GBP');
		$expected = '35p';
		$this->assertEquals($expected, $result);

		$result = $this->Number->currency('0.35', 'EUR');
		$expected = '&#8364;0,35';
		$this->assertEquals($expected, $result);
	}

/**
 * testToReadableSize method
 *
 * @return void
 */
	public function testToReadableSize() {
		$result = $this->Number->toReadableSize(0);
		$expected = '0 Bytes';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1);
		$expected = '1 Byte';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(45);
		$expected = '45 Bytes';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1023);
		$expected = '1023 Bytes';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024);
		$expected = '1 KB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 512);
		$expected = '512 KB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 - 1);
		$expected = '1.00 MB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 512);
		$expected = '512.00 MB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 - 1);
		$expected = '1.00 GB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 512);
		$expected = '512.00 GB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 - 1);
		$expected = '1.00 TB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 512);
		$expected = '512.00 TB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 1024 - 1);
		$expected = '1024.00 TB';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 1024 * 1024);
		$expected = (1024 * 1024) . '.00 TB';
		$this->assertEquals($expected, $result);
	}

/**
 * test toReadableSize() with locales
 *
 * @return void
 */
	public function testReadableSizeLocalized() {
		$restore = setlocale(LC_NUMERIC, 0);
		setlocale(LC_NUMERIC, 'de_DE');
		$result = $this->Number->toReadableSize(1321205);
		$this->assertRegExp('/1[,.]26 MB/', $result);

		$result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 512);
		$this->assertRegExp('/512[,.]00 GB/', $result);
		setlocale(LC_NUMERIC, $restore);
	}

/**
 * testToPercentage method
 *
 * @return void
 */
	public function testToPercentage() {
		$result = $this->Number->toPercentage(45, 0);
		$expected = '45%';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toPercentage(45, 2);
		$expected = '45.00%';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toPercentage(0, 0);
		$expected = '0%';
		$this->assertEquals($expected, $result);

		$result = $this->Number->toPercentage(0, 4);
		$expected = '0.0000%';
		$this->assertEquals($expected, $result);
	}

/**
 * testFromReadableSize
 *
 * @dataProvider filesizes
 * @return void
 */
	public function testFromReadableSize($params, $expected) {
		$result = $this->Number->fromReadableSize($params['size'], $params['default']);
		$this->assertEquals($expected, $result);
	}

/**
 * testFromReadableSize
 *
 * @expectedException CakeException
 * @return void
 */
	public function testFromReadableSizeException() {
		$result = $this->Number->fromReadableSize('bogus', false);
	}

/**
 * filesizes dataprovider
 *
 * @return array
 */
	public function filesizes() {
		return array(
			array(array('size' => '512B', 'default' => false), 512),
			array(array('size' => '1KB', 'default' => false), 1024),
			array(array('size' => '1.5KB', 'default' => false), 1536),
			array(array('size' => '1MB', 'default' => false), 1048576),
			array(array('size' => '1mb', 'default' => false), 1048576),
			array(array('size' => '1.5MB', 'default' => false), 1572864),
			array(array('size' => '1GB', 'default' => false), 1073741824),
			array(array('size' => '1.5GB', 'default' => false), 1610612736),
			array(array('size' => '512', 'default' => 'Unknown type'), 512),
			array(array('size' => '2VB', 'default' => 'Unknown type'), 'Unknown type')
		);
	}

}
