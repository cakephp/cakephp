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
 * @since         3.4.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Represents a single identifier name in the database
 */
interface IdentifierInterface extends ExpressionInterface
{
    /**
     * Returns the identifier this expression represents
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Sets the identifier this expression represents
     *
     * @param string $identifier The identifier
     * @return void
     */
    public function setIdentifier($identifier);
}