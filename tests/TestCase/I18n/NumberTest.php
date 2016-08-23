<?php
/**
 * NumberTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\I18n;
use Cake\I18n\Number;
use Cake\TestSuite\TestCase;

/**
 * NumberTest class
 */
class NumberTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Number = new Number();
        $this->locale = I18n::locale();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Number);
        I18n::locale($this->locale);
        Number::defaultCurrency(false);
    }

    /**
     * testFormatAndCurrency method
     *
     * @return void
     */
    public function testFormat()
    {
        $value = '100100100';

        $result = $this->Number->format($value);
        $expected = '100,100,100';
        $this->assertEquals($expected, $result);

        $result = $this->Number->format($value, ['before' => '#']);
        $expected = '#100,100,100';
        $this->assertEquals($expected, $result);

        $result = $this->Number->format($value, ['places' => 3]);
        $expected = '100,100,100.000';
        $this->assertEquals($expected, $result);

        $result = $this->Number->format($value, ['locale' => 'es_VE']);
        $expected = '100.100.100';
        $this->assertEquals($expected, $result);

        $value = 0.00001;
        $result = $this->Number->format($value, ['places' => 1, 'before' => '$']);
        $expected = '$0.0';
        $this->assertEquals($expected, $result);

        $value = -0.00001;
        $result = $this->Number->format($value, ['places' => 1, 'before' => '$']);
        $expected = '$-0.0';
        $this->assertEquals($expected, $result);

        $value = 1.23;
        $options = ['locale' => 'fr_FR', 'after' => ' €'];
        $result = $this->Number->format($value, $options);
        $expected = '1,23 €';
        $this->assertEquals($expected, $result);
    }

    /**
     * testParseFloat method
     *
     * @return void
     */
    public function testParseFloat()
    {
        I18n::locale('de_DE');
        $value = '1.234.567,891';
        $result = $this->Number->parseFloat($value);
        $expected = 1234567.891;
        $this->assertEquals($expected, $result);

        I18n::locale('pt_BR');
        $value = '1.234,37';
        $result = $this->Number->parseFloat($value);
        $expected = 1234.37;
        $this->assertEquals($expected, $result);

        $value = '1,234.37';
        $result = $this->Number->parseFloat($value, ['locale' => 'en_US']);
        $expected = 1234.37;
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormatDelta method
     *
     * @return void
     */
    public function testFormatDelta()
    {
        $value = '100100100';

        $result = $this->Number->formatDelta($value, ['places' => 0]);
        $expected = '+100,100,100';
        $this->assertEquals($expected, $result);

        $result = $this->Number->formatDelta($value, ['before' => '', 'after' => '']);
        $expected = '+100,100,100';
        $this->assertEquals($expected, $result);

        $result = $this->Number->formatDelta($value, ['before' => '[', 'after' => ']']);
        $expected = '[+100,100,100]';
        $this->assertEquals($expected, $result);

        $result = $this->Number->formatDelta(-$value, ['before' => '[', 'after' => ']']);
        $expected = '[-100,100,100]';
        $this->assertEquals($expected, $result);

        $result = $this->Number->formatDelta(-$value, ['before' => '[ ', 'after' => ' ]']);
        $expected = '[ -100,100,100 ]';
        $this->assertEquals($expected, $result);

        $value = 0;
        $result = $this->Number->formatDelta($value, ['places' => 1, 'before' => '[', 'after' => ']']);
        $expected = '[0.0]';
        $this->assertEquals($expected, $result);

        $value = 0.0001;
        $result = $this->Number->formatDelta($value, ['places' => 1, 'before' => '[', 'after' => ']']);
        $expected = '[0.0]';
        $this->assertEquals($expected, $result);

        $value = 9876.1234;
        $result = $this->Number->formatDelta($value, ['places' => 1, 'locale' => 'de_DE']);
        $expected = '+9.876,1';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test currency method.
     *
     * @return void
     */
    public function testCurrency()
    {
        $value = '100100100';

        $result = $this->Number->currency($value);
        $expected = '$100,100,100.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'USD');
        $expected = '$100,100,100.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'EUR');
        $expected = '€100,100,100.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'EUR', ['locale' => 'de_DE']);
        $expected = '100.100.100,00 €';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'USD', ['locale' => 'de_DE']);
        $expected = '100.100.100,00 $';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'USD', ['locale' => 'en_US']);
        $expected = '$100,100,100.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'USD', ['locale' => 'en_CA']);
        $expected = 'US$100,100,100.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'INR', ['locale' => 'en_IN']);
        $expected = '₹ 10,01,00,100.00';
        $this->assertEquals($expected, $result);

        $options = ['locale' => 'en_IN', 'pattern' => "Rs'.' #,##,###"];
        $result = $this->Number->currency($value, 'INR', $options);
        $expected = 'Rs. 10,01,00,100';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'GBP');
        $expected = '£100,100,100.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'GBP', ['locale' => 'da_DK']);
        $expected = '100.100.100,00 £';
        $this->assertEquals($expected, $result);

        $options = ['locale' => 'fr_FR', 'pattern' => 'EUR #,###.00'];
        $result = $this->Number->currency($value, 'EUR', $options);
        $expected = 'EUR 100 100 100,00';
        $this->assertEquals($expected, $result);

        $options = ['locale' => 'fr_FR', 'pattern' => '#,###.00 ¤¤'];
        $result = $this->Number->currency($value, 'EUR', $options);
        $expected = '100 100 100,00 EUR';
        $this->assertEquals($expected, $result);

        $options = ['locale' => 'fr_FR', 'pattern' => '#,###.00;(¤#,###.00)'];
        $result = $this->Number->currency(-1235.03, 'EUR', $options);
        $expected = '(€1 235,03)';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency(0.5, 'USD', ['locale' => 'en_US', 'fractionSymbol' => 'c']);
        $expected = '50c';
        $this->assertEquals($expected, $result);

        $options = ['fractionSymbol' => ' cents'];
        $result = $this->Number->currency(0.2, 'USD', $options);
        $expected = '20 cents';
        $this->assertEquals($expected, $result);

        $options = ['fractionSymbol' => 'cents ', 'fractionPosition' => 'before'];
        $result = $this->Number->currency(0.2, null, $options);
        $expected = 'cents 20';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency(0.2, 'EUR');
        $expected = '€0.20';
        $this->assertEquals($expected, $result);

        $options = ['fractionSymbol' => false, 'fractionPosition' => 'before'];
        $result = $this->Number->currency(0.5, null, $options);
        $expected = '$0.50';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency(0, 'GBP');
        $expected = '£0.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency(0.00000, 'GBP');
        $expected = '£0.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency('0.00000', 'GBP');
        $expected = '£0.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency('22.389', 'CAD');
        $expected = 'CA$22.39';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test currency format with places and fraction exponents.
     * Places should only matter for non fraction values and vice versa.
     *
     * @return void
     */
    public function testCurrencyWithFractionAndPlaces()
    {
        $result = $this->Number->currency('1.23', 'EUR', ['locale' => 'de_DE', 'places' => 3]);
        $expected = '1,230 €';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency('0.23', 'GBP', ['places' => 3, 'fractionSymbol' => 'p']);
        $expected = '23p';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency('0.001', 'GBP', ['places' => 3, 'fractionSymbol' => 'p']);
        $expected = '0p';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency('1.23', 'EUR', ['locale' => 'de_DE', 'precision' => 1]);
        $expected = '1,2 €';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test default currency
     *
     * @return void
     */
    public function testDefaultCurrency()
    {
        $result = $this->Number->defaultCurrency();
        $this->assertEquals('USD', $result);

        $this->Number->defaultCurrency(false);
        I18n::locale('es_ES');
        $this->assertEquals('EUR', $this->Number->defaultCurrency());

        $this->Number->defaultCurrency('JPY');
        $this->assertEquals('JPY', $this->Number->defaultCurrency());
    }

    /**
     * testCurrencyCentsNegative method
     *
     * @return void
     */
    public function testCurrencyCentsNegative()
    {
        $value = '-0.99';

        $result = $this->Number->currency($value, 'EUR', ['locale' => 'de_DE']);
        $expected = '-0,99 €';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'USD', ['fractionSymbol' => 'c']);
        $expected = '-99c';
        $this->assertEquals($expected, $result);
    }

    /**
     * testCurrencyZero method
     *
     * @return void
     */
    public function testCurrencyZero()
    {
        $value = '0';

        $result = $this->Number->currency($value, 'USD');
        $expected = '$0.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'EUR', ['locale' => 'fr_FR']);
        $expected = '0,00 €';
        $this->assertEquals($expected, $result);
    }

    /**
     * testCurrencyOptions method
     *
     * @return void
     */
    public function testCurrencyOptions()
    {
        $value = '1234567.89';

        $result = $this->Number->currency($value, null, ['before' => 'Total: ']);
        $expected = 'Total: $1,234,567.89';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, null, ['after' => ' in Total']);
        $expected = '$1,234,567.89 in Total';
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that it is possible to use the international currency code instead of the whole
     * when using the currency method
     *
     * @return void
     */
    public function testCurrencyIntlCode()
    {
        $value = '123';
        $result = $this->Number->currency($value, 'USD', ['useIntlCode' => true]);
        $expected = 'USD 123.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'EUR', ['useIntlCode' => true]);
        $expected = 'EUR 123.00';
        $this->assertEquals($expected, $result);

        $result = $this->Number->currency($value, 'EUR', ['useIntlCode' => true, 'locale' => 'da_DK']);
        $expected = '123,00 EUR';
        $this->assertEquals($expected, $result);
    }

    /**
     * test precision() with locales
     *
     * @return void
     */
    public function testPrecisionLocalized()
    {
        I18n::locale('fr_FR');
        $result = $this->Number->precision(1.234);
        $this->assertEquals('1,234', $result);
    }

    /**
     * testToPercentage method
     *
     * @return void
     */
    public function testToPercentage()
    {
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

        $result = $this->Number->toPercentage(45, 0, ['multiply' => false]);
        $expected = '45%';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toPercentage(45, 2, ['multiply' => false]);
        $expected = '45.00%';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toPercentage(0, 0, ['multiply' => false]);
        $expected = '0%';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toPercentage(0, 4, ['multiply' => false]);
        $expected = '0.0000%';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toPercentage(0.456, 0, ['multiply' => true]);
        $expected = '46%';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toPercentage(0.456, 2, ['multiply' => true]);
        $expected = '45.60%';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toPercentage(0.456, 2, ['locale' => 'de-DE', 'multiply' => true]);
        $expected = '45,60%';
        $this->assertEquals($expected, $result);
    }

    /**
     * testToReadableSize method
     *
     * @return void
     */
    public function testToReadableSize()
    {
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
        $expected = '1,023 Bytes';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024);
        $expected = '1 KB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 + 123);
        $expected = '1.12 KB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 512);
        $expected = '512 KB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 1024 - 1);
        $expected = '1 MB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(512.05 * 1024 * 1024);
        $expected = '512.05 MB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 1024 * 1024 - 1);
        $expected = '1 GB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 512);
        $expected = '512 GB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 - 1);
        $expected = '1 TB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 512);
        $expected = '512 TB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 1024 - 1);
        $expected = '1,024 TB';
        $this->assertEquals($expected, $result);

        $result = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 1024 * 1024);
        $expected = '1,048,576 TB';
        $this->assertEquals($expected, $result);
    }

    /**
     * test toReadableSize() with locales
     *
     * @return void
     */
    public function testReadableSizeLocalized()
    {
        I18n::locale('fr_FR');
        $result = $this->Number->toReadableSize(1321205);
        $this->assertEquals('1,26 MB', $result);

        $result = $this->Number->toReadableSize(512.05 * 1024 * 1024 * 1024);
        $this->assertEquals('512,05 GB', $result);
    }

    /**
     * test config()
     *
     * @return void
     */
    public function testConfig()
    {
        $result = $this->Number->currency(15000, 'INR', ['locale' => 'en_IN']);
        $this->assertEquals('₹ 15,000.00', $result);

        Number::config('en_IN', \NumberFormatter::CURRENCY, [
            'pattern' => '¤ #,##,##0'
        ]);

        $result = $this->Number->currency(15000, 'INR', ['locale' => 'en_IN']);
        $this->assertEquals('₹ 15,000', $result);
    }

    /**
     * test ordinal() with locales
     *
     * @return void
     */
    public function testOrdinal()
    {
        I18n::locale('en_US');
        $result = $this->Number->ordinal(1);
        $this->assertEquals('1st', $result);

        $result = $this->Number->ordinal(2);
        $this->assertEquals('2nd', $result);

        $result = $this->Number->ordinal(2, [
            'locale' => 'fr_FR'
        ]);
        $this->assertEquals('2e', $result);

        $result = $this->Number->ordinal(3);
        $this->assertEquals('3rd', $result);

        $result = $this->Number->ordinal(4);
        $this->assertEquals('4th', $result);

        I18n::locale('fr_FR');
        $result = $this->Number->ordinal(1);
        $this->assertEquals('1er', $result);

        $result = $this->Number->ordinal(2);
        $this->assertEquals('2e', $result);
    }
}
