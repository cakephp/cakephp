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

use ArgumentCountError;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\Predicate;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

use function Cake\Database\Expression\{allOf, anyOf, between, comparison, equals, expr, func, greaterThan, identifier, in, isNull, lessThan, not, notEquals, notIn};

/**
 * Verifies SQL function builders, deferred predicates, and independent expression trees.
 */
class FunctionsTest extends TestCase
{
    /**
     * Checks standalone expressions support fluent methods, raw SQL, and expression operands independently.
     */
    public function testExpressionBuilderExpressions(): void
    {
        $expression = expr()->equalFields('Articles.author_id', 'Authors.id');
        $sql = 'Articles.author_id = Authors.id';

        $this->assertSame($sql, $expression->sql(new ValueBinder()));
        $this->assertSame($sql, expr($sql)->sql(new ValueBinder()));
        $this->assertSame($sql, expr($expression)->sql(new ValueBinder()));
        $this->assertSame('', expr()->sql(new ValueBinder()));
    }

    /**
     * Checks standalone condition arrays honor field types and the requested conjunction.
     */
    public function testExpressionBuilderConditions(): void
    {
        $expression = expr(
            ['price' => greaterThan(10), 'stock >' => 0],
            ['price' => 'decimal', 'stock' => 'integer'],
            'OR',
        );
        $binder = new ValueBinder();

        $this->assertSame('(price > :c0 OR stock > :c1)', $expression->sql($binder));
        $this->assertSame([10, 0], array_column($binder->bindings(), 'value'));
        $this->assertSame(['decimal', 'integer'], array_column($binder->bindings(), 'type'));
    }

    /**
     * Checks an explicit type map supplies types for fluent expression conditions.
     */
    public function testExpressionBuilderTypeMap(): void
    {
        $expression = expr(types: new TypeMap(['id' => 'integer']))->equals('id', 1);
        $binder = new ValueBinder();

        $this->assertSame('id = :c0', $expression->sql($binder));
        $this->assertSame(['integer'], array_column($binder->bindings(), 'type'));
    }

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
     * Checks identifier operands remain column references and preserve optional collations without bindings.
     */
    public function testIdentifierExpressions(): void
    {
        $expression = new QueryExpression(['Articles.author_id' => equals(identifier('Authors.id'))]);
        $binder = new ValueBinder();

        $this->assertSame('Articles.author_id = Authors.id', $expression->sql($binder));
        $this->assertSame([], $binder->bindings());

        $collated = identifier('Articles.title', 'utf8mb4_bin');
        $this->assertSame('Articles.title COLLATE utf8mb4_bin', $collated->sql($binder));
        $this->assertSame([], $binder->bindings());
    }

    /**
     * Checks omitted case values create searched expressions with typed predicates and bound results.
     */
    public function testSearchedCaseStatement(): void
    {
        $expression = expr()->case()
            ->when(['price' => greaterThan(100, 'decimal')])->then('premium')
            ->when(['price' => greaterThan(10, 'decimal')])->then('standard')
            ->else('budget');
        $binder = new ValueBinder();

        $this->assertSame(
            'CASE WHEN price > :c0 THEN :c1 WHEN price > :c2 THEN :c3 ELSE :c4 END',
            $expression->sql($binder),
        );
        $this->assertSame([100, 'premium', 10, 'standard', 'budget'], array_column($binder->bindings(), 'value'));
        $this->assertSame(
            ['decimal', 'string', 'decimal', 'string', 'string'],
            array_column($binder->bindings(), 'type'),
        );
        $this->assertSame('string', $expression->getReturnType());
    }

    /**
     * Checks simple case values honor explicit types and bind SQL-shaped strings as data.
     */
    public function testSimpleCaseStatement(): void
    {
        $input = "draft' OR 1=1 --";
        $expression = expr()->case(value: 1, type: 'string')->when($input)->then('matched');
        $binder = new ValueBinder();

        $this->assertSame('CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END', $expression->sql($binder));
        $this->assertSame([1, $input, 'matched'], array_column($binder->bindings(), 'value'));
        $this->assertSame(['string', 'string', 'string'], array_column($binder->bindings(), 'type'));
    }

    /**
     * Checks an explicit null case value remains distinct from an omitted value.
     */
    public function testNullCaseStatementValue(): void
    {
        $expression = expr()->case(null)->when(1)->then('one');
        $binder = new ValueBinder();

        $this->assertSame('CASE NULL WHEN :c0 THEN :c1 ELSE NULL END', $expression->sql($binder));
        $this->assertSame([1, 'one'], array_column($binder->bindings(), 'value'));
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
            'equals',
            'notEquals',
            'greaterThan',
            'greaterThanOrEquals',
            'lessThan',
            'lessThanOrEquals',
            'like',
            'notLike',
            'isDistinctFrom',
            'isNotDistinctFrom',
            ] as $method
        ) {
            $cases[$method] = [$method, $method, [7]];
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
            'OR' => [['id' => greaterThan(1)], ['id' => between(10, 20)]],
            'NOT' => ['COUNT(Articles.id)' => greaterThan(100)],
        ]);
        $this->assertSame(
            '((id > :c0 OR id BETWEEN :c1 AND :c2) AND NOT (COUNT(Articles.id) > :c3))',
            $expression->sql(new ValueBinder()),
        );
    }

    /**
     * Supplies invalid predicate placements.
     *
     * @return array<string, array{array<mixed>}>
     */
    public static function invalidConditionsProvider(): array
    {
        return [
            'standalone predicate' => [[greaterThan(1)]],
            'boolean predicate' => [['OR' => greaterThan(1)]],
            'conflicting predicate key' => [['id >' => greaterThan(1)]],
            'conflicting keyword predicate key' => [['id NOT IN' => greaterThan(1)]],
            'conflicting null predicate key' => [['id IS NOT' => isNull()]],
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
