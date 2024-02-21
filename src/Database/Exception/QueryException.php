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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Exception;

use Cake\Database\Log\LoggedQuery;
use PDOException;

class QueryException extends PDOException
{
    protected LoggedQuery|string|null $query = null;

    /**
     * Set the query that caused this exception.
     *
     * @param \Cake\Database\Log\LoggedQuery|string $query
     * @return void
     */
    public function setQuery(LoggedQuery|string $query): void
    {
        $this->query = $query;
    }

    /**
     * Get the query string that caused this exception.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        if ($this->query === null) {
            return '';
        }

        if ($this->query instanceof LoggedQuery) {
            return (string)$this->query;
        }

        return $this->query;
    }
}
