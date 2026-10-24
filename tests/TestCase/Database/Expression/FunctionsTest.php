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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

use function Cake\Database\Expression\{between, comparison, gt, in, is, isNot};

/**
 * Verifies deferred predicates, field type inference, and independent expression trees.
 */
class FunctionsTest extends TestCase
{
    /**
     * Provides every field-based helper and its equivalent fluent arguments.
     *
     * @return array<string, array{string, array<mixed>}>
     */
    public static function helpersProvider(): array
    {
        $cases = [];
        foreach (['eq', 'notEq', 'gt', 'gte', 'lt', 'lte', 'like', 'notLike', 'isDistinctFrom', 'isNotDistinctFrom'] as $method) {
            $cases[$method] = [$method, [7]];
        }
        foreach (['in', 'notIn', 'inOrNull', 'notInOrNull'] as $method) {
            $cases[$method] = [$method, [[1, 2]]];
        }
        foreach (['between', 'notBetween'] as $method) {
            $cases[$method] = [$method, [1, 10]];
        }
        foreach (['isNull', 'isNotNull'] as $method) {
            $cases[$method] = [$method, []];
        }

        return $cases;
    }

    /**
     * Checks SQL and bound types against the existing expression builder.
     *
     * @param string $method The predicate and fluent method name.
     * @param array<mixed> $arguments The operands to compare.
     */
    #[DataProvider('helpersProvider')]
    public function testFluentEquivalence(string $method, array $arguments): void
    {
        $function = 'Cake\\Database\\Expression\\' . $method;
        $predicate = $function(...$arguments);
        $actual = new QueryExpression(['Articles.id' => $predicate], ['Articles.id' => 'integer']);
        $expected = new QueryExpression([], ['Articles.id' => 'integer']);
        $expected->$method('Articles.id', ...$arguments);
        $actualBinder = new ValueBinder();
        $expectedBinder = new ValueBinder();

        $this->assertSame($expected->sql($expectedBinder), $actual->sql($actualBinder));
        $this->assertSame($expectedBinder->bindings(), $actualBinder->bindings());
    }

    /**
     * Checks explicit types override the field type and reuse does not retain inferred types.
     */
    public function testTypePrecedenceAndReuse(): void
    {
        $predicate = between(1, 100);
        $first = new QueryExpression(['id' => $predicate], ['id' => 'integer']);
        $second = new QueryExpression(['other_id' => $predicate], ['other_id' => 'string']);
        $explicit = new QueryExpression(['id' => between(1, 100, 'float')], ['id' => 'integer']);
        foreach ([[$first, 'integer'], [$second, 'string'], [$explicit, 'float']] as [$expression, $type]) {
            $binder = new ValueBinder();
            $expression->sql($binder);
            $this->assertSame([$type, $type], array_column($binder->bindings(), 'type'));
        }
    }

    /**
     * Checks equality predicates retain scalar expansion for array binding types.
     */
    public function testArrayBindingTypes(): void
    {
        foreach ([['eq', 'IN'], ['notEq', 'NOT IN']] as [$helper, $operator]) {
            $function = 'Cake\\Database\\Expression\\' . $helper;
            $expression = new QueryExpression(['id' => $function(7)], ['id' => 'integer[]']);
            $binder = new ValueBinder();
            $this->assertSame('id ' . $operator . ' (:c0)', $expression->sql($binder));
            $this->assertSame([7], array_column($binder->bindings(), 'value'));
            $this->assertSame(['integer'], array_column($binder->bindings(), 'type'));
        }
    }

    /**
     * Checks nullable IS predicates choose their operator using the runtime value.
     */
    public function testNullableComparisons(): void
    {
        foreach ([[is(null), '(id) IS NULL'], [isNot(null), '(id) IS NOT NULL'], [is(1), 'id = :c0'], [isNot(1), 'id != :c0']] as [$predicate, $sql]) {
            $expression = new QueryExpression(['id' => $predicate]);
            $this->assertSame($sql, $expression->sql(new ValueBinder()));
        }
    }

    /**
     * Checks custom operators preserve the explicit SQL structure and bind their value.
     */
    public function testCustomComparison(): void
    {
        $binder = new ValueBinder();
        $expression = new QueryExpression(['data' => comparison('@>', '{"key":"value"}', 'string')]);
        $this->assertSame('data @> :c0', $expression->sql($binder));
        $this->assertSame('{"key":"value"}', $binder->bindings()[':c0']['value']);
    }

    /**
     * Checks SQL-shaped input remains bound data in ranges and membership lists.
     */
    public function testInjectionShapedValues(): void
    {
        $input = '1) OR 1=1 --';
        foreach ([between($input, $input), in([$input, $input])] as $predicate) {
            $binder = new ValueBinder();
            $sql = new QueryExpression(['id' => $predicate])->sql($binder);
            $this->assertStringNotContainsString($input, $sql);
            $this->assertSame([$input, $input], array_column($binder->bindings(), 'value'));
        }
    }

    /**
     * Checks reusable predicates copy expression operands inside membership arrays.
     */
    public function testExpressionOperandsAreIndependent(): void
    {
        $operand = new IdentifierExpression('other_id');
        $predicate = in([$operand]);
        $first = $predicate->resolve('id', 'integer');
        $first->traverse(static function ($part): void {
            if ($part instanceof IdentifierExpression) {
                $part->setIdentifier('changed');
            }
        });
        $this->assertSame('id IN (other_id)', $predicate->resolve('id', 'integer')->sql(new ValueBinder()));
        $this->assertSame('other_id', $operand->getIdentifier());
    }

    /**
     * Checks predicates preserve boolean grouping and trusted field expressions.
     */
    public function testNestedConditionsAndFieldExpressions(): void
    {
        $expression = new QueryExpression([
            'OR' => [['id' => gt(1)], ['id' => between(10, 20)]],
            'NOT' => ['COUNT(Articles.id)' => gt(100)],
        ]);
        $this->assertSame(
            '((id > :c0 OR id BETWEEN :c1 AND :c2) AND NOT (COUNT(Articles.id) > :c3))',
            $expression->sql(new ValueBinder()),
        );
    }

    /**
     * Supplies invalid placements and removed operator-key syntax.
     *
     * @return array<string, array{array<mixed>}>
     */
    public static function invalidConditionsProvider(): array
    {
        return [
            'standalone predicate' => [[gt(1)]],
            'boolean predicate' => [['OR' => gt(1)]],
            'legacy greater than' => [['id >' => 1]],
            'legacy explicit equality' => [['id =' => 1]],
            'legacy null check' => [['id IS NOT' => null]],
            'legacy distinct' => [['id IS NOT DISTINCT FROM' => null]],
            'legacy custom operator' => [['data @>' => '{}']],
            'conflicting predicate key' => [['id >' => gt(1)]],
        ];
    }

    /**
     * Checks unsupported forms fail before SQL compilation.
     *
     * @param array<mixed> $conditions The invalid condition array.
     */
    #[DataProvider('invalidConditionsProvider')]
    public function testInvalidConditions(array $conditions): void
    {
        $this->expectException(InvalidArgumentException::class);
        new QueryExpression($conditions);
    }
}
