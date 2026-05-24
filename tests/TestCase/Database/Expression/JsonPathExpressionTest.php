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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\JsonPathExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;

class JsonPathExpressionTest extends TestCase
{
    public function testFullPath(): void
    {
        $expr = new JsonPathExpression('$.id');

        $binder = new ValueBinder();
        $this->assertSame("'$.id'", $expr->sql($binder));

        $expr
            ->passing(['val' => 123, 'name' => 'literal_name'])
            ->returning('int')
            ->onEmpty(JsonPathExpression::BEHAVIOR_DEFAULT, 0)
            ->onError(JsonPathExpression::BEHAVIOR_ERROR);

        $binder = new ValueBinder();
        $this->assertSame("'$.id' PASSING :param0 AS val, :param1 AS name RETURNING int DEFAULT 0 ON EMPTY ERROR ON ERROR", $expr->sql($binder));
        $this->assertSame(123, $binder->bindings()[':param0']['value']);
        $this->assertSame('literal_name', $binder->bindings()[':param1']['value']);
    }

    public function testPassing(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->passing(['val' => 123, 'name' => 'literal_name'], ['val' => 'integer', 'name' => 'string']);

        $binder = new ValueBinder();
        $this->assertSame("'$.id' PASSING :param0 AS val, :param1 AS name", $expr->sql($binder));
        $this->assertSame(123, $binder->bindings()[':param0']['value']);
        $this->assertSame('literal_name', $binder->bindings()[':param1']['value']);
    }

    public function testReturning(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->returning('int');

        $binder = new ValueBinder();
        $this->assertSame("'$.id' RETURNING int", $expr->sql($binder));
    }

    public function testOnEmpty(): void
    {
        // Test behavior without value
        $expr = (new JsonPathExpression('$.id'))
            ->onEmpty(JsonPathExpression::BEHAVIOR_ERROR);

        $binder = new ValueBinder();
        $this->assertSame("'$.id' ERROR ON EMPTY", $expr->sql($binder));

        // Test behavior with value
        $expr->onEmpty(JsonPathExpression::BEHAVIOR_DEFAULT, 0);

        $binder = new ValueBinder();
        $this->assertSame("'$.id' DEFAULT 0 ON EMPTY", $expr->sql($binder));
    }

    public function testOnError(): void
    {
        // Test behavior without value
        $expr = (new JsonPathExpression('$.id'))
            ->onError(JsonPathExpression::BEHAVIOR_ERROR);

        $binder = new ValueBinder();
        $this->assertSame("'$.id' ERROR ON ERROR", $expr->sql($binder));

        // Test behavioar with value
        $expr->onError(JsonPathExpression::BEHAVIOR_DEFAULT, 0);

        $binder = new ValueBinder();
        $this->assertSame("'$.id' DEFAULT 0 ON ERROR", $expr->sql($binder));
    }
}
