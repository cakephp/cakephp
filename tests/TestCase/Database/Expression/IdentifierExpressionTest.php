<?php
/**
 *
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
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests IdentifierExpression class
 *
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
        $this->assertEquals('foo', $expression->getIdentifier());
        $expression->setIdentifier('bar');
        $this->assertEquals('bar', $expression->getIdentifier());
    }

    /**
     * Tests converting to sql
     *
     * @return void
     */
    public function testSQL()
    {
        $expression = new IdentifierExpression('foo');
        $this->assertEquals('foo', $expression->sql(new ValueBinder));
    }
}
