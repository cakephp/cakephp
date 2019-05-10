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
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Represents an expression that is known to return a specific type
 */
interface TypedResultInterface
{
    /**
     * Return the abstract type this expression will return
     *
     * @return string
     */
    public function getReturnType(): string;

    /**
     * Set the return type of the expression
     *
     * @param string $type The type name to use.
     * @return $this
     */
    public function setReturnType(string $type);
}
