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
namespace Cake\Database\Enum;

/**
 * Defines the operations supported by deferred condition predicates.
 */
enum PredicateOperation
{
    /**
     * Equality comparison.
     */
    case EQUALS;

    /**
     * Inequality comparison.
     */
    case NOT_EQUALS;

    /**
     * Greater-than comparison.
     */
    case GREATER_THAN;

    /**
     * Greater-than-or-equal comparison.
     */
    case GREATER_THAN_OR_EQUALS;

    /**
     * Less-than comparison.
     */
    case LESS_THAN;

    /**
     * Less-than-or-equal comparison.
     */
    case LESS_THAN_OR_EQUALS;

    /**
     * SQL pattern matching.
     */
    case LIKE;

    /**
     * Negated SQL pattern matching.
     */
    case NOT_LIKE;

    /**
     * Membership in values or a subquery.
     */
    case IN;

    /**
     * Exclusion from values or a subquery.
     */
    case NOT_IN;

    /**
     * Membership or a null field.
     */
    case IN_OR_NULL;

    /**
     * Exclusion or a null field.
     */
    case NOT_IN_OR_NULL;

    /**
     * Inclusive range comparison.
     */
    case BETWEEN;

    /**
     * Exclusion from an inclusive range.
     */
    case NOT_BETWEEN;

    /**
     * Unconditional null check.
     */
    case IS_NULL;

    /**
     * Unconditional non-null check.
     */
    case IS_NOT_NULL;

    /**
     * Null-safe inequality comparison.
     */
    case IS_DISTINCT_FROM;

    /**
     * Null-safe equality comparison.
     */
    case IS_NOT_DISTINCT_FROM;

    /**
     * Comparison using an explicit trusted SQL operator.
     */
    case COMPARISON;
}
