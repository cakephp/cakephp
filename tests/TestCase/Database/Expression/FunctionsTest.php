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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use ArgumentCountError;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\Predicate;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use function Cake\Database\Expression\allOf;
use function Cake\Database\Expression\anyOf;
use function Cake\Database\Expression\between;
use function Cake\Database\Expression\comparison;
use function Cake\Database\Expression\equals;
use function Cake\Database\Expression\exclusiveOr;
use function Cake\Database\Expression\func;
use function Cake\Database\Expression\greaterThan;
use function Cake\Database\Expression\in;
use function Cake\Database\Expression\isNull;
use function Cake\Database\Expression\lessThan;
use function Cake\Database\Expression\not;
use function Cake\Database\Expression\notEquals;
use function Cake\Database\Expression\notIn;

/**
 * Verifies SQL function builders, deferred predicates, and independent expression trees.
 */
class FunctionsTest extends TestCase
{
    /**
     * Checks standalone builders produce typed aggregate and bound function expressions.
     */
    public function testFunctionBuilderExpressions(): void
    {
        $count = func()->count('*');
        $this->assertSame('COUNT(*)', $count->sql(new ValueBinder()));
        $this->assertSame('integer', $count->getReturnType());

        $binder = new ValueBinder();
        $concat = func()->concat(['title' => 'identifier', ' suffix'], ['string']);
        $this->assertSame('CONCAT(title, :param0)', $concat->sql($binder));
        $this->assertSame('string', $concat->getReturnType());
        $this->assertSame([' suffix'], array_column($binder->bindings(), 'value'));
        $this->assertSame(['string'], array_column($binder->bindings(), 'type'));
    }

    /**
     * Provides every field-based helper and its equivalent fluent arguments.
     *
     * @return array<string, array{string, string, array<mixed>}>
     */
    public static function helpersProvider(): array
    {
        $cases = [];
        foreach (
            [
            'equals' => 'eq',
            'notEquals' => 'notEq',
            'greaterThan' => 'gt',
            'greaterThanOrEquals' => 'gte',
            'lessThan' => 'lt',
            'lessThanOrEquals' => 'lte',
            'like' => 'like',
            'notLike' => 'notLike',
            'isDistinctFrom' => 'isDistinctFrom',
            'isNotDistinctFrom' => 'isNotDistinctFrom',
            ] as $helper => $method
        ) {
            $cases[$helper] = [$helper, $method, [7]];
        }
        foreach (['in', 'notIn', 'inOrNull', 'notInOrNull'] as $method) {
            $cases[$method] = [$method, $method, [[1, 2]]];
        }
        foreach (['between', 'notBetween'] as $method) {
            $cases[$method] = [$method, $method, [1, 10]];
        }
        foreach (['isNull', 'isNotNull'] as $method) {
            $cases[$method] = [$method, $method, []];
        }

        return $cases;
    }

    /**
     * Checks SQL and bound types against the existing expression builder.
     *
     * @param string $helper The namespaced predicate function.
     * @param string $method The equivalent fluent method name.
     * @param array<mixed> $arguments The operands to compare.
     */
    #[DataProvider('helpersProvider')]
    public function testFluentEquivalence(string $helper, string $method, array $arguments): void
    {
        $function = 'Cake\\Database\\Expression\\' . $helper;
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
     * Supplies field-scoped groups, including nested and single-child predicates.
     *
     * @return array<string, array{\Cake\Database\Expression\Predicate, string, array<mixed>}>
     */
    public static function compositePredicatesProvider(): array
    {
        return [
            'all predicates' => [
                allOf(notIn([1, 2]), in([2, 3])),
                '(id NOT IN (:c0,:c1) AND id IN (:c2,:c3))',
                [1, 2, 2, 3],
            ],
            'any predicate' => [
                anyOf(lessThan(10), greaterThan(100)),
                '(id < :c0 OR id > :c1)',
                [10, 100],
            ],
            'nested disjunction' => [
                allOf(anyOf(lessThan(10), greaterThan(100)), notEquals(5)),
                '((id < :c0 OR id > :c1) AND id != :c2)',
                [10, 100, 5],
            ],
            'nested conjunction' => [
                anyOf(allOf(greaterThan(10), lessThan(20)), isNull()),
                '((id > :c0 AND id < :c1) OR (id) IS NULL)',
                [10, 20],
            ],
            'single conjunction child' => [allOf(equals(1)), 'id = :c0', [1]],
            'single disjunction child' => [anyOf(equals(1)), 'id = :c0', [1]],
            'named variadic predicates' => [
                allOf(equals(1), lower: greaterThan(0), upper: lessThan(2)),
                '(id = :c0 AND id > :c1 AND id < :c2)',
                [1, 0, 2],
            ],
            'negated predicate' => [not(equals(1)), 'NOT (id = :c0)', [1]],
            'negated group' => [
                not(anyOf(lessThan(10), greaterThan(100))),
                'NOT ((id < :c0 OR id > :c1))',
                [10, 100],
            ],
            'negated null check' => [not(equals(null)), 'NOT ((id) IS NULL)', []],
            'double negation' => [not(not(equals(1))), 'NOT (NOT (id = :c0))', [1]],
            'exclusive predicates' => [
                exclusiveOr(lessThan(10), greaterThan(5)),
                '((id < :c0 AND NOT (id > :c1)) OR (NOT (id < :c2) AND id > :c3))',
                [10, 5, 10, 5],
            ],
            'nested exclusive predicates' => [
                allOf(greaterThan(0), exclusiveOr(lessThan(10), greaterThan(5))),
                '(id > :c0 AND ((id < :c1 AND NOT (id > :c2)) OR (NOT (id < :c3) AND id > :c4)))',
                [0, 10, 5, 10, 5],
            ],
        ];
    }

    /**
     * Checks grouped predicates preserve parentheses, operand order, and inferred types.
     *
     * @param \Cake\Database\Expression\Predicate $predicate The field-scoped predicate group.
     * @param string $sql The expected SQL fragment.
     * @param array<mixed> $values The expected bound values.
     */
    #[DataProvider('compositePredicatesProvider')]
    public function testCompositePredicates(Predicate $predicate, string $sql, array $values): void
    {
        $expression = new QueryExpression(['id' => $predicate], ['id' => 'integer']);
        $binder = new ValueBinder();
        $this->assertSame($sql, $expression->sql($binder));
        $this->assertSame($values, array_column($binder->bindings(), 'value'));
        $this->assertSame(array_fill(0, count($values), 'integer'), array_column($binder->bindings(), 'type'));
    }

    /**
     * Checks groups inherit types afresh on reuse while preserving child type overrides.
     */
    public function testCompositeTypePrecedenceAndReuse(): void
    {
        $predicate = allOf(greaterThan(1), anyOf(lessThan(10, 'float'), equals(null)));
        foreach (['id' => 'integer', 'other_id' => 'string'] as $field => $type) {
            $expression = new QueryExpression([$field => $predicate], [$field => $type]);
            $binder = new ValueBinder();
            $this->assertSame(
                "($field > :c0 AND ($field < :c1 OR ($field) IS NULL))",
                $expression->sql($binder),
            );
            $this->assertSame([$type, 'float'], array_column($binder->bindings(), 'type'));
        }
    }

    /**
     * Checks negated and repeated XOR operands inherit types while preserving explicit child types.
     */
    public function testExclusiveTypePrecedenceAndReuse(): void
    {
        $predicate = exclusiveOr(not(greaterThan(1)), lessThan(10, 'float'));
        foreach (['id' => 'integer', 'other_id' => 'string'] as $field => $type) {
            $expression = new QueryExpression([$field => $predicate], [$field => $type]);
            $binder = new ValueBinder();
            $this->assertSame(
                "((NOT ($field > :c0) AND NOT ($field < :c1)) OR (NOT (NOT ($field > :c2)) AND $field < :c3))",
                $expression->sql($binder),
            );
            $this->assertSame([1, 10, 1, 10], array_column($binder->bindings(), 'value'));
            $this->assertSame([$type, 'float', $type, 'float'], array_column($binder->bindings(), 'type'));
        }
    }

    /**
     * Checks XOR branches and later resolutions hold independent copies of expression operands.
     */
    public function testExclusiveExpressionOperandsAreIndependent(): void
    {
        $operand = new IdentifierExpression('other_id');
        $predicate = exclusiveOr(equals($operand), not(in([$operand])));
        $first = $predicate->resolve('id', 'integer');
        $changed = false;
        $first->traverse(static function ($part) use (&$changed): void {
            if (!$changed && $part instanceof IdentifierExpression) {
                $part->setIdentifier('changed');
                $changed = true;
            }
        });
        $this->assertSame(
            '((id = changed AND NOT (NOT (id IN (other_id)))) OR (NOT (id = other_id) AND NOT (id IN (other_id))))',
            $first->sql(new ValueBinder()),
        );
        $this->assertSame(
            '((id = other_id AND NOT (NOT (id IN (other_id)))) OR (NOT (id = other_id) AND NOT (id IN (other_id))))',
            $predicate->resolve('id', 'integer')->sql(new ValueBinder()),
        );
        $this->assertSame('other_id', $operand->getIdentifier());
    }

    /**
     * Checks nested groups copy expression operands independently on each resolution.
     */
    public function testCompositeExpressionOperandsAreIndependent(): void
    {
        $operand = new IdentifierExpression('other_id');
        $predicate = allOf(equals($operand), anyOf(in([$operand]), isNull()));
        $first = $predicate->resolve('id', 'integer');
        $first->traverse(static function ($part): void {
            if ($part instanceof IdentifierExpression) {
                $part->setIdentifier('changed');
            }
        });
        $this->assertSame(
            '(id = other_id AND (id IN (other_id) OR (id) IS NULL))',
            $predicate->resolve('id', 'integer')->sql(new ValueBinder()),
        );
        $this->assertSame('other_id', $operand->getIdentifier());
    }

    /**
     * Supplies combinators that require a nonempty group of predicates.
     *
     * @return array<string, array{string}>
     */
    public static function compositeHelpersProvider(): array
    {
        return [
            'conjunction' => ['allOf'],
            'disjunction' => ['anyOf'],
            'negation' => ['not'],
            'exclusive disjunction' => ['exclusiveOr'],
        ];
    }

    /**
     * Checks an empty composite cannot silently remove a query condition.
     *
     * @param string $helper The combinator to invoke without children.
     */
    #[DataProvider('compositeHelpersProvider')]
    public function testEmptyCompositePredicates(string $helper): void
    {
        $function = 'Cake\\Database\\Expression\\' . $helper;
        $this->expectException(ArgumentCountError::class);
        $function();
    }

    /**
     * Checks equality predicates retain scalar expansion for array binding types.
     */
    public function testArrayBindingTypes(): void
    {
        foreach ([['equals', 'IN'], ['notEquals', 'NOT IN']] as [$helper, $operator]) {
            $function = 'Cake\\Database\\Expression\\' . $helper;
            $expression = new QueryExpression(['id' => $function(7)], ['id' => 'integer[]']);
            $binder = new ValueBinder();
            $this->assertSame('id ' . $operator . ' (:c0)', $expression->sql($binder));
            $this->assertSame([7], array_column($binder->bindings(), 'value'));
            $this->assertSame(['integer'], array_column($binder->bindings(), 'type'));
        }
    }

    /**
     * Checks equality predicates choose null checks using the runtime value.
     */
    public function testNullableComparisons(): void
    {
        foreach ([[equals(null), '(id) IS NULL'], [notEquals(null), '(id) IS NOT NULL'], [equals(1), 'id = :c0'], [notEquals(1), 'id != :c0']] as [$predicate, $sql]) {
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
            $sql = (new QueryExpression(['id' => $predicate]))->sql($binder);
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
            'OR' => [['id' => greaterThan(1)], ['id' => between(10, 20)]],
            'NOT' => ['COUNT(Articles.id)' => greaterThan(100)],
        ]);
        $this->assertSame(
            '((id > :c0 OR id BETWEEN :c1 AND :c2) AND NOT (COUNT(Articles.id) > :c3))',
            $expression->sql(new ValueBinder()),
        );
    }

    /**
     * Supplies invalid predicate placements and conflicting operator keys.
     *
     * @return array<string, array{array<mixed>}>
     */
    public static function invalidConditionsProvider(): array
    {
        return [
            'standalone predicate' => [[greaterThan(1)]],
            'boolean predicate' => [['OR' => greaterThan(1)]],
            'negated predicate' => [['NOT' => greaterThan(1)]],
            'standalone callback predicate' => [[static fn() => greaterThan(1)]],
            'boolean callback predicate' => [['OR' => static fn() => greaterThan(1)]],
            'conflicting predicate key' => [['id >' => greaterThan(1)]],
            'standalone composite predicate' => [[allOf(greaterThan(1), lessThan(10))]],
            'boolean composite predicate' => [['OR' => anyOf(equals(1), equals(2))]],
            'negated composite predicate' => [['NOT' => allOf(greaterThan(1), lessThan(10))]],
            'conflicting composite predicate key' => [['id >' => allOf(greaterThan(1), lessThan(10))]],
            'standalone negated predicate' => [[not(equals(1))]],
            'standalone exclusive predicate' => [[exclusiveOr(equals(1), equals(2))]],
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
