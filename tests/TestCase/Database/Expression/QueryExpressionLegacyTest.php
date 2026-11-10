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
 * Verifies legacy conditions remain usable while applications migrate to predicates.
 */
class QueryExpressionLegacyTest extends TestCase
{
    /**
     * Supplies each deprecated comparison and its replacement.
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
     * Checks deprecated methods preserve chaining, named arguments, SQL, and binding types.
     *
     * @param string $legacy The deprecated comparison method.
     * @param string $replacement The preferred comparison method.
     * @return void
     */
    #[DataProvider('comparisonNamesProvider')]
    public function testDeprecatedComparisons(string $legacy, string $replacement): void
    {
        foreach ([null, 'float'] as $type) {
            $actual = new QueryExpression([], ['id' => 'integer']);
            $expected = new QueryExpression([], ['id' => 'integer']);
            $expected->$replacement(field: 'id', value: 7, type: $type);

            $this->deprecated(function () use ($actual, $legacy, $type): void {
                $this->assertSame($actual, $actual->$legacy(field: 'id', value: 7, type: $type));
            });

            $actualBinder = new ValueBinder();
            $expectedBinder = new ValueBinder();
            $this->assertSame($expected->sql($expectedBinder), $actual->sql($actualBinder));
            $this->assertSame($expectedBinder->bindings(), $actualBinder->bindings());
        }
    }

    /**
     * Checks method deprecations identify the replacement.
     *
     * @param string $legacy The deprecated comparison method.
     * @param string $replacement The preferred comparison method.
     * @return void
     */
    #[DataProvider('comparisonNamesProvider')]
    public function testComparisonDeprecationMessage(string $legacy, string $replacement): void
    {
        $this->expectDeprecationMessageMatches(
            '/QueryExpression::' . $legacy . '\(\) is deprecated\. Use ' . $replacement . '\(\) instead\./',
            static function () use ($legacy): void {
                (new QueryExpression())->$legacy('id', 7);
            },
        );
    }

    /**
     * Supplies legacy operators, including custom SQL and multi-word comparisons.
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
     * Checks legacy keys still compile and bind their values while emitting a deprecation.
     *
     * @param string $key The legacy field and operator.
     * @param mixed $value The comparison value.
     * @param string $sql The expected SQL.
     * @return void
     */
    #[DataProvider('operatorConditionsProvider')]
    public function testDeprecatedOperatorConditions(string $key, mixed $value, string $sql): void
    {
        $this->deprecated(function () use ($key, $value, $sql): void {
            $expression = new QueryExpression([$key => $value], ['id' => 'integer']);
            $binder = new ValueBinder();
            $this->assertSame($sql, $expression->sql($binder));
            if (!str_ends_with($sql, 'NULL')) {
                $this->assertSame((array)$value ?: [null], array_column($binder->bindings(), 'value'));
                $this->assertSame(str_starts_with($key, 'id ') ? 'integer' : 'string', $binder->bindings()[':c0']['type']);
            } else {
                $this->assertSame([], $binder->bindings());
            }
        });
    }

    /**
     * Checks warnings identify dynamically assembled operator keys.
     *
     * @return void
     */
    public function testOperatorDeprecationMessage(): void
    {
        $field = 'Articles.score';
        $operator = '>';
        $this->expectDeprecationMessageMatches(
            '/Operator-bearing condition key `Articles.score >` is deprecated\./',
            static function () use ($field, $operator): void {
                new QueryExpression([$field . ' ' . $operator => 7]);
            },
        );
    }

    /**
     * Checks old and new conditions can be migrated independently within boolean groups.
     *
     * @return void
     */
    public function testMixedConditionStyles(): void
    {
        $this->deprecated(function (): void {
            $expression = new QueryExpression([
                'OR' => [['id >' => 1], ['id' => greaterThan(2)]],
                'active' => true,
            ], ['id' => 'integer', 'active' => 'boolean']);
            $binder = new ValueBinder();
            $this->assertSame('((id > :c0 OR id > :c1) AND active = :c2)', $expression->sql($binder));
            $this->assertSame([1, 2, true], array_column($binder->bindings(), 'value'));
            $this->assertSame(['integer', 'integer', 'boolean'], array_column($binder->bindings(), 'type'));
        });
    }
}
