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
namespace Cake\Test\TestCase\Database;

use Cake\Database\Query\SelectQuery;

trait QueryAssertsTrait
{
    /**
     * Assertion for comparing a regex pattern against a query having its identifiers
     * quoted. It accepts queries quoted with the characters `<` and `>`. If the third
     * parameter is set to true, it will alter the pattern to both accept quoted and
     * unquoted queries
     *
     * @param string $pattern
     * @param string $query the result to compare against
     * @param bool $optional
     */
    public function assertQuotedQuery($pattern, $query, $optional = false): void
    {
        if ($optional) {
            $optional = '?';
        }
        $pattern = str_replace('<', '[`"\[]' . $optional, $pattern);
        $pattern = str_replace('>', '[`"\]]' . $optional, $pattern);
        $this->assertMatchesRegularExpression('#' . $pattern . '#', $query);
    }

    /**
     * Assertion for comparing a table's contents with what is in it.
     *
     * @param string $table
     * @param int $count
     * @param array $rows
     * @param array $conditions
     */
    public function assertTable($table, $count, $rows, $conditions = []): void
    {
        $result = (new SelectQuery($this->connection))->select('*')
            ->from($table)
            ->where($conditions)
            ->execute();
        $results = $result->fetchAll('assoc');
        $this->assertCount($count, $results, 'Row count is incorrect');
        $this->assertEquals($rows, $results);
        $result->closeCursor();
    }
}
