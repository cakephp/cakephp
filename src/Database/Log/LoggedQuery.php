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
namespace Cake\Database\Log;

/**
 * Contains a query string, the params used to executed it, time taken to do it
 * and the number of rows found or affected by its execution.
 *
 * @internal
 */
class LoggedQuery
{

    /**
     * Query string that was executed
     * This value should not be modified once assigned
     *
     * @var string
     */
    public $queryString;

    /**
     * Query string that was executed
     * This value may be modified by QueryLogger
     *
     * @var string
     */
    public $query = '';

    /**
     * Number of milliseconds this query took to complete
     *
     * @var float
     */
    public $took = 0;

    /**
     * Associative array with the params bound to the query string
     *
     * @var string
     */
    public $params = [];

    /**
     * Number of rows affected or returned by the query execution
     *
     * @var int
     */
    public $numRows = 0;

    /**
     * The exception that was thrown by the execution of this query
     *
     * @var \Exception
     */
    public $error;

    /**
     * Returns the string representation of this logged query
     *
     * @return string
     */
    public function __toString()
    {
        return "duration={$this->took} rows={$this->numRows} {$this->query}";
    }
}
