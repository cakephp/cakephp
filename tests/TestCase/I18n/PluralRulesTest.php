<?php
declare(strict_types=1);

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

use Cake\I18n\PluralRules;
use Cake\TestSuite\TestCase;
use Iterator;

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
    public static function localesProvider(): Iterator
    {
        yield ['jp', 0, 0];
        yield ['jp', 1, 0];
        yield ['jp_JP', 2, 0];
        yield ['en_US', 0, 1];
        yield ['en', 1, 0];
        yield ['en_UK', 2, 1];
        yield ['es-ES', 2, 1];
        yield ['pt-br', 0, 0];
        yield ['pt_BR', 1, 0];
        yield ['pt_BR', 2, 1];
        yield ['pt', 0, 1];
        yield ['pt', 1, 0];
        yield ['pt', 2, 1];
        yield ['pt_PT', 0, 1];
        yield ['pt_PT', 1, 0];
        yield ['pt_PT', 2, 1];
        yield ['fr_FR', 0, 0];
        yield ['fr', 1, 0];
        yield ['fr', 2, 1];
        yield ['ru', 0, 2];
        yield ['ru', 1, 0];
        yield ['ru', 2, 1];
        yield ['ru', 21, 0];
        yield ['ru', 22, 1];
        yield ['ru', 5, 2];
        yield ['ru', 7, 2];
        yield ['sk', 0, 2];
        yield ['sk', 1, 0];
        yield ['sk', 2, 1];
        yield ['sk', 5, 2];
        yield ['ga', 0, 2];
        yield ['ga', 1, 0];
        yield ['ga', 2, 1];
        yield ['ga', 7, 3];
        yield ['ga', 11, 4];
        yield ['is', 1, 0];
        yield ['is', 2, 1];
        yield ['is', 3, 1];
        yield ['is', 11, 1];
        yield ['is', 21, 0];
        yield ['lt', 0, 2];
        yield ['lt', 1, 0];
        yield ['lt', 2, 1];
        yield ['lt', 11, 2];
        yield ['lt', 31, 0];
        yield ['sl', 0, 0];
        yield ['sl', 1, 1];
        yield ['sl', 2, 2];
        yield ['sl', 3, 3];
        yield ['sl', 10, 0];
        yield ['sl', 101, 1];
        yield ['sl', 103, 3];
        yield ['mk', 0, 2];
        yield ['mk', 1, 0];
        yield ['mk', 13, 2];
        yield ['mt', 0, 1];
        yield ['mt', 1, 0];
        yield ['mt', 11, 2];
        yield ['mt', 13, 2];
        yield ['mt', 21, 3];
        yield ['mt', 102, 1];
        yield ['lv', 0, 2];
        yield ['lv', 1, 0];
        yield ['lv', 2, 1];
        yield ['lv', 101, 0];
        yield ['pl', 0, 2];
        yield ['pl', 1, 0];
        yield ['pl', 2, 1];
        yield ['pl', 101, 2];
        yield ['ro', 0, 1];
        yield ['ro', 1, 0];
        yield ['ro', 2, 1];
        yield ['ro', 20, 2];
        yield ['ro', 101, 1];
        yield ['ar', 0, 0];
        yield ['ar', 1, 1];
        yield ['ar', 2, 2];
        yield ['ar', 20, 4];
        yield ['ar', 111, 4];
        yield ['ar', 1000, 5];
        yield ['cy', 0, 2];
        yield ['cy', 1, 0];
        yield ['cy', 10, 2];
        yield ['cy', 11, 3];
        yield ['cy', 8, 3];
        yield ['tr', 0, 1];
        yield ['tr', 1, 0];
        yield ['tr', 2, 1];
    }

    /**
     * Tests that the correct plural form is selected for the locale, number combination
     *
     * @dataProvider localesProvider
     */
    public function testCalculate(string $locale, int $number, int $expected): void
    {
        $this->assertSame($expected, PluralRules::calculate($locale, $number));
    }
}
