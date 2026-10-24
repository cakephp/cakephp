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
use InvalidArgumentException;

/**
 * Defers a predicate until its field and binding type are supplied by a condition array.
 */
final readonly class Predicate
{
    /**
     * Creates a reusable description of a supported predicate operation.
     *
     * @param \Cake\Database\Enum\PredicateOperation $operation The predicate operation, rather than raw SQL.
     * @param array<mixed> $values The operands supplied to the operation.
     * @param string|null $type An explicit binding type, overriding inferred types.
     */
    public function __construct(
        private PredicateOperation $operation,
        private array $values = [],
        private ?string $type = null,
    ) {
    }

    /**
     * Builds an independent expression using the surrounding field and type.
     *
     * @param string $field The field or trusted SQL expression to compare.
     * @param string|null $type The type inferred from the surrounding condition array.
     * @return \Cake\Database\ExpressionInterface
     */
    public function resolve(string $field, ?string $type = null): ExpressionInterface
    {
        $values = array_map($this->cloneValue(...), $this->values);
        $type = $this->type ?? $type;
        $operation = $this->operation;
        if (
            in_array($operation, [
                PredicateOperation::EQ,
                PredicateOperation::NOT_EQ,
                PredicateOperation::GT,
                PredicateOperation::GTE,
                PredicateOperation::LT,
                PredicateOperation::LTE,
                PredicateOperation::LIKE,
                PredicateOperation::NOT_LIKE,
            ], true)
            && $values[0] === null
        ) {
            throw new InvalidArgumentException(sprintf(
                'Expression `%s` has invalid `null` value. Use a null-aware predicate such as isNull() or is().',
                $field,
            ));
        }
        if (
            in_array($operation, [PredicateOperation::EQ, PredicateOperation::NOT_EQ], true)
            && is_string($type)
            && str_contains($type, '[]')
        ) {
            return new ComparisonExpression(
                $field,
                $values[0] instanceof ExpressionInterface ? $values[0] : (array)$values[0],
                $type,
                $operation === PredicateOperation::EQ ? 'IN' : 'NOT IN',
            );
        }

        $builder = new QueryExpression();
        $expression = match ($operation) {
            PredicateOperation::EQ => $builder->eq($field, $values[0], $type),
            PredicateOperation::NOT_EQ => $builder->notEq($field, $values[0], $type),
            PredicateOperation::GT => $builder->gt($field, $values[0], $type),
            PredicateOperation::GTE => $builder->gte($field, $values[0], $type),
            PredicateOperation::LT => $builder->lt($field, $values[0], $type),
            PredicateOperation::LTE => $builder->lte($field, $values[0], $type),
            PredicateOperation::LIKE => $builder->like($field, $values[0], $type),
            PredicateOperation::NOT_LIKE => $builder->notLike($field, $values[0], $type),
            PredicateOperation::IN => $builder->in($field, $values[0], $type),
            PredicateOperation::NOT_IN => $builder->notIn($field, $values[0], $type),
            PredicateOperation::IN_OR_NULL => $builder->inOrNull($field, $values[0], $type),
            PredicateOperation::NOT_IN_OR_NULL => $builder->notInOrNull($field, $values[0], $type),
            PredicateOperation::BETWEEN => $builder->between($field, $values[0], $values[1], $type),
            PredicateOperation::NOT_BETWEEN => $builder->notBetween($field, $values[0], $values[1], $type),
            PredicateOperation::IS_NULL => $builder->isNull($field),
            PredicateOperation::IS_NOT_NULL => $builder->isNotNull($field),
            PredicateOperation::IS_DISTINCT_FROM => $builder->isDistinctFrom($field, $values[0], $type),
            PredicateOperation::IS_NOT_DISTINCT_FROM => $builder->isNotDistinctFrom($field, $values[0], $type),
            PredicateOperation::IS => $values[0] === null
                ? $builder->isNull($field)
                : $builder->eq($field, $values[0], $type),
            PredicateOperation::IS_NOT => $values[0] === null
                ? $builder->isNotNull($field)
                : $builder->notEq($field, $values[0], $type),
            PredicateOperation::COMPARISON => new ComparisonExpression($field, $values[1], $type, $values[0]),
        };
        if ($expression instanceof QueryExpression && count($expression) === 1) {
            $part = null;
            $expression->iterateParts(static function ($value) use (&$part) {
                if ($value instanceof ExpressionInterface) {
                    $part = $value;
                }

                return $value;
            });

            return $part ?? $expression;
        }

        return $expression;
    }

    /**
     * Copies expression operands so quoting and compilation cannot mutate another resolution.
     *
     * @param mixed $value The operand to copy.
     * @return mixed
     */
    private function cloneValue(mixed $value): mixed
    {
        if ($value instanceof ExpressionInterface) {
            return clone $value;
        }
        if (is_array($value)) {
            return array_map($this->cloneValue(...), $value);
        }

        return $value;
    }
}
