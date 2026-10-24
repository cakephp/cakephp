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
    case EQ;

    /**
     * Inequality comparison.
     */
    case NOT_EQ;

    /**
     * Greater-than comparison.
     */
    case GT;

    /**
     * Greater-than-or-equal comparison.
     */
    case GTE;

    /**
     * Less-than comparison.
     */
    case LT;

    /**
     * Less-than-or-equal comparison.
     */
    case LTE;

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
     * Null check or equality, depending on the value.
     */
    case IS;

    /**
     * Non-null check or inequality, depending on the value.
     */
    case IS_NOT;

    /**
     * Comparison using an explicit trusted SQL operator.
     */
    case COMPARISON;
}
