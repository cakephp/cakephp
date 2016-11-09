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
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\PluralRules;
use Cake\TestSuite\TestCase;

/**
 * PluralRules tests
 */
class PluralRulesTest extends TestCase
{

    /**
     * Returns the notable combinations for locales and numbers
     * with the respective plural form that should be selected
     *
     * @return array
     */
    public function localesProvider()
    {
        return [
            ['jp', 0, 0],
            ['jp', 1, 0],
            ['jp_JP', 2, 0],
            ['en_US', 0, 1],
            ['en', 1, 0],
            ['en_UK', 2, 1],
            ['pt_BR', 0, 1],
            ['pt_BR', 1, 0],
            ['pt_BR', 2, 1],
            ['pt', 0, 1],
            ['pt', 1, 0],
            ['pt', 2, 1],
            ['pt_PT', 0, 0],
            ['pt_PT', 1, 0],
            ['pt_PT', 2, 1],
            ['fr_FR', 0, 0],
            ['fr', 1, 0],
            ['fr', 2, 1],
            ['ru', 0, 2],
            ['ru', 1, 0],
            ['ru', 2, 1],
            ['sk', 0, 2],
            ['sk', 1, 0],
            ['sk', 2, 1],
            ['sk', 5, 2],
            ['ga', 0, 2],
            ['ga', 1, 0],
            ['ga', 2, 1],
            ['ga', 7, 3],
            ['ga', 11, 4],
            ['is', 1, 0],
            ['is', 2, 1],
            ['is', 3, 1],
            ['is', 11, 1],
            ['is', 21, 0],
            ['lt', 0, 2],
            ['lt', 1, 0],
            ['lt', 2, 1],
            ['lt', 11, 2],
            ['lt', 31, 0],
            ['sl', 0, 0],
            ['sl', 1, 1],
            ['sl', 2, 2],
            ['sl', 3, 3],
            ['sl', 10, 0],
            ['sl', 101, 1],
            ['sl', 103, 3],
            ['mk', 0, 2],
            ['mk', 1, 0],
            ['mk', 13, 2],
            ['mt', 0, 1],
            ['mt', 1, 0],
            ['mt', 11, 2],
            ['mt', 13, 2],
            ['mt', 21, 3],
            ['mt', 102, 1],
            ['lv', 0, 2],
            ['lv', 1, 0],
            ['lv', 2, 1],
            ['lv', 101, 0],
            ['pl', 0, 2],
            ['pl', 1, 0],
            ['pl', 2, 1],
            ['pl', 101, 2],
            ['ro', 0, 1],
            ['ro', 1, 0],
            ['ro', 2, 1],
            ['ro', 20, 2],
            ['ro', 101, 1],
            ['ar', 0, 0],
            ['ar', 1, 1],
            ['ar', 2, 2],
            ['ar', 20, 4],
            ['ar', 111, 4],
            ['ar', 1000, 5],
            ['cy', 0, 2],
            ['cy', 1, 0],
            ['cy', 10, 2],
            ['cy', 11, 3],
            ['cy', 8, 3],
        ];
    }

    /**
     * Tests that the correct plural form is selected for the locale, number combination
     *
     * @dataProvider localesProvider
     * @return void
     */
    public function testCalculate($locale, $number, $expected)
    {
        $this->assertEquals($expected, PluralRules::calculate($locale, $number));
    }
}
