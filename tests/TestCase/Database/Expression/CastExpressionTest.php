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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\CastExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

/**
 * Tests CastExpression class
 */
class CastExpressionTest extends TestCase
{
    public function testConstructorWithString(): void
    {
        $cast = new CastExpression('field_name', 'INTEGER');

        $this->assertSame('field_name', $cast->getValue());
        $this->assertSame('INTEGER', $cast->getType());
        $this->assertSame('string', $cast->getReturnType());
    }

    public function testConstructorWithExpression(): void
    {
        $identifier = new IdentifierExpression('Users.age');
        $cast = new CastExpression($identifier, 'VARCHAR');

        $this->assertSame($identifier, $cast->getValue());
        $this->assertSame('VARCHAR', $cast->getType());
    }

    public function testConstructorWithReturnType(): void
    {
        $cast = new CastExpression('count_field', 'INTEGER', 'integer');

        $this->assertSame('integer', $cast->getReturnType());
    }

    public function testSqlWithString(): void
    {
        $cast = new CastExpression('field_name', 'INTEGER');
        $binder = new ValueBinder();

        $this->assertSame('CAST(field_name AS INTEGER)', $cast->sql($binder));
    }

    public function testSqlWithExpression(): void
    {
        $identifier = new IdentifierExpression('Users.age');
        $cast = new CastExpression($identifier, 'VARCHAR');
        $binder = new ValueBinder();

        $this->assertSame('CAST(Users.age AS VARCHAR)', $cast->sql($binder));
    }

    public function testSetValue(): void
    {
        $cast = new CastExpression('field1', 'INTEGER');
        $identifier = new IdentifierExpression('field2');

        $cast->setValue($identifier);

        $this->assertSame($identifier, $cast->getValue());
    }

    public function testSetType(): void
    {
        $cast = new CastExpression('field', 'INTEGER');

        $cast->setType('VARCHAR');

        $this->assertSame('VARCHAR', $cast->getType());
    }

    public function testSetReturnType(): void
    {
        $cast = new CastExpression('field', 'INTEGER');

        $cast->setReturnType('integer');

        $this->assertSame('integer', $cast->getReturnType());
    }

    public function testTraverse(): void
    {
        $identifier = new IdentifierExpression('Users.age');
        $cast = new CastExpression($identifier, 'INTEGER');

        $expressions = [];
        $cast->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp;
        });

        $this->assertCount(1, $expressions);
        $this->assertSame($identifier, $expressions[0]);
    }

    public function testClone(): void
    {
        $identifier = new IdentifierExpression('Users.age');
        $cast = new CastExpression($identifier, 'INTEGER');

        $cloned = clone $cast;

        $this->assertNotSame($cast->getValue(), $cloned->getValue());
        $this->assertEquals($cast->getValue(), $cloned->getValue());
    }
}
