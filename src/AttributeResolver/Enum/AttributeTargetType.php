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
namespace Cake\AttributeResolver\Enum;

/**
 * Enum representing the different types of targets that can have attributes.
 *
 * PHP attributes can be attached to various language constructs:
 * - Classes
 * - Methods
 * - Properties
 * - Parameters
 * - Class constants
 */
enum AttributeTargetType: string
{
    /**
     * Attribute attached to a class
     */
    case CLASS_ = 'class';

    /**
     * Attribute attached to a method
     */
    case METHOD = 'method';

    /**
     * Attribute attached to a property
     */
    case PROPERTY = 'property';

    /**
     * Attribute attached to a parameter
     */
    case PARAMETER = 'parameter';

    /**
     * Attribute attached to a class constant
     */
    case CONSTANT = 'constant';
}
