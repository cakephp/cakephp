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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

interface QueryInterface extends ExpressionInterface
{
    /**
     * Sets the connection instance to be used for executing and transforming this query.
     *
     * @param \Cake\Database\Connection $connection Connection instance
     * @return $this
     */
    public function setConnection(Connection $connection);

    /**
     * Gets the connection instance to be used for executing and transforming this query.
     *
     * @return \Cake\Database\Connection
     */
    public function getConnection(): Connection;
}
