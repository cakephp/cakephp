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
 * Enum representing the declaring class kind for an attribute target.
 */
enum DeclaringClassType: string
{
    /**
     * Declaring type is a class.
     */
    case CLASS_TYPE = 'class';

    /**
     * Declaring type is an interface.
     */
    case INTERFACE = 'interface';

    /**
     * Declaring type is a trait.
     */
    case TRAIT = 'trait';

    /**
     * Declaring type is an enum.
     */
    case ENUM = 'enum';
}
