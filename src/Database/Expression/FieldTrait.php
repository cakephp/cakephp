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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;

/**
 * Contains the field property with a getter and a setter for it
 */
trait FieldTrait
{
    /**
     * The field name or expression to be used in the left hand side of the operator
     *
     * @var \Cake\Database\ExpressionInterface|array|string
     */
    protected ExpressionInterface|array|string $_field;

    /**
     * Sets the field name
     *
     * @param \Cake\Database\ExpressionInterface|array|string $field The field to compare with.
     * @return void
     */
    public function setField(ExpressionInterface|array|string $field): void
    {
        $this->_field = $field;
    }

    /**
     * Returns the field name
     *
     * @return \Cake\Database\ExpressionInterface|array|string
     */
    public function getField(): ExpressionInterface|array|string
    {
        return $this->_field;
    }
}
