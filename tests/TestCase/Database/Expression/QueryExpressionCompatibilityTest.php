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

use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function Cake\Database\Expression\greaterThan;

/**
 * Verifies existing conditions and comparison methods remain compatible with predicates.
 */
class QueryExpressionCompatibilityTest extends TestCase
{
    /**
     * Supplies each short comparison method and its equivalent predicate function.
     *
     * @return array<string, array{string, string}>
     */
    public static function comparisonNamesProvider(): array
    {
        return [
            'equality' => ['eq', 'equals'],
            'inequality' => ['notEq', 'notEquals'],
            'greater than' => ['gt', 'greaterThan'],
            'greater than or equal' => ['gte', 'greaterThanOrEquals'],
            'less than' => ['lt', 'lessThan'],
            'less than or equal' => ['lte', 'lessThanOrEquals'],
        ];
    }

    /**
     * Checks short methods preserve chaining, named arguments, SQL, and binding types.
     *
     * @param string $method The existing comparison method.
     * @param string $helper The equivalent predicate function.
     * @return void
     */
    #[DataProvider('comparisonNamesProvider')]
    public function testShortComparisons(string $method, string $helper): void
    {
        $function = 'Cake\\Database\\Expression\\' . $helper;
        foreach ([null, 'float'] as $type) {
            $actual = new QueryExpression([], ['id' => 'integer']);
            $expected = new QueryExpression(['id' => $function(7, $type)], ['id' => 'integer']);
            $this->assertSame($actual, $actual->$method(field: 'id', value: 7, type: $type));

            $actualBinder = new ValueBinder();
            $expectedBinder = new ValueBinder();
            $this->assertSame($expected->sql($expectedBinder), $actual->sql($actualBinder));
            $this->assertSame($expectedBinder->bindings(), $actualBinder->bindings());
        }
    }

    /**
     * Supplies existing operators, including custom SQL and multi-word comparisons.
     *
     * @return array<string, array{string, mixed, string}>
     */
    public static function operatorConditionsProvider(): array
    {
        return [
            'explicit equality' => ['id =', 7, 'id = :c0'],
            'inequality' => ['id !=', 7, 'id != :c0'],
            'alternate inequality' => ['id <>', 7, 'id <> :c0'],
            'greater than' => ['id >', 7, 'id > :c0'],
            'greater than or equal' => ['id >=', 7, 'id >= :c0'],
            'less than' => ['id <', 7, 'id < :c0'],
            'less than or equal' => ['id <=', 7, 'id <= :c0'],
            'null' => ['id IS', null, '(id) IS NULL'],
            'not null' => ['id IS NOT', null, '(id) IS NOT NULL'],
            'nullable equality' => ['id IS', 7, 'id = :c0'],
            'nullable inequality' => ['id IS NOT', 7, 'id != :c0'],
            'distinct' => ['id IS DISTINCT FROM', null, 'id IS DISTINCT FROM :c0'],
            'not distinct' => ['id IS NOT DISTINCT FROM', null, 'id IS NOT DISTINCT FROM :c0'],
            'membership' => ['id IN', [1, 2], 'id IN (:c0,:c1)'],
            'exclusion' => ['id NOT IN', [1, 2], 'id NOT IN (:c0,:c1)'],
            'pattern' => ['id LIKE', '7%', 'id LIKE :c0'],
            'negated pattern' => ['id NOT LIKE', '7%', 'id NOT LIKE :c0'],
            'custom symbol' => ['id @>', '{}', 'id @> :c0'],
            'custom keyword' => ['id CUSTOM', 7, 'id CUSTOM :c0'],
            'mixed case' => ['id nOt LiKe', '7%', 'id NOT LIKE :c0'],
            'field expression' => ["CONCAT(first_name, ' ', last_name) IN", ['Cake'], "CONCAT(first_name, ' ', last_name) IN (:c0)"],
        ];
    }

    /**
     * Checks operator keys still compile and bind values without deprecations.
     *
     * @param string $key The field and operator.
     * @param mixed $value The comparison value.
     * @param string $sql The expected SQL.
     * @return void
     */
    #[DataProvider('operatorConditionsProvider')]
    public function testOperatorConditions(string $key, mixed $value, string $sql): void
    {
        $expression = new QueryExpression([$key => $value], ['id' => 'integer']);
        $binder = new ValueBinder();
        $this->assertSame($sql, $expression->sql($binder));
        if (!str_ends_with($sql, 'NULL')) {
            $this->assertSame((array)$value ?: [null], array_column($binder->bindings(), 'value'));
            $this->assertSame(str_starts_with($key, 'id ') ? 'integer' : 'string', $binder->bindings()[':c0']['type']);
        } else {
            $this->assertSame([], $binder->bindings());
        }
    }

    /**
     * Checks existing and predicate conditions can be combined within boolean groups.
     *
     * @return void
     */
    public function testMixedConditionStyles(): void
    {
        $expression = new QueryExpression([
            'OR' => [['id >' => 1], ['id' => greaterThan(2)]],
            'active' => true,
        ], ['id' => 'integer', 'active' => 'boolean']);
        $binder = new ValueBinder();
        $this->assertSame('((id > :c0 OR id > :c1) AND active = :c2)', $expression->sql($binder));
        $this->assertSame([1, 2, true], array_column($binder->bindings(), 'value'));
        $this->assertSame(['integer', 'integer', 'boolean'], array_column($binder->bindings(), 'type'));
    }
}
