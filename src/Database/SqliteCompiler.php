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
namespace Cake\Database;

/**
 * Responsible for compiling a Query object into its SQL representation
 * for SQLite
 *
 * @internal
 */
class SqliteCompiler extends QueryCompiler
{

    /**
     * The list of query clauses to traverse for generating a DELETE statement
     *
     * @var array
     */
    protected $_deleteParts = ['delete', 'modifier', 'from', 'where', 'epilog'];

    /**
     * SQLite does not support ORDER BY in UNION queries.
     *
     * @var bool
     */
    protected $_orderedUnion = false;
}
