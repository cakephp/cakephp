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

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\JsonPathExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class JsonPathExpressionTest extends TestCase
{
    public function testFullPath(): void
    {
        $expr = new JsonPathExpression('$.id');

        $binder = new ValueBinder();
        $this->assertSame(':param0', $expr->sql($binder));

        $expr
            ->passing(['val' => 123, 'name' => 'literal_name'])
            ->returning('int')
            // Called out of order from expected SQL sequence
            ->onError(JsonPathExpression::BEHAVIOR_ERROR)
            ->onEmpty(JsonPathExpression::BEHAVIOR_DEFAULT, 0);

        $binder = new ValueBinder();
        $this->assertSame(':param0 PASSING :param1 AS val, :param2 AS name RETURNING int DEFAULT 0 ON EMPTY ERROR ON ERROR', $expr->sql($binder));
        $this->assertSame(123, $binder->bindings()[':param1']['value']);
        $this->assertSame('literal_name', $binder->bindings()[':param2']['value']);
    }

    public function testPathIsBound(): void
    {
        $path = "x')) > '0' OR (SELECT 1) --";

        $binder = new ValueBinder();
        $expr = new JsonPathExpression($path);

        $this->assertSame(':param0', $expr->sql($binder));
        $this->assertSame($path, $binder->bindings()[':param0']['value']);
        $this->assertSame('string', $binder->bindings()[':param0']['type']);
    }

    public function testPassing(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->passing(['val' => 123, 'name' => 'literal_name'], ['val' => 'integer', 'name' => 'string']);

        $binder = new ValueBinder();
        $this->assertSame(':param0 PASSING :param1 AS val, :param2 AS name', $expr->sql($binder));
        $this->assertSame(123, $binder->bindings()[':param1']['value']);
        $this->assertSame('literal_name', $binder->bindings()[':param2']['value']);
    }

    public function testPassingExpression(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->passing(['now' => new FunctionExpression('NOW'), 'val' => 5]);

        $binder = new ValueBinder();
        $this->assertSame(':param0 PASSING NOW() AS now, :param1 AS val', $expr->sql($binder));
        $this->assertCount(2, $binder->bindings());
        $this->assertSame(5, $binder->bindings()[':param1']['value']);
    }

    public function testReturning(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->returning('int');

        $binder = new ValueBinder();
        $this->assertSame(':param0 RETURNING int', $expr->sql($binder));
    }

    public function testReturningDoesNotChangeReturnType(): void
    {
        $expr = (new JsonPathExpression('$.id'))->setReturnType('int')->returning('float');

        $this->assertSame('int', $expr->getReturnType());
    }

    public function testOnEmpty(): void
    {
        // Test behavior without value
        $expr = (new JsonPathExpression('$.id'))
            ->onEmpty(JsonPathExpression::BEHAVIOR_ERROR);

        $binder = new ValueBinder();
        $this->assertSame(':param0 ERROR ON EMPTY', $expr->sql($binder));

        // Test behavior with value
        $expr->onEmpty(JsonPathExpression::BEHAVIOR_DEFAULT, 0);

        $binder = new ValueBinder();
        $this->assertSame(':param0 DEFAULT 0 ON EMPTY', $expr->sql($binder));
    }

    public function testOnError(): void
    {
        // Test behavior without value
        $expr = (new JsonPathExpression('$.id'))
            ->onError(JsonPathExpression::BEHAVIOR_ERROR);

        $binder = new ValueBinder();
        $this->assertSame(':param0 ERROR ON ERROR', $expr->sql($binder));

        // Test behavioar with value
        $expr->onError(JsonPathExpression::BEHAVIOR_DEFAULT, 0);

        $binder = new ValueBinder();
        $this->assertSame(':param0 DEFAULT 0 ON ERROR', $expr->sql($binder));
    }

    public function testInvalidOnEmptyBehavior(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new JsonPathExpression('$.id'))->onEmpty('INVALID_BEHAVIOR');
    }

    public function testInvalidOnErrorBehavior(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new JsonPathExpression('$.id'))->onError('INVALID_BEHAVIOR');
    }

    public function testNullBehavior(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->onEmpty(JsonPathExpression::BEHAVIOR_NULL)
            ->onError(JsonPathExpression::BEHAVIOR_NULL);

        $this->assertSame(':param0 NULL ON EMPTY NULL ON ERROR', $expr->sql(new ValueBinder()));
    }

    public function testBehaviorIsCaseInsensitive(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->onEmpty('null')
            ->onError('default', 1);

        $this->assertSame(':param0 NULL ON EMPTY DEFAULT 1 ON ERROR', $expr->sql(new ValueBinder()));
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function defaultValueProvider(): array
    {
        return [
            'integer' => [5, '5'],
            'float' => [2.5, '2.5'],
            'string' => ['abc', "'abc'"],
            'string with quote' => ["it's", "'it''s'"],
            'true' => [true, 'TRUE'],
            'false' => [false, 'FALSE'],
            'null' => [null, 'NULL'],
            'expression' => [new FunctionExpression('NOW'), 'NOW()'],
        ];
    }

    #[DataProvider('defaultValueProvider')]
    public function testDefaultValues(mixed $value, string $expected): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->onEmpty(JsonPathExpression::BEHAVIOR_DEFAULT, $value)
            ->onError(JsonPathExpression::BEHAVIOR_DEFAULT, $value);

        $this->assertSame(
            sprintf(':param0 DEFAULT %1$s ON EMPTY DEFAULT %1$s ON ERROR', $expected),
            $expr->sql(new ValueBinder()),
        );
    }

    public function testTraverseWithoutExpressions(): void
    {
        $expr = (new JsonPathExpression('$.id'))
            ->passing(['val' => 1])
            ->onEmpty(JsonPathExpression::BEHAVIOR_DEFAULT, 'a')
            ->onError(JsonPathExpression::BEHAVIOR_NULL);

        $visited = [];
        $result = $expr->traverse(function ($e) use (&$visited): void {
            $visited[] = $e;
        });

        $this->assertSame($expr, $result);
        $this->assertSame([], $visited);
    }

    public function testTraverse(): void
    {
        $passing = new FunctionExpression('NOW');
        $empty = new FunctionExpression('ABS', [1]);
        $error = new FunctionExpression('ABS', [2]);

        $expr = (new JsonPathExpression('$.id'))
            ->passing(['now' => $passing])
            ->onEmpty(JsonPathExpression::BEHAVIOR_DEFAULT, $empty)
            ->onError(JsonPathExpression::BEHAVIOR_DEFAULT, $error);

        $visited = [];
        $expr->traverse(function ($e) use (&$visited): void {
            $visited[] = $e;
        });

        $this->assertCount(3, $visited);
        $this->assertSame($passing, $visited[0]);
        $this->assertSame($empty, $visited[1]);
        $this->assertSame($error, $visited[2]);
    }

    public function testTraverseNestedExpressions(): void
    {
        $inner = new FunctionExpression('NOW');
        $outer = new FunctionExpression('ABS', [$inner]);

        $expr = (new JsonPathExpression('$.id'))
            ->onError(JsonPathExpression::BEHAVIOR_DEFAULT, $outer);

        $visited = [];
        $expr->traverse(function ($e) use (&$visited): void {
            $visited[] = $e;
        });

        $this->assertSame([$outer, $inner], $visited);
    }
}
