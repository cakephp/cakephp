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
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests IdentifierExpression class
 */
class IdentifierExpressionTest extends TestCase
{
    /**
     * Tests getting and setting the field
     *
     * @return void
     */
    public function testGetAndSet()
    {
        $expression = new IdentifierExpression('foo');
        $this->assertSame('foo', $expression->getIdentifier());
        $expression->setIdentifier('bar');
        $this->assertSame('bar', $expression->getIdentifier());
    }

    /**
     * Tests converting to sql
     *
     * @return void
     */
    public function testSQL()
    {
        $expression = new IdentifierExpression('foo');
        $this->assertSame('foo', $expression->sql(new ValueBinder()));
    }

    /**
     * Tests setting collation.
     *
     * @return void
     */
    public function testCollation()
    {
        $expresssion = new IdentifierExpression('test', 'utf8_general_ci');
        $this->assertSame('test COLLATE utf8_general_ci', $expresssion->sql(new ValueBinder()));
    }
}
