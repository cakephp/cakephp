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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Fs\Enum;

/**
 * Enum for depth comparison operators
 *
 * Defines the comparison operators that can be used with Finder::depth()
 */
enum DepthOperator: string
{
    /**
     * Equal to (==)
     */
    case EQUAL = '==';

    /**
     * Not equal to (!=)
     */
    case NOT_EQUAL = '!=';

    /**
     * Less than (<)
     */
    case LESS_THAN = '<';

    /**
     * Greater than (>)
     */
    case GREATER_THAN = '>';

    /**
     * Less than or equal to (<=)
     */
    case LESS_THAN_OR_EQUAL = '<=';

    /**
     * Greater than or equal to (>=)
     */
    case GREATER_THAN_OR_EQUAL = '>=';
}
