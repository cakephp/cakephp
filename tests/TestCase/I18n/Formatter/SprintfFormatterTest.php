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
namespace Cake\Test\TestCase\I18n\Formatter;

use Cake\I18n\Formatter\SprintfFormatter;
use Cake\TestSuite\TestCase;

/**
 * SprintfFormatter tests
 */
class SprintfFormatterTest extends TestCase
{
    /**
     * Tests that variables are interpolated correctly
     */
    public function testFormatSimple(): void
    {
        $formatter = new SprintfFormatter();
        $this->assertSame('Hello José', $formatter->format('en_US', 'Hello %s', ['José']));
        $this->assertSame('1 Orange', $formatter->format('en_US', '%d %s', [1, 'Orange']));
    }
}
