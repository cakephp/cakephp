<?php
/**
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

use Cake\I18n\Date;
use Cake\TestSuite\TestCase;

/**
 * DateTest class
 */
class DateTest extends TestCase
{
    /**
     * Backup the locale property
     *
     * @var string
     */
    protected $locale;

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->locale = Date::$defaultLocale;
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Date::$defaultLocale = $this->locale;
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     *
     * @return void
     */
    public function testI18nFormat()
    {
        $time = new Date('Thu Jan 14 13:59:28 2010');
        $result = $time->i18nFormat();
        $expected = '1/14/10';
        $this->assertEquals($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'es-ES');
        $expected = 'jueves, 14 de enero de 2010, 0:00:00 (GMT)';
        $this->assertEquals($expected, $result);

        $format = [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT];
        $result = $time->i18nFormat($format);
        $expected = '12:00 AM';
        $this->assertEquals($expected, $result);

        $result = $time->i18nFormat('HH:mm:ss', 'Australia/Sydney');
        $expected = '00:00:00';
        $this->assertEquals($expected, $result);

        Date::$defaultLocale = 'fr-FR';
        $result = $time->i18nFormat(\IntlDateFormatter::FULL);
        $expected = 'jeudi 14 janvier 2010 00:00:00 UTC';
        $this->assertEquals($expected, $result);

        $result = $time->i18nFormat(\IntlDateFormatter::FULL, null, 'es-ES');
        $expected = 'jueves, 14 de enero de 2010, 0:00:00 (GMT)';
        $this->assertEquals($expected, $result, 'Default locale should not be used');
    }

    public function testToString()
    {
        $this->markTestIncomplete();
    }

    public function testJsonSerialize()
    {
        $this->markTestIncomplete();
    }

    public function testParseDate()
    {
        $this->markTestIncomplete();
    }

    public function testParseDateTime()
    {
        $this->markTestIncomplete();
    }
}
