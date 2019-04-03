<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Formatter\IcuFormatter;
use Cake\TestSuite\TestCase;

/**
 * IcuFormatter tests
 */
class IcuFormatterTest extends TestCase
{
    /**
     * Tests that empty values can be used as formatting strings
     *
     * @return void
     */
    public function testFormatEmptyValues()
    {
        $formatter = new IcuFormatter();
        $this->assertEquals('', $formatter->format('en_US', '', []));
    }

    /**
     * Tests that variables are interpolated correctly
     *
     * @return void
     */
    public function testFormatSimple()
    {
        $formatter = new IcuFormatter();
        $this->assertEquals('Hello José', $formatter->format('en_US', 'Hello {0}', ['José']));
        $result = $formatter->format(
            '1 Orange',
            '{0, number} {1}',
            [1.0, 'Orange']
        );
        $this->assertEquals('1 Orange', $result);
    }

    /**
     * Tests that plurals can instead be selected using ICU's native selector
     *
     * @return void
     */
    public function testNativePluralSelection()
    {
        $formatter = new IcuFormatter();
        $locale = 'en_US';
        $string = '{0,plural,' .
            '=0{No fruits.}' .
            '=1{We have one fruit}' .
            'other{We have {1} fruits}' .
            '}';

        $params = [0, 0];
        $expect = 'No fruits.';
        $actual = $formatter->format($locale, $string, $params);
        $this->assertSame($expect, $actual);

        $params = [1, 0];
        $expect = 'We have one fruit';
        $actual = $formatter->format($locale, $string, $params);
        $this->assertSame($expect, $actual);

        $params = [10, 10];
        $expect = 'We have 10 fruits';
        $actual = $formatter->format($locale, $string, $params);
        $this->assertSame($expect, $actual);
    }

    /**
     * Tests that passing a message in the wrong format will throw an exception
     *
     * @return void
     * @expectedException \Exception
     * @expectedExceptionMessage msgfmt_create: message formatter
     */
    public function testBadMessageFormat()
    {
        $this->skipIf(version_compare(PHP_VERSION, '7', '>='));

        $formatter = new IcuFormatter();
        $formatter->format('en_US', '{crazy format', ['some', 'vars']);
    }

    /**
     * Tests that passing a message in the wrong format will throw an exception
     *
     * @return void
     * @expectedException \Exception
     * @expectedExceptionMessage Constructor failed
     */
    public function testBadMessageFormatPHP7()
    {
        $this->skipIf(version_compare(PHP_VERSION, '7', '<'));

        $formatter = new IcuFormatter();
        $formatter->format('en_US', '{crazy format', ['some', 'vars']);
    }
}
