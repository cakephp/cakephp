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
namespace Cake\Database\Expression;

use Cake\Database\Enum\PredicateOperation;
use Cake\Database\ExpressionInterface;

/**
 * Creates an equality predicate, using IS NULL when the value is null.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function equals(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::EQUALS, [$value], $type);
}

/**
 * Creates an inequality predicate, using IS NOT NULL when the value is null.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function notEquals(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::NOT_EQUALS, [$value], $type);
}

/**
 * Creates a predicate for a field greater than the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function greaterThan(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::GREATER_THAN, [$value], $type);
}

/**
 * Creates a predicate for a field greater than or equal to the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function greaterThanOrEquals(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::GREATER_THAN_OR_EQUALS, [$value], $type);
}

/**
 * Creates a predicate for a field less than the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function lessThan(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::LESS_THAN, [$value], $type);
}

/**
 * Creates a predicate for a field less than or equal to the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function lessThanOrEquals(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::LESS_THAN_OR_EQUALS, [$value], $type);
}

/**
 * Creates a predicate for a field matching the SQL pattern the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function like(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::LIKE, [$value], $type);
}

/**
 * Creates a predicate for a field not matching the SQL pattern the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function notLike(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::NOT_LIKE, [$value], $type);
}

/**
 * Creates a predicate for a field distinct from the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function isDistinctFrom(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::IS_DISTINCT_FROM, [$value], $type);
}

/**
 * Creates a predicate for a field not distinct from the supplied value.
 *
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function isNotDistinctFrom(mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::IS_NOT_DISTINCT_FROM, [$value], $type);
}

/**
 * Creates a in predicate using the existing query builder's membership semantics.
 *
 * @param \Cake\Database\ExpressionInterface|array|string $values The membership values or subquery.
 * @param string|null $type The element binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function in(ExpressionInterface|array|string $values, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::IN, [$values], $type);
}

/**
 * Creates a notIn predicate using the existing query builder's membership semantics.
 *
 * @param \Cake\Database\ExpressionInterface|array|string $values The membership values or subquery.
 * @param string|null $type The element binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function notIn(ExpressionInterface|array|string $values, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::NOT_IN, [$values], $type);
}

/**
 * Creates a inOrNull predicate using the existing query builder's membership semantics.
 *
 * @param \Cake\Database\ExpressionInterface|array|string $values The membership values or subquery.
 * @param string|null $type The element binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function inOrNull(ExpressionInterface|array|string $values, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::IN_OR_NULL, [$values], $type);
}

/**
 * Creates a notInOrNull predicate using the existing query builder's membership semantics.
 *
 * @param \Cake\Database\ExpressionInterface|array|string $values The membership values or subquery.
 * @param string|null $type The element binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function notInOrNull(ExpressionInterface|array|string $values, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::NOT_IN_OR_NULL, [$values], $type);
}

/**
 * Creates a between predicate with independently bound range endpoints.
 *
 * @param mixed $from The beginning of the range.
 * @param mixed $to The end of the range.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function between(mixed $from, mixed $to, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::BETWEEN, [$from, $to], $type);
}

/**
 * Creates a notBetween predicate with independently bound range endpoints.
 *
 * @param mixed $from The beginning of the range.
 * @param mixed $to The end of the range.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function notBetween(mixed $from, mixed $to, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::NOT_BETWEEN, [$from, $to], $type);
}

/**
 * Creates a isNull predicate without binding a value.
 *
 * @return \Cake\Database\Expression\Predicate
 */
function isNull(): Predicate
{
    return new Predicate(PredicateOperation::IS_NULL);
}

/**
 * Creates a isNotNull predicate without binding a value.
 *
 * @return \Cake\Database\Expression\Predicate
 */
function isNotNull(): Predicate
{
    return new Predicate(PredicateOperation::IS_NOT_NULL);
}

/**
 * Creates a comparison using a trusted SQL operator and a bound value.
 *
 * The operator is SQL structure and must never be taken from untrusted input.
 * Prefer a dedicated predicate function when one exists.
 *
 * @param string $operator The trusted SQL operator.
 * @param mixed $value The value or expression to compare.
 * @param string|null $type The binding type, overriding the surrounding field type.
 * @return \Cake\Database\Expression\Predicate
 */
function comparison(string $operator, mixed $value, ?string $type = null): Predicate
{
    return new Predicate(PredicateOperation::COMPARISON, [$operator, $value], $type);
}
