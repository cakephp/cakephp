<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * Contains the field property with a getter and a setter for it
 *
 * @internal
 */
trait FieldTrait
{

    /**
     * The field name or expression to be used in the left hand side of the operator
     *
     * @var string
     */
    protected $_field;

    /**
     * Sets the field name
     *
     * @param string $field The field to compare with.
     * @return void
     */
    public function setField($field)
    {
        $this->_field = $field;
    }

    /**
     * Returns the field name
     *
     * @return string|\Cake\Database\ExpressionInterface
     */
    public function getField()
    {
        return $this->_field;
    }
}
