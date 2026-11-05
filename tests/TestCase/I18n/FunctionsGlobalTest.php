<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

require_once CAKE . 'I18n/functions_global.php';

/**
 * Test cases for functions in I18n\functions_global.php
 */
class FunctionsGlobalTest extends TestCase
{
    /**
     * Tests that the translation functions select plural forms and format their arguments
     */
    public function testTranslationFunctions(): void
    {
        $this->assertSame('Dom', __('Dom'));
        $this->assertSame('text value', __('text {0}', 'value'));

        $this->assertSame('texts value', __n('text {0}', 'texts {0}', 2, 'value'));
        $this->assertSame('text value', __d('default', 'text {0}', 'value'));
        $this->assertSame('texts value', __dn('default', 'text {0}', 'texts {0}', 2, 'value'));
        $this->assertSame('text value', __x('context', 'text {0}', 'value'));
        $this->assertSame('texts value', __xn('context', 'text {0}', 'texts {0}', 2, 'value'));
        $this->assertSame('text value', __dx('default', 'context', 'text {0}', 'value'));
        $this->assertSame('texts value', __dxn('default', 'context', 'text {0}', 'texts {0}', 2, 'value'));
    }

    /**
     * Tests that toDateTime() and toDate() parse values like their namespaced versions
     */
    public function testToDateTimeAndToDate(): void
    {
        $this->assertEquals(new DateTime('2024-01-02 03:04:05'), toDateTime('2024-01-02 03:04:05', 'Y-m-d H:i:s'));
        $this->assertNull(toDateTime('not a date'));

        $this->assertEquals(new Date('2024-01-02'), toDate('2024-01-02'));
        $this->assertNull(toDate('not a date'));
    }
}
