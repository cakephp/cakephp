<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\StringExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests StringExpression class
 */
class StringExpressionTest extends TestCase
{
    public function testCollation()
    {
        $expr = new StringExpression('testString', 'utf8_general_ci');

        $binder = new ValueBinder();
        $this->assertSame(':c0 COLLATE utf8_general_ci', $expr->sql($binder));
        $this->assertSame('testString', $binder->bindings()[':c0']['value']);
        $this->assertSame('string', $binder->bindings()[':c0']['type']);
    }
}
