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
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Log\LoggedQuery;
use Cake\TestSuite\TestCase;

/**
 * Tests LoggedQuery class
 */
class LoggedQueryTest extends TestCase
{
    /**
     * Tests that LoggedQuery can be converted to string
     *
     * @return void
     */
    public function testStringConversion()
    {
        $logged = new LoggedQuery();
        $logged->query = 'SELECT foo FROM bar';
        $this->assertSame('duration=0 rows=0 SELECT foo FROM bar', (string)$logged);
    }

    /**
     * Tests that query placeholders are replaced when logged
     *
     * @return void
     */
    public function testStringInterpolation()
    {
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = :p1 AND b = :p2 AND c = :p3 AND d = :p4 AND e = :p5 AND f = :p6';
        $query->params = ['p1' => 'string', 'p3' => null, 'p2' => 3, 'p4' => true, 'p5' => false, 'p6' => 0];

        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = 3 AND c = NULL AND d = 1 AND e = 0 AND f = 0";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that positional placeholders are replaced when logging a query
     *
     * @return void
     */
    public function testStringInterpolationNotNamed()
    {
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = ? AND b = ? AND c = ? AND d = ? AND e = ? AND f = ?';
        $query->params = ['string', '3', null, true, false, 0];

        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = '3' AND c = NULL AND d = 1 AND e = 0 AND f = 0";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that repeated placeholders are correctly replaced
     *
     * @return void
     */
    public function testStringInterpolationDuplicate()
    {
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = :p1 AND b = :p1 AND c = :p2 AND d = :p2';
        $query->params = ['p1' => 'string', 'p2' => 3];

        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = 'string' AND c = 3 AND d = 3";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that named placeholders
     *
     * @return void
     */
    public function testStringInterpolationNamed()
    {
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = :p1 AND b = :p11 AND c = :p20 AND d = :p2';
        $query->params = ['p11' => 'test', 'p1' => 'string', 'p2' => 3, 'p20' => 5];

        $expected = "duration=0 rows=0 SELECT a FROM b where a = 'string' AND b = 'test' AND c = 5 AND d = 3";
        $this->assertEquals($expected, (string)$query);
    }

    /**
     * Tests that placeholders are replaced with correctly escaped strings
     *
     * @return void
     */
    public function testStringInterpolationSpecialChars()
    {
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = :p1 AND b = :p2 AND c = :p3 AND d = :p4';
        $query->params = ['p1' => '$2y$10$dUAIj', 'p2' => '$0.23', 'p3' => 'a\\0b\\1c\\d', 'p4' => "a'b"];

        $expected = "duration=0 rows=0 SELECT a FROM b where a = '\$2y\$10\$dUAIj' AND b = '\$0.23' AND c = 'a\\\\0b\\\\1c\\\\d' AND d = 'a''b'";
        $this->assertEquals($expected, (string)$query);
    }

    public function testJsonSerialize()
    {
        $query = new LoggedQuery();
        $query->query = 'SELECT a FROM b where a = :p1';
        $query->params = ['p1' => '$2y$10$dUAIj'];
        $query->numRows = 4;
        $query->error = new \Exception('You fail!');

        $expected = json_encode([
            'query' => $query->query,
            'numRows' => 4,
            'params' => $query->params,
            'took' => 0,
            'error' => [
                'class' => get_class($query->error),
                'message' => $query->error->getMessage(),
                'code' => $query->error->getCode(),
            ],
        ]);

        $this->assertEquals($expected, json_encode($query));
    }
}
